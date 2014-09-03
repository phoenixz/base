<?php
/*
 * This library contains CommandLineInterface functions
 *
 * It will be automatically loaded when running on command line
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
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

    public function yellow($string) {
        return $this->getColoredString($string, 'yellow', 'black');
    }

    public function green($string) {
        return $this->getColoredString($string, 'green' , 'black');
    }

    public function purple($string) {
        return $this->getColoredString($string, 'purple', 'black');
    }
}



/*
 * Only allow execution on shell scripts
 */
function cli_only(){
    if(PLATFORM != 'shell'){
        throw new lsException('cli_only(): This can only be done from command line', 'clionly');
    }
}



/*
 * Die correctly on commandline
 *
 * ALWAYS USE return cli_die(); in case script_exec() was used!
 */
function cli_die($exitcode, $message = '', $color = ''){
    log_console($message, ($exitcode ? 'error' : 'exit'), ($exitcode ? 'red' : $color));

    /*
     * Make sure we're not in a script_exec(), where die should NOT happen!
     */
    foreach(debug_backtrace() as $trace){
        if($trace['function'] == 'script_exec'){
            /*
             * Do NOT die!!
             */
            if($exitcode){
                throw new lsException('cli_die(): Script failed with exit code "'.str_log($exitcode).'"', $exitcode);
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
function this_script_already_runs($action = 'exception', $force = false){
    try{
        if(PLATFORM != 'shell'){
            throw new lsException('this_script_already_runs(): This function does not work for platform "'.PLATFORM.'", it is only for "shell" usage');
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
                            throw new lsException('this_script_already_runs(): This script is already running', 'alreadyrunning');

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
                            throw new lsException('this_script_already_runs(): Unknown action "'.str_log($action).'" specified', 'unknown');
                    }

                    return true;
                }
            }
        }

        return false;

    }catch(Exception $e){
        if($e->code == 'alreadyrunning'){
            /*
            * Just keep throwing this one
            */
            throw($e);
        }

        throw new lsException('this_script_already_runs(): Failed', $e);
    }
}



/*
 * Safe and simple way to get arguments
 *
 * This function will REMOVE and then return the argument when its found
 * If the argument is not found, $default will be returned
 */
function argument($value, $next = null, $default = null){
    global $argv;

    if(is_numeric($value)){
        while($argument = isset_get($argv[$value++], $next)){
            switch($argument){
                case 'force':
                case 'test':
                    /*
                     * Ignore test and force arguments
                     */
                    break;

                default:
                    return $argument;
            }
        }
    }

    if(!in_array($value, $argv)){
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
            return array_from($argv, array_search($value, $argv));
        }

        /*
         * Return next argument, if available
         */
        return array_next_value($argv, $value);
    }

    return true;
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
        throw new lsException('cli_highlight(): Failed', $e);
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
function cli_error($message, $e){
    global $usage;

    /*
     * Add the last message
     */
    $e = new lsException($message, $e);

    uncaught_exception($e, false);

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

    if(argument('usage') or argument('-u')){
        cli_show_usage($usage, 'white');
        die(0);
    }

    if(argument('help') or argument('-h')){
        if(!$help){
            log_console('Sorry, this script has no help text defined yet', '', 'yellow');

        }else{
            $help = array_force($help, "\n");

            if(count($help) == 1){
                log_console(array_shift($help), '', 'white');

            }else{
                foreach(array_force($help, "\n") as $line){
                    log_console($line, '', 'white');
                }

                log_console('', '');
            }
        }

        die(0);
    }
}



/*
 *
 */
function cli_show_usage($usage, $color){
    try{
        if(!$usage){
            log_console('Sorry, this script has no usage description defined yet', '', 'yellow');

        }else{
            $usage = array_force($usage, "\n");

            if(count($usage) == 1){
                log_console('Usage: '.array_shift($usage), '', $color);

            }else{
                log_console('Usage:', '', $color);

                foreach(array_force($usage, "\n") as $line){
                    log_console($line, '', $color);
                }

                log_console('', '');
            }
        }

    }catch(Exception $e){
        throw new lsException('cli_show_usage(): Failed', $e);
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
        throw new lsException('cli_root_only(): This script can ONLY be executed by the root user', 'notrootnotallowed');
    }

    return true;
}



/*
 * Exception if this script is being run as root user
 */
function cli_not_root(){
    if(cli_is_root()){
        throw new lsException('cli_not_root(): This script can NOT be executed by the root user', 'rootnotallowed');
    }

    return true;
}
?>
