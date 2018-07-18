<?php
/*
 * Notifications library
 *
 * This library contains notifications functions, functions related to sending notifications back to ourselves in case of problems, events, etc.
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */
load_config('notifications');



/*
 * Do notifications
 */
function notifications_send($params){
    static $count = 0;
    global $_CONFIG, $core;

    try{
//        log_file(isset_get($params['message']), 'notifications', 'warning');
return false;

        if(is_object($params) and ($params instanceof Exception)){
            /*
             * Notify about an exception
             */
            $params = array('title'       => tr('Exception'),
                            'exception'   => true,
                            'description' => $params,
                            'class'       => 'exception');
        }

        array_ensure($params, 'title,description,class,user');
        array_default($params, 'url', (PLATFORM_HTTP ? $_SERVER['REQUEST_URI'] : 'cli'));

        if($params['exception'] and !$_CONFIG['production']){
            /*
             * Exception in non production environments, don't send
             * notifications since we're working on this project!
             */
            $code = $params['description']->getCode();

            if(str_until($code, '/') === 'warning'){
                /*
                 * Just ignore warnings in non production environments.
                 */
                return false;
            }

            /*
             * Instead of notifying, throw an exception that can be fixed by
             * the developer
             */
            throw new bException($message, $event);
        }

        if(++$count > 15){
            /*
             * Endless loop protection
             */
            if(!debug()){
                log_file(tr('notifications_send(): Stopped nofity_send endless loop for ":event" for classes ":classes" with message ":message"', array(':event' => $params['event'], ':classes' => $params['classes'], ':message' => $params['message'])), 'loop');
                return false;
            }

            throw new bException(tr('notifications_send(): Stopped nofity_send endless loop for ":event" for classes ":classes" with message ":message"', array(':event' => $params['event'], ':classes' => $params['classes'], ':message' => $params['message'])), 'loop');
        }

        $config = $_CONFIG['notifications'];

        if(!$_CONFIG['production'] and (strtolower($event) != 'deploy') and empty($config['force'])){
            /*
             * Events are only sent on production, or during deploys, OR if forced
             */
            return false;
        }

        /*
         * Validate classes
         * By default, the event will be regarded as an class as well. If its not defined, it will later on simply be skipped
         */
        $params['classes'] = array_force($params['classes']);

        if(strtolower($event) == 'exception'){
            /*
             * For errors we always notify the developers
             */
            $params['classes'][] = 'developers';
        }

        /*
         * Dont send the same notification twice to the same class
         */
        array_unique($params['classes']);
        $params['description'] = str_force($params['description']);

        if(empty($core->sql['core'])){
            /*
             * WHOOPS! No database available, we're effed in the A here...
             *
             * Just send email
             */
            $list = array('email');

        }else{
            /*
             * Send notifications for each class
             */
            $name_in = sql_in($params['classes']);
            $id_in   = sql_in($params['classes'], ':id');

            $list    = sql_list('SELECT `id`,
                                        `methods`

                                 FROM   `notifications_classes`

                                 WHERE (`name` IN ('.implode(',', array_keys($name_in)).')
                                 OR     `id`   IN ('.implode(',', array_keys($id_in)).'))
                                 AND    `status` IS NULL',

                                 array_merge($name_in, $id_in));
        }

        if(!$list){
            /*
             * No classes found to send notifications to
             */
            throw new bException(tr('notifications_send(): No classes found for specified classes ":classes"', array(':classes' => $params['classes'])));
        }

        foreach($list as $id => $methods){
            $methods = explode(',', $methods);

            if(empty($core->sql['core'])){
                /*
                 * WHOOPS! No database available, we're effed in the A here...
                 *
                 * Just notify the registered developer
                 */
                $members = array_force($_CONFIG['mail']['developers']);

            }else{
                $members = sql_list('SELECT    `users`.`id` AS mainid,
                                               `users`.`id`,
                                               `users`.`name`,
                                               `users`.`username`,
                                               `users`.`status`,
                                               `users`.`email`,
                                               `users`.`phones`

                                     FROM      `notifications_members`

                                     LEFT JOIN `users`
                                     ON        `notifications_members`.`users_id`   = `users`.`id`

                                     WHERE     `notifications_members`.`classes_id` = :classes_id',

                                     array(':classes_id' => $id));
            }

            if(!$members){
                /*
                 * No classes found to send notifications to
                 */
                log_console('notifications_send(): No members found for class id "'.str_log($id).'"');
                continue;
            }

            foreach($methods as $method){
                switch($method){
                    case 'internal':
                        notifications_internal($event, $message, $members);
                        break;

                    case 'sms':
                        notifications_twilio($event, $message, $members);
                        break;

                    case 'email':
                        notifications_email($event, $message, $members);
                        break;

                    case 'log':
                        log_database($message, $event);
                        break;

                    case 'prowl':
                        notifications_prowl($event, $message, $members);
                        break;

                    default:
                        throw new bException(tr('notifications_send(): Unknown method ":method" specified', array(':method' => $method)));
                }
            }
        }

        return true;

    }catch(Exception $e){
        log_console(tr('notifications_send(): Notification system failed with ":exception"', array(':exception' => $e->getMessage())), 'error');

        if(SCRIPT != 'init'){
            if(empty($_CONFIG['mail']['developer'])){
                log_console('[notifications_send() FAILED : '.strtoupper($_SESSION['domain']).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']');
                log_console("notifications_send() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification event was:\nEvent: \"".cfm($event)."\"\nMessage: \"".cfm($message)."\"");
                log_console('WARNING! $_CONFIG[mail][developer] IS NOT SET, NOTIFICATIONS CANNOT BE SENT!');

            }else{
                mail($_CONFIG['mail']['developer'], '[notifications_send() FAILED : '.strtoupper(isset_get($_SESSION['domain'], $_CONFIG['domain'])).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']', "notifications_send() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification event was:\nEvent: \"".cfm($event)."\"\nMessage: \"".cfm($message)."\"");
            }
        }

        throw new bException('notifications_send(): Failed', $e);
    }
}



