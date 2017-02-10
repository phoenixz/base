<?php
/*
 * Captcha Library
 *
 * Currently only supports recaptcha, but can potentially support other captcha systems as well
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */
load_config('captcha');



/*
 * Return captcha html
 */
function captcha_html($class = null){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['captcha']['public'])){
            throw new bException(tr('captcha_html(): No captcha public apikey specified'), 'not-specified');
        }

        switch($_CONFIG['captcha']['type']){
            case false:
                /*
                 * Use no captcha
                 */
                return '';

            case 'recaptcha':
                html_load_js('https://www.google.com/recaptcha/api.js');
                return '<div class="g-recaptcha'.($class ? ' '.$class : '').'" data-sitekey="'.$_CONFIG['captcha']['public'].'"></div>';

            default:
               throw new bException(tr('captcha_html(): Unknown captcha type ":type" configured', array(':type' => $_CONFIG['captcha']['type'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('captcha_html(): Failed', $e);
    }
}



/*
 * Check captcha response
 */
function captcha_verify_response($captcha){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['captcha']['private'])){
            throw new bException(tr('captcha_verify_response(): No captcha public apikey specified'), 'not-specified');
        }

        if(!$_CONFIG['captcha']['type']){
            /*
             * Use no captcha
             */
            return false;
        }

        if(empty($captcha)){
            throw new bException('verify_captcha_response(): Captcha response is empty', 'not_specified');
        }

        switch($_CONFIG['captcha']['type']){
            case 'recaptcha':
                $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$_CONFIG['captcha']['private'].'&response='.$captcha.'&remoteip='.$_SERVER['REMOTE_ADDR']);
                $response = json_decode($response, true);

                if(!$response['success']){
                    throw new bException('captcha_verify_response(): Recaptcha is not valid', 'captcha');
                }

                break;

            default:
               throw new bException(tr('captcha_verify_response(): Unknown captcha type ":type" configured', array(':type' => $_CONFIG['captcha']['type'])), 'unknown');
        }

        return true;

    }catch(Exception $e){
        throw new bException('captcha_verify_response(): Failed', $e);
    }
}
?>
