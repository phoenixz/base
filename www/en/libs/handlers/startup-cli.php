<?php
/*
 * This is the startup sequence for CLI programs
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * All scripts will execute cli_done() automatically once done
 */
load_libs('cli');
register_shutdown_function('cli_done');



/*
 * Define basic platform constants
 */
try{
    define('ADMIN'      , '');
    define('SCRIPT'     ,  str_runtil(str_rfrom($_SERVER['PHP_SELF'], '/'), '.php'));
    define('PWD'        , slash(isset_get($_SERVER['PWD'])));
    define('VERBOSE'    , (cli_argument('-V,--verbose,-V2,--very-verbose') ? 'VERBOSE'     : ''));
    define('VERYVERBOSE', ((VERBOSE or cli_argument('-V2,--very-verbose')) ? 'VERYVERBOSE' : ''));
    define('QUIET'      , cli_argument('-Q,--quiet'));
    define('FORCE'      , cli_argument('-F,--force'));
    define('NOCOLOR'    , cli_argument('-C,--no-color'));
    define('TEST'       , cli_argument('-T,--test'));
    define('LIMIT'      , cli_argument('--limit'     , true));
    define('ALL'        , cli_argument('-A,--all'    , true));
    define('DELETED'    , cli_argument('-d,--deleted', true));
    define('STATUS'     , cli_argument('-S,--status' , true));
    define('STARTDIR'   , slash(getcwd()));

}catch(Exception $e){
    $e->setCode('parameters');
    throw new bException(tr('core::startup(): Failed to parse one or more system parameters'), $e);
}

log_file(tr('Running script ":script"', array(':script' => $_SERVER['PHP_SELF'])), 'startup', 'cyan');



/*
 * Process basic shell arguments
 */
try{
    /*
     * Correct $_SERVER['PHP_SELF'], sometimes seems empty
     */
    if(empty($_SERVER['PHP_SELF'])){
        if(!isset($_SERVER['_'])){
            throw new Exception('No $_SERVER[PHP_SELF] or $_SERVER[_] found', 'notfound');
        }

         $_SERVER['PHP_SELF'] =  $_SERVER['_'];
    }

    foreach($GLOBALS['argv'] as $argid => $arg){
        /*
         * (Usually first) argument may contain the startup of this script, which we may ignore
         */
        if($arg == $_SERVER['PHP_SELF']){
            continue;
        }

        switch($arg){
            case '-D':
                // FALLTHROUGH
            case '--debug':
                /*
                 * Run script in debug mode
                 */
                debug(true);
                break;

            case '--version':
                /*
                 * Show version information
                 */
                log_console(tr('BASE framework code version ":fv", project code version ":pv"', array(':fv' => FRAMEWORKCODEVERSION, ':pv' => PROJECTCODEVERSION)));
                $die = 0;
                break;

            case '-U':
                // FALLTHROUGH
            case '--usage':
                // FALLTHROUGH
            case 'usage':
                cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                $die = 0;
                break;

            case '-H':
                // FALLTHROUGH
            case '--help':
                // FALLTHROUGH
            case 'help':
                if(isset_get($GLOBALS['argv'][$argid + 1]) == 'system'){
                    load_libs('help');
                    help('system');

                }else{
                    if(empty($GLOBALS['help'])){
                        throw new bException(tr('core::startup(): Sorry, this script has no help text defined'), 'warning');
                    }

                    $GLOBALS['help'] = array_force($GLOBALS['help'], "\n");

                    if(count($GLOBALS['help']) == 1){
                        log_console(array_shift($GLOBALS['help']), 'white');

                    }else{
                        foreach(array_force($GLOBALS['help'], "\n") as $line){
                            log_console($line, 'white');
                        }

                        log_console();
                    }
                }

                $die = 0;
                break;

            case '-L':
                // FALLTHROUGH
            case '--language':
                /*
                 * Set language to be used
                 */
                if(isset($language)){
                    throw new bException(tr('core::startup(): Language has been specified twice'), 'exists');
                }

                if(!isset($GLOBALS['argv'][$argid + 1])){
                    throw new bException(tr('core::startup(): The "language" argument requires a two letter language core right after it'), 'invalid');
                }

                $language = $GLOBALS['argv'][$argid + 1];

                unset($GLOBALS['argv'][$argid]);
                unset($GLOBALS['argv'][$argid + 1]);
                break;

            case '-E':
                // FALLTHROUGH
            case '--env':
                /*
                 * Set environment and reset next
                 */
                if(isset($environment)){
                    throw new bException(tr('core::startup(): Environment has been specified twice'), 'exists');
                }

                if(!isset($GLOBALS['argv'][$argid + 1])){
                    throw new bException(tr('core::startup(): The "environment" argument requires an existing environment name right after it'), 'invalid');
                }

                $environment = $GLOBALS['argv'][$argid + 1];

                unset($GLOBALS['argv'][$argid]);
                unset($GLOBALS['argv'][$argid + 1]);
                break;

            case '-O':
                // TALLTHROUGH
            case '--orderby':
                define('ORDERBY', ' ORDER BY `'.str_until($GLOBALS['argv'][$argid + 1], ' ').'` '.str_from($GLOBALS['argv'][$argid + 1], ' ').' ');

                $valid = preg_match('/^ ORDER BY `[a-z0-9_]+`(?:\s+(?:DESC|ASC))? $/', ORDERBY);

                if(!$valid){
                    /*
                     * The specified column ordering is NOT valid
                     */
                    throw new bException(tr('core::startup(): The specified orderby argument ":argument" is invalid', array(':argument' => ORDERBY)), 'invalid');
                }

                unset($GLOBALS['argv'][$argid]);
                unset($GLOBALS['argv'][$argid + 1]);
                break;

            case '--timezone':
                /*
                 * Set timezone
                 */
                if(isset($timezone)){
                    throw new bException(tr('core::startup(): Timezone has been specified twice'), 'exists');
                }

                if(!isset($GLOBALS['argv'][$argid + 1])){
                    throw new bException(tr('core::startup(): The "timezone" argument requires a valid and existing timezone name right after it'), 'invalid');

                }

                $timezone = $GLOBALS['argv'][$argid + 1];

                unset($GLOBALS['argv'][$argid]);
                unset($GLOBALS['argv'][$argid + 1]);
                break;

            default:
                /*
                 * This is not a system parameter
                 */
                break;
        }
    }

    unset($arg);
    unset($argid);

    if(!defined('ORDERBY')){
        define('ORDERBY', '');
    }

}catch(Exception $e){
    echo "startup-cli: Command line parser failed with \"".$e->getMessage()."\"\n";
    $core->register['exit_code'] = 1;
    die(1);
}

