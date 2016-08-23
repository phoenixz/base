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
 * Ensure that the posix extension is available.
 */
$GLOBALS['posix'] = true;
if(!function_exists('posix_getuid')){
    log_error('WARNING: The POSIX extension seems to be unavailable, this may cause some functionalities to fail or give unexpected results', 'noposix');
    $GLOBALS['posix'] = false;
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
        $this->foreground_colors['light_red']    = '1;31';
        $this->foreground_colors['purple']       = '0;35';
        $this->foreground_colors['light_purple'] = '1;35';
        $this->foreground_colors['brown']        = '0;33';
        $this->foreground_colors['yellow']       = '1;33';
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
    public function getColoredString($string, $foreground_color = null, $background_color = null) {
        $colored_string = "";

        // Check if given foreground color found
        if(isset($this->foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }

        // Check if given background color found
        if(isset($this->background_colors[$background_color])) {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .=  $string . "\033[0m";

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

    public function white($string) {
        return $this->getColoredString($string, 'white' , 'black');
    }

    public function red($string) {
        return $this->getColoredString($string, 'red'   , 'black');
    }

    public function error($string) {
        return $this->red($string);
    }

    public function yellow($string) {
        return $this->getColoredString($string, 'yellow', 'black');
    }

    public function green($string) {
        return $this->getColoredString($string, 'green' , 'black');
    }

    public function purple($string) {
        return $this->getColoredString($string, 'purple', 'black');
    }

    public function cyan($string) {
        return $this->getColoredString($string, 'cyan', 'black');
    }
}



/*
 * Only allow execution on shell scripts
 */
function cli_only($exclusive = false){
    if(PLATFORM != 'shell'){
        throw new bException('cli_only(): This can only be done from command line', 'clionly');
    }

    if($exclusive){
        cli_run_once();
    }
}



/*
 * Die correctly on commandline
 *
 * ALWAYS USE return cli_die(); in case script_exec() was used!
 */
function cli_die($exitcode, $message = '', $color = ''){
    cli_log($message, ($exitcode ? 'red' : $color));

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
}



/*
 * Returns the shell console to return the cursor to the beginning of the line
 */
function cli_code_back($count){
    $retval = '';

    for($i = 1; $i <= $count; $i++){
        $retval .= "\033[D";
    }

    return $retval;
}



/*
 * Returns the shell console to return the cursor to the beginning of the line
 */
function cli_code_begin(){
    return "\033[D";
}



/*
 * Returns true if the startup script is already running
 */
function cli_run_once($action = 'exception', $force = false){
    try{
        if(PLATFORM != 'shell'){
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
                            throw new bException('cli_run_once(): This script is already running', 'alreadyrunning');

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

                                safe_exec('kill '.($force ? '-9 ' : '').$pid);
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
        if($e->getCode() == 'alreadyrunning'){
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
function cli_method($default = null){
    global $argv;
    static $method;

    try{
        if($default === false){
            $method = null;
        }

        if($method){
            return $method;
        }

        foreach($argv as $key => $value){
            if(substr($value, 0, 1) !== '-'){
                unset($argv[$key]);
                $method = $value;
                return $method;
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
                $retval = array_from($argv, $value);
                $argv   = array_until($argv, $value);

                return $retval;
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
            $retval = str_replace('-', '', array_shift($argv));
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
                 * Return all following arguments, if available
                 */
                return array_from($argv, array_search($value, $argv), true);
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

    $method = cli_method();

    if($method){
        switch(count($argv)){
            case 0:
                throw new bException(tr('cli_no_arguments_left(): Unknown method ":method" encountered', array(':method' => $method)), 'invalid_arguments');

            case 1:
                throw new bException(tr('cli_no_arguments_left(): Unknown method ":method" with unknown argument ":argument" encountered', array(':argument' => str_force($argv, ', '), ':method' => $method)), 'invalid_arguments');

            default:
                throw new bException(tr('cli_no_arguments_left(): Unknown method ":method" with unknown arguments ":arguments" encountered', array(':arguments' => str_force($argv, ', '), ':method' => $method)), 'invalid_arguments');
        }
    }

    if(count($argv) > 1){
        throw new bException(tr('cli_no_arguments_left(): Unknown arguments ":arguments" encountered', array(':arguments' => str_force($argv, ', '))), 'invalid_arguments');
    }

    throw new bException(tr('cli_no_arguments_left(): Unknown argument ":argument" encountered', array(':argument' => str_force($argv, ', '))), 'invalid_arguments');
}



/*
 * Mark the specified keywords in the specified string with the specified color
 */
function cli_highlight($string, $keywords, $color){
    try{
        $c = cli_init_color();

        foreach(array_force($keywords) as $keyword){
            $string = str_replace($keyword, $c->$color($keyword), $string);
        }

        return $string;

    }catch(Exception $e){
        throw new bException('cli_highlight(): Failed', $e);
    }
}



/*
 * Return the specified string in the specified color
 */
function cli_color($string, $color){
    $c = cli_init_color();
    return $c->$color($string);
}



/*
 * Initialize one global color object
 */
function cli_init_color(){
    if(empty($GLOBALS['c'])){
        $GLOBALS['c'] = new Colors();
    }

    return $GLOBALS['c'];
}



/*
 * Show error on screen with usage
 */
function cli_error($e = null){
    global $usage;

    if(!empty($usage)){
        echo "\n";
        cli_show_usage($usage, 'red');
    }
}



/*
 * Process basic arguments, if possible
 */
function cli_basic_arguments(){
    global $usage, $help;

    try{
        if(cli_argument('usage') or cli_argument('-u') or cli_argument('--usage')){
            cli_show_usage($usage, 'white');
            die(0);
        }

        if(cli_argument('help') or cli_argument('-h') or cli_argument('--help')){
            if(!$help){
                cli_log(tr('Sorry, this script has no help text defined yet'), 'yellow');

            }else{
                $help = array_force($help, "\n");

                if(count($help) == 1){
                    cli_log(array_shift($help), 'white');

                }else{
                    foreach(array_force($help, "\n") as $line){
                        cli_log($line, 'white');
                    }

                    cli_log();
                }
            }

            die(0);
        }

    }catch(Exception $e){
        throw new bException('cli_basic_arguments(): Failed', $e);
    }
}



/*
 *
 */
function cli_show_usage($usage, $color){
    try{
        if(!$usage){
            cli_log(tr('Sorry, this script has no usage description defined yet'), 'yellow');

        }else{
            $usage = array_force(trim($usage), "\n");

            if(count($usage) == 1){
                cli_log(tr('Usage:')       , $color);
                cli_log(array_shift($usage), $color);

            }else{
                cli_log(tr('Usage:'), $color);

                foreach(array_force($usage, "\n") as $line){
                    cli_log($line, $color);
                }

                cli_log();
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
    if($GLOBALS['posix']){
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
 * Show a dot on the console each $each call
 * if $each is false, "DONE" will be printed, with next line
 * Internal counter will reset if a different $each is received.
 */
function cli_dot($each = 10, $color = 'green', $dot = '.'){
    static $count  = 0,
           $l_each = 0;

    try{
        if($each === false){
            cli_log(tr('Done'), $color);
            $l_each = 0;
            $count  = 0;
            return true;
        }

        if($l_each != $each){
            $l_each = $each;
            $count  = 0;
        }

        if(++$count > $l_each){
            $count = 0;
            cli_log($dot, $color, false);
            return true;
        }

    }catch(Exception $e){
        throw new bException('cli_dot(): Failed', $e);
    }
}



/*
 * Log specified message to console, but only if we are in console mode!
 */
function cli_log($message = '', $color = null, $newline = true, $filter_double = false){
    static $c, $fh, $last;

    try{
        if(PLATFORM != 'shell') return false;

        if(($filter_double == true) and ($message == $last)){
            /*
            * We already displayed this message, skip!
            */
            return;
        }

        $last = $message;

        if($color and defined('NOCOLOR') and !NOCOLOR){
            load_libs('cli');

            $c       = cli_init_color();
            $message = $c->$color($message);
        }

        $message = stripslashes(br2nl($message)).($newline ? "\n" : '');

        if(empty($error)){
            echo $message;

        }else{
            /*
             * Log to STDERR instead of STDOUT
             */
            if(empty($fh)){
                $fh = fopen('php://stderr','w');
            }

            fwrite($fh, $message);
        }

        return true;

    }catch(Exception $e){
        throw new bException('cli_log(): Failed', $e, array('message' => $message));
    }
}

/*
 *
 */
function cli_arguments_none_left(){
    return cli_no_arguments_left();
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
