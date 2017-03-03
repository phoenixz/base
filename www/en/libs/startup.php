<?php
/*
 * This is not a real library per-se, it will just start up the system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */

/*
 * Force a specific environment. This is only needed to debug production
 * environments
 */
//$environment = 'production_debug';



/*
 * Framework version
 */
define('FRAMEWORKCODEVERSION', '0.47.1');



/*
 * By default run in quiet mode, and set the projects ROOT path
 */
if(!isset($GLOBALS['quiet'])){
    $GLOBALS['quiet'] = true;
}


/*
 * Allow for ROOT to be predefined. This may be useful when using www/404.php with www/en, www/es, etc
 */
define('ROOT', realpath(dirname(__FILE__).'/../../..').'/');


/*
 * Report ALL errors
 */
error_reporting(E_ALL);


/*
 * Include project file
 */
include_once(ROOT.'config/project.php');


/*
 * Check what platform we're in
 */
define('PLATFORM', (php_sapi_name() === 'cli') ? 'shell' : 'http');

if(PLATFORM == 'shell'){
    /*
     * Pre-process very basic command line arguments
     */
    require('handlers/startup_shell_arguments.php');
    define('PLATFORM_HTTP' , false);
    define('PLATFORM_SHELL', true);

}else{
    define('PLATFORM_HTTP' , true);
    define('PLATFORM_SHELL', false);
}


try{
    /*
     * Check what environment we're in
     */
    if((isset($environment) and ($env = $environment)) or ($env = getenv(PROJECT.'_ENVIRONMENT'))){
        define('ENVIRONMENT', $env);

    }elseif(empty($env)){
        /*/
         * No environment specified in project configuration
         */
        die("\033[0;31mstartup: No required environment name specified for project \"".PROJECT."\"\033[0m\n");

    }else{
        /*/
         * No environment set in ENV, maybe given by parameter?
         */
        die("\033[0;31mstartup: No required environment specified for project \"".PROJECT."\"\033[0m\n");
    }

    /*
     * Cleanup
     */
    unset($env);
    unset($environment);

    /*
     * Load basic required libraries
     */
    $path      = __DIR__.'/';
    $libraries = array('system', 'strings', 'array', 'sql', 'mb');

    foreach($libraries as $library){
        include_once($path.$library.'.php');
    }

    unset($path);
    unset($library);
    unset($libraries);

}catch(Exception $e){
    /*
     * NOTE! Do not use tr() here! If the system library is not yet loaded, this
     * function will not be available
     */
    throw new bException('startup(): Failed to load system library "'.$library.'"', $e);
}



/*
 * Load basic platform libraries
 */
if(PLATFORM_SHELL){
    /*
     * Add CLI support library
     */
    load_libs('cli'.(empty($_CONFIG['memcached']) ? '' : ',memcached'));
    define('ADMIN', '');

}else{
    /*
     * Add HTTP support library
     */
    load_libs('http,html,inet,cdn'.(empty($_CONFIG['memcached']) ? '' : ',memcached'));

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
     * HTTP specific stuff
     */
// :TODO: Replace this with only one global variable.
    $GLOBALS['page_is_mobile'] = false;
    $GLOBALS['page_is_admin']  = false;
    $GLOBALS['page_is_ajax']   = false;
    $GLOBALS['page_is_api']    = false;
    $GLOBALS['page_is_404']    = false;
    $GLOBALS['page_is_amp']    = $_CONFIG['amp']['enabled'] and !empty($_GET['amp']);

    if(substr($_SERVER['PHP_SELF'], -7, 7) == '404.php'){
        /*
         * This is a 404 page
         */
        $GLOBALS['page_is_404'] = true;
    }

    $_CONFIG['cdn']['prefix'] = slash($_CONFIG['cdn']['prefix']);

    if($_CONFIG['cdn']['prefix'] != '/pub/'){
        $GLOBALS['header'] = html_script('var cdnprefix="'.$_CONFIG['cdn']['prefix'].'";', false);
    }

    if(substr($_SERVER['REQUEST_URI'], 0, 7) == '/admin/'){
        /*
         * This is an admin page
         * Disabled all caching (both server side, and HTTP) for admin pages
         */
        $GLOBALS['page_is_admin']            = true;

        $_CONFIG['cache']['method']          = false;
        $_CONFIG['cache']['http']['enabled'] = false;

        define('ADMIN', '_admin');
        restore_post();

    }elseif(strstr($_SERVER['PHP_SELF'], '/ajax/')){
        $GLOBALS['page_is_ajax'] = true;
        define('ADMIN', '');

    }elseif(strstr($_SERVER['PHP_SELF'], '/api/')){
        $GLOBALS['page_is_api'] = true;
        define('ADMIN', '');

    }elseif(!empty($GLOBALS['page_force'])){
        /*
         * We're being forced to run a different script from the one that was requested
         */
        restore_post();
        page_show(SCRIPT, true);
        define('ADMIN', '');

    }else{
        /*
         * Just a normal page
         */
        restore_post();
        define('ADMIN', '');
    }
}



