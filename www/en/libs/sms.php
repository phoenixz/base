<?php
/*
 * SMS library
 *
 * This library is the generic SMS interface library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */
load_config('sms');



/*
 * Send SMS
 */
function sms_send_message($message, $to, $from = null){
    global $_CONFIG;

    try{
        if($from === 'crmtext'){
            $provider = $from;

        }else{
            $provider = $_CONFIG['sms']['preferred']['send'];
        }

        switch($provider){
            case 'crmtext':
                load_libs('crmtext');
                crmtext_send_message($message, $to);
                break;

            case 'twilio':
                load_libs('twilio');
                twilio_send_message($message, $to, $from);
                break;

            default:
                throw new bException(tr('sms_send(): Unknown preferred SMS provider "%provider%" specified, check your configuration $_CONFIG[sms][prefer]', array('%provider%' => $_CONFIG['sms']['prefer'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('sms_send(): Failed', $e);
    }
}
?>
