<?php
/*
 * This is not a real library per-se, it will just start up the system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */



/*
 * Framework version
 */
define('FRAMEWORKCODEVERSION', '1.3.0');



/*
 * This constant can be used to measure time used to render page or process
 * script
 */
define('STARTTIME', microtime(true));



/*
 * Define project paths.
 *
 * ROOT   is the root directory of this project and should be used as the root for all other paths
 * TMP    is a private temporary directory
 * PUBTMP is a public (accessible by web server) temporary directory
 */
define('ROOT'  , realpath(__DIR__.'/../../..').'/');
define('TMP'   , ROOT.'data/tmp/');
define('PUBTMP', ROOT.'data/content/tmp/');
define('LIBS'  , __DIR__.'/');


/*
 * Include project setup file. This file contains the very bare bones basic
 * information about this project
 *
 * Load system library and initialize core
 */
include_once(ROOT.'config/project.php');



/*
 * Setup error handling, report ALL errors
 */
error_reporting(E_ALL);
set_error_handler('php_error_handler');
set_exception_handler('uncaught_exception');



/*
 * Load the system core and boot the correct startup script
 */
$core = new core();
$core->startup();



/*
 * BELOW FOLLOW TWO CLASSES AND AFTER THAT ONLY SYSTEM FUNCTIONS
 */



/*
 *
 */
class core{
    public $sql          = array();
    public $mc           = array();
    public $register     = array('js'            => array(),
                                 'css'           => array(),
                                 'quiet'         => true,
                                 'footer'        => '',
                                 'debug_queries' => array());

    private $callType    = null;

    function __construct(){
        global $_CONFIG;

        try{
            /*
             * Check what platform we're in
             */
            define('PLATFORM', (php_sapi_name() === 'cli') ? 'cli' : 'http');



            /*
             * Detect platform and execute specific platform startup sequence
             */
            switch(PLATFORM){
                case 'http':
                    define('PLATFORM_HTTP', true);
                    define('PLATFORM_CLI' , false);

                    /*
                     * Detect what http platform we're on
                     */
                    if((substr($_SERVER['REQUEST_URI'], 0, 7) == '/admin/') or (substr($_SERVER['REQUEST_URI'], 3, 7) == '/admin/')){
                        $this->callType = 'admin';

                    }elseif(strstr($_SERVER['PHP_SELF'], '/ajax/')){
                        $this->callType = 'ajax';

                    }elseif(strstr($_SERVER['PHP_SELF'], '/api/')){
                        $this->callType = 'api';

                    }elseif($_CONFIG['amp']['enabled'] and !empty($_GET['amp'])){
                        $this->callType = 'amp';

                    }elseif(substr($_SERVER['PHP_SELF'], -7, 7) == '404.php'){
                        $this->callType = 'system';

                    }else{
                        $this->callType = 'http';
                    }

                    break;

                case 'cli':
                    define('PLATFORM_HTTP', false);
                    define('PLATFORM_CLI' , true);

                    $this->callType = 'cli';
                    break;
            }



            /*
             * Verify project data integrity
             */
            if(!defined('SEED') or !SEED or (PROJECTCODEVERSION == '0.0.0')){
                return include(__DIR__.'/handlers/startup_no_project_data.php');
            }

        }catch(Exception $e){
            throw new bException(tr('core::__construct(): Failed'), $e);
        }
    }

    public function startup(){
        global $_CONFIG, $core;

        try{
            /*
             * Load basic libraries
             */
            load_libs('strings,array,sql,mb,meta');


            /*
             * Start the call type dependant startup script
             */
            require('handlers/startup-'.$this->callType.'.php');

        }catch(Exception $e){
            throw new bException(tr('core::startup(): Failed'), $e);
        }
    }

    public function executedQuery($query_data){
        $this->register['debug_queries'][] = $query_data;
        return count($this->register['debug_queries']);
    }

    public function register($key, $value = null){
        if($value === null){
            return isset_get($this->register[$key]);
        }

        return $this->register[$key] = $value;
    }

    public function callType($type = null){
        if($type){
            switch($type){
                case 'http':
                    // FALLTHROUGH
                case 'admin':
                    // FALLTHROUGH
                case 'cli':
                    // FALLTHROUGH
                case 'mobile':
                    // FALLTHROUGH
                case 'ajax':
                    // FALLTHROUGH
                case 'api':
                    // FALLTHROUGH
                case 'amp':
                    // FALLTHROUGH
                case 404:
                    return false;

                default:
                    throw new bException(tr('core::callType(): Unknown call type ":type" specified', array(':type' => $type)), 'unknown');
            }

            return ($this->callType === $type);
        }

        return $this->callType;
    }
}



/*
 * Extend basic PHP exception to automatically add exception trace information
 * inside the exception objects
 */
class bException extends Exception{
    private $messages = array();
    private $data     = null;
    public  $code     = null;

    function __construct($messages, $code, $data = null){
        global $core;

        $messages = array_force($messages, "\n");

        if(is_object($code)){
            /*
             * Specified code is not a code but a previous exception. Get
             * history from previous exception and add new exception message
             */
            $e    = $code;
            $code = null;

            if($e instanceof bException){
                $this->messages = $e->getMessages();
                $this->data     = $e->getData();

            }else{
                if(!($e instanceof Exception)){
                    throw new bException(tr('bException: Specified exception object for exception ":message" is not valid (either not object or not an exception object)', array(':message' => $messages)), 'invalid');
                }

                $this->messages[] = $e->getMessage();
            }

            $orgmessage = $e->getMessage();
            $code       = $e->getCode();

        }else{
            if(!is_scalar($code)){
                throw new bException(tr('bException: Specified exception code ":code" for exception ":message" is not valid (either scalar, or an exception object)', array(':code' => $code, ':message' => $messages)), 'invalid');
            }

            $orgmessage = reset($messages);
            $this->data = $data;
        }

        if(!$messages){
            throw new Exception(tr('bException: No exception message specified in file ":file" @ line ":line"', array(':file' => current_file(1), ':line' => current_line(1))));
        }

        if(!is_array($messages)){
            $messages = array($messages);
        }

        try{
            /*
             * Only log to file if core is available and config_ok (configuration is loaded correclty)
             */
            if(!empty($core) and !empty($core->register['config_ok'])){
                foreach($messages as $message){
                    log_file($message, 'exceptions');
                }
            }

        }catch(Exception $f){
            /*
             * Exception database logging failed. Ignore, since from here on there is little to do
             */

// :TODO: Add notifications!
        }

        parent::__construct($orgmessage, null);
        $this->code = str_log($code);

        /*
         * If there are any more messages left, then add them as well
         */
        if($messages){
            foreach($messages as $id => $message){
                $this->messages[] = $message;
            }
        }
    }

    public function addMessage($message){
        $this->messages[] = $message;
        return $this;
    }

    public function setCode($code){
        $this->code = $code;
        return $this;
    }

    public function getMessages($separator = null){
        if($separator === null){
            return $this->messages;
        }

        return implode($separator, $this->messages);
    }

    public function getData(){
        return $this->data;
    }

    public function setData($data){
        $this->data = $data;
    }
}



/*
 * Convert all PHP errors in exceptions
 */
function php_error_handler($errno, $errstr, $errfile, $errline, $errcontext){
    return include(__DIR__.'/handlers/system_php_error_handler.php');
}



/*
 * Display a fatal error
 */
function uncaught_exception($e, $die = 1){
    return include(__DIR__.'/handlers/system_uncaught_exception.php');
}



/*
 * Display value if exists
 * IMPORTANT! After calling this function, $var will exist!
 */
function isset_get(&$variable, $return = null, $altreturn = null){
    if(isset($variable)){
        return $variable;
    }

    unset($variable);

    if($return === null){
        return $altreturn;
    }

    return $return;
}



/*
 * tr() is a translator marker function. It basic function is to tell the
 * translation system that the text within should be translated.
 *
 * Since text may contain data from either variables or function output, and
 * translators should not be burdened with copying variables or function calls,
 * all variable data should be identified in the text by a :marker, and the
 * :marker should be a key (with its value) in the $replace array.
 *
 * $replace values are always processed first by str_log() to ensure they are
 * readable texts, so the texts sent to tr() do NOT require str_log().
 *
 * On non production systems, tr() will perform a check on both the $text and
 * $replace data to ensure that all markers have been replaced, and non were
 * forgotten. If results were found, an exception will be thrown. This
 * behaviour does NOT apply to production systems
 */
