<?php
/*
 * Notifications library
 *
 * This library contains notifications functions, functions related to sending notifications back to ourselves in case of problems, events, etc.
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Do notifications
 */
function notifications_do($event, $message, $classes = null, $alternate_subenvironment = null){
    global $_CONFIG;

    try{
        $n = $_CONFIG['notifications'];

        if((ENVIRONMENT != 'production') and (strtolower($event) != 'deploy') and empty($n['force'])){
            /*
             * Events are only sent on production, or during deploys, OR if forced
             */
            return false;
        }

        /*
         * Validate classes
         */
        if(!$classes){
            $classes = array();
        }

        if(!is_array($classes)){
            $classes = array($classes);
        }

        /*
         * By default, the event will be regarded as an event as well. If its not defined, it will later on simply be skipped
         */
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

        /*
         * Send notifications for each class
         */
        $keys = array_sequential_values(count($classes), ':classes');

        if(!isset_get($GLOBALS['sql'])){
            /*
             * WHOOPS! No database available, we're effed in the A here...
             *
             * Just send email
             */
            $list = array('email');

        }else{
            $list = sql_list('SELECT `id`, `methods`
                              FROM   `notifications_classes`
                              WHERE  `name` IN ('.implode(',', $keys).')',

                              array_combine($keys, $classes));
        }

        foreach($list as $id => $methods){
            $methods = explode(',', $methods);

            if(!isset_get($GLOBALS['sql'])){
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
                                               `users`.`email`

                                     FROM      `notifications_members`

                                     LEFT JOIN `users`
                                     ON        `notifications_members`.`users_id`   = `users`.`id`

                                     WHERE     `notifications_members`.`classes_id` = :classes_id',

                                     array(':classes_id' => $id));
            }

            foreach($methods as $method){
                switch($method){
                    case 'sms':
                        throw new lsException('notifications_do(): SMS notifications are not yet supported');

                    case 'email':
                        notifications_email($event, $message, $members);
                        break;

                    case 'prowl':
                        notifications_prowl($event, $message, $members);
                        break;

                    default:
                        throw new lsException('notifications_do(): Unknown method "'.str_log($method).'" specified');
                }
            }
        }

    }catch(Exception $e){
        if(SCRIPT == 'init'){
            log_console('notifications_do(): Notification system failed with "'.str_log($e->getMessage()).'"', 'warning');

        }else{
            if(empty($_CONFIG['mail']['developer'])){
                log_error('[notifications_do() FAILED : '.strtoupper($_CONFIG['domain']).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).(SUBENVIRONMENT ? ' / '.SUBENVIRONMENT : '').']', "notifications_do() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification event was:\nEvent: \"".cfm($event)."\"\nMessage: \"".cfm($message)."\"");
                log_error('WARNING! $_CONFIG[mail][developer] IS NOT SET, NOTIFICATIONS CANNOT BE SENT!');

            }else{
                mail($_CONFIG['mail']['developer'], '[notifications_do() FAILED : '.strtoupper($_CONFIG['domain']).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).(SUBENVIRONMENT ? ' / '.SUBENVIRONMENT : '').']', "notifications_do() failed with: ".implode("\n", $e->getMessages())."\n\nOriginal notification event was:\nEvent: \"".cfm($event)."\"\nMessage: \"".cfm($message)."\"");
            }
        }

        throw new lsException('notifications_do(): Failed', $e);
    }
}



/*
 * Do email notifications
 */
function notifications_email($event, $message, $users, $alternate_subenvironment = null){
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
                             'From'         => str_capitalize($_CONFIG['domain']).' Notifier <notifier@'.$_CONFIG['domain'].'>',
                             'To'           => (empty($user['name']) ? $user : $user['name']).' <'.isset_get($user['email']).'>');

            $start = microtime(true);
            mail($user['email'], '['.strtoupper(cfm($event)).' : '.strtoupper($_CONFIG['domain']).' / '.strtoupper(php_uname('n')).' / '.strtoupper(ENVIRONMENT).(REQUIRE_SUBENVIRONMENTS ? ' / '.($alternate_subenvironment ? $alternate_subenvironment : SUBENVIRONMENT ) : '').']', $message, mail_headers($headers));

            if((microtime(true) - $start) > 15){
                /*
                 * Mail command took too long, this is probably a configuration error
                 * in /etc/hosts where either localhost is missing, or localhost.localdomain,
                 * or the hostname itself.
                 *
                 * Since this error occurs in the notification system itself, this makes it
                 * rather hard to notify for, so for now, just log the problem in the database
                 */
                log_error('notifications_email(): The PHP mail() command took "'.(microtime(true) - $start).'" seconds to send the message "'.str_log($message).'". This usually is caused by a misconfiguration of /etc/hosts, where one (or multiples) of localhost, localhost.localdomain, or the machines hostname will be missing. Also might be needed to have a FQD in the form of host.domain.com, like laptop.mydomain.com, which then in /etc/hosts may be configured to point to 127.0.1.1', 'mailslow');
            }
        }

    }catch(Exception $e){
        throw new lsException('notifications_email(): Failed', $e);
    }
}



/*
 * Do prowl notifications
 */
function notifications_prowl($event, $message, $users, $alternate_subenvironment = null){
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
        throw new lsException('notifications_prowl(): Failed', $e);
    }
}



/*
 *
 */
function notifications_classes_insert($params){
    try{
        array_params($params);

        user_or_redirect();

        if(empty($params['name'])){
            throw new lsException('notifications_classes_insert(): No name specified', 'notspecified');
        }

        if(empty($params['methods'])){
            throw new lsException('notifications_classes_insert(): No methods specified', 'notspecified');
        }

        if(!is_array($params['methods'])){
            $params['methods'] = explode(',', $params['methods']);
        }

        foreach($params['methods'] as $method){
            if(!in_array($method, array('email', 'prowl'))){
                throw new lsException('notifications_classes_insert(): Unknown method "'.str_log($method).'" specified', 'unknown');
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
        throw new lsException('notifications_classes_insert(): Failed', $e);
    }
}



/*
 *
 */
function notifications_members_insert($params){
    try{
        array_params($params);

        user_or_redirect();

        if(empty($params['classes_id'])){
            if(empty($params['name'])){
                throw new lsException('notifications_members_insert(): No notification class specified', 'notspecified');
            }

            $class = sql_get('SELECT `id`,
                                     `name`

                              FROM   `notifications_classes`

                              WHERE  `id`   = :id
                              OR     `name` = :name',

                              array(':id'   => $params['name'],
                                    ':name' => $params['name']));

            if(!$class){
                throw new lsException('notifications_members_insert(): Specified notification class "'.str_log($params['classes_id']).'" does not exist', 'notexists');
            }

            $params['classes_id'] = $class['id'];
        }

        if(empty($params['members'])){
            throw new lsException('notifications_members_insert(): No members specified', 'notspecified');
        }

        load_libs('user');

        $count = 0;

        foreach(array_force($params['members']) as $member){
            if(!$users_id = user_get($member, 'id')){
                log_error('notifications_members_insert(): Specified member "'.str_log($member).'" does not exist', 'notexists');
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
        throw new lsException('notifications_members_insert(): Failed', $e);
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
        throw new lsException('notifications_desktop(): Failed', $e);
    }
}
?>