if(isset($die)){
    $core->register['ready']     = true;
    $core->register['exit_code'] = $die;
    die($die);
}

// :TODO: Check what this does, either delete it or comment it!
array_shift($GLOBALS['argv']);



/*
 * Check what environment we're in
 */
if(empty($environment)){
    $env = getenv(PROJECT.'_ENVIRONMENT');

    if(empty($env)){
        echo "\033[0;31mstartup: No required environment specified for project \"".PROJECT."\"\033[0m\n";
        $core->register['exit_code'] = 2;
        die(2);
    }

}else{
    $env = $environment;
}

if(strstr($env, '_')){
    echo "\033[0;31mstartup: Specified environment \"$env\" is invalid, environment names cannot contain the underscore character\033[0m\n";
    $core->register['exit_code'] = 4;
    die(4);
}

define('ENVIRONMENT', $env);

if(!file_exists(ROOT.'config/'.$env.'.php')){
    echo "\033[0;31mstartup: Configuration file \"ROOT/config/".$env.".php\" for specified environment\"".$env."\" not found\033[0m\n";
    $core->register['exit_code'] = 5;
    die(5);
}



/*
 * Load basic configuration for the current environment
 * Load cache libraries (done until here since these need configuration @ load time)
 */
load_config(' ');
load_libs('cache'.(empty($_CONFIG['memcached']) ? '' : ',memcached').(empty($_CONFIG['cdn']['enabled']) ? '' : ',cdn'));



/*
 * Get terminal data
 */
$core->register['cli'] = array('term' => cli_get_term());

if($core->register['cli']['term']){
    $core->register['cli']['columns'] = cli_get_columns();
    $core->register['cli']['lines']   = cli_get_lines();

    if(!$core->register['cli']['columns']){
        $core->register['cli']['size'] = 'unknown';

    }elseif($core->register['cli']['columns'] <= 80){
        $core->register['cli']['size'] = 'small';

    }elseif($core->register['cli']['columns'] <= 160){
        $core->register['cli']['size'] = 'medium';

    }else{
        $core->register['cli']['size'] = 'large';
    }
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
if($_CONFIG['charset'] == 'UTF-8'){
    mb_init(not_empty($_CONFIG['locale'][LC_CTYPE], $_CONFIG['locale'][LC_ALL]));

    if(function_exists('mb_internal_encoding')){
        mb_internal_encoding('UTF-8');
    }
}



/*
 * Set timezone information
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

define('TIMEZONE', $_CONFIG['timezone']['display']);
$_SESSION['user']['timezone'] = $_CONFIG['timezone']['display'];



/*
 * Get required language.
 */
$language = not_empty(cli_argument('--language'), cli_argument('L'), $_CONFIG['language']['default']);

if($_CONFIG['language']['supported'] and !isset($_CONFIG['language']['supported'][$language])){
    throw new bException(tr('core::startup(): Unknown language ":language" specified', array(':language' => $language)), 'unknown');
}

define('LANGUAGE', $language);
define('LOCALE'  , $language.(empty($_SESSION['location']['country']['code']) ? '' : '_'.$_SESSION['location']['country']['code']));

$_SESSION['language'] = $language;



/*
 * Give some startup messages, if needed
 */
if(VERBOSE){
    if(QUIET){
        throw new bException(tr('core::startup(): Both QUIET and VERBOSE have been specified but these options are mutually exclusive. Please specify either one or the other'), 'warning/invalid');
    }

    if(VERYVERBOSE){
        log_console(tr('Running in VERYVERBOSE mode, started @ ":datetime"', array(':datetime' => date_convert(STARTTIME, 'human_datetime'))), 'white');

    }else{
        log_console(tr('Running in VERBOSE mode, started @ ":datetime"', array(':datetime' => date_convert(STARTTIME, 'human_datetime'))), 'white');
    }

    log_console(tr('Detected ":size" terminal with ":columns" columns and ":lines" lines', array(':size' => $core->register['cli']['size'], ':columns' => $core->register['cli']['columns'], ':lines' => $core->register['cli']['lines'])));
}

if(FORCE){
    if(TEST){
        throw new bException(tr('core::startup(): Both FORCE and TEST modes where specified, these modes are mutually exclusive'), 'invalid');
    }

    log_console(tr('Running in FORCE mode'), 'yellow');

}elseif(TEST){
    log_console(tr('Running in TEST mode'), 'yellow');
}

if(ALL){
    if(DELETED){
        throw new bException(tr('core::startup(): Both ALL and DELETED modes where specified, these modes are mutually exclusive'), 'invalid');
    }

    log_console(tr('Showing ALL entries', 'VERBOSE/cyan'));

}elseif(DELETED){
    log_console(tr('Showing DELETED entried', 'VERBOSE/cyan'));
}

/*
 * Load custom library, if available
 */
load_libs('custom');
?>
