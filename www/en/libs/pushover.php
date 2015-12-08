<?php
/*
 * Pushover Library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */
load_config('pushover');
load_libs('ext/php-pushover/Pushover');



/*
 * Send an SMS through the pushover service
 */
function pushover_send_sms($user, $params){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'msg'      , 'Empty message');
        array_default($params, 'title'    , 'Empty title');
        array_default($params, 'url-title', 'Empty url-title');

        /*
         * User preferences
         */
        $user = $_CONFIG['pushover']['users'][$user];

        /*
         * New pushover object
         */
        $push = new Pushover();

        /*
         * Set Tokens
         */
        $push->setToken($_CONFIG['pushover']['api-token']);
        $push->setUser($user['key']);

        /*
         * Set SMS content
         */
        $push->setTitle($params['title']);
        $push->setMessage($params['msg']);
        $push->setUrl(domain());
        $push->setUrlTitle($params['url-title']);
        $push->setDevice($user['device']);

        /*
         * Set SMS extra-settings
         *
         * setRetry: Used with Priority = 2; Pushover will resend the notification every 60 seconds until the user accepts.
         * setExpire: Used with Priority = 2; Pushover will resend the notification every 60 seconds for 3600 seconds. After that point, it stops sending notifications.
         */
        $push->setPriority(2);
        $push->setRetry(60);
        $push->setExpire(3600);
        $push->setCallback(domain());
        $push->setTimestamp(time());
        $push->setDebug(true);
        $push->setSound($user['sound']);

        /*
         * Send the SMS
         */
        $push->send();

    }catch(Exception $e){
        throw new bException(tr('pushover_send_sms(): Failed'), $e);
    }
}



/*
 *
 */
function pushover_send_sms_all($params){
    global $_CONFIG;

    try{
        foreach($_CONFIG['pushover']['users'] as $user => $values){
            /*
             * Send SMS
             */
            pushover_send_sms($user, $params);
        }

    }catch(Exception $e){
        throw new bException(tr('pushover_send_sms_all(): Failed'), $user);
    }
}
?>
