<?php
/*
 * Force session cookie configuration
 */
ini_set('session.gc_maxlifetime' , $_CONFIG['sessions']['timeout']);
ini_set('session.cookie_lifetime', $_CONFIG['sessions']['lifetime']);
ini_set('session.use_strict_mode', $_CONFIG['sessions']['strict']);
ini_set('session.name'           , 'base');
ini_set('session.cookie_httponly', $_CONFIG['sessions']['http_only']);
ini_set('session.cookie_secure'  , $_CONFIG['sessions']['secure_only']);
ini_set('session.cookie_samesite', $_CONFIG['sessions']['same_site']);
ini_set('session.use_strict_mode', $_CONFIG['sessions']['strict']);

if($_CONFIG['sessions']['check_referrer']){
    ini_set('session.referer_check', $_CONFIG['domain']);
}

if(debug() or !$_CONFIG['cache']['http']['enabled']){
     ini_set('session.cache_limiter', 'nocache');

}else{
    if($_CONFIG['cache']['http']['enabled'] === 'auto'){
        ini_set('session.cache_limiter', $_CONFIG['cache']['http']['php_cache_limiter']);
        ini_set('session.cache_expire' , $_CONFIG['cache']['http']['php_cache_expire']);
    }
}



/*
 * Correctly detect the remote IP
 */
if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}



/*
 * New session? Detect client type, language, and mobile device
 */
if(empty($_COOKIE['base'])){
    load_libs('detect');
    detect();
}



/*
 * Set session and cookie parameters
 */
