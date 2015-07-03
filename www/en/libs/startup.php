<?php
/*
 * This is not a real library per-se, it will just start up the system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
 */

/*
 * Force a specific environment. This is only needed to debug production
 * environments
 */
//$environment = 'production_debug';



/*
 * Framework version
 */
define('FRAMEWORKCODEVERSION', '0.22.4');


/*
 * By default run in quiet mode, and set the projects ROOT path
 */
if(!isset($GLOBALS['quiet'])){
    $GLOBALS['quiet'] = true;
}


/*
 * Allow for ROOT to be predefined. This may be useful when using www/404.php with www/en, www/es, etc
 */
if(!defined('ROOT')){
    define('ROOT', realpath(dirname(__FILE__).'/../../..').'/');
}


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
define('PLATFORM', isset($_SERVER['SHELL']) ? 'shell' : 'http');


if((PLATFORM == 'shell') and (count($argv) > 1)){
    /*
     * Pre-process command line arguments
     */
    require('handlers/system_shell_arguments.php');
}


try{
    /*
     * Check what environment we're in
     */
    if((isset($environment) and ($env = $environment)) or ($env = getenv(PROJECT.'_ENVIRONMENT'))){
        define('ENVIRONMENT', $env);

        /*
         * Check for sub environment settings
         */
        if(!defined('REQUIRE_SUBENVIRONMENTS')){
            define('REQUIRE_SUBENVIRONMENTS', false);
        }

        if(!REQUIRE_SUBENVIRONMENTS){
            define('SUBENVIRONMENT'    , false);
            define('SUBENVIRONMENTNAME', '');

        }else{
            if((isset($subenvironment) and ($env = $subenvironment)) or ($env = getenv(PROJECT.'_SUBENVIRONMENT'))){
                define('SUBENVIRONMENT'    , $env);
                define('SUBENVIRONMENTNAME', strtolower($env));

            }else{
                die("\033[0;31mstartup: No required sub environment specified for project \"".PROJECT."\"\033[0m\n");
            }
        }

    }elseif(empty($env)){
        /*/
         * No environment specified in project configuration
         */
        die("\033[0;31mstartup: No required environment name specified for project \"".PROJECT."\" in ".ROOT."config/project.php\033[0m\n");

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
    unset($subenvironment);

    /*
     * Load basic required libraries
     */
    $path      = dirname(__FILE__).'/';
    $libraries = array('system', 'mb', 'strings', 'array', 'pdo');

    /*
     * Always (obligatory) load the custom subenvironment file
     */
    if(SUBENVIRONMENT){
        $libraries[] = 'custom_'.SUBENVIRONMENTNAME;
    }

    foreach($libraries as $library){
        include_once($path.$library.'.php');
    }

    unset($path);
    unset($library);
    unset($libraries);

}catch(Exception $e){
    throw new bException('startup: Failed to load system library "'.str_log($library).'"', $e);
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
    include(ROOT.'config/production.php');

    /*
     * Also load environment specific configuration, overwriting some production settings
     */
    if(SUBENVIRONMENT){
        include($file = ROOT.'config/production_'.SUBENVIRONMENTNAME.'.php');

        if(ENVIRONMENT != 'production'){
            include($file = ROOT.'config/'.ENVIRONMENT.'_'.SUBENVIRONMENTNAME.'.php');

        }else{
            /*
             * So we are on production configuration..!
             */
            ini_set('display_errors', 0);
        }

    }else{
        if(ENVIRONMENT != 'production'){
            include($file = ROOT.'config/'.ENVIRONMENT.'.php');
        }
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
    if(SUBENVIRONMENT){
        if(!file_exists($file)){
            die("\033[0;31mstartup: Missing configuration file \"".str_log($file)."\" for environment \"".str_log(ENVIRONMENT)."\" with subenvironment \"".str_log(SUBENVIRONMENT)."\"\033[0m\n");
        }

        die("\033[0;31mstartup: Failed to load configuration for environment \"".str_log(ENVIRONMENT)."\" with subenvironment \"".str_log(SUBENVIRONMENT)."\"\033[0m\n");

    }else{
        if(!file_exists($file)){
            die("\033[0;31mstartup: Missing configuration file \"".str_log($file)."\" for environment \"".str_log(ENVIRONMENT)."\"\033[0m\n");
        }

        die("\033[0;31mstartup: Failed to load $pathuration for environment \"".str_log(ENVIRONMENT)."\"\033[0m\n");
    }
}


try{
    /*
     * Load debug files if needed
     */
    try{
        if($_CONFIG['debug']){
            /*
             * We're in debug mode, so load debug configuration and library as well
             */
            include_once($file = ROOT.'config/debug.php');
            include_once($file = dirname(__FILE__).'/debug.php');
        }

    }catch(Exception $e){
        switch($path){
            case 'libs':
                $type = 'library';
                break;

            case 'config':
                $type = 'configuration';
                break;
        }

        if(!file_exists($file)){
            throw new bException('startup: The debug '.str_log($type).' file "'.str_log($file).'" does not exist', $e);
        }

        throw new bException('startup: Failed to load the debug '.str_log($type).' file "'.str_log($file).'"', $e);
    }


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

    if($_CONFIG['charset'] = 'UTF-8'){
        mb_init($_CONFIG['locale']);

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
        switch($_CONFIG['tmp']){
            case 'local':
                define('TMP', ROOT.'tmp/');
                break;

            case 'global':
                define('TMP', '/tmp/'.strtolower(PROJECT).'/'.(SUBENVIRONMENTNAME ? SUBENVIRONMENTNAME.'/' : ''));
                break;

            default:
                throw new bException('startup: Unknown $_CONFIG[tmp] "'.str_log($_CONFIG['tmp']).'" specified. Please use only "local" or "global"', 'unknown');
        }

        switch(PLATFORM){
            case 'http':
                /*
                 * Start for HTTP
                 *
                 * Set some base parameters
                 */
                define('PWD'   , realpath(dirname(__FILE__).'/..').'/');
                define('TEST'  , (isset($_GET['test'])  ? $_GET['test']  : false));
                define('FORCE' , (isset($_GET['force']) ? $_GET['force'] : false));

                if($_CONFIG['maintenance']){
                    /*
                     * We are in maintenance mode, have to show mainenance page.
                     */
                    $GLOBALS['page_force'] = true;
// :TODO: The following line should not be necessary
//                    define('SCRIPT', 'maintenance');
                    page_show(500);

                }else{
                    define('SCRIPT', str_runtil(str_rfrom($_SERVER['PHP_SELF'], '/'), '.php'));
                }

                try{
                    session_set_cookie_params($_CONFIG['cookie']['lifetime'], $_CONFIG['cookie']['path'], $_CONFIG['cookie']['domain'], $_CONFIG['cookie']['secure'], $_CONFIG['cookie']['httponly']);

                    if(!empty($_CONFIG['sessions']['shared_memory'])){
                        /*
                         * Store session data in share memory. This is very useful for security on shared servers if you do not want your session data available to other users
                         */
                        ini_set('session.save_handler', 'mm');
                    }

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
                            throw new bException('startup: session start and session regenerate both failed, check PHP session directory', $e);
                        }
                    }

                    check_extended_session();

                    /*
                     * Language might have been set by GET or POST
                     */
                    if(empty($_REQUEST['l'])){
                        $_REQUEST['l'] = substr(__DIR__, -7, 2);
                    }

                    if(!empty($_REQUEST['l'])){
                        $language = $_REQUEST['l'];

                        /*
                         * Ensure that the requested language exists
                         */
                        if(is_scalar(isset_get($language))){
                            if(!empty($_CONFIG['language']['supported'][$language])){
                                $_SESSION['language'] = $language;
                            }
                        }

                    }elseif(!empty($_SESSION['language'])){
                        /*
                         * Get language from session
                         */
                        $language = $_SESSION['language'];
                    }

                }catch(Exception $e){
                    if(!is_writable(session_save_path())){
                        throw new bException('startup: Session startup failed because the session path "'.str_log(session_save_path()).'" is not writable for platform "'.PLATFORM.'"', $e);
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
                                throw new bException('startup: Specified cookie domain "'.str_log($_CONFIG['cookie']['domain']).'" is invalid for current domain "'.str_log($_SERVER['SERVER_NAME']).'"', 'cookiedomain');
                            }

                            unset($test);
                            unset($length);
                    }
                }


                /*
                 * Add HTTP support library
                 */
                load_libs('http,html'.(empty($_CONFIG['memcached']) ? '' : ',memcached'));
                break;

            case 'shell':
                include(dirname(__FILE__).'/handlers/startup_platform_shell.php');
                break;

            default:
                throw new bException('startup: Unknown platform "'.str_log(PLATFORM).'" detected', 'unknownplatform');
        }

    }catch(Exception $e){
        throw new bException('startup: Platform specific processing failed', $e);
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
        if(empty($language)){
            if(PLATFORM == 'shell'){
                $language = isset_get($_CONFIG['language']['fallback'], 'en');

            }else{
                /*
                 * No specific language was requested, rules are as follows:
                 * Use the language in $_SESSION[language], if set
                 * If not, use language set in $_CONFIG[language][supported]
                 * If $_CONFIG[language][supported] is "auto" then use geoip
                 * location
                 */
                if(empty($_SESSION['language'])){
                    if($_CONFIG['language']['default'] == 'auto'){
                        /*
                         * Use GEO-IP language detection
                         */
                        try{
                            load_libs('locales');
                            $locales  = locales_get_for_ip($_SERVER['REMOTE_ADDR']);
                            $language =  str_until(str_until($locales, ','), '-');

                        }catch(Exception $e){
                            /*
                             * If anything goes wrong, fall back to US english
                             */
// :TODO: Notifications?
                            $language = isset_get($_CONFIG['language']['fallback'], 'en');
                        }

                    }else{
                        $language = $_CONFIG['language']['default'];
                    }

                }else{
                    $language = $_SESSION['language'];
                }
            }

// :DELETE: Next line was from old language detection system
//            define('LANGUAGE', str_rfrom(substr(dirname(__FILE__), 0, -5), '/'));
        }

        if(empty($_CONFIG['language']['supported'][$language])){
            throw new bException('startup: Specified language code "'.str_log($language).'" is not supported', 'invalidlanguage');
        }

        define('LANGUAGE', $language);
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

        $e = new bException('startup: Language selection failed', $e);
    }


    /*
     * Set library directory, and if a standard custom
     * library file exists, load it
     */
    define('LIBS', ROOT.'www/'.LANGUAGE.'/libs/');

    try{
       if(file_exists(LIBS.'/custom.php')){
           include_once(LIBS.'/custom.php');
       }

    }catch(Exception $f){
        /*
         * Exception might already be set by language error
         */
        if(isset($e)){
            $e->addMessage('startup: Additional exception while loading custom library');
            $e->addMessage($f->getMessage());

        }else{
            $e = new bException('startup: Failed to load custom library', $f);
        }

        unset($f);
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
     * http specific post processing
     */
    if(PLATFORM == 'http'){
        /*
         * New session?
         */
        if(!isset($_SESSION['client'])){
            /*
             * Detect what client we are dealing with
             */
            client_detect();
        }


        /*
         * HTTP specific stuff
         */
        // :TODO: Replace this with only one global variable.
        $GLOBALS['page_is_mobile'] = false;
        $GLOBALS['page_is_admin']  = false;
        $GLOBALS['page_is_ajax']   = false;
        $GLOBALS['page_is_404']    = false;


        if(substr($_SERVER['PHP_SELF'], -7, 7) == '404.php'){
            /*
             * This is a 404 page
             */
            $GLOBALS['page_is_404'] = true;
        }

        /*
         * Check if some session redirect was requested
         */
        if(isset($_GET['redirect'])){
            $_SESSION['redirect'] = $_GET['redirect'];
        }

        if(substr($_SERVER['PHP_SELF'], 0, 7) == '/admin/'){
            /*
             * This is an admin page
             * Disabled all caching (both server side, and HTTP) for admin pages
             */
            $GLOBALS['page_is_admin']               = true;

            $_CONFIG['cache']['method']             = false;
            $_CONFIG['cache']['http']['enabled']    = false;

            load_config('admin');
            load_libs('custom_admin');
            restore_post();

        }elseif((!empty($_SESSION['mobile']['site']) and $_CONFIG['mobile']['enabled']) or !empty($_CONFIG['mobile']['force'])){
            /*
             * Switch to mobile site
             * We're not on mobile version, but should show mobile version.
             * If a mobile version exists for this page, load it
             */
            $GLOBALS['page_is_mobile'] = true;
            load_libs('mobile');
            restore_post();
            page_show(SCRIPT, true);

        }elseif(substr($_SERVER['PHP_SELF'], 0, 7) == '/ajax/'){
            $GLOBALS['page_is_ajax'] = true;

        }elseif(!empty($GLOBALS['page_force'])){
            /*
             * We're being forced to run a different script from the one that was requested
             */
            restore_post();
            page_show(SCRIPT, true);

        }else{
            /*
             * Just a normal page
             */
            restore_post();
        }

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

}catch(Exception $e){
    throw new bException('startup: Failed', $e);
}
?>
