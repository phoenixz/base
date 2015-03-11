<?php
/*
 * CRMTEXT library
 *
 * This library contains API functions for CRMTEXT SMS
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */
load_config('crmtext');



/*
 * Authenticate with CRM text
 */
function crm_authenticate(){
    global $_CONFIG;

    try{
        $userid     = 'userid';
        $passwd     = 'password';
        $postFields = "method=optincustomer&phone_number=9995551212&firstname=&lastname=";
        $authString = $userid . ':'. $password . ':'.$keyword ;
        $centralUrl = 'https://restapi.crmtext.com/smapi/rest';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL           , $centralUrl );
        curl_setopt($ch, CURLOPT_FAILONERROR   , 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT       , 3 );
        curl_setopt($ch, CURLOPT_POST          , true );
        curl_setopt($ch, CURLOPT_HTTPAUTH      , CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD       , $authString);
        curl_setopt($ch, CURLOPT_POSTFIELDS    , $postFields );

        $result = curl_exec($ch);

showdie($result);
    }catch(Exception $e){
        throw new bException('crm_authenticate(): Failed', $e);
    }
}



/*
 *
 */
function crmtext_send_message($message, $phone){
    global $_CONFIG;

    try{
        $config     = $_CONFIG['crmtext'];
        $postFields = 'method=sendsmsmsg&phone_number='.$phone.'&message='.$message;
        $authString = $config['user'].':'.$config['password']. ':'.$config['keyword'];
        $centralUrl = 'https://restapi.crmtext.com/smapi/rest';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL           , $centralUrl);
        curl_setopt($ch, CURLOPT_FAILONERROR   , 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT       , 3);
        curl_setopt($ch, CURLOPT_POST          , true);
        curl_setopt($ch, CURLOPT_HTTPAUTH      , CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD       , $authString);
        curl_setopt($ch, CURLOPT_POSTFIELDS    , $postFields);

        $result = curl_exec($ch);

show($result);
show(curl_error($ch));
showdie($ch);
    }catch(Exception $e){
        throw new bException('crmtext_send_message(): Failed', $e);
    }
}
?>