/*
 * Do email notifications
 */
function notifications_email($event, $message, $users){
    global $_CONFIG;

    try{
        load_libs('mail');

        $c = $_CONFIG['notifications']['methods']['email'];

        if(empty($c['enabled'])){
            /*
             * Don't do this notification, its disabled by configuration
             */
            return false;
        }

        /*
         * Send notifications for each class
         */
        foreach($users as $user){
            if(!$user) continue;

            if(!is_array($user)){
                /*
                 * Users are specified from DB, or in emergencies, the $_CONFIG
                 * array, which may contain invalid configuration (should be )
                 */
                log_console('notifications_email(): Invalid user "'.str_log($user).'" specified. Should have been an array');
                continue;
            }

            $headers = array('MIME-Version' => '1.0',
                             'Content-type' => 'text/html; charset=UTF-8',
                             'From'         => str_capitalize($_SESSION['domain']).' Notifier <notifier@'.$_SESSION['domain'].'>',
                             'To'           => (empty($user['name']) ? $user : $user['name']).' <'.isset_get($user['email']).'>');

            $start = microtime(true);
            mail($user['email'], '['.strtoupper(cfm($event)).' : '.strtoupper($_SESSION['domain']).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']', $message, mail_headers($headers));

            if((microtime(true) - $start) > 15){
                /*
                 * Mail command took too long, this is probably a configuration error
                 * in /etc/hosts where either localhost is missing, or localhost.localdomain,
                 * or the hostname itself.
                 *
                 * Since this error occurs in the notification system itself, this makes it
                 * rather hard to notify for, so for now, just log the problem in the database
                 */
                log_console('notifications_email(): The PHP mail() command took "'.(microtime(true) - $start).'" seconds to send the message "'.str_log($message).'". This usually is caused by a misconfiguration of /etc/hosts, where one (or multiples) of localhost, localhost.localdomain, or the machines hostname will be missing. Also might be needed to have a FQD in the form of host.domain.com, like laptop.mydomain.com, which then in /etc/hosts may be configured to point to 127.0.1.1. See http://superuser.com/questions/626205/sendmail-very-slow-etc-hosts-configuration/626219#626219 and http://google.com for more information', 'yellow');
            }
        }

    }catch(Exception $e){
        throw new bException('notifications_email(): Failed', $e);
    }
}



/*
 * Do twilio notifications
 */
