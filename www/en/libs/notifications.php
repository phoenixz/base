<?php
/*
 * Notifications library
 *
 * This library contains notifications functions, functions related to sending notifications back to ourselves in case of problems, events, etc.
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */
load_config('notifications');



/*
 * Do notifications
 */
function notifications_do($event, $message, $classes = null){
    static $count = 0;
    global $_CONFIG;

    if(empty($message) and is_object($event) and ($event instanceof Exception)){
        /*
         * Notify about an exception
         */
        $message = $event;
        $event   = 'exception';
        $group   = 'developers';
    }

    if(empty($_CONFIG['production'])){
        throw new bException($message, $event);
    }

return false;
    if(++$count > 15){
        /*
         * Endless loop protection
         */
        if(!debug()){
            return false;
        }

        show($count);
        show($event);
        show($message);
        showdie(debug_trace());
    }

    try{
        $message = str_force($message);
        $n       = $_CONFIG['notifications'];

        if(!$_CONFIG['production'] and (strtolower($event) != 'deploy') and empty($n['force'])){
            /*
             * Events are only sent on production, or during deploys, OR if forced
             */
            return false;
        }

        /*
         * Validate classes
         * By default, the event will be regarded as an class as well. If its not defined, it will later on simply be skipped
         */
        $classes   = array_force($classes);
        $classes[] = $event;

        if(strtolower($event) == 'error'){
            /*
             * For errors we always notify the developers
             */
            $classes[] = 'developers';
        }

        /*
         * Dont send the same notification twice to the same class
         */
        array_unique($classes);

        if(!isset_get($GLOBALS['sql_core'])){
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
            $name_in = sql_in($classes);
            $id_in   = sql_in($classes, ':id');

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
            throw new bException('notifications_do(): No classes found for specified classes "'.str_log($classes).'"');
        }

        foreach($list as $id => $methods){
            $methods = explode(',', $methods);

            if(!isset_get($GLOBALS['sql_core'])){
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
                log_error('notifications_do(): No members found for class id "'.str_log($id).'"');
                continue;
            }

            foreach($methods as $method){
                switch($method){
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
                        throw new bException(tr('notifications_do(): Unknown method ":method" specified', array(':method' => $method)));
                }
            }
        }

        return true;

    }catch(Exception $e){
        log_error(tr('notifications_do(): Notification system failed with ":exception"', array(':exception' => $e->getMessage())), 'error', false);

        if(SCRIPT != 'init'){
            if(empty($_CONFIG['mail']['developer'])){
                log_error('[notifications_do() FAILED : '.strtoupper($_SESSION['domain']).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']', "notifications_do() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification event was:\nEvent: \"".cfm($event)."\"\nMessage: \"".cfm($message)."\"");
                log_error('WARNING! $_CONFIG[mail][developer] IS NOT SET, NOTIFICATIONS CANNOT BE SENT!');

            }else{
                mail($_CONFIG['mail']['developer'], '[notifications_do() FAILED : '.strtoupper($_SESSION['domain']).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).']', "notifications_do() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification event was:\nEvent: \"".cfm($event)."\"\nMessage: \"".cfm($message)."\"");
            }
        }

        throw new bException('notifications_do(): Failed', $e);
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
                log_error('notifications_email(): Invalid user "'.str_log($user).'" specified. Should have been an array');
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
                log_error('notifications_email(): The PHP mail() command took "'.(microtime(true) - $start).'" seconds to send the message "'.str_log($message).'". This usually is caused by a misconfiguration of /etc/hosts, where one (or multiples) of localhost, localhost.localdomain, or the machines hostname will be missing. Also might be needed to have a FQD in the form of host.domain.com, like laptop.mydomain.com, which then in /etc/hosts may be configured to point to 127.0.1.1. See http://superuser.com/questions/626205/sendmail-very-slow-etc-hosts-configuration/626219#626219 and http://google.com for more information', 'mailslow');
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
                log_error('notifications_twilio(): Invalid user ":user" specified. Should have been an array', array(':user' => $user));
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
            foreach($n['classes'] as $c_class => $c_config){
// :TODO: Implement
            }
        }

    }catch(Exception $e){
        throw new bException('notifications_prowl(): Failed', $e);
    }
}



/*
 *
 */
