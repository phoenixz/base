<?php
/*
 * This is the startup sequence for api call scripts
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
define('STARTDIR'   , slash(getcwd()));

define('FORCE'      , (getenv('FORCE')       ? 'FORCE'       : null));
define('NOCOLOR'    , (getenv('NOCOLOR')     ? 'NOCOLOR'     : null));
define('TEST'       , (getenv('TEST')        ? 'TEST'        : null));
define('VERBOSE'    , (getenv('VERBOSE')     ? 'VERBOSE'     : null));
define('VERYVERBOSE', (getenv('VERYVERBOSE') ? 'VERYVERBOSE' : null));
define('QUIET'      , (getenv('QUIET')       ? 'QUIET'       : null));
define('LIMIT'      , (getenv('LIMIT')       ? 'LIMIT'       : null));
define('ORDERBY'    , (getenv('ORDERBY')     ? 'ORDERBY'     : null));
define('ALL'        , (getenv('ALL')         ? 'ALL'         : null));
define('DELETED'    , (getenv('DELETED')     ? 'DELETED'     : null));
define('STATUS'     , (getenv('STATUS')      ? 'STATUS'      : null));



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
load_libs('http,html,inet,json,api,cache'.(empty($_CONFIG['memcached']) ? '' : ',memcached').(empty($_CONFIG['cdn']['enabled']) ? '' : ',cdn'));



/*
 * All scripts will execute http_done() automatically once done
 */
register_shutdown_function('http_done');



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
