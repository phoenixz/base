<?php
/*
 * Set cookie, but only if page is not API and domain has
 * cookie configured
 */
if(empty($_CONFIG['cookie']['domain'])){
    /*
     * Ensure we have a domain configured in $_SESSION[domain]
     */
    session_reset_domain();

}else{
    /*
     * Set session and cookie parameters
     */
    try{
        if(!empty($_CONFIG['sessions']['shared_memory'])){
            /*
             * Store session data in share memory. This is very
             * useful for security on shared servers if you do not
             * want your session data available to other users
             */
            ini_set('session.save_handler', 'mm');
        }


        /*
         *
         */
        switch(true){
            case ($_CONFIG['whitelabels']['enabled'] === 'all'):
                // FALLTHROUGH
            case ($_CONFIG['whitelabels']['enabled'] === false):
                /*
                 * white label domains are disabled, so the detected domain MUST match the configured domain
                 */
                session_set_cookie_params($_CONFIG['cookie']['lifetime'], $_CONFIG['cookie']['path'], cfm($_SERVER['HTTP_HOST']), $_CONFIG['cookie']['secure'], $_CONFIG['cookie']['httponly']);
                break;

            case ($_CONFIG['whitelabels']['enabled'] === 'sub'):
                /*
                 * white label domains are disabled, but sub domains from the $_CONFIG[domain] are allowed
                 */
                session_set_cookie_params($_CONFIG['cookie']['lifetime'], $_CONFIG['cookie']['path'], $_CONFIG['domain'], $_CONFIG['cookie']['secure'], $_CONFIG['cookie']['httponly']);
                break;

            default:
                /*
                 * Check the detected domain against the configured domain.
                 * If it doesnt match then check if its a registered whitelabel domain
                 */
                if($_SERVER['SERVER_NAME'] === $_CONFIG['domain']){
                    session_set_cookie_params($_CONFIG['cookie']['lifetime'], $_CONFIG['cookie']['path'], $_SERVER['SERVER_NAME'], $_CONFIG['cookie']['secure'], $_CONFIG['cookie']['httponly']);

                }else{
                    $domain = sql_get('SELECT `domain` FROM `whitelabels` WHERE `domain` = :domain AND `status` IS NULL', 'domain', array(':domain' => $_SERVER['HTTP_HOST']));

                    if(empty($domain)){
                        /*
                         * Either we have no domain or it is not allowed. Redirect to main domain
                         */
                        redirect();
                    }

                    session_set_cookie_params($_CONFIG['cookie']['lifetime'], $_CONFIG['cookie']['path'], $domain, $_CONFIG['cookie']['secure'], $_CONFIG['cookie']['httponly']);
                }
        }



        /*
         *
         */
        if($_CONFIG['sessions']['lifetime']){
            if(ini_get('session.gc_maxlifetime') < $_CONFIG['sessions']['lifetime']){
                /*
                 * Ensure that session data is not considdered
                 * garbage within the configured session lifetime!
                 */
                ini_set('session.gc_maxlifetime', $_CONFIG['sessions']['lifetime']);
            }
        }



        /*
         * Disable PHP caching stuff
         */
// :TODO: WHY?
        session_cache_limiter('');
        session_cache_expire(0);



        /*
         *
         */
        try{
            session_start();

        }catch(Exception $e){
            /*
             * Session startup failed. Clear session and try again
             */
            try{
                session_stop();
                session_start();
                session_regenerate_id(true);

            }catch(Exception $e){
                /*
                 * Woah, something really went wrong..
                 *
                 * This may be
                 * headers already sent (the SCRIPT file has a space or BOM at the beginning maybe?)
                 * permissions of PHP session directory?
                 */
// :TODO: Add check on SCRIPT file if it contains BOM!
                throw new bException('startup-webpage(): session start and session regenerate both failed, check PHP session directory', $e);
            }
        }

        if($_CONFIG['sessions']['regenerate_id']){
            if(isset($_SESSION['created']) and (time() - $_SESSION['created'] > $_CONFIG['sessions']['regenerate_id'])){
                /*
                 * Use "created" to monitor session id age and
                 * refresh it periodically to mitigate attacks on
                 * sessions like session fixation
                 */
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }

        if($_CONFIG['sessions']['lifetime']){
            if(isset($_SESSION['last_activity']) and (time() - $_SESSION['last_activity'] > $_CONFIG['sessions']['lifetime'])){
                /*
                 * Session expired!
                 */
                session_unset();
                session_destroy();
                session_start();
                session_regenerate_id(true);
                session_reset_domain();
            }
        }



        /*
         * Ensure we have domain information
         *
         * NOTE: This SHOULD be done before the session_start because
         * there we set a cookie to a possibly invalid domain BUT
         * if we do this before session_start(), then $_SESSION['domain']
         * does not yet exist, and we would perfom this check every page
         * load instead of just once every session.
         */
        if(isset_get($_SESSION['domain']) !== $_SERVER['HTTP_HOST']){
            /*
             * Check requested domain
             */
            session_reset_domain();
        }



        /*
         * Set last activity, and vist_visit variables
         */
        $_SESSION['last_activity'] = time();

        if(isset($_SESSION['first_visit'])){
            $_SESSION['first_visit'] = false;

        }else{
            $_SESSION['first_visit'] = true;
        }



        /*
         * Auto extended sessions?
         */
        check_extended_session();



        /*
         * Set users timezone
         */
        if(empty($_SESSION['user']['timezone'])){
            $_SESSION['user']['timezone'] = $_CONFIG['timezone']['display'];

        }else{
            try{
                $check = new DateTimeZone($_SESSION['user']['timezone']);

            }catch(Exception $e){
                notify($e);
                $_SESSION['user']['timezone'] = $_CONFIG['timezone']['display'];
            }
        }

    }catch(Exception $e){
        if(!is_writable(session_save_path())){
            throw new bException('startup-webpage(): Session startup failed because the session path ":path" is not writable for platform ":platform"', array(':path' => session_save_path(), ':platform' => PLATFORM), $e);
        }

        throw new bException('Session startup failed', $e);
    }

}
?>