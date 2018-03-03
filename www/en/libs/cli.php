<?php
/*
 * This library contains CommandLineInterface functions
 *
 * It will be automatically loaded when running on command line
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function cli_library_init(){
    global $core;

    try{
        /*
         * Ensure that the posix extension is available.
         */
        $core->register['posix'] = true;

        if(!function_exists('posix_getuid')){
            log_console('WARNING: The POSIX extension seems to be unavailable, this may cause some functionalities to fail or give unexpected results', 'yellow');
            $core->register['posix'] = false;
        }

    }catch(Exception $e){
        throw new bException('cli_library_init(): Failed', $e);
    }
}



/*
 * Downloaded from http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 */
class Colors {
    private $foreground_colors = array();
    private $background_colors = array();

    public function __construct() {
        // Set up shell colors
        $this->foreground_colors['black']        = '0;30';
        $this->foreground_colors['dark_gray']    = '1;30';
        $this->foreground_colors['blue']         = '0;34';
        $this->foreground_colors['light_blue']   = '1;34';
        $this->foreground_colors['green']        = '0;32';
        $this->foreground_colors['light_green']  = '1;32';
        $this->foreground_colors['cyan']         = '0;36';
        $this->foreground_colors['light_cyan']   = '1;36';
        $this->foreground_colors['red']          = '0;31';
        $this->foreground_colors['error']        = '0;31';
        $this->foreground_colors['light_red']    = '1;31';
        $this->foreground_colors['purple']       = '0;35';
        $this->foreground_colors['light_purple'] = '1;35';
        $this->foreground_colors['brown']        = '0;33';
        $this->foreground_colors['yellow']       = '1;33';
        $this->foreground_colors['warning']      = '1;33';
        $this->foreground_colors['light_gray']   = '0;37';
        $this->foreground_colors['white']        = '1;37';

        $this->background_colors['black']        = '40';
        $this->background_colors['red']          = '41';
        $this->background_colors['green']        = '42';
        $this->background_colors['yellow']       = '43';
        $this->background_colors['blue']         = '44';
        $this->background_colors['magenta']      = '45';
        $this->background_colors['cyan']         = '46';
        $this->background_colors['light_gray']   = '47';
    }

    // Returns colored string
    public function getColoredString($string, $foreground_color, $background_color = 'black') {
        $colored_string = '';

        if(NOCOLOR){
            /*
             * Do NOT apply color
             */
            return $string;
        }

        if(!is_string($foreground_color) or !is_string($background_color) or !isset($this->foreground_colors[$foreground_color]) or !isset($this->background_colors[$background_color])){
            /*
             * If requested colors do not exist, return no
             */
            log_console(tr('[ WARNING ] getColoredString(): specified foreground/background color combination ":fore/:back" for the next line does not exist. The line will be displayed without colors', array(':fore' => $foreground_color, ':back' => $background_color)), 'warning');
            return $string;
        }

        // Check if given foreground color found
        if(isset($this->foreground_colors[$foreground_color])) {
            $colored_string .= "\033[".$this->foreground_colors[$foreground_color].'m';
        }

        // Check if given background color found
        if(isset($this->background_colors[$background_color])) {
            $colored_string .= "\033[".$this->background_colors[$background_color].'m';
        }

        // Add string and end coloring
        $colored_string .=  $string."\033[0m";

        return $colored_string;
    }

    // Returns all foreground color names
    public function getForegroundColors() {
        return array_keys($this->foreground_colors);
    }

    // Returns all background color names
    public function getBackgroundColors() {
        return array_keys($this->background_colors);
    }
}



/*
 * Return the specified string in the specified color
 */
function cli_color($string, $fore_color, $back_color = 'black'){
    try{
        static $color;

        if(!$color){
            $color = new Colors();
        }

        return $color->getColoredString($string, $fore_color, $back_color);

    }catch(Exception $e){
        throw new bException('cli_color(): Failed', $e);
    }
}



/*
 * Return the specified string without color information
 */
