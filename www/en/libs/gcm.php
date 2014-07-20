<?php
/*
 * GCM library
 *
 * This library contains Google Cloud Messaging functions
 *
 * May be used for, for example, android push notifications
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * GCM send Android Push Notification
 */
function gcm_send_notification($registatoin_ids, $message) {
    global $_CONFIG;

    try{
        load_libs('json');

        if(!function_exists('curl_init')){
            throw new lsException('gcm_send_notification(): PHP CURL is not installed, this function cannot work without this library');
        }

        if(!is_array($registatoin_ids)){
            $registatoin_ids = array($registatoin_ids);
        }

        if(!is_array($message)){
            $message = array('message' => $message);
        }

        // Set POST variables
        $url    = 'https://android.googleapis.com/gcm/send';

        $fields = array('registration_ids' => $registatoin_ids,
                        'data'             => $message);

        $headers = array('Authorization: key=' . $_CONFIG['gcm']['google_api_key'],
                         'Content-Type: application/json');

        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL           , $url);
        curl_setopt($ch, CURLOPT_POST          , true);
        curl_setopt($ch, CURLOPT_HTTPHEADER    , $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS    , json_encode_custom($fields));

        // Execute post
        $result = curl_exec($ch);

        if ($result === FALSE) {
            throw new isException('gcm_send_notification(): Curl failed with "'.curl_error($ch).'"');
        }

        // Close connection
        curl_close($ch);

        return $result;

    }catch(Exception $e){
        throw new isException('gcm_send_notification(): Failed with "'.curl_error($e->getMessage()).'"');
    }
}
?>
