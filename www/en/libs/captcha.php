<?php
/*
 * Captcha Library
 *
 * Currently only supports recaptcha, but can potentially support other captcha systems as well
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */
load_config('captcha');



/*
 * Return captcha html
 */
function captcha_html(){
    global $_CONFIG;

    try{
        switch($_CONFIG['captcha']['type']){
            case false:
                /*
                 * Use no captcha
                 */
                return '';

            case 'recaptcha':
                return '<div class="g-recaptcha" data-sitekey="'.$_CONFIG['captcha']['recaptcha']['site-key'].'"></div>';

            default:
               throw new bException(tr('captcha_verify_response(): Unknown captcha type "%type%" configured', array('%type%' => $_CONFIG['captcha']['type'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('get_captcha_html(): Failed', $e);
    }
}



/*
 * Check captcha response
 */
function captcha_verify_response($captcha){
    global $_CONFIG;

    try{
        if(!$_CONFIG['captcha']['type']){
            /*
             * Use no captcha
             */
            return true;
        }

        if(empty($captcha)){
            throw new bException('verify_captcha_response(): Captcha response is empty', 'not_specified');
        }

        switch($_CONFIG['captcha']['type']){
            case 'recaptcha':
                $response = file_get_contents($_CONFIG['captcha']['recaptcha']['verify-api'].'?secret='.$_CONFIG['captcha']['recaptcha']['secret-key'].'&response='.$captcha.'&remoteip='.$_SERVER['REMOTE_ADDR']);
                $response = json_decode($response, true);

                if(!$response["success"]){
                    throw new bException('captcha_verify_response(): Recaptcha is not valid', 'captcha');
                }

                break;

            default:
               throw new bException(tr('captcha_verify_response(): Unknown captcha type "%type%" configured', array('%type%' => $_CONFIG['captcha']['type'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('captcha_verify_response(): Failed', $e);
    }
}
?>
