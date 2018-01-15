<?php
global $_CONFIG, $core;
static $executed = false;

try{
    if($executed){
        /*
         * We seem to be stuck in an uncaught exception loop, cut it out now!
         */
        die('exception loop');
    }

    $executed = true;

    if(!empty($core) and !empty($core->register['config_ok'])){
        log_file($e, 'exceptions');
    }

    if(!defined('PLATFORM')){
        /*
         * Wow, system crashed before platform detection. See $core->__constructor()
         */
        die('exception');
    }

    switch(PLATFORM){
        case 'cli':
            if(empty($core) or empty($core->register['config_ok'])){
                /*
                 * Configuration hasn't been loaded yet, we cannot even know if we are
                 * in debug mode or not!
                 */
                echo "\033[0;31mpre config exception\033[0m\n";
                print_r($e);
                die("\033[0;31mpre config exception\033[0m\n");
            }

            /*
             * Command line script crashed.
             * Try to give nice error messages for known issues
             */
            if(str_until($e->getCode(), '/') == 'warning'){
                /*
                 * This is just a simple warning, no backtrace and such needed, only show the principal message
                 */
                log_console(trim(str_from($e->getMessage(), '():')), 'yellow');
                $core->register['exit_code'] = 255;
                die(255);
            }

            switch((string) $e->getCode()){
                case 'already-running':
                    log_console($e->getMessage(), 'yellow');
                    $core->register['exit_code'] = 4;
                    die(4);

                case 'no-method':
                    log_console($e->getMessage(), 'yellow');
                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                    $core->register['exit_code'] = 5;
                    die(5);

                case 'unknown-method':
                    log_console($e->getMessage(), 'yellow');
                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                    $core->register['exit_code'] = 6;
                    die(6);

                case 'invalid_arguments':
                    log_console($e->getMessage(), 'yellow');
                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                    $core->register['exit_code'] = 7;
                    die(7);

                default:
                    log_console('*** UNCAUGHT EXCEPTION ***', 'red');

                    debug(true);
                    show($e);

                    $core->register['exit_code'] = 8;
                    die(8);
            }

        case 'http':
            if(empty($core) or empty($core->register['config_ok'])){
                /*
                 * Configuration hasn't been loaded yet, we cannot even know if we are
                 * in debug mode or not!
                 */
                die('pre config exception');
            }

            if(!debug()){
                notify('uncaught-exception', 'developers', $e);
                page_show(500);
            }

            show('*** UNCAUGHT EXCEPTION ***');
            showdie($e);
    }

}catch(Exception $f){
    if(!defined('PLATFORM')){
        /*
         * Wow, system crashed before platform detection. See $core->__constructor()
         */
        die('exception handler');
    }

    switch(PLATFORM){
        case 'cli':
            log_console('*** UNCAUGHT EXCEPTION HANDLER CRASHED ***', 'red');
            log_console('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***', 'red');

            debug(true);
            show($f);
            showdie($e);

        case 'http':
            if(!debug()){
                notify('uncaught-exception-crash', 'developers', $f);
                notify('uncaught-exception'      , 'developers', $e);
                page_show(500);
            }

            show('*** UNCAUGHT EXCEPTION HANDLER CRASHED ***');
            show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');

            show($f);
            showdie($e);
    }
}
?>