function cli_strip_color($string){
    try{
        return preg_replace('/\x1B\[([0-9]{1,2}(;[0-9]{1,2})?)?[mGK]/', '',  $string);
// :DELETE:
//        return preg_replace('/\[([0-9]{1,2}(;[0-9]{1,2})?)?[mGK]/', '',  $string);
//        return preg_replace('/\033\[([0-9]{1,2}(;[0-9]{1,2})?)?[mGK]/', '',  $string);

    }catch(Exception $e){
        throw new bException('cli_strip_color(): Failed', $e);
    }
}



/*
 * Only allow execution on shell scripts
 */
function cli_only($exclusive = false){
    try{
        if(!PLATFORM_CLI){
            throw new bException('cli_only(): This can only be done from command line', 'clionly');
        }

        if($exclusive){
            cli_run_once_local();
        }

    }catch(Exception $e){
        throw new bException('cli_only(): Failed', $e);
    }
}



// :OBSOLETE: Now use cli_done();
/*
 * Die correctly on commandline
 *
 * ALWAYS USE return cli_die(); in case script_exec() was used!
 */
function cli_die($exitcode, $message = '', $color = ''){
    try{
        log_console($message, ($exitcode ? 'red' : $color));

        /*
         * Make sure we're not in a script_exec(), where die should NOT happen!
         */
        foreach(debug_backtrace() as $trace){
            if($trace['function'] == 'script_exec'){
                /*
                 * Do NOT die!!
                 */
                if($exitcode){
                    throw new bException(tr('cli_die(): Script failed with exit code ":code"', array(':code' => str_log($exitcode))), $exitcode);
                }

                return $exitcode;
            }
        }

        die($exitcode);

    }catch(Exception $e){
        throw new bException('cli_die(): Failed', $e);
    }
}



/*
 * ?
 */