/*
 * Set error handlers
 */
$GLOBALS['system']['olderrorhandler'] = set_error_handler('php_error_handler');
set_exception_handler('uncaught_exception');


/*
 * Load configuration of product environment, then overwrite with current environment
 */
try{
    include($file = ROOT.'config/base/default.php');
    include($file = ROOT.'config/production'.ADMIN.'.php');

    /*
     * Also load environment specific configuration, overwriting some production settings
     */
    if(ENVIRONMENT !== 'production'){
        include($file = ROOT.'config/'.ENVIRONMENT.ADMIN.'.php');
    }

    /*
     * Optionally load the platform specific configuration file, if it exists
     */
    if(file_exists($file = ROOT.'config/'.ENVIRONMENT.'_'.PLATFORM.'.php')){
        include($file);
    }

    unset($file);

}catch(Exception $e){
    /*
     * Failed to load configuration!
     */
    if(!file_exists($file)){
        die("\033[0;31mstartup: Missing configuration file \"".str_log($file)."\" for environment \"".str_log(ENVIRONMENT)."\"\033[0m\n");
    }

    die("\033[0;31mstartup: Failed to load ".str_log($file)." for environment \"".str_log(ENVIRONMENT)."\"\033[0m\n");
}



try{
    /*
     * Set security umask
     */
    umask($_CONFIG['security']['umask']);


    /*
     * Autoload the cache and memcached library if configuration is setup for it
     */
    $libraries[] = 'cache';

    if(!empty($_CONFIG['memcached'])){
        $libraries[] = 'memcached';
    }

    if(!empty($libraries)){
        load_libs($libraries);
    }


    /*
     * Setup locale and character encoding
     *
     * Prepare for unicode usage
     */
    ini_set('default_charset', $_CONFIG['charset']);

    foreach($_CONFIG['locale'] as $key => $value){
        if($value){
            setlocale($key, $value);
        }
    }

    if($_CONFIG['charset'] = 'UTF-8'){
        mb_init(not_empty($_CONFIG['locale'][LC_CTYPE], $_CONFIG['locale'][LC_ALL]));

        if(function_exists('mb_internal_encoding')){
            mb_internal_encoding('UTF-8');
        }
    }

    unset($file);


    /*
     * Set timezone
     * See http://www.php.net/manual/en/timezones.php for more info
     */
    date_default_timezone_set($_CONFIG['timezone']);


    /*
     * Start platform specific stuff
     */
    try{
        define('TMP'   , ROOT.'tmp/');
        define('PUBTMP', ROOT.'data/content/tmp/');

        switch(PLATFORM){
            case 'http':
                /*
                 * Start for HTTP
                 *
                 * Set some base parameters
                 */
                define('PWD'    , realpath(dirname(__FILE__).'/..').'/');
                define('TEST'   , (isset($_GET['test'])  ? $_GET['test']  : false));
                define('FORCE'  , (isset($_GET['force']) ? $_GET['force'] : false));
                define('VERBOSE', debug());

                if($_CONFIG['maintenance']){
                    /*
                     * We are in maintenance mode, have to show mainenance page.
                     */
                    $GLOBALS['page_force'] = true;
// :TODO: The following line should not be necessary
                    page_show(500);

                }else{
                    define('SCRIPT', str_runtil(str_rfrom($_SERVER['PHP_SELF'], '/'), '.php'));
                }

                /*
                 * Set cookie, but only if page is not API and domain has
                 * cookie configured
                 */
                if(empty($_CONFIG['cookie']['domain']) or $GLOBALS['page_is_api']){
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

                        session_set_cookie_params($_CONFIG['cookie']['lifetime'], $_CONFIG['cookie']['path'], cfm($_SERVER['HTTP_HOST']), $_CONFIG['cookie']['secure'], $_CONFIG['cookie']['httponly']);

                        try{
                            session_start();

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
                                throw new bException('startup(): session start and session regenerate both failed, check PHP session directory', $e);
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
                                session_reset_domain();
                                session_regenerate_id();
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

                    }catch(Exception $e){
                        if(!is_writable(session_save_path())){
                            throw new bException('startup(): Session startup failed because the session path ":path" is not writable for platform ":platform"', array(':path' => session_save_path(), ':platform' => PLATFORM), $e);
                        }

                        throw new bException('Session startup failed', $e);
                    }

                    /*
                     * Check the cookie domain configuration to see if its valid. This should only have to be checked
                     * at first session page load. Since $_SESSION[language] should always be set, we can check its not set,
                     * we know we have a fresh and new session.
                     */
                    if(empty($_SESSION['language'])){
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
                                    throw new bException('startup(): Specified cookie domain "'.str_log($_CONFIG['cookie']['domain']).'" is invalid for current domain "'.str_log($_SERVER['SERVER_NAME']).'"', 'cookiedomain');
                                }

                                unset($test);
                                unset($length);
                        }
                    }
                }

                break;

            case 'shell':
                include(dirname(__FILE__).'/handlers/startup_platform_shell.php');
                break;

            default:
                throw new bException('startup(): Unknown platform "'.str_log(PLATFORM).'" detected', 'unknownplatform');
        }

    }catch(Exception $e){
        throw new bException('startup(): Platform specific processing failed', $e);
    }



    /*
     * Get required language.
     *
     * This is normally done by checking the current dirname of the startup file, this will be LANGUAGECODE/libs/startup.php
     *
     * Language may be changed by $_SESSION['language'], or (Check system argument processing above):
     *
     * $_GET['language'] value
     * $argv['language'] > next value
     *
     * Both will set $language
     */
    try{
        if(PLATFORM_SHELL){
        /*
         * This is a shell,
         */
        $language = cli_argument('language');

        if($language){
            $_SESSION['language'] = $language;

        }else{
            $language = cli_argument('L');

            if($language){
                $_SESSION['language'] = $language;

            }else{
                $_SESSION['language'] = isset_get($_CONFIG['language']['default'], 'en');
            }
        }

        return $_SESSION['language'];

        }else{
            /*
             * Language is defined by the www/LANGUAGE dir that is used.
             */
            $_GET['l'] = substr(__DIR__, -7, 2);

            if(!is_string($_GET['l']) or strlen($_GET['l']) != 2){
                unset($_GET['l']);
                page_show(404);
            }

            if(empty($_CONFIG['language']['supported'][$_GET['l']])){
                unset($_GET['l']);
                page_show(404);

            }

            $language = $_GET['l'];
        }

// :TODO: Add GEO-IP language lookup
//                    /*
//                     * Use GEO-IP language detection
//                     */
//                    try{
//                        load_libs('locales');
//                        $locales  = locales_get_for_ip($_SERVER['REMOTE_ADDR']);
//                        $language =  str_until(str_until($locales, ','), '-');
//
//                    }catch(Exception $e){
//                        /*
//                         * If anything goes wrong, fall back to US english
//                         */
//// :TODO: Notifications?
//                        $language = isset_get($_CONFIG['language']['fallback'], 'en');
//                    }

        define('LANGUAGE', $language);
        define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_'.$_SESSION['location']['country']['code']));
        unset($language);

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

        $e = new bException('startup(): Language selection failed', $e);
    }

    /*
     * Delayed exception throwing for
     */
    if(isset($e)){
        throw $e;
    }

    /*
     * Verify project data integrity
     */
    if(!defined("SEED") or !SEED or (PROJECTCODEVERSION == '0.0.0')){
        return include(dirname(__FILE__).'/handlers/startup_no_project_data.php');
    }

    /*
     * Shell and HTTP specific post processing
     */
    if(PLATFORM_HTTP){
        /*
         * New session?
         */
        if(!isset($_SESSION['client'])){
            /*
             * Detect what client we are dealing with
             */
            detect_client();
        }

        if(!isset($_SESSION['location'])){
            /*
             * Detect at what location client is
             */
            detect_location();
        }

        if(!isset($_SESSION['language'])){
            /*
             * Detect what language client wants
             */
            detect_language();
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

    }else{
        /*
         * Shell platform
         *
         * Check basic CLI arguments
         */
        cli_basic_arguments();

        if(TEST){
            log_console('Warning: Running in TEST mode', 'test', 'yellow');
        }
    }



    /*
     * Set library directory, and if a standard custom
     * library file exists, load it
     */
    define('LIBS', ROOT.'www/'.LANGUAGE.'/libs/');

    try{
        if(empty($GLOBALS['page_is_admin'])){
            if(file_exists(LIBS.'/custom.php')){
               include_once(LIBS.'/custom.php');
            }

        }else{
            if(file_exists(LIBS.'/custom_admin.php')){
               include_once(LIBS.'/custom_admin.php');
            }
        }

    }catch(Exception $e){
        throw new bException('startup(): Failed to load custom library', $e);
    }

}catch(Exception $e){
    throw new bException('startup(): Failed', $e);
}
?>
