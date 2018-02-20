<?php
/*
 * Single Sign On library
 *
 * This library contains single sign on library functions to help facebook connect, google connect, etc
 *
 * Requires the socialmedia-oauth-login library, with a "sol" symlink pointing to it (sol for ease of use)
 *
 * NOTE: This library requires PHP hybridauth library!
 * NOTE: This library requires 3rd party plugins for each provider!
 *
 * thridparty facebook: https://github.com/facebook/php-graph-sdk
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



sso_init();



/*
 * Ensure all required dependancies are available. If not, run the auto installer
 */
function sso_init(){
   try{
        ensure_installed(array('name'      => 'hybridauth',
                               'project'   => 'hybridauth',
                               'callback'  => 'sso_install',
                               'checks'    => array(ROOT.'libs/external/hybridauth/Hybrid/Auth.php',
                                                    ROOT.'libs/external/hybridauth/Hybrid/Auth.php')));

    }catch(Exception $e){
        throw new bException(tr('sso_init(): Failed'), $e);
    }
}



/*
 * Download and install the required hybridauth library
 */
function sso_install($params){
   try{
        $params['methods'] = array('download' => array('commands'  => function($hash){
                                                                        /*
                                                                         * Download the hybridauth v2 library, and install it in the external libraries path
                                                                         */
                                                                        load_libs('file');
                                                                        file_delete(TMP.'hybridauth');
                                                                        file_ensure_path(TMP.'hybridauth');
                                                                        safe_exec('wget -O '.TMP.'hybridauth/hybridauth.zip https://github.com/hybridauth/hybridauth/archive/v2.zip');
                                                                        safe_exec('cd '.TMP.'hybridauth/; unzip '.TMP.'hybridauth/hybridauth.zip');

                                                                        /*
                                                                         * Install facebook adapter
                                                                         */
                                                                        safe_exec('wget -O '.TMP.'hybridauth/facebook-graph.zip https://github.com/facebook/php-graph-sdk/archive/5.5.zip');
                                                                        safe_exec('cd '.TMP.'hybridauth/; unzip '.TMP.'hybridauth/facebook-graph.zip');
                                                                        file_delete(TMP.'hybridauth/hybridauth-2/hybridauth/Hybrid/thirdparty/Facebook/');
                                                                        rename(TMP.'hybridauth/php-graph-sdk-5.5/src/Facebook/', TMP.'hybridauth/hybridauth-2/hybridauth/Hybrid/thirdparty/Facebook/');

                                                                        /*
                                                                         * Install library and clean up
                                                                         */
                                                                        file_execute_mode(ROOT.'www/en/libs/external', 0770, function(){
                                                                            file_delete(ROOT.'www/en/libs/external/hybridauth');
                                                                            rename(TMP.'hybridauth/hybridauth-2/hybridauth', ROOT.'www/en/libs/external/hybridauth');
                                                                        });

                                                                        file_delete(TMP.'hybridauth');
                                                                      }));

        return install($params);

    }catch(Exception $e){
        throw new bException(tr('sso_install(): Failed'), $e);
    }
}



/*
 * Single Sign On
 */