function tr($text, $replace = null, $verify = true){
    global $_CONFIG;

    try{
        if($replace){
            foreach($replace as &$value){
                $value = str_log($value);
            }

            unset($value);

            $text = str_replace(array_keys($replace), array_values($replace), $text, $count);

            /*
             * Only on non production machines, crash when not all entries were replaced as an extra check.
             */
            if(empty($_CONFIG['production']) and $verify){
                if($count != count($replace)){
                    throw new bException('tr(): Not all specified keywords were found in text', 'notfound');
                }

                /*
                 * Do NOT check for :value here since the given text itself may contain :value (ie, in prepared statements!)
                 */
            }

            return $text;
        }

        return $text;

    }catch(Exception $e){
        /*
         * Do NOT use tr() here for obvious reasons!
         */
        throw new bException('tr(): Failed with text "'.str_log($text).'". Very likely issue with $replace not containing all keywords, or one of the $replace values is non-scalar', $e);
    }
}



/*
 * Cleanup string
 */
function cfm($source, $utf8 = true){
    try{
        if(!is_scalar($source)){
            if(!is_null($source)){
                throw new bException(tr('cfm(): Specified source ":source" from ":location" should be datatype "string" but has datatype ":datatype"', array(':source' => $source, ':datatype' => gettype($source), ':location' => current_file(1).'@'.current_line(1))), 'invalid');
            }
        }

        if($utf8){
            load_libs('utf8');
            return mb_trim(html_entity_decode(utf8_unescape(strip_tags(utf8_escape($source)))));
        }

        return mb_trim(html_entity_decode(strip_tags($source)));

    }catch(Exception $e){
        throw new bException(tr('cfm(): Failed with string ":string"', array(':string' => $source)), $e);
    }
// :TODO:SVEN:20130709: Check if we should be using mysqli_escape_string() or addslashes(), since the former requires SQL connection, but the latter does NOT have correct UTF8 support!!
//    return mysqli_escape_string(trim(decode_entities(mb_strip_tags($str))));
}



/*
 * Force integer
 */
function cfi($source, $allow_null = true){
    try{
        if(!$source and $allow_null){
            return null;
        }

        return (integer) $source;

    }catch(Exception $e){
        throw new bException(tr('cfi(): Failed with source ":source"', array(':source' => $source)), $e);
    }
}



/*
 * Force float
 */
function cf($source, $allow_null = true){
    try{
        if(!$source and $allow_null){
            return null;
        }

        return (float) $source;

    }catch(Exception $e){

    }
}



/*
 * Returns if site is running in debug mode or not
 */
function debug($class = null){
    global $_CONFIG;

    try{
        if(!is_array($_CONFIG['debug'])){
            throw new bException(tr('debug(): Invalid configuration, $_CONFIG[debug] is boolean, and it should be an array. Please check your config/ directory for "$_CONFIG[\'debug\']"'), 'invalid');
        }

        if($class === null){
            return (boolean) $_CONFIG['debug']['enabled'];
        }

        if($class === true){
            /*
             * Force debug to be true. This may be useful in production situations where some bug needs quick testing.
             */
            $_CONFIG['debug']['enabled'] = true;
            return true;
        }

        if(!isset($_CONFIG['debug'][$class])){
            throw new bException(tr('debug(): Unknown debug class ":class" specified', array(':class' => $class)), 'unknown');
        }

        return $_CONFIG['debug'][$class];

    }catch(Exception $e){
        throw new bException(tr('debug(): Failed'), $e);
    }
}



/*
 * Send notifications of the specified event to the specified class
 */
function notify($params){
    try{
        load_libs('notifications');
        return notifications_send($params);

    }catch(Exception $e){
        /*
         * Notification failed!
         *
         * Do NOT cause exception, because it its not caught, it might cause another notification, that will fail, cause exception and an endless loop!
         */
        log_file(tr('Failed to notify event ":event" for classes ":classes" with message ":message"', array(':event' => $event, ':classes' => $classes, ':message' => $message)), 'failed');
        return false;
    }
}



/*
 * Load specified library files
 */
function load_libs($libraries, $exception = true){
    global $_CONFIG, $core;

    try{
        if(defined('LIBS')){
            $libs = LIBS;

        }else{
            /*
             * LIBS is not defined yet. This may happen when load_libs() is
             * called during the startup sequence (for example, location
             * detection during startup sequence uses load_libs(). For now,
             * assume the same directory as this systems library file
             */
            $libs = slash(__DIR__);
        }

        foreach(array_force($libraries) as $library){
            if(!$library){
                if($exception){
                    throw new bException('load_libs(): Empty library specified', 'emptyspecified');
                }

            }else{
                include_once($libs.$library.'.php');
            }
        }

    }catch(Exception $e){
        throw new bException(tr('load_libs(): Failed to load one or more of libraries ":libraries"', array(':libraries' => $libraries)), $e);
    }
}



/*
 * Load specified configuration file
 */
function load_config($files = ''){
    global $_CONFIG;
    static $paths;

    try{
        if(!$paths){
            $paths = array(ROOT.'config/base/',
                           ROOT.'config/production',
                           ROOT.'config/'.ENVIRONMENT.'');
        }

        $files = array_force($files);

        foreach($files as $file){
            $loaded = false;
            $file   = trim($file);

            /*
             * Include first the default configuration file, if available, then
             * production configuration file, if available, and then, if
             * available, the environment file
             */
            foreach($paths as $id => $path){
                if(!$file){
                    /*
                     * Trying to load default configuration files again
                     */
                    if(!$id){
                        $path .= 'default.php';

                    }else{
                        $path .= '.php';
                    }

                }else{
                    if($id){
                        $path .= '_'.$file.'.php';

                    }else{
                        $path .= $file.'.php';
                    }
                }

                if(file_exists($path)){
                    include($path);
                    $loaded = true;
                }
            }

            if(!$loaded){
                throw new bException(tr('load_config(): No configuration file was found for requested configuration ":file"', array(':file' => $file)), 'not-found');
            }
        }

    }catch(Exception $e){
        throw new bException(tr('load_config(): Failed to load some or all of config file(s) ":file"', array(':file' => $files)), $e);
    }
}



/*
 * Execute shell commands with exception checks
 */
function safe_exec($commands, $ok_exitcodes = null, $route_errors = true){
    return include(__DIR__.'/handlers/system_safe_exec.php');
}



/*
 * Execute the specified script from the ROOT/scripts directory
 */
function script_exec($script, $arguments = null, $ok_exitcodes = null){
    return include(__DIR__.'/handlers/system_script_exec.php');
}



/*
 * Load html templates from disk
 */
function load_content($file, $replace = false, $language = null, $autocreate = null, $validate = true){
    global $_CONFIG, $core;

    try{
        load_libs('file');

        /*
         * Set default values
         */
        if($language === null){
            if($core->callType('cli')){
                $language = '';

            }else{
                $language = LANGUAGE.'/';
            }
        }

        if(!isset($replace['###SITENAME###'])){
            $replace['###SITENAME###'] = str_capitalize(isset_get($_SESSION['domain']));
        }

        if(!isset($replace['###DOMAIN###'])){
            $replace['###DOMAIN###']   = isset_get($_SESSION['domain']);
        }

        /*
         * Check if content file exists
         */
        $realfile = realpath(ROOT.'data/content/'.$language.cfm($file).'.html');

        if($realfile){
            /*
             * File exists, we're okay, get and return contents.
             */
            $retval = file_get_contents($realfile);
            $retval = str_replace(array_keys($replace), array_values($replace), $retval);

            /*
             * Make sure no replace markers are left
             */
            if($validate and preg_match('/###.*?###/i', $retval, $matches)){
                /*
                 * Oops, specified $from array does not contain all replace markers
                 */
                if(!$_CONFIG['production']){
                    throw new bException('load_content(): Missing markers "'.str_log($matches).'" for content file "'.str_log($realfile).'"', 'missingmarkers');
                }
            }

            return $retval;
        }

        $realfile = ROOT.'data/content/'.cfm($language).'/'.cfm($file).'.html';

        /*
         * From here, the file does not exist.
         */
        if(!$_CONFIG['production']){
            notify('content-file-missing', tr('Content file ":file" is missing', array(':file' => $realfile)), 'developers');
            return '';
        }

        if($autocreate === null){
            $autocreate = $_CONFIG['content']['autocreate'];
        }

        if(!$autocreate){
            throw new bException('load_content(): Specified file "'.str_log($file).'" does not exist for language "'.str_log($language).'"', 'not-exist');
        }

        /*
         * Make content directory exists
         */
        file_ensure_path(dirname($realfile));

        $default  = 'File created '.$file.' by '.realpath(PWD.$_SERVER['PHP_SELF'])."\n";
        $default .= print_r($replace, true);

        file_put_contents($realfile, $default);

        if($replace){
            return str_replace(array_keys($replace), array_values($replace), $default);
        }

        return $default;

    }catch(Exception $e){
        notify('error', "LOAD_CONTENT() FAILED [".$e->getCode()."]\n".implode("\n", $e->getMessages()));
        error_log("LOAD_CONTENT() FAILED [".$e->getCode()."]\n".implode("\n", $e->getMessages()));

        switch($e->getCode()){
            case 'notexist':
                log_database('load_content(): File "'.cfm($language).'/'.cfm($file).'" does not exist', 'warning');
                break;

            case 'missingmarkers':
                log_database('load_content(): File "'.cfm($language).'/'.cfm($file).'" still contains markers after replace', 'warning');
                break;

            case 'searchreplacecounts':
                log_database('load_content(): Search count does not match replace count', 'warning');
                break;
        }

        throw new bException(tr('load_content(): Failed for file ":file"', array(':file' => $file)), $e);
    }
}



