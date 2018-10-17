<?php
global $_CONFIG, $core;
static $executed = false;

//echo "<pre>\n"; print_r($e->getCode()); echo"\n"; print_r($e); die();

try{
    if($executed){
        /*
         * We seem to be stuck in an uncaught exception loop, cut it out now!
         */
        die('exception loop detected');
    }

    $executed = true;

    if(!defined('SCRIPT')){
        define('SCRIPT', tr('unknown'));
    }

    if(!empty($core) and !empty($core->register['ready'])){
        log_file(tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" SCRIPT ":script" ***', array(':code' => $e->getCode(), ':type' => $core->callType(), ':script' => SCRIPT)), 'exceptions', 'error');
        log_file($e, 'exceptions');
    }

    if(!defined('PLATFORM')){
        /*
         * Wow, system crashed before platform detection. See $core->__constructor()
         */
        die('exception before platform detection');
    }

    switch(PLATFORM){
        case 'cli':
            if($e->getCode() === 'parameters'){
                log_console(trim(str_from($e->getMessage(), '():')), 'warning');
                $GLOBALS['core'] = false;
                die(1);
            }

            if(empty($core) or empty($core->register['ready'])){
                /*
                 * Configuration hasn't been loaded yet, we cannot even know if we are
                 * in debug mode or not!
                 */
                echo "\033[1;31mPre ready exception\033[0m\n";
                print_r($e);
                die("\033[1;31mPre ready exception\033[0m\n");
            }

            /*
             * Command line script crashed.
             *
             * If not using VERBOSE mode, then try to give nice error messages
             * for known issues
             */
            if(!VERBOSE){
                if(str_until($e->getCode(), '/') === 'warning'){
                    /*
                     * This is just a simple general warning, no backtrace and
                     * such needed, only show the principal message
                     */
                    log_console(tr('Warning: :warning', array(':warning' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                    $core->register['exit_code'] = 255;
                    die($core->register['exit_code']);
                }

                switch((string) $e->getCode()){
                    case 'already-running':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        $core->register['exit_code'] = 254;
                        die($core->register['exit_code']);

                    case 'no-method':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 253;
                        die($core->register['exit_code']);

                    case 'unknown-method':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 252;
                        die($core->register['exit_code']);

                    case 'missing-arguments':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 253;
                        die($core->register['exit_code']);

                    case 'invalid-arguments':
                        log_console(tr('Failed: :message', array(':message' => trim(str_from($e->getMessage(), '():')))), 'yellow');
                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 251;
                        die($core->register['exit_code']);

                    case 'validation':
                        $messages = $e->getMessages();

                        if(count($messages) > 2){
                            array_pop($messages);
                            array_pop($messages);
                            log_console(tr('Validation failed'), 'yellow');
                            log_console($messages, 'yellow');

                        }else{
                            log_console($messages, 'yellow');
                        }

                        cli_show_usage(isset_get($GLOBALS['usage']), 'white');
                        $core->register['exit_code'] = 250;
                        die($core->register['exit_code']);
                }
            }

            log_console(tr('*** UNCAUGHT EXCEPTION ":code" IN CONSOLE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => SCRIPT)), 'red');
            debug(true);

            if($e instanceof bException){
                if($e->getCode() === 'no-trace'){
                    $messages = $e->getMessages();
                    log_console(array_pop($messages), 'red');

                }else{
                    /*
                     * Show the entire exception
                     */
                    $messages = $e->getMessages();
                    $data     = $e->getData();
                    $code     = $e->getCode();
                    $file     = $e->getFile();
                    $line     = $e->getLine();
                    $trace    = $e->getTrace();

                    log_console(tr('Exception code    : ":code"'      , array(':code' => $code))                  , 'exception');
                    log_console(tr('Exception location: ":file@:line"', array(':file' => $file, ':line' => $line)), 'exception');

                    log_console(tr('Exception messages trace:'), 'exception');
                    foreach($messages as $message){
                        log_console('    '.$message, 'exception');
                    }

                    log_console('    '.SCRIPT.': Failed', 'exception');
                    log_console(tr('Exception function trace:'), 'exception');

                    if($trace){
                        show($trace, null, true);

                    }else{
                        show('N/A');
                    }

                    if($data){
                        log_console(tr('Exception data:'), 'exception');
                        show($data, null, true);
                    }
                }

            }else{
                /*
                 * Treat this as a normal PHP Exception object
                 */
                if($e->getCode() === 'no-trace'){
                    log_console($e->getMessage(), 'red');

                }else{
                    /*
                     * Show the entire exception
                     */
                    show($e, null, true);
                }
            }

            $core->register['exit_code'] = 64;
            die(8);

        case 'http':
            if(empty($core) or empty($core->register['ready'])){
                /*
                 * Configuration hasn't been loaded yet, we cannot even know if we are
                 * in debug mode or not!
                 */
                if(!headers_sent()){
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                }

                die('pre ready exception');
            }

            if($e->getCode() === 'validation'){
                $e->setCode(400);
            }

            if(is_numeric($e->getCode()) and page_show($e->getCode(), array('exists' => true))){
                log_file(tr('Displaying exception page ":page"', array(':page' => $e->getCode())), 'exceptions', 'error');
                page_show($e->getCode(), array('message' =>$e->getMessage()));
            }

            if(debug()){
                if(!headers_sent()){
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                }

                switch($core->callType()){
                    case 'api':
                        // FALLTHROUGH
                    case 'ajax':
                        load_libs('json');
                        echo "UNCAUGHT EXCEPTION\n\n";
                        showdie($e);
                }

                $retval = ' <style type="text/css">
                            table.exception{
                                font-family: sans-serif;
                                width:99%;
                                background:#AAAAAA;
                                border-collapse:collapse;
                                border-spacing:2px;
                                margin: 5px auto 5px auto;
                            }
                            td.center{
                                text-align: center;
                            }
                            table.exception thead{
                                background: #CE0000;
                                color: white;
                                font-weight: bold;
                            }
                            table.exception td{
                                border: 1px solid black;
                                padding: 15px;
                            }
                            table.exception td.value{
                                word-break: break-all;
                            }
                            table.debug{
                                background:#AAAAAA !important;
                            }
                            table.debug thead{
                                background: #CE0000 !important;
                                color: white;
                            }
                            table.debug .debug-header{
                                display: none;
                            }
                            </style>
                            <table class="exception">
                                <thead>
                                    <td colspan="2" class="center">
                                        '.tr('*** UNCAUGHT EXCEPTION ":code" IN ":type" TYPE SCRIPT ":script" ***', array(':code' => $e->getCode(), ':script' => SCRIPT, 'type' => $core->callType())).'
                                    </td>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="center">
                                            '.tr('An uncaught exception with code ":code" occured in script ":script". See the exception core dump below for more information on how to fix this issue', array(':code' => $e->getCode(), ':script' => SCRIPT)).'
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            '.tr('File').'
                                        </td>
                                        <td>
                                            '.$e->getFile().'
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            '.tr('Line').'
                                        </td>
                                        <td>
                                            '.$e->getLine().'
                                        </td>
                                    </tr>
                                </tbody>
                            </table>';

                echo $retval;
                showdie($e);
            }

            notify($e);

            switch($core->callType()){
                case 'api':
                    // FALLTHROUGH
                case 'ajax':
                    load_libs('json');
                    json_error(tr('Something went wrong, please try again later'), 500);

            }

            page_show(500);
    }

}catch(Exception $f){
    log_file('STARTUP-UNCAUGHT-EXCEPTION HANDLER CRASHED!', 'exception-handler', 'red');
    log_file($f, 'exception-handler');

    if(!defined('PLATFORM')){
        /*
         * Wow, system crashed before platform detection. See $core->__constructor()
         */
        die('exception handler');
    }

    switch(PLATFORM){
        case 'cli':
            log_console(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => SCRIPT)), 'red');
            log_console(tr('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***'), 'red');

            debug(true);
            show($f);
            showdie($e);

        case 'http':
            if(!debug()){
                notify($f);
                notify($e);
                page_show(500);
            }

            show(tr('*** UNCAUGHT EXCEPTION HANDLER CRASHED FOR SCRIPT ":script" ***', array(':script' => SCRIPT)));
            show('*** SHOWING HANDLER EXCEPTION FIRST, ORIGINAL EXCEPTION BELOW ***');

            show($f);
            showdie($e);
    }
}
?>
