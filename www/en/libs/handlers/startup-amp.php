<?php
/*
 * This is the startup sequence for amp web pages
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */



/*
 * Load basic libraries
 */
load_libs('strings,array,sql,mb');



/*
 * Define basic platform constants
 */
define('ADMIN'   , '');
define('SCRIPT'  , str_runtil(str_rfrom($_SERVER['PHP_SELF'], '/'), '.php'));
define('PWD'     , slash(isset_get($_SERVER['PWD'])));
define('FORCE'   , false);
define('NOCOLOR' , false);
define('TEST'    , false);
define('VERBOSE' , false);
define('QUIET'   , false);
define('LIMIT'   , false);
define('STARTDIR', slash(getcwd()));



/*
 * Check what environment we're in
 */
$env = getenv(PROJECT.'_ENVIRONMENT');

if(empty($env)){
    /*
     * No environment set in ENV, maybe given by parameter?
     */
    die('startup: Required environment not specified for project "'.PROJECT.'"');
}

if(strstr($env, '_')){
    die('startup: Specified environment "'.$env.'" is invalid, environment names cannot contain the underscore character');
}

define('ENVIRONMENT', $env);



/*
 * Load basic configuration for the current environment
 * Load cache libraries (done until here since these need configuration @ load time)
 */
load_config(' ');
load_libs(',http,html,inet,cdn,cache'.(empty($_CONFIG['memcached']) ? '' : ',memcached'));



/*
 * Configuration has been loaded succesfully, from here all debug functions
 * will work correctly
 */
$core->register['config_ok'] = true;



/*
 * Check OPTIONS request.
 * If options was requested, just return basic HTTP headers
 */
// :TODO: Should pages themselves not check for this and perhaps send other headers?
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    http_headers(200, 0);
    die();
}



/*
 * Set security umask
 */
umask($_CONFIG['security']['umask']);



/*
 * Setup locale and character encoding
 */
ini_set('default_charset', $_CONFIG['charset']);

foreach($_CONFIG['locale'] as $key => $value){
    if($value){
        setlocale($key, $value);
    }
}



/*
 * Prepare for unicode usage
 */
if($_CONFIG['charset'] = 'UTF-8'){
    mb_init(not_empty($_CONFIG['locale'][LC_CTYPE], $_CONFIG['locale'][LC_ALL]));

    if(function_exists('mb_internal_encoding')){
        mb_internal_encoding('UTF-8');
    }
}



/*
 * Check for configured maintenance mode
 */
if($_CONFIG['maintenance']){
    /*
     * We are in maintenance mode, have to show mainenance page.
     */
    page_show(503);
}



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
            if(ini_get('session.gc_maxlifetime') < $_CONFIG['sessions']['lifetime']){
                /*
                 * Ensure that session data is not considdered
                 * garbage within the configured session lifetime!
                 */
                ini_set('session.gc_maxlifetime', $_CONFIG['sessions']['lifetime']);
            }

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
         *
         */
        $_SESSION['last_activity'] = time();

        check_extended_session();

        /*
         * Set users timezone
         */
        if(empty($_SESSION['user']['timezone'])){
            $_SESSION['user']['timezone'] = $_CONFIG['timezone']['display'];
        }

    }catch(Exception $e){
        if(!is_writable(session_save_path())){
            throw new bException('startup-webpage(): Session startup failed because the session path ":path" is not writable for platform ":platform"', array(':path' => session_save_path(), ':platform' => PLATFORM), $e);
        }

        throw new bException('Session startup failed', $e);
    }

}



/*
 * New session? Perform basic checks
 */
if(empty($_SESSION['init'])){
    $_SESSION['init'] = time();
    load_libs('detect');



    /*
     * Detect what client we are dealing with
     */
    detect_client();


    /*
     * Detect what language client wants. Redirect if needed
     */
    detect_language();



    /*
     * Detect at what location client is
     */
    detect_location();



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
            $_CONFIG['domain'] = $_SERVER['SERVER_NAME'];
            break;

        case '.auto':
            $_CONFIG['domain'] = '.'.$_SERVER['SERVER_NAME'];
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

            if(substr($_SERVER['SERVER_NAME'], -$length, $length) != $test){
                throw new bException(tr('startup-webpage(): Specified cookie domain ":cookie_domain" is invalid for current domain ":current_domain"', array(':cookie_domain' => $_CONFIG['cookie']['domain'], ':current_domain' => $_SERVER['SERVER_NAME'])), 'cookiedomain');
            }

            unset($test);
            unset($length);
    }
}



/*
 * Set timezone
 * See http://www.php.net/manual/en/timezones.php for more info
 */
try{
    $timezone = isset_get($_SESSION['user']['timezone'], $_CONFIG['timezone']['display']);
    date_default_timezone_set($timezone);

}catch(Exception $e){
    /*
     * Users timezone failed, use the configured one
     */
    notify($e);
    $timezone = $_CONFIG['timezone']['display'];
    date_default_timezone_set($timezone);
}

define('TIMEZONE', $timezone);



/*
 * Set language data
 *
 * This is normally done by checking the current dirname of the startup file,
 * this will be LANGUAGECODE/libs/handlers/startup-webpage.php
 */
try{
    /*
     * Language is defined by the www/LANGUAGE dir that is used.
     */
    $language = substr(__DIR__, -16, 2);

    define('LANGUAGE', $language);
    define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_'.$_SESSION['location']['country']['code']));

    /*
     * Ensure $_SESSION['language'] available
     */
    if(empty($_SESSION['language'])){
        $_SESSION['language'] = LANGUAGE;
    }

}catch(Exception $e){
    /*
     * Language selection failed
     */
    if(!defined('LANGUAGE')){
        define('LANGUAGE', 'en');
    }

    $e = new bException('startup-webpage(): Language selection failed', $e);
}



/*
 * Check if some session redirect was requested
 */
if(isset($_GET['redirect'])){
    $_SESSION['redirect'] = $_GET['redirect'];
}



/*
 * Check for URL's with queries. Depending on configuration, 301 direct to URL without query
 */
http_redirect_query_url();



// :TODO: What to do with this?
//$_CONFIG['cdn']['prefix'] = slash($_CONFIG['cdn']['prefix']);
//
//if($_CONFIG['cdn']['prefix'] != '/pub/'){
//    if($_CONFIG['cdn']['enabled']){
//        load_libs('cdn');
//        $GLOBALS['header'] = html_script('var cdnprefix="'.cdn_domain($_CONFIG['cdn']['prefix']).'";', false);
//
//    }else{
//        $GLOBALS['header'] = html_script('var cdnprefix="'.$_CONFIG['cdn']['prefix'].'";', false);
//    }
//}



/*
 * Load custom library, if available
 */
load_libs('custom');
?>