/*
 * Log specified message to console, but only if we are in console mode!
 */
function log_console($messages = '', $color = null, $newline = true, $filter_double = false){
    static $c, $last;

    try{
        if(!PLATFORM_CLI){
            /*
             * If not on CLI, then log to file
             */
            return log_file($messages, SCRIPT);
        }

        switch(str_until($color, '/')){
            case 'VERBOSE':
                if(!VERBOSE){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the VERBOSE
                 */
                $color = str_replace('/', '', str_replace('VERBOSE', '', $color));
                break;

            case 'QUIET':
                if(QUIET){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the QUIET
                 */
                $color = str_replace('/', '', str_replace('QUIET', '', $color));
                break;

            case 'DOT':
                if(!VERBOSE){
                    if(PLATFORM_CLI){
                        /*
                         * Only show a dot instead of the text
                         */
                        return cli_dot('.', str_replace('DOT', '', $color));
                    }
                }

                /*
                 * Remove the VERBOSE
                 */
                $color = str_replace('/', '', str_replace('VERBOSE', '', $color));
                break;

            case 'DEBUG':
                if(!debug()){
                    /*
                     * Only log this if we're in debug mode
                     */
                    return false;
                }

                /*
                 * Remove the QUIET
                 */
                $color = str_replace('/', '', str_replace('DEBUG', '', $color));
        }

        if(($filter_double == true) and ($messages == $last)){
            /*
            * We already displayed this message, skip!
            */
            return false;
        }

        $last = $messages;

        if(is_object($messages)){
            if($color){
                $messages->setCode($color);
            }

            if($messages instanceof bException){
                if(str_until($messages->getCode(), '/') === 'warning'){
                    $messages = array($messages->getMessage());
                    $color    = 'warning';

                }else{
                    $messages = $messages->getMessages();
                    $color    = 'error';
                }

            }elseif($messages instanceof Exception){
                $messages = array($messages->getMessage());

            }else{
                $messages = $messages->__toString();
            }

        }elseif(!is_array($messages)){
            $messages = array($messages);
        }

        if($color){
            if(defined('NOCOLOR') and !NOCOLOR){
                if(empty($c)){
                    if(!class_exists('Colors')){
                        /*
                         * This log_console() was called before the "cli" library
                         * was loaded. Show the line without color
                         */
                        $color = '';

                    }else{
                        $c = new Colors();
                    }
                }
            }

            switch($color){
                case 'yellow':
                    // FALLTHROUGH
                case 'warning':
                    // FALLTHROUGH
                case 'red':
                    // FALLTHROUGH
                case 'error':
                    $error = true;
            }
        }

        foreach($messages as $message){
            if($color and defined('NOCOLOR') and !NOCOLOR){
                $message = $c->getColoredString($message, $color);
            }

            if(QUIET){
                $message = trim($message);
            }

            $message = stripslashes(br2nl($message)).($newline ? "\n" : '');

            if(empty($error)){
                echo $message;

            }else{
                /*
                 * Log to STDERR instead of STDOUT
                 */
                fwrite(STDERR, $message);
            }
        }

        return $message;

    }catch(Exception $e){
        throw new bException('log_console(): Failed', $e, array('message' => $message));
    }
}



/*
 * Log specified message to database, but only if we are in console mode!
 */
function log_database($messages, $type = 'unknown'){
    global $core;
    static $q, $last, $busy;

    try{
        switch(str_until($type, '/')){
            case 'VERBOSE':
                if(!VERBOSE){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the VERBOSE
                 */
                $type = str_replace('/', '', str_replace('VERBOSE', '', $type));
                break;

            case 'QUIET':
                if(QUIET){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the QUIET
                 */
                $type = str_replace('/', '', str_replace('QUIET', '', $type));
                break;

            case 'DEBUG':
                if(!debug()){
                    /*
                     * Only log this if we're in debug mode
                     */
                    return false;
                }

                /*
                 * Remove the QUIET
                 */
                $type = str_replace('/', '', str_replace('DEBUG', '', $type));
        }

        /*
         * Avoid endless looping if the database log fails
         */
        if(!$busy){
            $busy = true;

            if(!empty($core->register['no-db'])){
                /*
                 * Don't log to DB, there is no DB
                 */
                return $messages;
            }

            if(is_object($messages)){
                if($messages instanceof bException){
                    $messages = $messages->getMessages();
                    $type     = 'exception';
                }

                if($messages instanceof Exception){
                    $messages = $messages->getMessage();
                    $type     = 'exception';
                }
            }

            if($messages == $last){
                /*
                * We already displayed this message, skip!
                */
                return $messages;
            }

            $last = $messages;

            if(is_numeric($type)){
                throw new bException(tr('log_database(): Type is ":type" for message ":message" is numeric but should not be numeric', array(':type' => $type, ':message' => $message)));
            }

            foreach(array_force($messages, "\n") as $message){
                sql_query('INSERT INTO `log` (`createdby`, `ip`, `type`, `message`)
                           VALUES            (:createdby , :ip , :type , :message )',

                           array(':createdby' => isset_get($_SESSION['user']['id']),
                                 ':ip'        => isset_get($_SERVER['REMOTE_ADDR']),
                                 ':type'      => cfm($type),
                                 ':message'   => $message));
            }

            $busy = false;
        }

        return $messages;

    }catch(Exception $e){
//log_database($e);
// :TODO: Add Notifications!
        log_file(tr('log_database(): Failed to log message ":message" to database', array(':message' => $messages)), 'error');

        /*
         * Don't exception here because the exception may cause another log_database() call and loop endlessly
         * Don't try to log again!
         */
        $core->register['no-db'] = true;
    }
}



/*
 * Log specified message to file.
 */
function log_file($messages, $class = 'messages', $type = null){
    global $_CONFIG;
    static $h = array(), $last;

    try{
        switch(str_until($class, '/')){
            case 'VERBOSE':
                if(!VERBOSE){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the VERBOSE
                 */
                $class = str_replace('/', '', str_replace('VERBOSE', '', $class));
                break;

            case 'QUIET':
                if(QUIET){
                    /*
                     * Only log this if we're in verbose mode
                     */
                    return false;
                }

                /*
                 * Remove the QUIET
                 */
                $class = str_replace('/', '', str_replace('QUIET', '', $class));
                break;

            case 'DEBUG':
                if(!debug()){
                    /*
                     * Only log this if we're in debug mode
                     */
                    return false;
                }

                /*
                 * Remove the QUIET
                 */
                $class = str_replace('/', '', str_replace('DEBUG', '', $class));
        }

        if($messages == $last){
            /*
            * We already displayed this message, skip!
            */
            return;
        }

        $last = $messages;

        if(is_object($messages)){
            if($messages instanceof bException){
                $messages = $messages->getMessages();

                if($type == 'unknown'){
                    $type = 'exception';
                }
            }

            if($messages instanceof Exception){
                $messages = $messages->getMessage();

                if($type == 'unknown'){
                    $type = 'exception';
                }
            }
        }

        if(!is_scalar($class)){
            load_libs('json');
            throw new bException(tr('log_file(): Specified class ":class" is not scalar', array(':class' => str_truncate(json_encode_custom($class), 20))));
        }

        if(empty($h[$class])){
            load_libs('file');
            file_ensure_path(ROOT.'data/log');

            $h[$class] = fopen(slash(ROOT.'data/log').$class, 'a+');
        }

        $messages = array_force($messages, "\n");
        $date     = new DateTime();
        $date     = $date->format('Y/m/d H:i:s');

        foreach($messages as $key => $message){
            $type = ($type ? '['.$type.'] ' : '');

            if($key and (count($messages) > 1)){
                fwrite($h[$class], $date.' '.$type.$key.' => '.$message."\n");

            }else{
                fwrite($h[$class], $date.' '.$type.$message."\n");
            }
        }

        return $messages;

    }catch(Exception $e){
        throw new bException('log_file(): Failed', $e, array('message' => $messages));
    }
}



/*
 * Keep track of statistics
 */
function add_stat($code, $count = 1, $details = '') {
    global $_CONFIG;

    try{
        if(empty($_CONFIG['statistics']['enabled'])){
            /*
             * Statistics has been disabled
             */
            return false;
        }

        if($count > 0) {
            sql_query('INSERT INTO `statistics` (`code`          , `count`        , `statdate`)
                       VALUES                   ("'.cfm($code).'", '.cfi($count).','.date('d', time()).')

                       ON DUPLICATE KEY UPDATE `count` = `count` + '.cfi($count).';');
        }

        error_log($_SESSION['domain'].'-'.str_log($code).($details ? ' "'.str_log($details).'"' : ''));

    }catch(Exception $e){
        throw new bException('add_stat(): Failed', $e);
    }
}



/*
 * Calculate the hash value for the given password with the (possibly) given
 * algorithm
 */
function password($source, $algorithm, $add_meta = true){
    return get_hash($source, $algorithm, $add_meta );
}

function get_hash($source, $algorithm, $add_meta = true){
    global $_CONFIG;

    try{
        try{
            $source = hash($algorithm, SEED.$source);

        }catch(Exception $e){
            if(strstr($e->getMessage(), 'Unknown hashing algorithm')){
                throw new bException(tr('get_hash(): Unknown hash algorithm ":algorithm" specified', array(':algorithm' => $algorithm)), 'unknown-algorithm');
            }

            throw $e;
        }

        if($add_meta){
            return '*'.$algorithm.'*'.$source;
        }

        return $source;

    }catch(Exception $e){
        throw new bException('get_hash(): Failed', $e);
    }
}



/*
 * Return complete domain with HTTP and all
 */
function domain($current_url = false, $query = null, $root = null, $domain = null, $language = null){
    global $_CONFIG;

    try{
        if(!$domain){
            //if(empty($_SESSION['domain'])){
            //    if(PLATFORM_HTTP){
            //        throw new bException(tr('domain(): $_SESSION[\'domain\'] is not configured'), 'not-specified');
            //    }
            //
            //    $_SESSION['domain'] = $_CONFIG['domain'];
            //}
            //
            //if($_SESSION['domain'] == 'auto'){
            //    $_SESSION['domain'] = $_SERVER['SERVER_NAME'];
            //}

            $domain = $_CONFIG['domain'];

        }elseif($domain === true){
            /*
             * Use current domain name
             */
            $domain = $_SERVER['SERVER_NAME'];
        }

        if(empty($_CONFIG['language']['supported'])){
            $language = '';

        }else{
            /*
             * Multilingual site
             */
            if($language === null){
                $language = LANGUAGE;

            }else{
                /*
                 * Ensure language is an empty string
                 */
                $language = '';
            }
        }

        if($language){
            /*
             * This is a multilingual website. Ensure language is supported and
             * add language selection to the URL.
             */
            if(empty($_CONFIG['language']['supported'][$language])){
                $language = $_CONFIG['language']['default'];
                notify('unsupported-language-specified', 'developers', 'domain(): The specified language ":language" is not supported', array(':language' => $language));
            }

            $language .= '/';
        }

        if($root === null){
            $root = $_CONFIG['root'];
        }

        if(!$current_url){
            $retval = $_CONFIG['protocol'].slash($domain).$language.$root;

        }elseif($current_url === true){
            $retval = $_CONFIG['protocol'].$domain.$_SERVER['REQUEST_URI'];

        }else{
            if($root){
                $root = str_starts_not(str_ends($root, '/'), '/');
            }

            $retval = $_CONFIG['protocol'].slash($domain).$language.$root.str_starts_not($current_url, '/');
        }

        if($query){
            load_libs('inet');
            $retval = url_add_query($retval, $query);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('domain(): Failed', $e);
    }
}



/*
 * Returns true if the current session user has the specified right
 * This function will automatically load the rights for this user if
 * they are not yet in the session variable
 */
function has_rights($rights, &$user = null){
    global $_CONFIG;

    try{
        if($user === null){
            if(empty($_SESSION['user'])){
                /*
                 * No user specified and there is no session user either,
                 * so there are absolutely no rights at all
                 */
                return false;
            }

            $user = &$_SESSION['user'];

        }elseif(!is_array($user)){
            throw new bException(tr('has_rights(): Specified user is not an array'), 'invalid');
        }

        /*
         * Dynamically load the user rights
         */
        if(empty($user['rights'])){
            if(empty($user)){
                /*
                 * There is no user, so there are no rights at all
                 */
                return false;
            }

            load_libs('user');
            $user['rights'] = user_load_rights($user);
        }

        if(empty($rights)){
            throw new bException('has_rights(): No rights specified');
        }

        if(!empty($user['rights']['god'])){
            return true;
        }

        foreach(array_force($rights) as $right){
            if(empty($user['rights'][$right]) or !empty($user['rights']['devil']) or !empty($fail)){
                if((PLATFORM_CLI) and VERBOSE){
                    load_libs('user');
                    log_console(tr('has_rights(): Access denied for user ":user" in page ":page" for missing right ":right"', array(':user' => name($_SESSION['user']), ':page' => $_SERVER['PHP_SELF'], ':right' => $right)), 'yellow');
                }

                return false;
            }
        }

        return true;

    }catch(Exception $e){
        throw new bException('has_rights(): Failed', $e);
    }
}






/*
 * Returns true if the current session user has the specified group
 * This function will automatically load the groups for this user if
 * they are not yet in the session variable
 */
function has_groups($groups, &$user = null){
    global $_CONFIG;

    try{
        if($user === null){
            if(empty($_SESSION['user'])){
                /*
                 * No user specified and there is no session user either,
                 * so there are absolutely no groups at all
                 */
                return false;
            }

            $user = &$_SESSION['user'];

        }elseif(!is_array($user)){
            throw new bException(tr('has_groups(): Specified user is not an array'), 'invalid');
        }

        /*
         * Dynamically load the user groups
         */
        if(empty($user['groups'])){
            if(empty($user)){
                /*
                 * There is no user, so there are no groups at all
                 */
                return false;
            }

            load_libs('user');
            $user['groups'] = user_load_groups($user);
        }

        if(empty($groups)){
            throw new bException('has_groups(): No groups specified');
        }

        if(!empty($user['rights']['god'])){
            return true;
        }

        foreach(array_force($groups) as $group){
            if(empty($user['groups'][$group]) or !empty($user['rights']['devil']) or !empty($fail)){
                if((PLATFORM_CLI) and VERBOSE){
                    load_libs('user');
                    log_console(tr('has_groups(): Access denied for user ":user" in page ":page" for missing group ":group"', array(':user' => name($_SESSION['user']), ':page' => $_SERVER['PHP_SELF'], ':group' => $group)), 'yellow');
                }

                return false;
            }
        }

        return true;

    }catch(Exception $e){
        throw new bException('has_groups(): Failed', $e);
    }
}



/*
 * Either a user is logged in or the person will be redirected to the specified URL
 */
function user_or_signin(){
    global $_CONFIG, $core;

    try{
        if(PLATFORM_HTTP){
            if(empty($_SESSION['user']['id'])){
                /*
                 * No session
                 */
                if($core->callType('api') or $core->callType('ajax')){
                    json_reply(tr('Specified token ":token" has no session', array(':token' => $_POST['PHPSESSID'])), 'signin');

                }else{
                    html_flash_set('Unauthorized: PLease sign in to continue');
                    redirect(isset_get($_CONFIG['redirects']['signin'], 'signin.php').'?redirect='.urlencode($_SERVER['REQUEST_URI']), 302);
                }
            }

            if(!empty($_SESSION['force_page'])){
                /*
                 * Session is, but locked
                 * Redirect all pages EXCEPT the lock page itself!
                 */
                if(empty($_CONFIG['redirects'][$_SESSION['force_page']])){
                    throw new bException(tr('user_or_signin(): Forced page ":page" does not exist in $_CONFIG[redirects]', array(':page' => $_SESSION['force_page'])), 'not-exist');
                }

                if($_CONFIG['redirects'][$_SESSION['force_page']] !== str_until(str_rfrom($_SERVER['REQUEST_URI'], '/'), '?')){
                    redirect($_CONFIG['redirects'][$_SESSION['force_page']].'?redirect='.urlencode($_SERVER['REQUEST_URI']));
                }
            }

            /*
             * Is user restricted to a page? if so, keep him there
             */
            if(empty($_SESSION['lock']) and !empty($_SESSION['user']['redirect'])){
                if(str_from($_SESSION['user']['redirect'], '://') != $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']){
                    redirect($_SESSION['user']['redirect']);
                }
            }
        }

        return $_SESSION['user'];

    }catch(Exception $e){
        throw new bException('user_or_signin(): Failed', $e);
    }
}



/*
 * The current user has the specified rights, or will be redirected or get shown "access denied"
 */
function rights_or_access_denied($rights, $url = null){
    global $_CONFIG;

    try{
        if(!$rights){
            return true;
        }

        user_or_signin();

        if(PLATFORM_CLI or has_rights($rights)){
            return $_SESSION['user'];
        }

        if(in_array('admin', array_force($rights))){
            redirect(isset_get($url, $_CONFIG['redirects']['signin']));
        }

        page_show(403);

    }catch(Exception $e){
        throw new bException('rights_or_access_denied(): Failed', $e);
    }
}



/*
 * The current user has the specified groups, or will be redirected or get shown "access denied"
 */
function groups_or_access_denied($groups){
    global $_CONFIG;

    try{
        user_or_signin();

        if(PLATFORM_CLI or has_groups($groups)){
            return $_SESSION['user'];
        }

        if(in_array('admin', array_force($groups))){
            redirect($_CONFIG['redirects']['signin']);
        }

        page_show($_CONFIG['redirects']['access-denied']);

    }catch(Exception $e){
        throw new bException('groups_or_access_denied(): Failed', $e);
    }
}



/*
 * Either a user is logged in or  the person will be shown specified page.
 */
function user_or_page($page){
    if(empty($_SESSION['user'])){
        page_show($page);
        return false;
    }

    return $_SESSION['user'];
}



/*
 * Return $with_rights if the current user has the specified rights
 * Return $without_rights if not
 */
function return_with_rights($rights, $with_rights, $without_rights = null){
    try{
        if(has_rights($rights)){
            return $with_rights;
        }

        return $without_rights;

    }catch(Exception $e){
        throw new bException('return_with_rights(): Failed', $e);
    }
}



/*
 * Return $with_groups if the current user is member of the specified groups
 * Return $without_groups if not
 */
function return_with_groups($groups, $with_groups, $without_groups = null){
    try{
        if(has_groups($groups)){
            return $with_groups;
        }

        return $without_groups;

    }catch(Exception $e){
        throw new bException('return_with_groups(): Failed', $e);
    }
}



/*
 * Read extended signin
 */
function check_extended_session() {
    global $_CONFIG;

    try{
        if(empty($_CONFIG['sessions']['extended'])) {
            return false;
        }

// :TODO: Clean garbage
        //if($api === null){
        //    $api = (strtolower(substr($_SERVER['SCRIPT_NAME'], 0, 5)) == '/api/');
        //}

        if(isset($_COOKIE['extsession']) and !isset($_SESSION['user'])) {
            /*
             * Pull  extsession data
             */
            $ext = sql_get('SELECT `users_id` FROM `extended_sessions` WHERE `session_key` = "'.cfm($_COOKIE['extsession']).'" AND DATE(`addedon`) < DATE(NOW());');

            if($ext['users_id']) {
                $user = sql_get('SELECT * FROM `users` WHERE `users`.`id` = '.cfi($ext['users_id']).';');

                if($user['id']) {
                    /*
                     * sign in user
                     */
                    load_libs('user');
                    user_signin($user, true);

                    //if(!$api){
                    //    redirect($_SERVER['REQUEST_URI']);
                    //}

                } else {
                    /*
                     * Remove cookie
                     */
                    setcookie('extsession', 'stub', 1);
                }

            } else {
                /*
                 * Remove cookie
                 */
                setcookie('extsession', 'stub', 1);
            }
        }

    }catch(Exception $e){
        throw new bException('user_create_extended_session(): Failed', $e);
    }
}



/*
 * Return the first non empty argument
 */
function not_empty(){
    foreach(func_get_args() as $argument){
        if($argument){
            return $argument;
        }
    }
}



/*
 * Return the first non null argument
 */
function not_null(){
    foreach(func_get_args() as $argument){
        if($argument === null) continue;
        return $argument;
    }
}



/*
 * Return the first non empty argument
 */
function pick_random($count){
    try{
        $args = func_get_args();

        /*
         * Remove the $count argument from the list
         */
        array_shift($args);

        if(!$count){
            /*
             * Get a random count
             */
            $count = mt_rand(1, count($args));
            $array = true;
        }

        if(($count < 1) or ($count > count($args))){
            throw new bException(tr('pick_random(): Invalid count ":count" specified for ":args" arguments', array(':count' => $count, ':args' => count($args))), 'invalid');

        }elseif($count == 1){
            if(empty($array)){
                return $args[array_rand($args, $count)];
            }

            return array($args[array_rand($args, $count)]);

        }else{
            $retval = array();

            for($i = 0; $i < $count; $i++){
                $retval[] = $args[$key = array_rand($args)];
                unset($args[$key]);
            }

            return $retval;
        }

    }catch(Exception $e){
        throw new bException(tr('pick_random(): Failed'), $e);
    }
}



/*
 * Return display status for specified status
 */
function status($status, $list = null){
    if(is_array($list)){
        /*
         * $list contains list of possible statusses
         */
        if(isset($list[$status])){
            return $list[$status];
        }


        return 'Unknown';
    }

    if($status === null){
        if($list){
            /*
             * Alternative name specified
             */
            return $list;
        }

        return 'Ok';
    }

    return str_capitalize($status);
}



/*
 * Update the session with values directly from $_REQUEST
 */
function session_request_register($key, $valid = null){
    try{
        $_SESSION[$key] = isset_get($_REQUEST[$key], isset_get($_SESSION[$key]));

        if($valid){
            /*
             * Only accept values in this valid list (AND empty!)
             * Invalid values will be set to null
             */
            if(!in_array($_SESSION[$key], array_force($valid))){
                $_SESSION[$key] = null;
            }
        }

        if(empty($_SESSION[$key])){
            unset($_SESSION[$key]);
            return null;
        }

        return $_SESSION[$key];

    }catch(Exception $e){
        throw new bException('session_request_register(): Failed', $e);
    }
}



/*
 * Will return $return if the specified item id is in the specified source.
 */
function in_source($source, $key, $return = true){
    try{
        if(!is_array($source)){
            throw new bException(tr('in_source(): Specified source ":source" should be an array', array(':source' => $source)), 'invalid');
        }

        if(isset_get($source[$key])){
            return $return;
        }

        return '';

    }catch(Exception $e){
        throw new bException('in_source(): Failed', $e);
    }
}



/*
 *
 */
function is_natural($number, $start = 1){
    try{
        if(!is_numeric($number)){
            return false;
        }

        if($number < $start){
            return false;
        }

        if($number != (integer) $number){
            return false;
        }

        return true;

    }catch(Exception $e){
        throw new bException('is_natural(): Failed', $e);
    }
}



/*
 *
 */
function is_new($entry){
    try{
        if(!is_array($entry)){
            throw new bException(tr('is_new(): Specified entry is not an array'), 'invalid');
        }

        if(isset_get($entry['status']) === '_new'){
            return true;
        }

        if(isset_get($entry['id']) === null){
            return true;
        }

        return false;

    }catch(Exception $e){
        throw new bException('is_new(): Failed', $e);
    }
}



/*
 *
 */
function force_natural($number, $default = 1, $start = 1){
    try{
        if(!is_numeric($number)){
            return (integer) $default;
        }

        if($number < $start){
            return (integer) $default;
        }

        if(!is_int($number)){
            return (integer) round($number);
        }

        return (integer) $number;

    }catch(Exception $e){
        throw new bException('force_natural(): Failed', $e);
    }
}



/*
 * Return a code that is guaranteed unique
 */
function unique_code($hash = 'sha512'){
    global $_CONFIG;

    try{
        return hash($hash, uniqid('', true).microtime(true).$_CONFIG['security']['seed']);

    }catch(Exception $e){
        throw new bException('unique_code(): Failed', $e);
    }
}



/*
 * Return deploy configuration for the specified environment
 */
function get_config($file = null, $environment = null){
    try{
        if(!$environment){
            $environment = ENVIRONMENT;
        }

        $_CONFIG = array('deploy' => array());

        if($file){
            $file = '_'.$file;

            if(file_exists(ROOT.'config/'.$file.'.php')){
                include(ROOT.'config/'.$file.'.php');
            }

        }else{
            include(ROOT.'config/base/default.php');
        }

        if(file_exists(ROOT.'config/production'.$file.'.php')){
            include(ROOT.'config/production'.$file.'.php');
        }

        if(file_exists(ROOT.'config/'.$environment.$file.'.php')){
            include(ROOT.'config/'.$environment.$file.'.php');
        }

        return $_CONFIG;

    }catch(Exception $e){
        throw new bException('get_config(): Failed', $e);
    }
}



/*
 *
 */
function name($user = null, $key_prefix = '', $default = null){
    try{
        if($user){
            if($key_prefix){
                $key_prefix = str_ends($key_prefix, '_');
            }

            if(is_scalar($user)){
                if(!is_numeric($user)){
                    /*
                     * String, assume its a username
                     */
                    return $user;
                }

                /*
                 * This is not a user assoc array, but a user ID.
                 * Fetch user data from DB, then treat it as an array
                 */
                if(!$user = sql_get('SELECT `nickname`, `name`, `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $user))){
                   throw new bException('name(): Specified user id ":id" does not exist', array(':id' => str_log($user)), 'not-exist');
                }
            }

            if(!is_array($user)){
                throw new bException(tr('name(): Invalid data specified, please specify either user id, name, or an array containing username, email and or id'), 'invalid');
            }

            $user = not_empty(isset_get($user[$key_prefix.'nickname']), isset_get($user[$key_prefix.'name']), isset_get($user[$key_prefix.'username']), isset_get($user[$key_prefix.'email']));
            $user = trim($user);

            if($user){
                return $user;
            }
        }

        if($default === null){
            $default = tr('Guest');
        }

        /*
         * No user data found, assume guest user.
         */
        return $default;

    }catch(Exception $e){
        throw new bException(tr('name(): Failed'), $e);
    }
}



/*
 * Show the specified page
 */
function page_show($pagename, $params = null){
    global $_CONFIG, $core;

    try{
        array_params($params, 'message');
        array_default($params, 'exists', false);

        if(!empty($core->callType('ajax'))){
            if($params['exists']){
                return file_exists(ROOT.'www/'.LANGUAGE.'/ajax/'.$pagename.'.php');
            }

            /*
             * Execute ajax page
             */
            return include(ROOT.'www/'.LANGUAGE.'/ajax/'.$pagename.'.php');

        }elseif(!empty($core->callType('admin'))){
            $prefix = 'admin/';

        }else{
            $prefix = '';
        }

        if($params['exists']){
            return file_exists(ROOT.'www/'.LANGUAGE.'/'.$prefix.$pagename.'.php');
        }

        $result = include(ROOT.'www/'.LANGUAGE.'/'.$prefix.$pagename.'.php');

        if(isset_get($params['return'])){
            return $result;
        }

        die();

    }catch(Exception $e){
        throw new bException(tr('page_show(): Failed to show page ":page"', array(':page' => $pagename)), $e);
    }
}



/*
 * Throw an "under construction" exception
 */
function under_construction($functionality = ''){
    if($functionality){
        throw new bException(tr('The functionality ":f" is under construction!', array(':f' => $functionality)), 'under-construction');
    }

    throw new bException(tr('This function is under construction!'), 'under-construction');
}



/*
 * Return NULL if specified variable is considered "empty", like 0, "", array(), etc.
 * If not, return the specified variable unchanged
 */
function get_null($source){
    try{
        if($source){
            return $source;
        }

        return null;

    }catch(Exception $e){
        throw new bException(tr('get_null(): Failed'), $e);
    }
}



/*
 * Ensure that the specifed library is installed. If not, install it before
 * continuing
 */
function ensure_installed($params){
    try{
        array_params($params);
        array_default($params, 'checks', null);
        array_default($params, 'name'  , null);

        /*
         * Check if specified library is installed
         */
        if(!$params['name']){
            throw new bException(tr('ensure_installed(): No name specified for library'), 'not-specified');
        }

        if(!$params['checks']){
            throw new bException(tr('ensure_installed(): No checks specified for library with checks":checks"', array(':checks' => $params['checks'])), 'not-specified');
        }

        foreach(array_force($params['checks']) as $path){
            if(!file_exists($path)){
                $fail = true;
            }
        }

        if(!empty($fail)){
            load_libs('install');

            if(empty($params['callback'])){
                return install($params);
            }

            return $params['callback']($params);
        }

    }catch(Exception $e){
        throw new bException(tr('ensure_installed(): Failed'), $e);
    }
}



/*
 *
 */
function ensure_value($value, $enum, $default){
    try{
        if(in_array($value, $enum)){
           return $value;
        }

        return $default;

    }catch(Exception $e){
        throw new bException(tr('ensure_value(): Failed'), $e);
    }
}



/*
 * Disconnect from webserver but continue working
 */
function disconnect(){
    try{
        switch(php_sapi_name()){
            case 'fpm-fcgi':
                fastcgi_finish_request();
                break;

            case '':
                throw new bException(tr('disconnect(): No SAPI detected'), 'unknown');

            default:
                throw new bException(tr('disconnect(): Unknown SAPI ":sapi" detected', array(':sapi' => php_sapi_name())), 'unknown');
        }

    }catch(Exception $e){
        throw new bException(tr('disconnect(): Failed'), $e);
    }
}



/*
 *
 */
function session_reset_domain(){
    global $_CONFIG;

    try{
        $domain = cfm($_SERVER['HTTP_HOST']);

        switch(true){
            case ($_CONFIG['whitelabels']['enabled'] === false):
                /*
                 * white label domains are disabled, so the detected domain MUST match the configured domain
                 */
                if($domain !== $_CONFIG['domain']){
                    $domain = null;
                }

                break;

            case ($_CONFIG['whitelabels']['enabled'] === 'sub'):
                /*
                 * white label domains are disabled, but sub domains from the $_CONFIG[domain] are allowed
                 */
                $length = strlen($_CONFIG['domain']);

                if(substr($domain, -$length, $length) !== $_CONFIG['domain']){
                    $domain = null;
                }

                break;

            case ($_CONFIG['whitelabels']['enabled'] === 'all'):
                /*
                 * Permit whichever domain
                 */
                break;

            default:
                /*
                 * Check the detected domain against the configured domain.
                 * If it doesnt match then check if its a registered whitelabel domain
                 */
                if($domain !== $_CONFIG['domain']){
                    $domain = sql_get('SELECT `domain` FROM `whitelabels` WHERE `domain` = :domain AND `status` IS NULL', 'domain', array(':domain' => $_SERVER['HTTP_HOST']));
                }
        }

        if(!$domain){
            redirect($_CONFIG['protocol'].$_CONFIG['domain']);
        }

        $_SESSION['domain'] = $domain;

    }catch(Exception $e){
        throw new bException(tr('set_session_domain(): Failed'), $e);
    }
}



// :DELETE: WTF does this do, where would it possibly be useful?
/*
 * Callback funtion
 */
function execute_callback($callback_name, $params = null){
    try{
        if(is_callable($callback_name)){
            return $callback_name($params);
        }

        return $params;

    }catch(Exception $e){
        throw new bException(tr('execute_callback(): Failed'), $e);
    }
}



/*
 *
 */
function get_boolean($value){
    try{
        switch(strtolower($value)){
            case 'off':
                return false;

            case 'on':
                return true;

            case 'true':
                return true;

            case 'false':
                return false;

            case '1':
                return true;

            case '0':
                return false;

            default:
                throw new bException(tr('get_boolean(): Unknown value ":value"', array(':value' => $value)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException(tr('get_boolean(): Failed'), $e);
    }
}



/*
 *
 */
function language_lock($language, $script = null){
    global $core;

    static $checked   = false;
    static $incorrect = false;

    try{
        if(is_array($script)){
            /*
             * Script here will contain actually a list of all scripts for
             * each language. This can then be used to determine the name
             * of the script in the correct language to build linksx
             */
            $core->register['scripts'] = $script;
        }

        /*
         *
         */
        if(!$checked){
            $checked = true;

            if($language and (LANGUAGE !== $language)){
                $incorrect = true;
            }
        }

        if(!is_array($script)){
            /*
             * Show the specified script, it will create the content for
             * this SCRIPT
             */
            page_show($script);
        }

        /*
         * Script and language match, continue
         */
        if($incorrect){
            page_show(404);
        }

    }catch(Exception $e){
        throw new bException(tr('language_lock(): Failed'), $e);
    }
}



/*
 * Read value for specified key from $_SESSION[cache][$key]
 *
 * If $_SESSION[cache][$key] does not exist, then execute the callback and
 * store the resulting value in $_SESSION[cache][$key]
 */
function session_cache($key, $callback){
    try{
        if(empty($_SESSION)){
            return null;
        }

        if(!isset($_SESSION['cache'])){
            $_SESSION['cache'] = array();
        }

        if(!isset($_SESSION['cache'][$key])){
            $_SESSION['cache'][$key] = $callback();
        }

        return $_SESSION['cache'][$key];

    }catch(Exception $e){
        throw new bException(tr('session_cache(): Failed'), $e);
    }
}



/*
 * BELOW ARE FUNCTIONS FROM EXTERNAL LIBRARIES THAT ARE INCLUDED IN STARTUP BECAUSE THEY ARE USED BEFORE THOSE OTHER LIBRARIES ARE LOADED
 */



/*
 * Adds the required amount of copies of the specified file to random CDN servers
 */
function cdn_add_files($files, $section = 'pub', $group = null, $delete = true){
    global $_CONFIG;

    try{
        if(!$_CONFIG['cdn']['enabled']){
            return false;
        }

        log_file(tr('cdn_add_files(): Adding files ":files"', array(':files' => $files)), 'DEBUG/cdn');

        if(!$section){
            throw new bException(tr('cdn_add_files(): No section specified'), 'not-specified');
        }

        if(!$files){
            throw new bException(tr('cdn_add_files(): No files specified'), 'not-specified');
        }

        /*
         * In what servers are we going to store these files?
         */
        $servers     = cdn_assign_servers();
        $file_insert = sql_prepare('INSERT IGNORE INTO `cdn_files` (`servers_id`, `section`, `group`, `file`)
                                    VALUES                         (:servers_id , :section , :group , :file )');

        /*
         * Register at what CDN servers the files will be uploaded, and send the
         * files there
         */
        foreach($servers as $servers_id => $server){
            foreach($files as $url => $file){
                log_file(tr('cdn_add_files(): Added file ":file" with url ":url" to CDN server ":server"', array(':file' => $file, ':url' => $url, ':server' => $server)), 'DEBUG/cdn');

                $file_insert->execute(array(':servers_id' => $servers_id,
                                            ':section'    => $section,
                                            ':group'      => $group,
                                            ':file'       => str_starts($url, '/')));
            }

            /*
             * Send the files
             */
            cdn_send_files($files, $server, $section, $group);
        }

        /*
         * Now that the file has been sent to the CDN system delete the file
         * locally
         */
        if($delete){
            foreach($files as $url => $file){
                file_delete($file, true);
            }
        }

        return count($files);

    }catch(Exception $e){
        throw new bException('cdn_add_files(): Failed', $e);
    }
}



/*
 *
 */
function cdn_domain($file, $section = 'pub', $false_on_not_exist = false, $force_cdn = false){
    global $_CONFIG;

    try{
        if(!$_CONFIG['cdn']['enabled'] and !$force_cdn){
            if($section == 'pub'){
                if(!empty($_CONFIG['cdn']['prefix'])){
                    $section = $_CONFIG['cdn']['prefix'];
                }
            }

            return domain($file, null, $section, null, '');
        }

        if($section == 'pub'){
            /*
             * Process pub files, "system" files like .css, .js, static website
             * images ,etc
             */
            if(!isset($_SESSION['cdn'])){
                /*
                 * Get a CDN server for this session
                 */
                $_SESSION['cdn'] = sql_get('SELECT   `baseurl`

                                            FROM     `cdn_servers`

                                            WHERE    `status` IS NULL

                                            ORDER BY RAND() LIMIT 1', true);

                if(empty($_SESSION['cdn'])){
                    /*
                     * There are no CDN servers available!
                     * Switch to working without CDN servers
                     */
                    notify('configuration-missing', tr('CDN system is enabled but there are no CDN servers configuraed'), 'developers');
                    $_CONFIG['cdn']['enabled'] = false;
                    return cdn_domain($file, $section);

                }else{
                    $_SESSION['cdn'] = slash($_SESSION['cdn']).'pub/'.strtolower(str_replace('_', '-', PROJECT).'/');
                }
            }

            if(!empty($_CONFIG['cdn']['prefix'])){
                $file = $_CONFIG['cdn']['prefix'].$file;
            }

            return $_SESSION['cdn'].str_starts_not($file, '/');
        }

        /*
         * Get this URL from the CDN system
         */
        $url = sql_get('SELECT    `cdn_files`.`file`,
                                  `cdn_files`.`servers_id`,
                                  `cdn_servers`.`baseurl`

                        FROM      `cdn_files`

                        LEFT JOIN `cdn_servers`
                        ON        `cdn_files`.`servers_id` = `cdn_servers`.`id`

                        WHERE     `cdn_files`.`file` = :file
                        AND       `cdn_servers`.`status` IS NULL

                        ORDER BY  RAND()

                        LIMIT     1',

                        array(':file' => $file));

        if($url){
            /*
             * Yay, found the file in the CDN database!
             */
            return slash($url['baseurl']).strtolower(str_replace('_', '-', PROJECT)).$url['file'];
        }

        /*
         * The specified file is not found in the CDN system
         */
        if($false_on_not_exist){
            return false;
        }

        return domain($file);

        ///*
        // * We have a CDN server in session? If not, get one.
        // */
        //if(isset_get($_SESSION['cdn']) === null){
        //    $server = sql_get('SELECT `baseurl` FROM `cdn_servers` WHERE `status` IS NULL ORDER BY RAND() LIMIT 1', true);
        //
        //    if(!$server){
        //        /*
        //         * Err we have no CDN servers, though CDN is configured.. Just
        //         * continue locally?
        //         */
        //        notify('no-cdn-servers', tr('CDN system is enabled, but no availabe CDN servers were found'), 'developers');
        //        $_SESSION['cdn'] = false;
        //        return domain($current_url, $query, $root);
        //    }
        //
        //    $_SESSION['cdn'] = slash($server).strtolower(str_replace('_', '-', PROJECT));
        //}
        //
        //return $_SESSION['cdn'].$current_url;

    }catch(Exception $e){
        throw new bException('cdn_domain(): Failed', $e);
    }
}



/*
 * Return the given string from the specified needle
 */
function str_from($source, $needle, $more = 0){
    try{
        if(!$needle){
            throw new bException('str_from(): No needle specified', 'not-specified');
        }

        $pos = mb_strpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $pos + mb_strlen($needle) - $more);

    }catch(Exception $e){
        throw new bException('str_from(): Failed for "'.str_log($source).'"', $e);
    }
}



/*
 * Return the given string from 0 until the specified needle
 */
function str_until($source, $needle, $more = 0, $start = 0){
    try{
        if(!$needle){
            throw new bException('str_until(): No needle specified', 'not-specified');
        }

        $pos = mb_strpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $start, $pos + $more);

    }catch(Exception $e){
        throw new bException('str_until(): Failed for "'.str_log($source).'"', $e);
    }
}



/*
 * Return the given string from the specified needle, starting from the end
 */
function str_rfrom($source, $needle, $more = 0){
    try{
        if(!$needle){
            throw new bException('str_rfrom(): No needle specified', 'not-specified');
        }

        $pos = mb_strrpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $pos + mb_strlen($needle) - $more);

    }catch(Exception $e){
        throw new bException('str_rfrom(): Failed for "'.str_log($source).'"', $e);
    }
}



/*
 * Return the given string from 0 until the specified needle, starting from the end
 */
function str_runtil($source, $needle, $more = 0, $start = 0){
    try{
        if(!$needle){
            throw new bException('str_runtil(): No needle specified', 'not-specified');
        }

        $pos = mb_strrpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $start, $pos + $more);

    }catch(Exception $e){
        throw new bException('str_runtil(): Failed for "'.str_log($source).'"', $e);
    }
}



/*
 * Ensure that specified source string starts with specified string
 */
function str_starts($source, $string){
    if(mb_substr($source, 0, mb_strlen($string)) == $string){
        return $source;
    }

    return $string.$source;
}



/*
 * Ensure that specified source string starts NOT with specified string
 */
function str_starts_not($source, $string){
    while(mb_substr($source, 0, mb_strlen($string)) == $string){
        $source = mb_substr($source, mb_strlen($string));
    }

    return $source;
}



/*
 * Ensure that specified string ends with specified character
 */
function str_ends($source, $string){
    try{
        $length = mb_strlen($string);

        if(mb_substr($source, -$length, $length) == $string){
            return $source;
        }

        return $source.$string;

    }catch(Exception $e){
        throw new bException('str_ends(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends NOT with specified character
 */
function str_ends_not($source, $strings, $loop = true){
    try{
        if(is_array($strings)){
            /*
             * For array test, we always loop
             */
            $redo = true;

            while($redo){
                $redo = false;

                foreach($strings as $string){
                    $new = str_ends_not($source, $string, true);

                    if($new != $source){
                        // A change was made, we have to rerun over it.
                        $redo = true;
                    }

                    $source = $new;
                }
            }

        }else{
            /*
             * Check for only one character
             */
            $length = mb_strlen($strings);

            while(mb_substr($source, -$length, $length) == $strings){
                $source = mb_substr($source, 0, -$length);
                if(!$loop) break;
            }
        }

        return $source;

    }catch(Exception $e){
        throw new bException('str_ends_not(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends with slash
 */
function slash($string){
    try{
        return str_ends($string, '/');

    }catch(Exception $e){
        throw new bException('slash(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends NOT with slash
 */
function unslash($string, $loop = true){
    try{
        return str_ends_not($string, '/', $loop);

    }catch(Exception $e){
        throw new bException('unslash(): Failed', $e);
    }
}



/*
 * Remove double "replace" chars
 */
function str_nodouble($source, $replace = '\1', $character = null, $case_insensitive = true){
    if($character){
        /*
         * Remove specific character
         */
        return preg_replace('/('.$character.')\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);
    }

    /*
     * Remove ALL double characters
     */
    return preg_replace('/(.)\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);

}



/*
 * Truncate string using the specified fill and method
 */
function str_truncate($source, $length, $fill = ' ... ', $method = 'right', $on_word = false){
    try{
        if(!$length or ($length < (mb_strlen($fill) + 1))){
            throw new bException('str_truncate(): No length or insufficient length specified. You must specify a length of minimal $fill length + 1', 'invalid');
        }

        if($length >= mb_strlen($source)){
            /*
             * No need to truncate, the string is short enough
             */
            return $source;
        }

        /*
         * Correct length
         */
        $length -= mb_strlen($fill);

        switch($method){
            case 'right':
                $retval = mb_substr($source, 0, $length);
                if($on_word and (strpos(substr($source, $length, 2), ' ') === false)){
                    if($pos = strrpos($retval, ' ')){
                        $retval = substr($retval, 0, $pos);
                    }
                }

                return trim($retval).$fill;

            case 'center':
                return mb_substr($source, 0, floor($length / 2)).$fill.mb_substr($source, -ceil($length / 2));

            case 'left':
                $retval = mb_substr($source, -$length, $length);

                if($on_word and substr($retval)){
                    if($pos = strpos($retval, ' ')){
                        $retval = substr($retval, $pos);
                    }
                }

                return $fill.trim($retval);

            default:
                throw new bException('str_truncate(): Unknown method "'.$method.'" specified, please use "left", "center", or "right" or undefined which will default to "right"', 'unknown');
        }

    }catch(Exception $e){
        throw new bException(tr('str_truncate(): Failed for ":source"', array(':source' => $source)), $e);
    }
}



/*
 * Return a string that is suitable for logging.
 */
function str_log($source, $truncate = 2047, $separator = ', '){
    try{
        load_libs('json');

        if(!$source){
            if(is_numeric($source)){
                return 0;
            }

            return '';
        }

        if(!is_scalar($source)){
            if(is_array($source)){
                try{
                    $source = mb_trim(json_encode_custom($source));

                }catch(Exception $e){
                    /*
                     * Most likely (basically only) reason for this would be that implode failed on imploding an array with sub arrays.
                     * Use json_encode_custom() instead
                     */
                    $source = mb_trim(json_encode_custom($source));
                }

            }elseif(is_object($source) and ($source instanceof bException)){
                $source = $source->getCode().' / '.$source->getMessage();

            }else{
                $source = mb_trim(json_encode_custom($source));
            }
        }

        return str_nodouble(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', str_replace('  ', ' ', str_replace("\n", ' ', str_truncate($source, $truncate, ' ... ', 'center')))), '\1', ' ');

    }catch(Exception $e){
        throw new bException('str_log(): Failed', $e);
    }
}



/*
 * Specified variable may be either string or array, but ensure that its returned as an array.
 */
function array_force($source, $separator = ','){
    try{
        if(!$source){
            return array();
        }

        if(!is_array($source)){
            if(!is_string($source)){
                return array($source);
            }

            return explode($separator, $source);
        }

        return $source;

    }catch(Exception $e){
        throw new bException('array_force(): Failed', $e);
    }
}



/*
 *
 */
function date_convert($date = null, $requested_format = 'human_datetime', $to_timezone = null, $from_timezone = null){
    global $_CONFIG;

    try{
        /*
         * Ensure we have some valid date string
         */
        if($date === null){
            $date = date('Y-m-d H:i:s');

        }elseif(!$date){
            return '';

        }elseif(is_numeric($date)){
            $date = date('Y-m-d H:i:s', $date);
        }

        /*
         * Compatibility check!
         * Older systems will still have the timezone specified as a single string, newer as an array
         * The difference between these two can result in systems no longer starting up after an update
         */
        if(!$to_timezone){
            $to_timezone = TIMEZONE;
        }

        if(!$from_timezone){
            $from_timezone = $_CONFIG['timezone']['system'];
        }

        /*
         * Ensure we have a valid format
         */
        if($requested_format == 'mysql'){
            /*
             * Use mysql format
             */
            $format = 'Y-m-d H:i:s';

        }elseif(isset($_CONFIG['formats'][$requested_format])){
            /*
             * Use predefined format
             */
            $format = $_CONFIG['formats'][$requested_format];

        }else{
            /*
             * Use custom format
             */
            $format = $requested_format;
        }

        /*
         * Force 12 or 24 hour format?
         */
        switch($_CONFIG['formats']['force1224']){
            case false:
                break;

            case '12':
                /*
                 * Only add AM/PM in case original spec has 24H and no AM/PM
                 */
                if(($requested_format != 'mysql') and strstr($format, 'g')){
                    $format = str_replace('H', 'g', $format);

                    if(!strstr($format, 'a')){
                        $format .= ' a';
                    }
                }

                break;

            case '24':
                $format = str_replace('g', 'H', $format);
                $format = trim(str_replace('a', '', $format));
                break;

            default:
                throw new bException(tr('date_convert(): Invalid force1224 hour format ":format" specified. Must be either false, "12", or "24". See $_CONFIG[formats][force1224]', array(':format' => $_CONFIG['formats']['force1224'])), 'invalid');
        }

        /*
         * Create date in specified timezone (if specifed)
         * Return formatted date
         *
         * If specified date is already a DateTime object, then from_timezone will not work
         */
        if(is_scalar($date)){
            $date = new DateTime($date, ($from_timezone ? new DateTimeZone($from_timezone) : null));

        }else{
            if(!($date instanceof DateTime)){
                throw new bException(tr('date_convert(): Specified date variable is a ":type" which is invalid. Should be either scalar or a DateTime object', array(':type' => gettype($date))), 'invalid');
            }
        }

        if($to_timezone){
            /*
             * Output to specified timezone
             */
            $date->setTimezone(new DateTimeZone($to_timezone));
        }

        try{
            return $date->format($format);

        }catch(Exception $e){
            throw new bException(tr('date_convert(): Invalid format ":format" specified', array(':format' => $format)), 'invalid');
        }

    }catch(Exception $e){
        throw new bException('date_convert(): Failed', $e);
    }
}



/*
 * BELOW ARE IMPORTANT BUT RARELY USED FUNCTIONS THAT HAVE CONTENTS IN HANDLER FILES
 */



/*
 * Show the correct HTML flash error message
 */
function error_message($e, $messages = array(), $default = null){
    return include(__DIR__.'/handlers/system_error_message.php');
}



/*
 * Switch to specified site type, and redirect back
 */
function switch_type($type, $redirect = ''){
    return include(__DIR__.'/handlers/system_switch_type.php');
}



/*
 *
 */
function get_global_data_path($section = '', $writable = true){
    return include(__DIR__.'/handlers/system_get_global_data_path.php');
}



/*
 *
 */
function run_background($cmd, $log = true, $single = true){
    return include(__DIR__.'/handlers/startup-run-background.php');
}



/*
 * DEBUG FUNCTIONS BELOW HERE
 */



/*
 * Return the file where this call was made
 */
function current_file($trace = 0){
    return include(__DIR__.'/handlers/debug_current_file.php');
}



/*
 * Return the line number where this call was made
 */
function current_line($trace = 0){
    return include(__DIR__.'/handlers/debug_current_line.php');
}



/*
 * Return the function where this call was made
 */
function current_function($trace = 0){
    return include(__DIR__.'/handlers/debug_current_function.php');
}



/*
 * Auto fill in values (very useful for debugging and testing)
 */
function value($format, $size = null){
    if(!debug()) return '';
    return include(__DIR__.'/handlers/debug_value.php');
}



/*
 * Show data, function results and variables in a readable format
 */
function show($data = null, $trace_offset = null, $quiet = false){
    return include(__DIR__.'/handlers/debug_show.php');
}



/*
 * Short hand for show and then die
 */
function showdie($data = null, $trace_offset = null){
    return include(__DIR__.'/handlers/debug_showdie.php');
}



/*
 * Short hand for show and then randomly die
 */
function showrandomdie($data = '', $return = false, $quiet = false, $trace_offset = 2){
    return include(__DIR__.'/handlers/debug_showrandomdie.php');
}



/*
 * Show nice HTML table with all debug data
 */
function debug_html($value, $key = null, $trace_offset = 0){
    return include(__DIR__.'/handlers/debug_html.php');
}



/*
 * Show HTML <tr> for the specified debug data
 */
function debug_html_row($value, $key = null, $type = null){
    return include(__DIR__.'/handlers/debug_html_row.php');
}



/*
 *
 */
function debug_sql($query, $execute = null, $return_only = false){
    return include(__DIR__.'/handlers/debug_sql.php');
}



/*
 * Gives a filtered debug_backtrace()
 */
function debug_trace($filters = 'args'){
    return include(__DIR__.'/handlers/debug_trace.php');
}



/*
 * Return an HTML bar with debug information that can be used to monitor site and fix issues
 */
function debug_bar(){
    return include(__DIR__.'/handlers/debug_bar.php');
}



/*
 * Recursively cleanup the specified variable, removing any password like variable
 */
function debug_cleanup($data){
    return include(__DIR__.'/handlers/debug_cleanup.php');
}



/*
 *
 */
function die_in($count, $message = null){
    return include(__DIR__.'/handlers/debug_die_in.php');
}



/*
 *
 */
function variable_zts_safe($variable, $level = 0){
    return include(__DIR__.'/handlers/variable_zts_safe.php');
}
?>