function sso($provider, $method, $redirect, $role = 'user'){
    global $_CONFIG;

    try{
        switch($provider){
            case 'facebook':
                // FALLTHROUGH
            case 'twitter':
                // FALLTHROUGH
            case 'google':
                break;

            case '':
                throw new bException(tr('sso(): No provider specified'), 'not-specified');

            default:
                throw new bException(tr('sso(): Unknown provider ":provider" specified', array(':provider' => $provider)), 'unknown');
        }

        switch($method){
            case 'authorized':
                include_once(ROOT.'libs/external/hybridauth/Hybrid/Auth.php');
                include_once(ROOT.'libs/external/hybridauth/Hybrid/Endpoint.php');

                if(isset($_REQUEST['hauth_start']) or isset($_REQUEST['hauth_done'])){
                    Hybrid_Endpoint::process();

                }else{
                    /*
                     * Invalid request!
                     */
                    throw new bException(tr('sso(): Neither one of required hauth_start or hauth_done has been specified'), 'invalid');
                }

                break;

            case 'signin':
                include_once(ROOT.'libs/external/hybridauth/Hybrid/Auth.php');

                $hybridauth = new Hybrid_Auth(sso_config($provider));
                $result     = $hybridauth->authenticate(str_capitalize($provider));
                $profile    = $result->getUserProfile();
                $profile    = array_from_object($profile);
//showdie($profile);

                try{
                    $birthday = date_convert($profile['birthYear'].'-'.$profile['birthMonth'].'-'.$profile['birthDay'], 'mysql');

                }catch(Exception $e){
                    /*
                     * Invalid birthday data available
                     */
                    $birthday = null;
                }

                load_libs('user');

                /*
                 * Find account
                 *
                 * If account doesn't exist yet, then create it automatically
                 *
                 * If account does exist, update the account data with the
                 * providers information
                 */
                $user = sql_get('SELECT *

                                 FROM   `users`

                                 WHERE  `status` IS NULL
                                 AND    `email`  = :email',

                                 array(':email'  => $profile['email']));

                if(!$user){
                    /*
                     * Account doesn't exist yet, create it first
                     */
                    $user = user_signup(array('name' => $profile['displayName'], 'email' => $profile['email']), true);
                    $user = user_get($user);
                }

                /*
                 * Update user account with provider profile data if local data
                 * is not available yet
                 */
                sql_query('UPDATE `users` SET `phones`   = :phones   WHERE `id` = :id AND (`phones`   = "" OR `phones`   IS NULL)', array(':id' => $user['id'], ':phones'   => $profile['phone']));
                sql_query('UPDATE `users` SET `avatar`   = :avatar   WHERE `id` = :id AND (`avatar`   = "" OR `avatar`   IS NULL)', array(':id' => $user['id'], ':avatar'   => $profile['photoURL']));
                sql_query('UPDATE `users` SET `language` = :language WHERE `id` = :id AND (`language` = "" OR `language` IS NULL)', array(':id' => $user['id'], ':language' => $profile['language']));
                sql_query('UPDATE `users` SET `birthday` = :birthday WHERE `id` = :id AND (`birthday` = "" OR `birthday` IS NULL)', array(':id' => $user['id'], ':birthday' => $birthday));
                sql_query('UPDATE `users` SET `nickname` = :nickname WHERE `id` = :id AND (`nickname` = "" OR `nickname` IS NULL)', array(':id' => $user['id'], ':nickname' => $profile['displayName']));
                sql_query('UPDATE `users` SET `name`     = :name     WHERE `id` = :id AND (`name`     = "" OR `name`     IS NULL)', array(':id' => $user['id'], ':name'     => $profile['displayName']));

                /*
                 * Store all provider profile data
                 */
                sql_query('INSERT INTO `users_sso` (`users_id`, `provider`, `identifier`, `email`, `phones`, `avatar_url`, `profile_url`, `website_url`, `display_name`, `description`, `first_name`, `last_name`, `gender`, `language`, `age`, `birthday`, `country`, `region`, `city`, `zip`, `job`, `organization`)
                           VALUES                  (:users_id , :provider , :identifier , :email , :phones , :avatar_url , :profile_url , :website_url , :display_name , :description , :first_name , :last_name , :gender , :language , :age , :birthday , :country , :region , :city , :zip , :job , :organization )

                           ON DUPLICATE KEY UPDATE `modifiedon`   = NOW(),
                                                   `users_id`     = :update_users_id,
                                                   `provider`     = :update_provider,
                                                   `identifier`   = :update_identifier,
                                                   `email`        = :update_email,
                                                   `phones`       = :update_phones,
                                                   `avatar_url`   = :update_avatar_url,
                                                   `profile_url`  = :update_profile_url,
                                                   `website_url`  = :update_website_url,
                                                   `display_name` = :update_display_name,
                                                   `description`  = :update_description,
                                                   `first_name`   = :update_first_name,
                                                   `last_name`    = :update_last_name,
                                                   `gender`       = :update_gender,
                                                   `language`     = :update_language,
                                                   `age`          = :update_age,
                                                   `birthday`     = :update_birthday,
                                                   `country`      = :update_country,
                                                   `region`       = :update_region,
                                                   `city`         = :update_city,
                                                   `zip`          = :update_zip,
                                                   `job`          = :update_job,
                                                   `organization` = :update_organization',

                           array(':users_id'            => $user['id'],
                                 ':provider'            => $provider,
                                 ':identifier'          => $profile['identifier'],
                                 ':email'               => $profile['email'],
                                 ':phones'              => $profile['phone'],
                                 ':avatar_url'          => $profile['photoURL'],
                                 ':profile_url'         => $profile['profileURL'],
                                 ':website_url'         => $profile['webSiteURL'],
                                 ':display_name'        => $profile['displayName'],
                                 ':description'         => $profile['description'],
                                 ':first_name'          => $profile['firstName'],
                                 ':last_name'           => $profile['lastName'],
                                 ':gender'              => $profile['gender'],
                                 ':language'            => $profile['language'],
                                 ':age'                 => $profile['age'],
                                 ':birthday'            => $birthday,
                                 ':country'             => $profile['country'],
                                 ':region'              => $profile['region'],
                                 ':city'                => $profile['city'],
                                 ':zip'                 => $profile['zip'],
                                 ':job'                 => $profile['job_title'],
                                 ':organization'        => $profile['organization_name'],
                                 ':update_users_id'     => $user['id'],
                                 ':update_provider'     => $provider,
                                 ':update_identifier'   => $profile['identifier'],
                                 ':update_email'        => $profile['email'],
                                 ':update_phones'       => $profile['phone'],
                                 ':update_avatar_url'   => $profile['photoURL'],
                                 ':update_profile_url'  => $profile['profileURL'],
                                 ':update_website_url'  => $profile['webSiteURL'],
                                 ':update_display_name' => $profile['displayName'],
                                 ':update_description'  => $profile['description'],
                                 ':update_first_name'   => $profile['firstName'],
                                 ':update_last_name'    => $profile['lastName'],
                                 ':update_gender'       => $profile['gender'],
                                 ':update_language'     => $profile['language'],
                                 ':update_age'          => $profile['age'],
                                 ':update_birthday'     => $birthday,
                                 ':update_country'      => $profile['country'],
                                 ':update_region'       => $profile['region'],
                                 ':update_city'         => $profile['city'],
                                 ':update_zip'          => $profile['zip'],
                                 ':update_job'          => $profile['job_title'],
                                 ':update_organization' => $profile['organization_name']));

                /*
                 * Signin!
                 */
                $user['avatar'] = $profile['photoURL'];
                user_signin($user, false, $redirect);
                break;

            case '':
                throw new bException(tr('sso(): No method specified'), 'not-specified');

            default:
                throw new bException(tr('sso(): Unknown method ":method" specified', array(':method' => $method)), 'unknown');
        }

    }catch(Exception $e){
        switch($e->getCode()){
            case 0:
                throw new bException(tr('sso(): Unspecified error'), $e);

            case 1:
                throw new bException(tr('sso(): Hybridauth configuration error'), $e);

            case 2:
                throw new bException(tr('sso(): Provider not properly configured'), $e);

            case 3:
                throw new bException(tr('sso(): Unknown or disabled provider'), $e);

            case 4:
                $e = new bException(tr('sso(): Missing provider application credentials'), $e);
                throw $e->setCode(400);

            case 5:
                $e = new bException(tr('sso(): Authentication failed The user has canceled the authentication or the provider refused the connection'), $e);
                throw $e->setCode(400);

            case 6:
                $result->logout();
                throw new bException(tr('sso(): User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again'), $e);

            case 7:
                $result->logout();
                throw new bException(tr('sso(): User not connected to the provider'), $e);

            case 8:
                $e = new bException(tr('sso(): Provider does not support this feature'), $e);
                throw $e->setCode(400);

            default:
                throw new bException(tr('sso(): Failed'), $e);
        }
    }
}



