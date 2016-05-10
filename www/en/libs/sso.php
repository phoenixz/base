<?php
/*
 * Single Sign On library
 *
 * This library contains single sign on library functions to help facebook connect, google connect, etc
 *
 * Requires the socialmedia-oauth-login library, with a "sol" symlink pointing to it (sol for ease of use)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Single Sign On
 */
function sso($provider, $redirect = true, $get_check = true){
    global $_CONFIG;

    try{
        load_libs('socialmedia_oauth_connect,array,user,json');

        $provider  = strtolower($provider);

        $providers = array('facebook'  => 'fb',
                           'google'    => 'gp',
                           'microsoft' => 'ms',
                           'paypal'    => 'pp',
                           'twitter'   => 'tw');

        $prefix    = $providers[$provider];

        /*
         * Reset SSO referrer. This is used in case SSO fails, to redirect back to the correct origin (signup or signin page, usuallyt)
         */
        if(!empty($_SERVER['HTTP_REFERER'])){
            $_SESSION['sso_referrer'] = $_SERVER['HTTP_REFERER'];

        }else{
            unset($_SESSION['sso_referrer']);
        }

        /*
         * Check if specified provider is supported
         */
        if(empty($_CONFIG['sso'][$provider])){
            throw new bException('sso(): No configuration for SSO provider "'.str_log($provider).'" available', 'config');
        }

        $oauth  = new socialmedia_oauth_connect();
        $config = $_CONFIG['sso'][$provider];

        /*
         * Validate configuration
         */
        foreach($_CONFIG['sso'][$provider] as $key => $value){
            if(($key != 'scope') and !$value){
//                throw new bException('sso(): Key "'.$key.'" of SSO provider "'.$provider.'" has not been set', 'config');
            }
        }

        /*
         * Configure oauth
         */
        $oauth->provider      = ucfirst($provider);
        $oauth->client_id     = $config['appid'];
        $oauth->client_secret = $config['secret'];
        $oauth->scope         = $config['scope'];
        $oauth->redirect_uri  = $config['redirect'];

        $oauth->Initialize();

        $code = (!empty($_REQUEST['code'])) ?  ($_REQUEST['code']) : '';

        if(empty($code)) {
            if($get_check and count($_GET)){
                /*
                 * We got SOME info from an SSO login, but not the code, probably an error
                 */
                throw new bException('sso(): Provider "'.str_log($provider).'" redirected without code, probably an error. To avoid a redirect loop, this SSO has been canceled');
            }

            /*
             * Not authenticated yet, do so now
             */
            switch($provider){
                case 'twitter':
                    load_libs('twitter');
                    $code = twitter_get_bearer_token();
                    break;

                case 'facebook':
                    load_libs('facebook');
                    facebook_signin();
                    break;

                case 'google':
//                    $oauth->client_id .= '&access_type=offline';
                    // FALLTHROUGH

                default:
                    $oauth->Authorize();
                    die();
            }
        }

        /*
         * Add provider and code
         */
        $retval['provider'] = $provider;
        $retval['code']     = $code;
        $retval['data']     = array();

        switch($provider){
            case 'paypal':
                /*
                 * Seems like paypal "strangely" has no token.. Why not? Why do they not let me control your bank account? bunch of assholes..
                 */
                $retval['data']  = sso_get_profile($oauth, $code);
                $retval['token'] = false;

                break;

            case 'facebook':
                /*
                 * Cleanup
                 */
                load_libs('facebook');

                $retval['data']   = facebook_signin();
                $retval['token']  = $retval['data']['token'];

                if(empty($retval['data']['email'])){
                    throw new bException('sso(): facebook_signin() data did not contain an email.', 'noemail');
                }

                unset($retval['data']['token']);
                break;

            case 'google':
                /*
                 * Cleanup
                 */
                $retval['data'] = sso_get_profile($oauth, $code);

                if(!empty($retval['data']['data'])){
                    $retval['data'] = $retval['data']['data'];
                }

// IMPLEMENT!!! Get correct grant
                $retval['token'] = sso_get_token($oauth, $provider);
                break;

            case 'microsoft':
                /*
                 * Cleanup
                 */
                $retval['data'] = sso_get_profile($oauth, $code);

                if(!empty($retval['data']['data'])){
                    $retval['data'] = $retval['data']['data'];
                }

                if(!empty($retval['data']['emails']['data'])){
                    $retval['data']['emails'] = $retval['data']['emails']['data'];
                }

                /*
                 * Try to get email from the microsoft mess
                 */
                if(empty($retval['data']['email'])){
                    if(!empty($retval['data']['emails']['preferred'])){
                        $retval['data']['email'] = $retval['data']['emails']['preferred'];

                    }elseif(!empty($retval['data']['emails']['account'])){
                        $retval['data']['email'] = $retval['data']['emails']['account'];

                    }elseif(!empty($retval['data']['emails']['personal'])){
                        $retval['data']['email'] = $retval['data']['emails']['personal'];

                    }elseif(!empty($retval['data']['emails']['business'])){
                        $retval['data']['email'] = $retval['data']['emails']['business'];

                    }else{
                        $retval['data']['email'] = '';
                    }
                }

                $retval['token'] = sso_get_token($oauth, $provider);
                break;

            default:
                $retval['data']  = sso_get_profile($oauth, $code);
                $retval['token'] = sso_get_token($oauth, $provider);
        }

        /*
         * Make sure we have the user in the users table with its email address, linked to the current provider.
         */
        if(!$user = sql_get('SELECT `id` FROM `users` WHERE (`email` = "'.cfm($retval['data']['email']).'") OR (`'.$prefix.'_id` = '.cfi($retval['data']['id']).')')){
            /*
             * This user does not yet exist
             */
            if($provider == 'microsoft'){
                sql_query('INSERT INTO `users`     (`email`, `name`, `'.$prefix.'_id`, `'.$prefix.'_token_authentication`, `'.$prefix.'_token_access`)

                           VALUES                  ("'.$retval['data']['email'].'", "'.(empty($retval['data']['name']) ? '' : $retval['data']['name']).'", "'.$retval['data']['id'].'", "'.$retval['token']['authentication_token'].'", "'.$retval['token']['access_token'].'")');

            }else{
                sql_query('INSERT INTO `users`     (`email`, `'.$prefix.'_id`, `'.$prefix.'_token`)

                           VALUES                  ("'.$retval['data']['email'].'", "'.$retval['data']['id'].'", "'.$retval['token'].'")');
            }

            /*
             * Get the user data
             */
            $user = sql_get('SELECT * FROM `users` WHERE `id` = '.sql_insert_id());

        }else{
            /*
             * This user already exists
             */
            if($provider == 'microsoft'){
                sql_query('UPDATE `users`

                           SET    `'.$prefix.'_id`                   = "'.$retval['data']['id'].'",
                                  `'.$prefix.'_token_authentication` = "'.$retval['token']['authentication_token'].'",
                                  `'.$prefix.'_token_access`         = "'.$retval['token']['access_token'].'"

                           WHERE  `id`                               = '.cfi($user['id']));

            }else{
                sql_query('UPDATE `users`

                           SET    `'.$prefix.'_id`                   = "'.$retval['data']['id'].'",
                                  `'.$prefix.'_token`                = "'.$retval['token'].'"

                           WHERE  `id`                               = '.cfi($user['id']));
            }

            /*
             * Get the user data
             */
            $user = sql_get('SELECT * FROM `users` WHERE `id` = '.cfi($user['id']));
        }

        user_signin($user);

    }catch(Exception $e){
        throw new bException('sso(): Failed', $e);
    }
}



/*
 * Get the SSO token
 */
function sso_get_token($oauth, $provider){
    try{
        load_libs('json');
        $retval = json_decode_custom($oauth->getAccessToken());

        if(!empty($retval->error)){
            if(is_object($retval->error)){
                throw new bException('sso_get_token(): Provider "'.str_log($provider).'" returned error "'.str_log($retval->error->code).'" with message "'.str_log($retval->error->message).'"');
            }

            throw new bException('sso_get_token(): Provider "'.str_log($provider).'" returned error "'.str_log($retval->error).'"');
        }

        $retval = array_from_object($retval);

        if(!empty($retval['data'])){
            $retval = $retval['data'];
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('sso_get_token(): Failed', $e);
    }
}



/*
 * Get and return user profile
 */
function sso_get_profile($oauth, $code){
    try{
        /*
         * We are authenticated, get data
         */
        $oauth->code = $code;
        $profile     = $oauth->getUserProfile();

        $profile     = json_decode_custom($profile);

        if(is_object($profile)){
            return array_from_object($profile);
        }

        return $profile;

    }catch(Exception $e){
        throw new bException('sso_get_profile(): Failed', $e);
    }
}



/*
 * Handle SSO failure gracefully
 */
function sso_fail($message, $redirect = null){
    try{
        load_libs('html');

        if(!$redirect){
            $redirect = 'index.php';
        }

        html_flash_set($message, 'error');

        if(!empty($_SESSION['sso_referrer'])){
            $referrer = $_SESSION['sso_referrer'];
            unset($_SESSION['sso_referrer']);

        }else{
            $referrer = $redirect;
        }

        redirect($referrer);

    }catch(Exception $e){
        page_show(500);
    }
}
?>
