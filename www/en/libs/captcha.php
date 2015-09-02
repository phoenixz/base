<?php
/*
 * Captcha Library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */
load_config('captcha');

if(empty($_CONFIG['captcha']['type'])){
   throw new bException('captcha(): No type of captcha specified');
}

/*
 * Return captcha html
 */
function get_captcha_html(){
    global $_CONFIG;
    try{
        switch($_CONFIG['captcha']['type']){
            case 'recaptcha':
                return '<div class="g-recaptcha" data-sitekey="'.$_CONFIG['captcha']['recaptcha']['site-key'].'"></div>';
        }

    }catch(Exception $e){
        throw new bException('get_captcha_html(): Failed', $e);
    }
}



/*
 * Check captcha response
 */
function verify_captcha_response($captcha){
    global $_CONFIG;
    try{
        if(empty($captcha)){
            throw new bException('verify_captcha_response(): Captcha is empty', $e);
        }

        switch($_CONFIG['captcha']['type']){
            case 'recaptcha':
                $response = file_get_contents($_CONFIG['captcha']['recaptcha']['verify-api'].'?secret='.$_CONFIG['captcha']['recaptcha']['secret-key'].'&response='.$captcha.'&remoteip='.$_SERVER['REMOTE_ADDR']);

                $response = json_decode($response, true);

                if(!$response["success"]){
                    throw new bException('Recaptcha is not valid', 'invalid-captcha');
                }

                break;
        }

    }catch(Exception $e){
        throw new bException('verify_captcha_response(): Failed', $e);
    }
}
?>
