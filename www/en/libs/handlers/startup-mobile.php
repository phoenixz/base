<?php
/*
 * This is the startup sequence for mobile specific web pages
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>, Johan Geuze
 */



/*
 * Define basic platform constants
 */
define('ADMIN'      , '');
define('SCRIPT'     , str_runtil(str_rfrom($_SERVER['PHP_SELF'], '/'), '.php'));
define('PWD'        , slash(isset_get($_SERVER['PWD'])));
define('FORCE'      , false);
define('NOCOLOR'    , false);
define('TEST'       , false);
define('VERBOSE'    , false);
define('VERYVERBOSE', false);
define('QUIET'      , false);
define('LIMIT'      , false);
define('ORDERBY'    , false);
define('STARTDIR'   , slash(getcwd()));



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
load_libs('http,html,inet,cache'.(empty($_CONFIG['memcached']) ? '' : ',memcached').(empty($_CONFIG['cdn']['enabled']) ? '' : ',cdn'));



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
 * Set cookie, start session where needed, etc.
 */
include(ROOT.'libs/handlers/startup-manage-session.php');



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
                throw new bException(tr('startup-webpage(): Specified cookie domain ":cookie_domain" is invalid for current domain ":current_domain"', array(':cookie_domain' => $_CONFIG['cookie']['domain'], ':current_domain' => $_SERVER['HTTP_HOST'])), 'cookiedomain');
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
    date_default_timezone_set($_CONFIG['timezone']['system']);

}catch(Exception $e){
    /*
     * Users timezone failed, use the configured one
     */
    notify($e);
}

define('TIMEZONE', isset_get($_SESSION['user']['timezone'], $_CONFIG['timezone']['display']));



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
    $language = substr(__DIR__, -7, 2);

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
 * If POST request, automatically untranslate translated POST entries
 */
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    html_untranslate();

    if($_CONFIG['security']['csrf']['enabled'] === 'force'){
        /*
         * Force CSRF checks on every submit!
         */
        check_csrf();
    }
}



// :TODO: What to do with this?
//$_CONFIG['cdn']['prefix'] = slash($_CONFIG['cdn']['prefix']);
//
//if($_CONFIG['cdn']['prefix'] != '/pub/'){
//    if($_CONFIG['cdn']['enabled']){
//        load_libs('cdn');
//        $core->register['header'] = html_script('var cdnprefix="'.cdn_domain($_CONFIG['cdn']['prefix']).'";', false);
//
//    }else{
//        $core->register['header'] = html_script('var cdnprefix="'.$_CONFIG['cdn']['prefix'].'";', false);
//    }
//}



/*
 * Load custom library, if available
 */
load_libs('custom');
?>
