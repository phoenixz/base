<?php
/*
 * Paypal library
 *
 * This are functions only used for paypal
 *
 * @copyright Johan Gueze, Sven Oostenbrink
 */



/*
 *
 */
function paypal_get_subscr_id_from_custom($custom) {
    return sql_get("select subscr_id from paypal_payments where custom='".cfm($custom)."' order by logdate desc limit 0,1");
}



/*
 *
 */
function paypal_version() {
    global $_CONFIG;
    try{
        return $_CONFIG['paypal']['version'];
    }catch(Exception $e){
        throw new bException('paypal_version(): Failed', $e);
    }
}



/*
 * Camcel subscription
 * Actions are Cancel, Suspend and Reactivate
 */
function paypal_change_subscription_status($profile_id, $action='Cancel') {
    global $_CONFIG;
    try{
        $api_request = 'USER=' . urlencode($_CONFIG['paypal'][paypal_version()]['api-username'])
                .  '&PWD=' . urlencode($_CONFIG['paypal'][paypal_version()]['api-password'])
                .  '&SIGNATURE=' . urlencode($_CONFIG['paypal'][paypal_version()]['api-signature'])
                .  '&VERSION=76.0'
                .  '&METHOD=ManageRecurringPaymentsProfileStatus'
                .  '&PROFILEID=' . urlencode($profile_id)
                .  '&ACTION=' . urlencode($action)
                .  '&NOTE=' . urlencode(tr('Profile cancelled on website'));

        $ch = curl_init();
        if(paypal_version()=='sandbox') {
            curl_setopt( $ch, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp' );
        } else {
            curl_setopt( $ch, CURLOPT_URL, 'https://api-3t.paypal.com/nvp' );
        }
        curl_setopt( $ch, CURLOPT_VERBOSE, 1 );

        // Uncomment these to turn off server and peer verification
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        // Set the API parameters for this transaction
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $api_request );

        // Request response from PayPal
        $response = curl_exec( $ch );

        // If no response was received from PayPal there is no point parsing the response
        if( ! $response ) {
            throw new bException('paypal_change_subscription_status(): Failed ' . curl_error( $ch ) . '(' . curl_errno( $ch ) . ')', '');
        }

        curl_close( $ch );

        // An associative array is more usable than a parameter string
        parse_str( $response, $parsed_response );
        return $parsed_response;
    }catch(Exception $e){
        throw new bException('paypal_change_subscription_status(): Failed', $e);
    }
}



/*
 * create a paypal subscription button
 *
 * Todo sandbox testing, payments can be made from the sandbox account buyer@webmerica.com , password = kebab123
 */
function paypal_subscription_button($params=array()) {
    global $_CONFIG;
    try{
        array_default($params,'business', $_CONFIG['paypal'][paypal_version()]['email']);            //webmerica  business email address
        array_default($params,'lc', 'ES');                                 //Country
        array_default($params,'item_name', 'unknown');                             //item name, displayed to user when paying
        array_default($params,'item_number', 'product-one');                         //key that describes name of product, dont add weird chars or spaces!
        array_default($params,'return', 'http://'.$_SESSION['domain'].'/this_is_the_paypal_success_page');     //return user to this url after a successful subscription has been created.
        array_default($params,'cancel_return', 'http://'.$_SESSION['domain'].'/this_is_the_paypal_fail_page');     //return user to this url after setting up the payment was unsuccessfull.
        array_default($params,'src', 1);                                     //if we should rebill yes/no (1/0)
        array_default($params,'a3', '0.99');                                 //price
        array_default($params,'p3', '1');                                 //repeat every [nr] months
        array_default($params,'t3', 'M');                                 //repeat every 1 [M/Y/D]
        array_default($params,'currency_code', 'USD');                             //Currency code (https://cms.paypal.com/mx/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_currency_codes)
        array_default($params,'custom', 'none');                                 //local id to identify the payment
        array_default($params,'notify_url', 'http://'.$_SESSION['domain'].'/this_is_the_paypal_notify_page');     //url where paypal reports payment
        array_default($params,'image', 'https://www.paypalobjects.com/en_US/i/btn/btn_subscribeCC_LG.gif');    //url of button image
        array_default($params,'image_alt', tr('PayPal - The safer, easier way to pay online!'));        //alt text of button
        array_default($params,'cpp_header_image', 'http://'.$_SESSION['domain'].'/pub/img/paypal_header.jpg');        //image top header paypal payments


        $html='<form action="'.(paypal_version()=='live'?'https://www.paypal.com/cgi-bin/webscr':'https://www.sandbox.paypal.com/cgi-bin/webscr').'" method="post" target="_top">
            <input type="hidden" name="cmd" value="_xclick-subscriptions">
            <input type="hidden" name="business" value="'.$params['business'].'">
            <input type="hidden" name="lc" value="'.$params['lc'].'">
            <input type="hidden" name="item_name" value="'.$params['item_name'].'">
            <input type="hidden" name="item_number" value="'.$params['item_number'].'">
            <input type="hidden" name="no_note" value="1">
            <input type="hidden" name="no_shipping" value="1">
            <input type="hidden" name="rm" value="1">
            <input type="hidden" name="return" value="'.$params['return'].'">
            <input type="hidden" name="cancel_return" value="'.$params['cancel_return'].'">
            <input type="hidden" name="src" value="'.$params['src'].'">
            <input type="hidden" name="a3" value="'.$params['a3'].'">
            <input type="hidden" name="p3" value="'.$params['p3'].'">
            <input type="hidden" name="t3" value="'.$params['t3'].'">
            <input type="hidden" name="currency_code" value="'.$params['currency_code'].'">
            <input type="hidden" name="bn" value="PP-SubscriptionsBF:btn_subscribeCC_LG.gif:NonHosted">
            <input type="hidden" name="custom" value="'.$params['custom'].'">
            <input type="hidden" name="notify_url" value="'.$params['notify_url'].'">
            <input type="hidden" name="cpp_header_image" value="'.$params['cpp_header_image'].'">
            <input type="image" src="'.$params['image'].'" border="0" name="submit" alt="'.$params['image_alt'].'">
            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
        </form>';
        return $html;
    }catch(Exception $e){
        throw new bException('paypal_subscription_button(): Failed', $e);
    }
}