try{
    /*
     * Do not send cookies to crawlers!
     */
    if(isset_get($core->register['session']['client']['type']) === 'crawler'){
        log_file(tr('Crawler ":crawler" on URL ":url"', array(':crawler' => $core->register['session']['client'], ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])));
    }else{
        /*
         * Setup session handlers
         */
        switch($_CONFIG['sessions']['handler']){
            case false:
                file_ensure_path(ROOT.'data/cookies/');
                ini_set('session.save_path', ROOT.'data/cookies/');
                break;

            case 'sql':
                /*
                 * Store session data in MySQL
                 */
                load_libs('sessions-sql');
                session_set_save_handler('sessions_sql_open', 'sessions_sql_close', 'sessions_sql_read', 'sessions_sql_write', 'sessions_sql_destroy', 'sessions_sql_gc', 'sessions_sql_create_sid');
                register_shutdown_function('session_write_close');

            case 'mc':
                /*
                 * Store session data in memcached
                 */
                load_libs('sessions-mc');
                session_set_save_handler('sessions_mc_open', 'sessions_mc_close', 'sessions_mc_read', 'sessions_mc_write', 'sessions_mc_destroy', 'sessions_mc_gc', 'sessions_mc_create_sid');
                register_shutdown_function('session_write_close');

            case 'mm':
                /*
                 * Store session data in shared memory
                 */
                load_libs('sessions-mm');
                session_set_save_handler('sessions_mm_open', 'sessions_mm_close', 'sessions_mm_read', 'sessions_mm_write', 'sessions_mm_destroy', 'sessions_mm_gc', 'sessions_mm_create_sid');
                register_shutdown_function('session_write_close');
        }



        /*
         * Detect if we're on an allowed domain
         */
        $domain = session_detect_domain();



        /*
         * Check the cookie domain configuration to see if its valid.
         */
        switch($_CONFIG['cookie']['domain']){
            case '':
                /*
                 * This domain has no cookies
                 */
                break;

            case 'auto':
                $_CONFIG['domain'] = $_SERVER['HTTP_HOST'];
                break;

            case '.auto':
                $_CONFIG['domain'] = '.'.$_SERVER['HTTP_HOST'];
                break;

            default:
                /*
                 * Test cookie domain limitation
                 *
                 * If the configured cookie domain is different from the current domain then all cookie will inexplicably fail without warning,
                 * so this must be detected to avoid lots of hair pulling and throwing arturo off the balcony incidents :)
                 */
                if(substr($_CONFIG['cookie']['domain'], 0, 1) == '.'){
                    $test = substr($_CONFIG['cookie']['domain'], 1);

                }else{
                    $test = $_CONFIG['cookie']['domain'];
                }

                $length = strlen($test);

                if(substr($_SERVER['HTTP_HOST'], -$length, $length) != $test){
                    notify(new bException(tr('core::startup(): Specified cookie domain ":cookie_domain" is invalid for current domain ":current_domain". Please fix $_CONFIG[cookie][domain]! Redirecting to ":domain"', array(':domain' => str_starts_not($_CONFIG['cookie']['domain'], '.'), ':cookie_domain' => $_CONFIG['cookie']['domain'], ':current_domain' => $_SERVER['HTTP_HOST'])), 'cookiedomain'));
                    redirect('http://'.str_starts_not($_CONFIG['cookie']['domain'], '.'));
                }

                unset($test);
                unset($length);
        }



        /*
         * Set cookie, but only if page is not API and domain has
         * cookie configured
         */
        if($_CONFIG['sessions']['euro_cookies'] and empty($_COOKIE['base'])){
            load_libs('geo,geoip');

            if(geoip_is_european()){
                /*
                 * All first visits to european countries require cookie permissions given!
                 */
                $_SESSION['euro_cookie'] = true;
                return;
            }
        }

        if(!empty($_CONFIG['cookie']['domain']) and !$core->callType('api')){
            /*
             *
             */
            try{
                if(isset($_COOKIE['base'])){
                    if(!is_string($_COOKIE['base']) or !preg_match('/[a-z0-9]{1,128}/i', $_COOKIE['base'])){
                        log_file(tr('Received invalid cookie ":cookie", dropping', array(':cookie' => $_COOKIE['base'])), 'warning');
                        unset($_COOKIE['base']);
                        $_POST = array();

                        /*
                         * Start a new session without a cookie
                         */
                        session_start();

                    }elseif(!file_exists(ROOT.'data/cookies/sess_'.$_COOKIE['base'])){
                        /*
                         * Start a session with a non-existing cookie. Rename our
                         * session after the cookie, as deleting the cookie from the
                         * browser turned out to be problematic to say the least
                         */
                        log_file(tr('Received non existing cookie ":cookie", recreating', array(':cookie' => $_COOKIE['base'])), 'warning');

                        session_start();
                        header_remove('Set-Cookie');
                        session_id($_COOKIE['base']);
                        html_flash_set(tr('Your browser cookie was expired, or does not exist. please try again'), 'warning');
                        $_POST = array();

                    }else{
                        /*
                         * Start a normal session with cookie
                         */
                        session_start();
                    }

                }else{
                    /*
                     * No cookie received, start a fresh session
                     */
                    session_start();
                }

            }catch(Exception $e){
                /*
                 * Session startup failed. Clear session and try again
                 */
                try{
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

            if(empty($_SESSION['init'])){
                load_libs('detect');
                detect();
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
                }
            }



        //        /*
        //         * Ensure we have domain information
        //         *
        //         * NOTE: This SHOULD be done before the session_start because
        //         * there we set a cookie to a possibly invalid domain BUT
        //         * if we do this before session_start(), then $_SESSION['domain']
        //         * does not yet exist, and we would perfom this check every page
        //         * load instead of just once every session.
        //         */
        //// :TODO: in this section, session_detect_domain() could be called like 5 times? Fix this!
        //        if(isset_get($_SESSION['domain']) !== $_SERVER['HTTP_HOST']){
        //            /*
        //             * Check requested domain
        //             */
        //            session_detect_domain();
        //        }



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

            $_SESSION['domain'] = $domain;
        }

        if(empty($_SESSION['init'])){
            /*
             * Initialize the session
             */
            $_SESSION['init']     = time();
            $_SESSION['first']    = true;
            $_SESSION['client']   = $core->register['session']['client'];
            $_SESSION['location'] = $core->register['session']['location'];
            $_SESSION['language'] = $core->register['session']['language'];

        }else{
            unset($_SESSION['first']);
        }
    }

}catch(Exception $e){
    if(!is_writable(session_save_path())){
        throw new bException('startup-manage-session: Session startup failed because the session path ":path" is not writable for platform ":platform"', array(':path' => session_save_path(), ':platform' => PLATFORM), $e);
    }

    throw new bException('startup-manage-session: Session startup failed', $e);
}
?>
