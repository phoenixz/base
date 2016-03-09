<?php
/*
 * Example message
 *

 "ToCountry":"US",
 "ToState":"NV",
 "SmsMessageSid":"SM601ce491b0f2cbf911d5432f938f9297",
 "NumMedia":"0",
 "ToCity":"NORTH LAS VEGAS",
 "FromZip":"89121",
 "SmsSid":"SM601ce491b0f2cbf911d5432f938f9297",
 "FromState":"NV",
 "SmsStatus":"received",
 "FromCity":"LAS VEGAS",
 "Body":"New toy!!!",
 "FromCountry":"US",
 "To":"+17024873355",
 "ToZip":"89032",
 "MessageSid":"SM601ce491b0f2cbf911d5432f938f9297",
 "AccountSid":"AC896dfa5937f13e1980d9ccd2cb19757b",
 "From":"+17028589578",
 "ApiVersion":"2010-04-01"

 */
// CODE TO TEST
//$_POST["ToCountry"]     = "US";
//$_POST["ToState"]       = "NV";
//$_POST["SmsMessageSid"] = "SM601ce491b0f2cbf911d5432f938f9297";
//$_POST["NumMedia"]      = "0";
//$_POST["ToCity"]        = "NORTH LAS VEGAS";
//$_POST["FromZip"]       = "89121";
//$_POST["SmsSid"]        = "SM601ce491b0f2cbf911d5432f938f9297";
//$_POST["FromState"]     = "NV";
//$_POST["SmsStatus"]     = "received";
//$_POST["FromCity"]      = "LAS VEGAS";
//$_POST["Body"]          = "New toy!!!";
//$_POST["FromCountry"]   = "US";
//$_POST["ToZip"]         = "89032";
//$_POST["MessageSid"]    = "SM601ce491b0f2cbf911d5432f938f9297";
//$_POST["AccountSid"]    = "AC896dfa5937f13e1980d9ccd2cb19757b";
//$_POST["To"]            = "+17024873355";
//$_POST["From"]          = "+18443385112";
//$_POST["ApiVersion"]    = "2010-04-01";

require_once(dirname(__FILE__).'/libs/startup.php');

try{
    load_libs('validate,twilio,sms');

    $v = new validate_form($_POST, 'ToCountry,ToState,SmsMessageSid,NumMedia,ToCity,FromZip,SmsSid,FromState,SmsStatus,FromCity,Body,FromCountry,To,ToZip,MessageSid,AccountSid,From,ApiVersion', '');


    /*
     * Get conversation for this local - remote message, and update the conversation with the message contents
     */
    $conversation = sms_get_conversation($_POST['To'], $_POST['From']);



    /*
     * Store the message
     */
    sql_query('INSERT INTO `sms_messages` (`conversations_id`, `direction`, `api_version`, `message_sid`, `account_sid`, `sms_status`, `sms_id`, `sms_message_sid`, `num_media`, `from_country`, `from_state`, `from_city`, `from_zip`, `from_phone`, `to_country`, `to_state`, `to_city`, `to_zip`, `to_phone`, `body`, `type`)
               VALUES                     (:conversations_id , "received" , :api_version , :message_sid , :account_sid , :sms_status , :sms_id , :sms_message_sid , :num_media , :from_country , :from_state , :from_city , :from_zip , :from_phone , :to_country , :to_state , :to_city , :to_zip , :to_phone , :body , :type )',

               array(':conversations_id' => $conversation['id'],
                     ':api_version'      => $_POST['ApiVersion'],
                     ':message_sid'      => $_POST['MessageSid'],
                     ':account_sid'      => $_POST['AccountSid'],
                     ':sms_status'       => $_POST['SmsStatus'],
                     ':sms_id'           => $_POST['SmsSid'],
                     ':sms_message_sid'  => $_POST['SmsMessageSid'],
                     ':num_media'        => $_POST['NumMedia'],
                     ':from_country'     => $_POST['FromCountry'],
                     ':from_state'       => $_POST['FromState'],
                     ':from_city'        => $_POST['FromCity'],
                     ':from_zip'         => $_POST['FromZip'],
                     ':from_phone'       => $_POST['From'],
                     ':to_country'       => $_POST['ToCountry'],
                     ':to_state'         => $_POST['ToState'],
                     ':to_city'          => $_POST['ToCity'],
                     ':to_zip'           => $_POST['ToZip'],
                     ':to_phone'         => $_POST['To'],
                     ':body'             => $_POST['Body'],
                     ':type'             => 'sms'));



    /*
     *
     */
    $messages_id = sql_insert_id();

    sms_update_conversation($conversation, $messages_id, 'received', $_POST['Body'], sql_get('SELECT `createdon` FROM `sms_messages` WHERE `id` = :id', 'createdon', array(':id' => $messages_id)), false);



    /*
     * MMS message?
     */
    for($i = 0; $i < 20; $i++){
        if(empty($_REQUEST['MediaUrl'.$i])){
            break;
        }

        twilio_add_image($messages_id, $_REQUEST['MediaUrl'.$i]);
    }


    /*
     * Respond to twilio
     */
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $header_sent = true;
    $html        = '';

    $html = '   <Response>
                </Response>';
//                <Message>'.tr('Copy!').'</Message>

    echo $html;

}catch(Exception $e){
    log_error($e->getMessage(), 'twilio');

    if(empty($header_sent)){
        header("content-type: text/xml");
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    }

    $html = '   <Response>
                    <Message>'.tr('ERROR! Something went wrong, please try again!').'</Message>
                </Response>';

    echo $html;
}
?>