//store ipn data from paypal and return it
function paypal_ipn_log() {
    global $_CONFIG;

    try{
        load_libs('json');

        //they can submit with post and get
        if(empty($_POST)){
            $_POST=$_GET;
        }

        if(!empty($_POST['txn_type']) && paypal_check_ipn_request()) {
            sql_query("insert into paypal_payments (custom,item_number,subscr_id,buyer_email,txn_type,currency_code,amount,logdate,raw_paypal_data,payment_status) values ('".cfm(isset_get($_POST['custom']))."','".cfm(isset_get($_POST['item_number']))."','".cfm(isset_get($_POST['subscr_id']))."','".cfm(isset_get($_POST['payer_email']))."','".cfm(isset_get($_POST['txn_type']))."','".cfm(isset_get($_POST['mc_currency']))."','".cfm(isset_get($_POST['mc_gross']))."',".time().",'".cfm(json_encode_custom($_POST))."','".cfm(isset_get($_POST['payment_status']))."');");
            return $_POST;
        } else {
            throw new bException('paypal_ipn_log(): Failed ipn check','ipn_check_fail');
        }
    }catch(Exception $e){
        throw new bException('paypal_ipn_log(): Failed', $e);
    }
}



//check with paypal if ipn request is valid
function paypal_check_ipn_request() {
    global $_CONFIG;
    try{
        // Read the notification from PayPal and create the acknowledgement response
        $req = 'cmd=_notify-validate';               // add 'cmd' to beginning of the acknowledgement you send back to PayPal

        //$raw = file_get_contents("php://input");
        foreach ($_POST as $key => $value) {         // Loop through the notification NV pairs
            $value = urlencode(stripslashes($value));  // Encode the values
            $req .= "&$key=$value";                    // Add the NV pairs to the acknowledgement
        }

        //Set up the acknowledgement request headers
        $header = "POST /cgi-bin/webscr HTTP/1.1\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";

        if(paypal_version()=='sandbox') {
            $header .= "Host: www.sandbox.paypal.com\r\n";
        } else {
            $header .= "Host: www.paypal.com\r\n";
        }

        $header .= "Content-Length: " . strlen($req) . "\r\n";
        $header .= "Connection: close\r\n\r\n";

        //Open a socket for the acknowledgement request
        if(paypal_version()=='sandbox') {
            $fp = fsockopen ('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
        } else {
            $fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);
        }
        // Post request back to PayPal for validation
        fputs ($fp, $header . $req);
        while (!feof($fp)) {                     // While not EOF
            $res = trim(fgets ($fp, 1024));

            if($res=='VERIFIED') {
                return true;
            } elseif($res=='INVALID') {
                throw new bException('paypal_check_ipn_request(): Returned FALSE', '');
            }
        }
    }catch(Exception $e){
        throw new bException('paypal_check_ipn_request(): Failed', $e);
    }
}
?>