function notifications_classes_insert($params){
    try{
        array_params($params);

        user_or_signin();

        if(empty($params['name'])){
            throw new bException('notifications_classes_insert(): No name specified', 'not-specified');
        }

        if(empty($params['methods'])){
            throw new bException('notifications_classes_insert(): No methods specified', 'not-specified');
        }

        if(!is_array($params['methods'])){
            $params['methods'] = explode(',', $params['methods']);
        }

        foreach($params['methods'] as $method){
            if(!in_array($method, array('email', 'prowl'))){
                throw new bException('notifications_classes_insert(): Unknown method "'.str_log($method).'" specified', 'unknown');
            }
        }

        sql_query('INSERT INTO `notifications_classes` (`addedby`, `name`, `methods`, `description`, `status`)
                   VALUES                              (:addedby , :name , :methods , :description , :status )',

                   array(':addedby'     => $_SESSION['user']['id'],
                         ':name'        => $params['name'],
                         ':methods'     => implode(',', $params['methods']),
                         ':description' => isset_get($params['description']),
                         ':status'      => isset_get($params['status'])));

        return sql_insert_id();

    }catch(Exception $e){
        throw new bException('notifications_classes_insert(): Failed', $e);
    }
}



/*
 *
 */
function notifications_members_insert($params){
    try{
        array_params($params);

        user_or_signin();

        if(empty($params['classes_id'])){
            if(empty($params['name'])){
                throw new bException('notifications_members_insert(): No notification class specified', 'not-specified');
            }

            $class = sql_get('SELECT `id`,
                                     `name`

                              FROM   `notifications_classes`

                              WHERE  `id`   = :id
                              OR     `name` = :name',

                              array(':id'   => $params['name'],
                                    ':name' => $params['name']));

            if(!$class){
                throw new bException(tr('notifications_members_insert(): Specified notification class ":class" does not exist', array(':class' => $params['classes_id'])), 'yellow');
            }

            $params['classes_id'] = $class['id'];
        }

        if(empty($params['members'])){
            throw new bException('notifications_members_insert(): No members specified', 'not-specified');
        }

        load_libs('user');

        $count = 0;

        foreach(array_force($params['members']) as $member){
            if(!$users_id = user_get($member, 'id')){
                log_error(tr('notifications_members_insert(): Specified member ":member" does not exist', array(':member' => $member)), 'yellow');
                continue;
            }

            if(sql_get('SELECT `id`
                        FROM   `notifications_members`
                        WHERE  `classes_id` = :classes_id
                        AND    `users_id`   = :users_id',

                        array(':classes_id' => $params['classes_id'],
                              ':users_id'   => $users_id))){
                log_error('notifications_members_insert(): Specified member "'.str_log($member).'" is aleady member of class "'.$params['classes_id'].'"', 'notexists', 'yellow');
                continue;
            }

            sql_query('INSERT INTO `notifications_members` (`addedby`, `classes_id`, `users_id`, `status`)
                       VALUES                              (:addedby , :classes_id , :users_id , :status )',

                       array(':addedby'    => $_SESSION['user']['id'],
                             ':classes_id' => $params['classes_id'],
                             ':users_id'   => $users_id,
                             ':status'     => isset_get($params['status'])));

            $count++;
        }

        return $count;

    }catch(Exception $e){
        throw new bException('notifications_members_insert(): Failed', $e);
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
function notifications_validate_class($class){
    load_libs('validate');

    $v = new validate_form($class, 'name,methods,description');

    $v->hasMinChars($class['name']       ,  2, tr('Please ensure that the notifications class name has more than 2 characters'));
    $v->hasMaxChars($class['name']       ,  32, tr('Please ensure that the notifications class name has less than 32 characters'));
    $v->hasMaxChars($class['description'], 255, tr('Please ensure that the notifications class description has less than 255 characters'));

    $class['methods'] = explode(',', $class['methods']);

    if(!count($class['methods'])){
        $v->setError(tr('Please ensure you have at least one method specified'));

    }elseif(count($class['methods']) > 3){
        $v->setError(tr('Please ensure you have less than three methods specified'));
    }

    foreach($class['methods'] as &$method){
        $method = trim($method);

        switch($method){
            case 'sms':
            case 'email':
                /*
                 * These are valid methods
                 */
                break;

            default:
                $v->setError(tr('Unknown notifications method "%method%" specified', array('%method%' => $method)));
        }
    }

    unset($method);

    $class['methods'] = implode(',', $class['methods']);

    if($class['id']){
        if($id = sql_get('SELECT `id` FROM `notifications_classes` WHERE `name` = :name AND `id` != :id', 'id', array(':id' => $class['id'], ':name' => $class['name']))){
            $v->setError(tr('Another notifications class with the name "%name%" already exists under the id "%id%"', array('%id%' => $id, '%name%' => $class['name'])));
        }

    }else{
        if($id = sql_get('SELECT `id` FROM `notifications_classes` WHERE `name` = :name', 'id', array(':name' => $class['name']))){
            $v->setError(tr('Another notifications class with the name "%name%" already exists under the id "%id%"', array('%id%' => $id, '%name%' => $class['name'])));
        }
    }

    $v->isValid();

    return $class;
}
?>
