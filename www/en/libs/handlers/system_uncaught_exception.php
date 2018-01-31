<?php
global $_CONFIG, $core;
static $executed = false;

//echo '<pre>'; print_r($e); die();

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
                 * This is just a simple general warning, no backtrace and
                 * such needed, only show the principal message
                 */
                log_console(tr('Warning: :warning', array(':warning' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                $core->register['exit_code'] = 255;
                die(255);
            }

            switch((string) $e->getCode()){
                case 'already-running':
                    log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                    $core->register['exit_code'] = 254;
                    die(4);

                case 'no-method':
                    log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                    $core->register['exit_code'] = 253;
                    die(5);

                case 'unknown-method':
                    log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                    $core->register['exit_code'] = 252;
                    die(6);

                case 'invalid_arguments':
                    log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                    $core->register['exit_code'] = 251;
                    die(7);

                case 'validation':
                    $messages = $e->getMessages();
                    array_pop($messages);
                    array_pop($messages);

                    log_console(tr('Validation failed'), 'yellow');
                    log_console($messages, 'yellow');
                    cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                    $core->register['exit_code'] = 250;
                    die(7);

                default:
                    log_console('*** UNCAUGHT EXCEPTION ***', 'red');

                    debug(true);

                    if($e->getCode() == 'no-trace'){
                        $messages = $e->getMessages();
                        log_console(array_pop($messages), 'red');

                    }else{
                        /*
                         * Show the entire exception
                         */
                        show($e, null, true);
                    }

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

            if(is_numeric($e->getCode()) and page_show($e->getCode(), array('exists' => true))){
                page_show($e->getCode());
            }

            if(debug()){
                if(!headers_sent()){
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                }

                show('*** UNCAUGHT EXCEPTION ***');
                show(array('SCRIPT' => SCRIPT));
                showdie($e);
            }

            notify($e);
            page_show(500);
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
                notify($f);
                notify($e);
                page_show(500);
            }

            show('*** UNCAUGHT EXCEPTION HANDLER CRASHED ***');
            show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');

            show($f);
            showdie($e);
    }
}
?>