function notifications_twilio($event, $message, $users){
    global $_CONFIG;

    try{
        load_libs('twilio');

        $c = $_CONFIG['notifications']['methods']['sms'];

        if(empty($_CONFIG['notifications']['methods']['sms'])){
            /*
             * Don't do this notification, its disabled by configuration
             */
            return false;
        }

        /*
         * Send notifications for each class
         */
        foreach($users as $user){
            if(!$user) continue;

            if(!is_array($user)){
                /*
                 * Users are specified from DB, or in emergencies, the $_CONFIG
                 * array, which may contain invalid configuration (should be )
                 */
                log_console(tr('notifications_twilio(): Invalid user ":user" specified. Should have been an array', array(':user' => $user)));
                continue;
            }

            if(!empty($user['phones'])){
                twilio_send_message(substr($message, 0, 140), $user['phones'], '');

            }else{
                throw new bException(tr('User ":user" has not configured phone', array(':user' => $user['username'])));
            }
        }

    }catch(Exception $e){
        throw new bException('notifications_twilio(): Failed', $e);
    }
}



/*
 * Do prowl notifications
 */
function notifications_prowl($event, $message, $users){
    global $_CONFIG;

    try{
        $c = $_CONFIG['notifications']['methods']['prowl'];

        if(empty($c['enabled'])){
            /*
             * Don't do this notification, its disabled by configuration
             */
            return false;
        }

        /*
         * Send notifications for each class
         */
        foreach($users as $user){
            foreach($config['classes'] as $c_class => $c_config){
// :TODO: Implement
            }
        }

    }catch(Exception $e){
        throw new bException('notifications_prowl(): Failed', $e);
    }
}



/*
 * Show notifications on the desktop
 *
 * KDE uses the default kdialog --passivepopup "Example text"
 * GNome uses notify-send "Example text" which requires sudo apt-get install libnotify-bin
 */
function notifications_desktop($params){
    try{
        if($command = safe_exec('which kdialog')){
            /*
             * Welcome to KDE!
             */

        }elseif($command = safe_exec('which notify-send')){
            /*
             * This is gnome
             */

        }elseif($command = safe_exec('which gol')){
            /*
             * This device has Growl for Linux
             */

        }

    }catch(Exception $e){
        throw new bException('notifications_desktop(): Failed', $e);
    }
}



/*
 *
 */
function notifications_class_validate($class){
    try{
        load_libs('validate');
        $v = new validate_form($class, 'name,methods,description');

        $v->hasMinChars($class['name']       ,  2, tr('Please ensure that the notifications class name has more than 2 characters'));
        $v->hasMaxChars($class['name']       ,  32, tr('Please ensure that the notifications class name has less than 32 characters'));
        $v->hasMaxChars($class['description'], 255, tr('Please ensure that the notifications class description has less than 255 characters'));

        $class['methods'] = explode(',', $class['methods']);
        $class['methods'] = array_unique($class['methods']);

        if(!count($class['methods'])){
            $v->setError(tr('Please ensure you have at least one method specified'));

        }elseif(count($class['methods']) > 6){
            $v->setError(tr('Please ensure you have less than six methods specified'));
        }

        foreach($class['methods'] as &$method){
            $method = trim($method);

            switch($method){
                case 'log':
                    // FALLTHROUGH
                case 'sms':
                    // FALLTHROUGH
                case 'email':
                    // FALLTHROUGH
                case 'prowl':
                    // FALLTHROUGH
                case 'desktop':
                    // FALLTHROUGH
                case 'internal':
                    /*
                     * These are valid methods
                     */
                    break;

                default:
                    $v->setError(tr('Unknown notification method ":method" specified', array(':method' => $method)));
            }
        }

        unset($method);

        $class['methods'] = implode(',', $class['methods']);

        if($class['id']){
            $exists = sql_get('SELECT `id` FROM `notifications_classes` WHERE `name` = :name AND `id` != :id', 'id', array(':id' => $class['id'], ':name' => $class['name']));

            if($exists){
                $v->setError(tr('Another notifications class with the name "%name%" already exists under the id "%id%"', array('%id%' => $id, '%name%' => $class['name'])));
            }

        }else{
            $exists = sql_get('SELECT `id` FROM `notifications_classes` WHERE `name` = :name', 'id', array(':name' => $class['name']));

            if($exists){
                $v->setError(tr('Another notifications class with the name "%name%" already exists under the id "%id%"', array('%id%' => $id, '%name%' => $class['name'])));
            }
        }

        $v->isValid();

    }catch(Exception $e){
        throw new bException('notifications_class_validate(): Failed', $e);
    }
}



/*
 *
 */
function notifications_member_validate($member){
    try{
        load_libs('validate');
        $v = new validate_form($member, 'name,methods,description');

    }catch(Exception $e){
        throw new bException('notifications_member_validate(): Failed', $e);
    }
}
?>
