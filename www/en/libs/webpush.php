<?php
/*
 * Webpush library
 *
 * This library contains front end functions for the external minishlink
 * web-push library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function webpush_library_init(){
    try{
        if(version_compare(PHP_VERSION, '5.6') === -1){
            throw new bException(tr('webpush_library_init(): The current PHP version is ":version" while version "5.6.0" or higher is required to use the webpush library', array(':version' => PHP_VERSION)), 'version');
        }

        //ensure_installed(array('name'      => 'sweetalert',
        //                       'project'   => 'sweetalert',
        //                       'callback'  => 'sweetalert_install',
        //                       'checks'    => array(ROOT.'pub/js/sweetalert/sweetalert.js',
        //                                            ROOT.'pub/css/sweetalert/sweetalert.css')));

        require_once(__DIR__.'/external/webpush/vendor/autoload.php');
        require_once(__DIR__.'/external/webpush/vendor/minishlink/web-push/src/WebPush.php');



    }catch(Exception $e){
        throw new bException('webpush_library_init(): Failed', $e);
    }
}



/**
 *
 * Sends a push notification to specific user (If user has webpush JSON in DB) with config params
 *
 * @param   string  $id         Desired user id to send the notification
 * @param   string  $subject    Can be a mailto:me@website.com or your website address
 * @param   string  $payload    Actual data you want to send over push notification
 * @param   bool    $flush      Get response from send notification, so, if notification fails, an array with detailed error is returned
 * @return  array|bool          Array with error data if @param $flush is true and send_notification fails.
 *                              If some required params are empty returns false
 *                              Any other case always returns true
 */
function webpush_notify_user($users_id, $subject = '', $payload = '', $flush = false){
    global $_CONFIG;

    try{
        load_config('webpush,json');

        $user = sql_get('SELECT `webpush` FROM `users` WHERE `id` = :id', array(':id' => $users_id));

        if(empty($user)){
            throw new bException(tr('webpush_notify_user(): Specified user ":user" does not exist', array(':user' => $users_id)), 'not-exist');
        }

        if(empty($_CONFIG['webpush']['public_key']) or empty($_CONFIG['webpush']['private_key'])){
            throw new bException(tr('webpush_notify_user(): webpush has not been configured, see $_CONFIG[webpush]'), 'not-configured');
        }

        if(!$user['webpush']){
            return false;
        }

        $subscription = json_decode_custom($user['webpush'], true);

        return send_notification($_CONFIG['webpush']['public_key'],
                                 $_CONFIG['webpush']['private_key'],
                                 $subscription['keys']['p256dh'],
                                 $subscription['keys']['auth'],
                                 $subscription['endpoint'],
                                 $subject,
                                 $payload,
                                 $flush);

    }catch(Exception $e){
        throw new bException(tr('webpush_notify_user(): Failed'), $e);
    }
}



/**
 *
 * Send a push notification overriding all params of the config file
 *
 * @param   string  $public_key     Generated public key, suggested site to get it: https://web-push-codelab.glitch.me
 * @param   string  $private_key    Generated private key, suggested site to get it: https://web-push-codelab.glitch.me
 * @param   string  $p256dh         Client uncompressed public key P-256
 * @param   string  $auth           Client secret multiplier of the private key (User auth token)
 * @param   string  $endpoint       Client endpoint, this value is unique
 * @param   string  $subject        Can be a mailto: or your website address
 * @param   string  $payload        Actual data you want to send over push notification
 * @param   bool    $flush          Get response from send notification, so, if notification fails, an array with detailed error is returned
 * @return  array|bool              Array with error data if @param $flush is true and send_notification fails, any other case always returns true
 */
function webpush_notify($public_key, $private_key, $p256dh, $auth, $endpoint, $subject = '', $payload = '', $flush = false) {
    try{
        $authentication = array('VAPID' => array('subject'    => $subject,
                                                 'publicKey'  => $public_key,
                                                 'privateKey' => $private_key));

        $webPush = new \Minishlink\WebPush\WebPush($authentication);
        $result  = $webPush->sendNotification($endpoint, $payload, $p256dh, $auth, $flush);

        if($flush and ($result !== true)){
            return $result;
        }

        return true;

    }catch(Exception $e){
        throw new bException(tr('send_notification(): Failed'), $e);
    }
}



/*
 *
 */
function webpush_subscribe($subscription){
    try{
        if (!empty($subscription)) {
            $subscription_json = json_decode_custom($subscription, true);

            if(!empty($subscription_json['endpoint'])){
                sql_query('UPDATE `users` SET `webpush` = :webpush WHERE `id` = :id', array(':webpush' => $subscription, ':id' => $_SESSION['user']['id']));
            }
        }

    }catch(Exception $e){
        throw new bException(tr('webpush_subscribe(): Failed'), $e);
    }
}
?>