/*
 * Generate hybridauth configuration file, and return file name
 */
function sso_config($provider){
    global $_CONFIG;

    try{
        $path = ROOT.'data/cache/sso/'.ENVIRONMENT.'/';
        $file = $path.$provider.'.php';

        if(file_exists($file) and ($_CONFIG['sso']['cache_config'] and ((time() - filemtime($file)) > $_CONFIG['sso']['cache_config']))){
            chmod($path, 0700);
            chmod($file, 0660);

            load_libs('file');
            file_delete($file);
        }

        if(!file_exists($file)){
            load_libs('file');

// :DELETE: Delete this crap
            //switch($provider){
            //    case 'facebook':
            //        $key    = 'id';
            //        $secret = 'secret';
            //        break;
            //
            //    case 'twitter':
            //        $key    = 'key';
            //        $secret = 'secret';
            //        break;
            //
            //    case 'twitter':
            //        $key    = 'key';
            //        $secret = 'secret';
            //        break;
            //}

            $config = array('base_url'  => $_CONFIG['sso'][$provider]['redirect'],
                            'providers' => array(str_capitalize($provider) => array('enabled' => true,
                                                                                    'keys'    => array()),

                                                  /*
                                                   * If you want to enable logging, set 'debug_mode' to true.
                                                   * You can also set it to
                                                   * - 'error' To log only error messages. Useful in production
                                                   * - 'info' To log info and error messages (ignore debug messages)
                                                   */
                                                  'debug_mode'             => true,
                                                  /*
                                                   * Path to file writable by the web server. Required if 'debug_mode' is not false
                                                   */
                                                  'debug_file'             => ROOT.'data/log/sso-'.ENVIRONMENT.'-'.$provider));

            /*
             * Add provider specific data
             */
            switch($provider){
                case 'facebook':
                    $config['providers'][str_capitalize($provider)]['scope']                  = $_CONFIG['sso'][$provider]['scope'];
                    $config['providers'][str_capitalize($provider)]['keys']['id']             = $_CONFIG['sso'][$provider]['appid'];
                    $config['providers'][str_capitalize($provider)]['keys']['secret']         = $_CONFIG['sso'][$provider]['secret'];
                    $config['providers'][str_capitalize($provider)]['keys']['trustForwarded'] = false;

                    break;

                case 'google':
                    $config['providers'][str_capitalize($provider)]['includeEmail']   = true;
                    $config['providers'][str_capitalize($provider)]['keys']['id']     = $_CONFIG['sso'][$provider]['appid'];
                    $config['providers'][str_capitalize($provider)]['keys']['secret'] = $_CONFIG['sso'][$provider]['secret'];
                    break;

                case 'twitter':
                    $config['providers'][str_capitalize($provider)]['includeEmail']   = true;
                    $config['providers'][str_capitalize($provider)]['keys']['key']    = $_CONFIG['sso'][$provider]['appid'];
                    $config['providers'][str_capitalize($provider)]['keys']['secret'] = $_CONFIG['sso'][$provider]['secret'];
                    break;

                default:
                    throw new bException(tr('sso(): Unknown provider ":provider" specified', array(':provider' => $provider)), 'unknown');
            }

            file_ensure_path($path);
            chmod($path, 0700);

            file_put_contents($file, '<?php return '.var_export($config, true).'; ?>');
            chmod($file, 0440);
            chmod($path, 0550);
        }

        return $file;

    }catch(Exception $e){
        throw new bException(tr('sso_config(): Failed'), $e);
    }
}



/*
 * Handle SSO failure gracefully
 */
function sso_fail($message, $redirect = null){
    try{
        load_libs('html');
        html_flash_set($message, 'error');

        if(empty($redirect)){
            page_show(500);
        }

    }catch(Exception $e){
        page_show(500);
    }
}
?>