function cli_code_back($count){
    try{
        $retval = '';

        for($i = 1; $i <= $count; $i++){
            $retval .= "\033[D";
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('cli_code_back(): Failed', $e);
    }
}



/*
 * Returns the shell console to return the cursor to the beginning of the line
 */
function cli_code_begin(){
    return "\033[D";
}



/*
 * Hide anything printed to screen
 */
function cli_hide(){
    echo "\033[30;40m";
    echo "\e[?25l";
}



/*
 * Restore screen printing
 */
function cli_restore(){
    echo "\033[0m";
    echo "\e[?25h";
}



/*
 * Read input from the command line
 */
function cli_readline($prompt = '', $hidden = false){
    try{
        if($prompt) echo $prompt;

        if($hidden){
            cli_hide();
        }

        $retval = rtrim(fgets(STDIN), "\n");

        if($hidden){
            cli_restore();
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('cli_readline(): Failed', $e);
    }
}



/*
 * Returns the current script that is running. script_exec() allows other
 * scripts to be run from the first, original, SCRIPT, this function returns the
 * script currently running from script_exec()
 */
function cli_current_script(){
    global $core;

    try{
        if(empty($core->register['scripts'])){
            return SCRIPT;
        }

        return str_rfrom(end($core->register['scripts']), '/');

    }catch(Exception $e){
        throw new bException('cli_current_script(): Failed', $e);
    }
}



/*
 * Returns true if the startup script is already running
 */
function cli_run_once_local($close = false){
    static $executed = array();

    try{
        $run_dir = ROOT.'data/run/';
        $script  = cli_current_script();

        load_libs('file');
        file_ensure_path($run_dir);

        if($close){
            if(empty($executed[$script])){
                /*
                 * Hey, this script is being closed but was never opened?
                 */
                throw new bException(tr('cli_run_once_local(): cli_run_once_local() has been called with close option, but it was never opened'), 'invalid');
            }

            file_delete($run_dir.$script);
            unset($executed[$script]);
            return true;
        }

        if(isset($executed[$script])){
            /*
             * Hey, script has already been run before, and its run again
             * without the close option, this should never happen!
             */
            throw new bException(tr('cli_run_once_local(): cli_run_once_local() has been called twice by script ":script" without $close set to true! This function should be called twice, once without argument, and once with boolean "true"', array(':script' => $script)), 'invalid');
        }

        $executed[$script] = true;

        if(file_exists($run_dir.$script)){
            /*
             * Run file exists, so either a process is running, or a process was
             * running but crashed before it could delete the run file. Check if
             * the registered PID exists, and if the process name matches this
             * one
             */
            $pid = file_get_contents($run_dir.$script);
            $pid = trim($pid);

            if(!is_numeric($pid) or !is_natural($pid) or ($pid > 65536)){
                log_console(tr('cli_run_once_local(): The run file ":file" contains invalid information, ignoring', array(':file' => $run_dir.$script)), 'yellow');

            }else{
                $name = safe_exec('ps -p '.$pid.' | tail -n 1');
                $name = array_pop($name);

                if($name){
                    preg_match_all('/.+?\d{2}:\d{2}:\d{2}\s+('.$script.')/', $name, $matches);

                    if(!empty($matches[1][0])){
                        throw new bException(tr('cli_run_once_local(): The script ":script" for this project is already running', array(':script' => $script)), 'already-running');
                    }
                }
            }

            /*
             * File exists, or contains invalid data, but PID either doesn't
             * exist, or is used by a different process. Remove the PID file
             */
            log_console(tr('cli_run_once_local(): Cleaning up stale run file ":file"', array(':file' => $run_dir.$script)), 'yellow');
            file_delete($run_dir.$script);
        }

        /*
         * No run file exists yet, create one now
         */
        file_put_contents($run_dir.$script, getmypid());
        return true;

    }catch(Exception $e){
        if($e->getCode() == 'already-running'){
            /*
            * Just keep throwing this one
            */
            throw($e);
        }

        throw new bException('cli_run_once_local(): Failed', $e);
    }
}



/*
 * Returns true if the startup script is already running
 */
function cli_run_max_local($processes){
    static $executed = array();
under_construction();
    try{
        $run_dir = ROOT.'data/run/';
        $script  = cli_current_script();

        load_libs('file');
        file_ensure_path($run_dir);

        if($processes === false){
            if(empty($executed[$script])){
                /*
                 * Hey, this script is being closed but was never opened?
                 */
                throw new bException(tr('cli_run_max_local(): The cli_run_max_local() has been called with close option, but it was never opened'), 'invalid');
            }

            file_delete($run_dir.$script);
            unset($executed[$script]);
            return true;
        }

        if(!empty($executed[$script])){
            /*
             * Hey, script has already been run before, and its run again
             * without the close option, this should never happen!
             */
            throw new bException(tr('cli_run_max_local(): The cli_run_max_local() has been called twice by script ":script" without $processes set to false! This function should be called twice, once without argument, and once with boolean "true"', array(':script' => $script)), 'invalid');
        }

        $executed[$script] = true;

        if(file_exists($run_dir.$script)){
            /*
             * Run file exists, so either a process is running, or a process was
             * running but crashed before it could delete the run file. Check if
             * the registered PID exists, and if the process name matches this
             * one
             */
            $pid = file_get_contents($run_dir.$script);
            $pid = trim($pid);

            if(!is_numeric($pid) or !is_natural($pid) or ($pid > 65536)){
                log_console(tr('cli_run_max_local(): The run file ":file" contains invalid information, ignoring', array(':file' => $run_dir.$script)), 'yellow');

            }else{
                $name = safe_exec('ps -p '.$pid.' | tail -n 1');
                $name = array_pop($name);

                if($name){
                    preg_match_all('/.+?\d{2}:\d{2}:\d{2}\s+('.$script.')/', $name, $matches);

                    if(!empty($matches[1][0])){
                        throw new bException(tr('cli_run_max_local(): The script ":script" for this project is already running', array(':script' => $script)), 'already-running');
                    }
                }
            }

            /*
             * File exists, or contains invalid data, but PID either doesn't
             * exist, or is used by a different process. Remove the PID file
             */
            log_console(tr('cli_run_max_local(): Cleaning up stale run file ":file"', array(':file' => $run_dir.$script)), 'yellow');
            file_delete($run_dir.$script);
        }

        /*
         * No run file exists yet, create one now
         */
        file_put_contents($run_dir.$script, getmypid());
        return true;

    }catch(Exception $e){
        if($e->getCode() == 'max-running'){
            /*
            * Just keep throwing this one
            */
            throw($e);
        }

        throw new bException('cli_run_max_local(): Failed', $e);
    }
}



/*
 * Returns true if the startup script is already running
 */
function cli_run_once($action = 'exception', $force = false){
    try{
        if(!PLATFORM_CLI){
            throw new bException('cli_run_once(): This function does not work for platform "'.PLATFORM.'", it is only for "shell" usage');
        }

        exec('ps -eF | grep php | grep -v grep', $output);

        /*
        * This scan will a possible other script AND our own, so we can only know
        * if it is already running if we count two or more of these scripts.
        */
        $count = 0;

        foreach($output as $line){
            $line = preg_match('/\d+:\d+:\d+ .*? (.*?)$/', $line, $matches);

            if(empty($matches[1])){
                continue;
            }

            $process = str_until(str_rfrom($matches[1], '/'), ' ');

            if($process == SCRIPT){
                if(++$count >= 2){
                    switch($action){
                        case 'exception':
                            throw new bException('cli_run_once(): This script is already running', 'already-running');

                        case 'kill':
                            $thispid = getmypid();

                            foreach($output as $line){
                                if(!preg_match('/^\s*?\w+?\s+?(\d+)/', trim($line), $matches) or empty($matches[1])){
                                    /*
                                     * This entry does not contain valid process id information
                                     */
                                    continue;
                                }

                                $pid = $matches[1];

                                if($pid == $thispid){
                                    /*
                                     * We're not going to suicide!
                                     */
                                    continue;
                                }

                                cli_kill($pid, ($force ? 9 : 15));
                            }

                            return false;

                        default:
                            throw new bException('cli_run_once(): Unknown action "'.str_log($action).'" specified', 'unknown');
                    }

                    return true;
                }
            }
        }

        return false;

    }catch(Exception $e){
        if($e->getCode() == 'already-running'){
            /*
            * Just keep throwing this one
            */
            throw($e);
        }

        throw new bException('cli_run_once(): Failed', $e);
    }
}



/*
 * Find the specified method, basically any argument without - or --
 *
 * The result will be removed from $argv, but will remain stored in a static
 * variable which will return the same result every subsequent function call
 */
function cli_method($index = null, $default = null){
    global $argv;
    static $method = array();

    try{
        if($default === false){
            $method[$index] = null;
        }

        if(isset($method[$index])){
            return $method[$index];
        }

        foreach($argv as $key => $value){
            if(substr($value, 0, 1) !== '-'){
                unset($argv[$key]);
                $method[$index] = $value;
                return $value;
            }
        }

        return $default;

    }catch(Exception $e){
        throw new bException('cli_method(): Failed', $e);
    }
}



/*
 * Safe and simple way to get arguments
 *
 * This function will REMOVE and then return the argument when its found
 * If the argument is not found, $default will be returned
 */
function cli_argument($value = null, $next = null, $default = null){
    global $argv;

    try{
        if(is_integer($value)){
            $count = count($argv) - 1;

            if($next === 'all'){
// :TODO: This could be optimized using a for() starting at $value instead of a foreach() over all entries
                foreach($argv as $argv_key => $argv_value){
                    if($argv_key < $value){
                        continue;
                    }

                    if($argv_key == $value){
                        unset($argv[$key]);
                        continue;
                    }

                    if(substr($argv_value, 0, 1) == '-'){
                        /*
                         * Encountered a new option, stop!
                         */
                        break;

                    }

                    /*
                     * Add this argument to the list
                     */
                    $retval[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return isset_get($retval);
            }

            if(!empty($argv[$value++])){
                $argument = $argv[$value - 1];
                unset($argv[$value - 1]);
                return $argument;
            }

            /*
             * No arguments found (except perhaps for test or force)
             */
            return $default;
        }

        if($value === null){
            $retval = array_shift($argv);
            $retval = str_starts_not($retval, '-');
            return $retval;
        }

        if(($key = array_search($value, $argv)) === false){
            /*
             * Specified argument not found
             */
            return $default;
        }

        if($next){
            if($next === 'all'){
                /*
                 * Return all following arguments, if available, until the next option
                 */
                foreach($argv as $argv_key => $argv_value){
                    if(empty($start)){
                        if($argv_value == $value){
                            $start = true;
                            unset($argv[$argv_key]);
                        }

                        continue;
                    }

                    if(substr($argv_value, 0, 1) == '-'){
                        /*
                         * Encountered a new option, stop!
                         */
                        break;
                    }

                    /*
                     * Add this argument to the list
                     */
                    $retval[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return $retval;
            }

            /*
             * Return next argument, if available
             */
            $retval = array_next_value($argv, $value, true);

            if(substr($retval, 0, 1) == '-'){
                throw new bException(tr('cli_argument(): Argument ":argument1" has no assigned value, it is immediately followed by argument ":argument2"', array(':argument1' => $value, ':argument2' => $retval)), 'invalid');
            }

            return $retval;
        }

        unset($argv[$key]);
        return true;

    }catch(Exception $e){
        throw new bException(tr('cli_argument(): Failed'), $e);
    }
}



/*
 *
 */
function cli_arguments($arguments = null){
    global $argv;

    try{
        if(!$arguments){
            $retval = $argv;
            $argv   = array();
            return $retval;
        }

        $retval = array();

        foreach(array_force($arguments) as $argument){
            if(is_numeric($argument)){
                /*
                 * If the key would be numeric, argument() would get into an endless loop
                 */
                throw new bException(tr('cli_arguments(): The specified argument ":argument" is numeric, and as such, invalid. cli_arguments() can only check for key-value pairs, where the keys can not be numeric', array(':argument' => $argument)), 'invalid');
            }

            if($value = cli_argument($argument, true)){
                $retval[str_replace('-', '', $argument)] = $value;
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('cli_arguments(): Failed'), $e);
    }
}



/*
 * Ensures that no other command line arguments are left.
 * If arguments were still found, an appropriate error will be thrown
 */
function cli_no_arguments_left(){
    global $argv;

    if(!$argv){
        return true;
    }

    throw new bException(tr('cli_no_arguments_left(): Unknown arguments ":arguments" encountered', array(':arguments' => str_force($argv, ', '))), 'invalid_arguments');
}



/*
 * Mark the specified keywords in the specified string with the specified color
 */
function cli_highlight($string, $keywords, $fore_color, $back_color = 'black'){
    static $color;

    try{
        if(!$color){
            $color = new Colors();
        }

        foreach(array_force($keywords) as $keyword){
            $string = str_replace($keyword, $color->getColoredString($string, $fore_color, $back_color), $string);
        }

        return $string;

    }catch(Exception $e){
        throw new bException('cli_highlight(): Failed', $e);
    }
}



/*
 * Show error on screen with usage
 */
function cli_error($e = null){
    global $usage;

    switch($e->getCode()){
        case 'already-running':
            break;

        default:
            if(!empty($usage)){
                echo "\n";
                cli_show_usage($usage, 'white');
            }
    }
}



/*
 *
 */
function cli_show_usage($usage, $color){
    try{
        if(!$usage){
            log_console(tr('Sorry, this script has no usage description defined yet'), 'yellow');

        }else{
            $usage = array_force(trim($usage), "\n");

            if(count($usage) == 1){
                log_console(tr('Usage:')       , $color);
                log_console(array_shift($usage), $color);

            }else{
                log_console(tr('Usage:'), $color);

                foreach(array_force($usage, "\n") as $line){
                    log_console($line, $color);
                }

                log_console();
            }
        }

    }catch(Exception $e){
        throw new bException('cli_show_usage(): Failed', $e);
    }
}



/*
 * Exception if this script is not being run as root user
 */
function cli_is_root(){
    global $core;

    if($core->register['posix']){
        return posix_getuid() == 0;
    }

    return false;
}



/*
 * Exception if this script is not being run as root user
 */
function cli_root_only(){
    if(!cli_is_root()){
        throw new bException('cli_root_only(): This script can ONLY be executed by the root user', 'notrootnotallowed');
    }

    return true;
}



/*
 * Exception if this script is being run as root user
 */
function cli_not_root(){
    if(cli_is_root()){
        throw new bException('cli_not_root(): This script can NOT be executed by the root user', 'rootnotallowed');
    }

    return true;
}



/*
 *
 */
function cli_arguments_none_left(){
    return cli_no_arguments_left();
}



/*
 *
 */
function cli_done(){
    global $core;

    try{
        if(empty($core) or empty($core->register['ready'])){
            echo "\033[0;31mCommand line terminated before \$core ready\033[0m\n";
            die(1);
        }

        $exit_code = isset_get($core->register['exit_code'], 0);

        if(!defined('ENVIRONMENT')){
            /*
             * Oh crap.. Environment hasn't been defined, so we died VERY soon.
             */
            return false;
        }

        if(QUIET){
            return false;
        }

        load_libs('time,numbers');

        if($exit_code and is_numeric($exit_code)){
            if($exit_code > 200){
                /*
                 * Script ended with warning
                 */
                log_console(tr('Script ":script" ended with warning in :time with ":usage" peak memory usage', array(':script' => SCRIPT, ':time' => time_difference(STARTTIME, microtime(true), 'auto', 2), ':usage' => bytes(memory_get_peak_usage()))), 'yellow');

            }else{
                log_console(tr('Script ":script" failed in :time with ":usage" peak memory usage', array(':script' => SCRIPT, ':time' => time_difference(STARTTIME, microtime(true), 'auto', 2), ':usage' => bytes(memory_get_peak_usage()))), 'red');
            }

        }else{
            log_console(tr('Finished ":script" script in :time with ":usage" peak memory usage', array(':script' => SCRIPT, ':time' => time_difference(STARTTIME, microtime(true), 'auto', 2), ':usage' => bytes(memory_get_peak_usage()))), 'green');
        }

    }catch(Exception $e){
        throw new bException('cli_done(): Failed', $e);
    }
}



/*
 * Returns the PID list for the specified process name, if exists
 */
function cli_pgrep($name){
    try{
        return safe_exec('pgrep '.$name, 1);

    }catch(Exception $e){
        throw new bException('cli_pgrep(): Failed', $e);
    }
}



/*
 * Returns the process name for the specified PID
 */
function cli_pidgrep($pid){
    try{
        try{
            $results = safe_exec('ps -p 1 > /dev/null');
            $result  = array_pop($pid);

            return $result;

        }catch(Exception $e){
            switch($e->getCode()){
                case 1:
                    return null;

                default:
                    throw $e;
            }
        }

    }catch(Exception $e){
        throw new bException('cli_pgrep(): Failed', $e);
    }
}



/*
 *
 */
function cli_kill($pid, $signal = null, $sudo = false){
    try{
        if(!$signal){
            $signal = 15;
        }

        /*
         * pkill returns 1 if process wasn't found, we can ignore that
         */
        safe_exec(($sudo ? 'sudo ' : '').'pkill -'.$signal.' '.$process, 1);

        if($verify){
            while(--$verify >= 0){
                sleep(0.5);

                /*
                 * Ensure that the progress is gone
                 */
                $results = cli_pidgrep($pid);

                if(!$results){
                    /*
                     * Killed it softly
                     */
                    return true;
                }

                sleep(0.5);
            }

            if($sigkill){
                /*
                 * Sigkill it!
                 */
                $result = cli_pkill($process, 9, $sudo, $verify, false);

                if($result){
                    /*
                     * Killed it the hard way!
                     */
                    return true;
                }
            }

            throw new bException(tr('cli_kill(): Failed to kill process ":process"', array(':process' => $process)), 'failed');
        }

    }catch(Exception $e){
        throw new bException('cli_kill(): Failed', $e);
    }
}



/*
 * Send a signal to the specified process. S
 */
function cli_pkill($process, $signal = null, $sudo = false, $verify = 3, $sigkill = true){
    try{
        if(!$signal){
            $signal = 15;
        }

        /*
         * pkill returns 1 if process wasn't found, we can ignore that
         */
        safe_exec(($sudo ? 'sudo ' : '').'pkill -'.$signal.' '.$process, 1);

        if($verify){
            while(--$verify >= 0){
                sleep(0.5);

                /*
                 * Ensure that the progress is gone
                 */
                $results = cli_pgrep($process);

                if(!$results){
                    /*
                     * Killed it softly
                     */
                    return true;
                }

                sleep(0.5);
            }

            if($sigkill){
                /*
                 * Sigkill it!
                 */
                $result = cli_pkill($process, 9, $sudo, $verify, false);

                if($result){
                    /*
                     * Killed it the hard way!
                     */
                    return true;
                }
            }

            throw new bException(tr('cli_pkill(): Failed to kill process ":process"', array(':process' => $process)), 'failed');
        }

    }catch(Exception $e){
        throw new bException('cli_pkill(): Failed', $e);
    }
}



/*
 *
 */
function cli_get_term(){
    try{
        $term = exec('echo $TERM');
        return $term;

    }catch(Exception $e){
        throw new bException('cli_get_term(): Failed', $e);
    }
}



/*
 *
 */
function cli_get_columns(){
    try{
        $cols = exec('tput cols');
        return $cols;

    }catch(Exception $e){
        throw new bException('cli_get_columns(): Failed', $e);
    }
}


/*
 *
 */
function cli_get_lines(){
    try{
        $rows = exec('tput lines');
        return $rows;

    }catch(Exception $e){
        throw new bException('cli_get_lines(): Failed', $e);
    }
}



/*
 * Run a process and run callbacks over the output
 */
function cli_run_process($command, $callback){
    try{
//$p = popen('executable_file_or_script', 'r');
//while(!feof($p)) {
//    echo fgets($p);
//    ob_flush();
//    flush();
//}
//pclose($p);

    }catch(Exception $e){
        throw new bException('cli_run_process(): Failed', $e);
    }
}



/*
 * Show a X% width pogress bar on the current line
 * See https://github.com/guiguiboy/PHP-CLI-Progress-Bar/blob/master/ProgressBar/Manager.php for inspiration
 */
function cli_progress_bar($width, $percentage, $color){
    try{

    }catch(Exception $e){
        throw new bException('cli_progress_bar(): Failed', $e);
    }
}



/*
 *
 */
function cli_status_color($status){
    try{
        $status = status($status);

        switch(strtolower($status)){
            case 'ok':
                // FALLTHROUGH
            case 'completed':
                return cli_color($status, 'green');

            case 'processing':
                return cli_color($status, 'light_blue');

            case 'failed':
                return cli_color($status, 'red');

            case 'deleted':
                return cli_color($status, 'yellow');

            default:

        }

        return $status;

    }catch(Exception $e){
        throw new bException('cli_status_color(): Failed', $e);
    }
}



/*
 * Check if the specified PID is running
 */
function cli_pid($pid){
    try{
        return file_exists('/proc/'.$pid);

    }catch(Exception $e){
        throw new bException('cli_pid(): Failed', $e);
    }
}



/*
 * WARNING! BELOW HERE BE OBSOLETE FUNCTIONS AND OBSOLETE-BUT-WE-WANT-TO-BE-BACKWARD-COMPATIBLE WRAPPERS
 */
function this_script_already_runs($action = 'exception', $force = false){
    return cli_run_once($action, $force);
}

function cli_exclusive($action = 'exception', $force = false){
    return cli_run_once($action, $force);
}
?>
