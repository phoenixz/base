<?php
/*
 * Add CLI support library
 */
load_libs('cli'.(empty($_CONFIG['memcached']) ? '' : ',memcached'));

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
define('FORCE'   , argument('-f', false, argument('--force')));
define('NOCOLOR' , argument('-c', false, argument('--nocolor')));
define('TEST'    , argument('-t', false, argument('--test')));
define('VERBOSE' , argument('-V', false, argument('--verbose')));
define('QUIET'   , argument('-q', false, argument('--quiet')));
define('LIMIT'   , in_array('limit', $argv) ? array_next_value($argv, 'limit') : false);
define('STARTDIR', slash(getcwd()));
define('NOLOGIN' , argument('-l') or isset_get($nologin));

if(VERBOSE){
    $GLOBALS['quiet'] = false;
}

array_shift($argv);

/*
 * Check current shell environment username and try to
 * signin with the shells username (no authentication required,
 * assume that if the user is in the shell, he already
 * authenticated with the shell
 *
 * Check for environment variable USER (used by shells) or LOGNAME (used by cron)
 */
if(!empty($signin)){
    try{
        load_libs('user');
        $_SESSION['user'] = user_authenticate($signin, $password);
        log_console('startup: Signed in as user "'.user_name($_SESSION['user']).'"', '', 'white');

        unset($signin);
        unset($password);

    }catch(Exception $e){
        throw new bException('startup: Failed to signin with specified user or email "'.str_log($signin).'"', $e);
    }

}elseif((!empty($_SERVER['USER']) or !empty($_SERVER['LOGNAME'])) and !NOLOGIN){
    try{
        if((SCRIPT != 'init') and (SCRIPT != 'update')){
            $user = sql_get('SELECT `id`,
                                    `name`,
                                    `username`,
                                    `email`

                             FROM   `users`

                             WHERE  `username` = :name
                             OR     `email`    = :email',

                             array(':name'  => isset_get($_SERVER['USER'], $_SERVER['LOGNAME']),
                                   ':email' => isset_get($_SERVER['USER'], $_SERVER['LOGNAME'])));

            if($user){
                load_libs('user');
                user_signin($user);
            }
        }

    }catch(Exception $e){
        if($e->getCode() != 'doinit'){
            throw new bException('startup: Auto shell user signin has failed', $e);
        }

        /*
         * The shell auto sign in failed due to INIT requirement, but we can just continue without login
         */
        log_console(tr('startup: Shell auto user sign in failed because the database required an init. Attempting to proceed without user session'), '', 'yellow');
        $_SESSION = array();
        unset($e);
    }
}
//showdie($_SESSION);
?>
