<?php
/*
 * Get script name and force option
 */
if(strpos($_SERVER['PHP_SELF'], '/') !== false) {
    define('SCRIPT', str_rfrom($_SERVER['PHP_SELF'], '/'));

}else{
    define('SCRIPT', $_SERVER['PHP_SELF']);
}

// :TODO: Should these command line arguments also be removed from the $argv array, since these are also system arguments???
define('PWD'     , slash(isset_get($_SERVER['PWD'])));
define('FORCE'   , cli_argument('-F', false, cli_argument('--force')));
define('NOCOLOR' , cli_argument('-C', false, cli_argument('--nocolor')));
define('TEST'    , cli_argument('-T', false, cli_argument('--test')));
define('VERBOSE' , empty($GLOBALS['quiet']) or cli_argument('-V', false, cli_argument('--verbose')));
define('LIMIT'   , cli_argument('--limit', true));
define('STARTDIR', slash(getcwd()));
define('TIMEZONE', isset_get($timezone, $_CONFIG['timezone']['display']));

date_default_timezone_set(TIMEZONE);

register_shutdown_function('cli_done');

if(cli_argument('-v') or cli_argument('--version')){
    log_console(tr('BASE framework code version ":fv", project code version ":pv"', array(':fv' => FRAMEWORKCODEVERSION, ':pv' => PROJECTCODEVERSION)));
    die(0);
}

if(cli_argument('-D') or cli_argument('--debug')){
    log_console('WARNING: RUNNING IN DEBUG MODE!');
    debug(true);
}

if(VERBOSE){
    log_console(tr('Running in VERBOSE mode, started @ ":datetime"', array(':datetime' => date_convert(null, 'human_datetime'))), 'white');
}

array_shift($argv);

$_SESSION['user']['timezone'] = $_CONFIG['timezone']['display'];

///*
// * Check current shell environment username and try to
// * signin with the shells username (no authentication required,
// * assume that if the user is in the shell, he already
// * authenticated with the shell
// *
// * Check for environment variable USER (used by shells) or LOGNAME (used by cron)
// */
//if(!empty($signin)){
//    try{
//        load_libs('user');
//        $_SESSION['user'] = user_authenticate($user, $password);
//        log_console('startup: Signed in as user "'.user_name($_SESSION['user']).'"', 'white');
//
//        unset($user);
//        unset($password);
//
//    }catch(Exception $e){
//        throw new bException('startup: Failed to signin with specified user or email "'.str_log($signin).'"', $e);
//    }
//
//}elseif((!empty($_SERVER['USER']) or !empty($_SERVER['LOGNAME'])) and !NOLOGIN){
//    try{
//        if((SCRIPT != 'init') and (SCRIPT != 'update')){
//            $user = sql_get('SELECT `id`,
//                                    `name`,
//                                    `username`,
//                                    `email`
//
//                             FROM   `users`
//
//                             WHERE  `username` = :name
//                             OR     `email`    = :email',
//
//                             array(':name'  => isset_get($_SERVER['USER'], isset_get($_SERVER['LOGNAME'])),
//                                   ':email' => isset_get($_SERVER['USER'], isset_get($_SERVER['LOGNAME']))));
//
//            if($user){
//                load_libs('user');
//                user_signin($user);
//            }
//        }
//
//    }catch(Exception $e){
//        if($e->getCode() != 'doinit'){
//            throw new bException('startup: Auto shell user signin has failed', $e);
//        }
//
//        /*
//         * The shell auto sign in failed due to INIT requirement, but we can just continue without login
//         */
//        log_console(tr('startup: Shell auto user sign in failed because the database required an init. Attempting to proceed without user session'), 'yellow');
//        $_SESSION = array();
//        unset($e);
//    }
//}
//showdie($_SESSION);
?>
