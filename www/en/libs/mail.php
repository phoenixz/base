<?php
/*
 * Mail library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Send a templated email
 */
function mail_send_templated_email($params, $subject, $body, $language = false, $template = 'email/template'){
    global $_CONFIG;

    try{
        array_params($params, 'to_email');

        if(empty($params['to_email'])){
            throw new bException('mail_send_templated_email(): No to_email specified', 'notpsecified');
        }

        if(!$language) {
            $language = LANGUAGE;
        }

        /*
         * On development servers do not send out mails to clients
         */
        if(!$_CONFIG['production']){
            //if(!$_CONFIG['notifications']['force']){
            //    return false;
            //}

// :DELETE: The following lines were for when notifications were still configured by means of config files.
            //foreach($_CONFIG['notifications']['users'] as $user){
            //    if($params['to_email'] == $user['email']) {
            //        $dev = true;
            //        break;
            //    }
            //}
            //
            //if(empty($dev)){
            //    if(!$_CONFIG['mail']['developer']){
            //        throw new bException('No developer email specified on environment "'.ENVIRONMENT.'"');
            //    }
            //
            //    $params['to_email'] = $_CONFIG['mail']['developer'];
            //}
        }

        $defaults = array('reply_to_name'  => ucfirst($_SESSION['domain']),
                          'reply_to_email' => 'noreply@'.$_SESSION['domain'],
                          'from_name'      => ucfirst($_SESSION['domain']),
                          'from_email'     => 'noreply@'.$_SESSION['domain']);

        $params   = array_merge($defaults, $params);

        $headers  = array('Reply-To'       => $params['reply_to_name'].' <'.$params['reply_to_email'].'>',
                          'Return-Path'    => $params['reply_to_email'],
                          'MIME-Version'   => '1.0',
                          'Content-type'   => 'text/html; charset=UTF-8',
                          'From'           => ucfirst($params['from_name']).' <'.$params['from_email'].'>',
                          'To'             => $params['to_name'].' <'.$params['to_email'].'>');

        $from     = array('###MAILERCODE###',
                          '###TRACE###',
                          '###TONAME###',
                          '###BODY###',
                          '###UNSUBSCRIBE###',
                          '###DOMAIN###',
                          '###SITENAME###',
                          '###ENVIRONMENT###',
                          '###EMAIL###');

        $to       = array(isset_get($params['mailer_code']),
                          mail_trace($params['to_email']),
                          $params['to_name'],
                          $body,
                          '<a href="http://'.$_SESSION['domain'].'/unsubscribe.php?email='.$params['to_email'].'">'.tr('Unsubscribe').'</a>',
                          $_SESSION['domain'],
                          $_CONFIG['name'],
                          ENVIRONMENT,
                          $params['to_email']);

        $body     = load_content($template, $from, $to, $language);

        if(!mail($params['to_email'], $subject, $body, mail_headers($headers))) {
            throw new bException('mail_send_templated_email(): The PHP mail() command failed', 'mailfail');
        }

        return true;

    }catch(Exception $e){
        throw new bException('mail_send_templated_email(): Failed', $e);
    }
}



/*
 * Generate mail headers
 */
function mail_headers($headers = array()) {
    global $_CONFIG;

    try{

        $defaults = array('MIME-Version' => '1.0',
                          'Content-type' => 'text/html; charset=UTF-8',
                          'From'         => str_capitalize($_SESSION['domain']).' <noreply@'.$_SESSION['domain'].'>');

        $headers  = array_merge($defaults, $headers);
        $string   = '';

        foreach ($headers as $header => $value) {
            $string .= $header.': '.$value.PHP_EOL;
        }

        return $string;

    }catch(Exception $e){
        throw new bException('mail_headers(): Failed', $e);
    }
}



/*
 * Generate some id so we can always trace an email back to an account
 */
function mail_trace($email) {
    try{
        //make save for transport
        return '#IDS#'.base64_encode(encrypt($email, 'sometimesitworks')).'#IDE#';

    }catch(Exception $e){
        throw new bException('mail_trace(): Failed', $e);
    }
}



/*
 * Send a feedback message back to sven
 */
function mail_feedback($subject, $message){
    global $_CONFIG;

    try{
        foreach($_CONFIG['feedback']['emails'] as $name => $email){
            if(!mail($email, $subject, $message)) {
                throw new bException('mail_feedback(): The PHP mail() command failed (is package "sendmail" installed?)', 'mailfail');
            }
        }

        return true;

    }catch(Exception $e){
        throw new bException('mail_feedback(): Failed', $e);
    }
}
?>
