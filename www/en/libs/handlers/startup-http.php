<?php
/*
 * This is the startup sequence for normal web pages
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
define('ALL'        , false);
define('DELETED'    , false);
define('STATUS'     , false);
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
 * Check HEAD and OPTIONS requests.
 * If HEAD was requested, just return basic HTTP headers
 */
// :TODO: Should pages themselves not check for this and perhaps send other headers?
switch($_SERVER['REQUEST_METHOD'] ){
    case 'OPTIONS':
under_construction();
    case 'HEAD':
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
     * Detect at what location client is
     */
    detect_location();



    /*
     * Detect what language client wants. Redirect if needed
     */
    detect_language();
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

    $e = new bException('core::startup(): Language selection failed', $e);
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
    html_fix_checkbox_values();

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
 * Setup $_GET count and limit
 */
set_limit();
set_count();



/*
 * Load custom library, if available
 */
load_libs('custom');
?>
