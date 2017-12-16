<?php
/*
 * This is the main system library, it contains all kinds of system functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */

/*
 * Extend normal exception to automatically log to error log
 */
class bException extends Exception{
    private $messages = array();
    private $data     = null;
    public  $code     = null;

    function __construct($messages, $code, $data = null){
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
            foreach($messages as $message){
                log_database($message, 'exception');
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

// :DELETE: No longer do this. Exceptions can be as simple as "Could not delete users, no users spoecified". We don't want those messages in the apache log. We DO want uncaught exceptions though!
                //if(PLATFORM == 'http'){
                //    error_log('Exception ['.$id.']: '.$message);
                //}
            }
        }
    }

    function addMessage($message){
        $this->messages[] = $message;
        return $this;
    }

    function getMessages($separator = null){
        if($separator === null){
            return $this->messages;
        }

        return implode($separator, $this->messages);
    }

    function getData(){
        return $this->data;
    }

    function setData($data){
        $this->data = $data;
    }
}



/*
 * Send notifications of the specified class
 */
function notify($event, $message = null, $classes = null){
    try{
        load_libs('notifications');
        return notifications_do($event, $message, $classes);

    }catch(Exception $e){
        /*
         * Notification failed!
         *
         * Do NOT cause exception, because it its not caught, it might cause another notification, that will fail, cause exception and an endless loop!
         */
        return false;
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
        throw new bException('tr(): Failed with text "'.str_log($text).'". Very likely issue with $replace not containing all keywords, or one of the $replace values is non-scalar', $e);
    }
}



// :DEPRECATED: This function will be kicked, it uses crappy preg_replace /e modifier, and is just html_entity_decode()
///*
// * Replacement value for html_entity_decode (which doesnt work very well)
// * Taken from http://php.net/manual/en/function.html-entity-decode.php
// */
//// :TODO:SVEN: What about this "UTF-8 does not work!" ?? Does this work with UTF8 OR NOT!!?!?
//function decode_entities($text) {
//    $text = html_entity_decode($text,ENT_QUOTES,"ISO-8859-1"); #NOTE: UTF-8 does not work!
//    $text = preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
//    $text = preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
//
//    return $text;
//}



/*
 * Cleanup string
 */
function cfm($string, $utf8 = true){
    if(!is_scalar($string)){
        if(!is_null($string)){
            throw new bException(tr('cfm(): Specified variable ":variable" from ":location" should be datatype "string" but has datatype ":datatype"', array(':variable' => $string, ':datatype' => gettype($string), ':location' => current_file(1).'@'.current_line(1))), 'invalid');
        }
    }

    if($utf8){
        load_libs('utf8');
        return mb_trim(html_entity_decode(utf8_unescape(strip_tags(utf8_escape($string)))));
    }

    return mb_trim(html_entity_decode(strip_tags($string)));

// :TODO:SVEN:20130709: Check if we should be using mysqli_escape_string() or addslashes(), since the former requires SQL connection, but the latter does NOT have correct UTF8 support!!
//    return mysqli_escape_string(trim(decode_entities(mb_strip_tags($str))));
}



/*
 * Force integer
 */
function cfi($source, $allow_null = true){
    if(!$source and $allow_null){
        return null;
    }

    return (integer) $source;
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
 * Load html templates from disk
 */
function load_content($file, $replace = false, $language = null, $autocreate = null, $validate = true){
    global $_CONFIG;

    try{
        load_libs('file');

        /*
         * Set default values
         */
        if($language === null){
            $language = LANGUAGE;
        }

        if(!isset($replace['###SITENAME###'])){
            $replace['###SITENAME###'] = str_capitalize($_SESSION['domain']);
        }

        if(!isset($replace['###DOMAIN###'])){
            $replace['###DOMAIN###']   = $_SESSION['domain'];
        }

        /*
         * Check if content file exists
         */
        if($realfile = realpath(ROOT.'data/content/'.LANGUAGE.'/'.cfm($file).'.html')){
            /*
             * File exists, we're okay, get and return contents.
             */
            $retval = str_replace(array_keys($replace), array_values($replace), file_get_contents($realfile));

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
 *
 */
function country_from_ip($ip = ''){
    if(empty($ip)){
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $dat = sql_get('SELECT countrySHORT FROM ipcountry WHERE INET_ATON("'.$ip.'") BETWEEN ipFROM AND ipTO OR INET_ATON("'.$ip.'") = ipFROM OR INET_ATON("'.$ip.'") = ipTO;');

    if(strlen($dat['countrySHORT']) > 1){
        return strtolower($dat['countrySHORT']);
    }

    return '??';
}



/*
 * Translate date to spanish
 */
function spa_date($string,$time){
    $from = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
    $to   = array('Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');

    return str_replace($from, $to, date($string, $time));
}



/*
 * Turn microtime into something useful
 */
function microtime_float(){
    list($usec, $sec) = explode(' ', microtime());
    return ((float) $usec + (float) $sec);
}



/*
 * Log specified error somewhere
 */
function log_error($message, $type = 'warning', $notify = true){
    global $_CONFIG;

    try{
        if(!$_CONFIG['production']){
            /*
             * Non production systems should fail immediately so that the issue can be resolved right away
             */
            switch($type){
                case 'fatal':
                    // FALLTHROUGH
                case 'error':
                    log_screen($message, $type, 'red');
                    log_screen(tr('Stopping process due to logged error on non production machine'), $type, 'red');
                    die(1);

                default:
                    log_screen($message, $type, 'yellow');
            }
        }

        if(is_object($message) and is_a($message, '')){
            foreach($message->getMessages() as $key => $value){
                log_error($key.': '.$value, $code);
            }

        }else{
            /*
             * Your system should be error free, log everything everywhere!
             */
            error_log($message);
            log_message($message, 'error/'.$type, 'red');
            log_database($message, 'error/'.$type, 'red');

            if($notify){
                notify('error', $message, 'developers');
            }
        }

    }catch(Exception $e){
        /*
         * Oops, error logging failed miserably, just try to log to system log and then we're done
         */
        error_log('log_error(): Failed to log error! See following line for more information');
        error_log($e->getMessage());
    }
}



/*
 * Log specified message in db and screen
 */
function log_message($message, $type = 'info', $color = null){
    global $_CONFIG;

    try{
        if(is_object($message)){
            if($message instanceof bException){
                foreach($message->getMessages() as $key => $realmessage){
                    log_error($key.': '.$realmessage, $message->code);
                }

                return $message;

            }elseif($message instanceof Exception){
// :TODO: This will very likely cause an endless loop!
throw new bException('log_message(): DEVELOPMENT FIX! This exception is here to stop an endless loop', 'fatal');
                return log_message($realmessage, 'error', 'red');
            }
        }

        if(PLATFORM == 'http'){
            error_log($message);
        }

        log_database($message, $type);

        if(!debug()){
            /*
             * In non debug environments, NEVER log to screen!
             */
            return $message;
        }

        return log_screen($message, $type, $color);

    }catch(Exception $e){
// :TODO: Add system notification!
        if(!$_CONFIG['production']){
            unset($_CONFIG['db']['pass']);
            log_screen($message.' (NODB '.print_r($_CONFIG['db'], true).')', $type, $color);
        }

        return $message;
    }
}



/*
 * Log specified message to screen (console or http)
 */
function log_screen($message, $type = 'info', $color = null){
    global $_CONFIG;
    static $last;

    if($message == $last){
        /*
        * We already displayed this message, skip!
        */
        return;
    }

    $last = $message;

    if(PLATFORM == 'shell'){
        return cli_log($message, $color);

    }elseif(!$_CONFIG['production']){
        /*
         * Do NOT display log data to browser client on production!
         */
        if((strpos($type, 'error') !== false) and ($color === null)){
            $color = 'red';
        }

        if((strpos($type, 'warning') !== false) and ($color === null)){
            $color = 'yellow';
        }

        echo '<div class="log'.($color ? ' '.$color : '').'">['.$type.'] '.$message.'</div>';
    }

    return $message;
}



/*
 * Log specified message to database, but only if we are in console mode!
 */
function log_database($messages, $type = 'unknown'){
    static $q, $last, $busy;

    try{
        /*
         * Avoid endless looping if the database log fails
         */
        if(!$busy){
            $busy = true;

            if(!empty($GLOBALS['no-db'])){
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
                throw new bException('log_database(): Type cannot be numeric');
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
        $GLOBALS['no-db'] = true;
    }
}



/*
 * Log specified message to file.
 */
function log_file($messages, $class = 'messages', $type = null){
    global $_CONFIG;
    static $h = array(), $last;

    try{
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
            throw new bException('log_file(): Specified class "'.str_truncate(json_encode_custom($class), 20).'" is not scalar');
        }

        if(empty($h[$class])){
            load_libs('file');
            file_ensure_path(ROOT.$_CONFIG['log']['path']);

            $h[$class] = fopen(slash(ROOT.$_CONFIG['log']['path']).$class, 'a+');
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
        throw new bException('log_file: Failed', $e, array('message' => $messages));
    }
}



/*
 * Load specified library files
 */
function load_libs($libraries){
    global $_CONFIG;

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
                throw new bException('load_libs(): Empty library specified', 'emptyspecified');
            }

            include_once($libs.$library.'.php');
        }

    }catch(Exception $e){
        throw new bException(tr('load_libs(): Failed to load one or more of libraries ":libraries"', array(':libraries' => $libraries)), $e);
    }
}



/*
 * Load specified configuration file
 */
function load_config($files = ' '){
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
        throw new bException(tr('load_config(): Failed to load some or all of config file(s) ":file"', array(':file' => $file)), $e);
    }
}



/*
 * Returns if site is running in debug mode or not
 */
function debug($class = null){
    try{
        global $_CONFIG;

        if($class === null){
            return (boolean) $_CONFIG['debug'];
        }

        if($class === true){
            /*
             * Force debug to be true. This may be useful in production situations where some bug needs quick testing.
             */
            $_CONFIG['debug'] = true;
            return true;
        }

        if(!isset($_CONFIG['debug'][$class])){
            throw new bException('debug(): Unknown debug class "'.str_log($class).'" specified', 'unknown');
        }

        return $_CONFIG['debug'][$class];

    }catch(Exception $e){
        throw new bException(tr('debug(): Failed'), $e);
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
function domain($current_url = false, $query = null, $root = null, $domain = null){
    global $_CONFIG;

    try{
        if($root === null){
            $root = str_ends_not($_CONFIG['root'], '/');
        }

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
        }

        if(!$current_url){
            $retval = $_CONFIG['protocol'].$domain.$root;

        }elseif($current_url === true){
            $retval = $_CONFIG['protocol'].$domain.$_SERVER['REQUEST_URI'];

        }else{
            $retval = $_CONFIG['protocol'].$domain.$root.str_starts($current_url, '/');
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
                if((PLATFORM == 'shell') and VERBOSE){
                    load_libs('user');
                    log_message(tr('has_rights(): Access denied for user ":user" in page ":page" for missing right ":right"', array(':user' => name($_SESSION['user']), ':page' => $_SERVER['PHP_SELF'], ':right' => $right)), 'accessdenied', 'yellow');
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
                if((PLATFORM == 'shell') and VERBOSE){
                    load_libs('user');
                    log_message(tr('has_groups(): Access denied for user ":user" in page ":page" for missing group ":group"', array(':user' => name($_SESSION['user']), ':page' => $_SERVER['PHP_SELF'], ':group' => $group)), 'accessdenied', 'yellow');
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
    global $_CONFIG;

    try{
        if(PLATFORM_HTTP){
            if(empty($_SESSION['user']['id'])){
                /*
                 * No session
                 */
                if($GLOBALS['page_is_api']){
                    json_reply(tr('api_start_session(): Specified token ":token" has no session', array(':token' => $_POST['PHPSESSID'])), 'signin');

                }else{
                    redirect(isset_get($_CONFIG['redirects']['signin'], 'signin.php').'?redirect='.urlencode($_SERVER['REQUEST_URI']));
                }
            }

            if(!empty($_SESSION['lock'])){
                /*
                 * Session is, but locked
                 * Redirect all pages EXCEPT the lock page itself!
                 */
                if($_CONFIG['redirects']['lock'] !== str_cut($_SERVER['REQUEST_URI'], '/', '?')){
                    redirect(isset_get($_CONFIG['redirects']['lock'], 'lock.php').'?redirect='.urlencode($_SERVER['REQUEST_URI']));
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
function rights_or_access_denied($rights){
    global $_CONFIG;

    try{
        if(!$rights){
            return true;
        }

        user_or_signin();

        if(PLATFORM_SHELL or has_rights($rights)){
            return $_SESSION['user'];
        }

        if(in_array('admin', array_force($rights))){
            redirect($_CONFIG['redirects']['signin']);
        }

        page_show($_CONFIG['redirects']['accessdenied']);

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

        if(PLATFORM_SHELL or has_groups($groups)){
            return $_SESSION['user'];
        }

        if(in_array('admin', array_force($groups))){
            redirect($_CONFIG['redirects']['signin']);
        }

        page_show($_CONFIG['redirects']['accessdenied']);

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
 * Sets client info in $_SESSION and returns it
 */
function detect_client(){
    return include(__DIR__.'/handlers/system_detect_client.php');
}



/*
 * Sets location info in $_SESSION and returns it
 */
function detect_location(){
    return include(__DIR__.'/handlers/system_detect_location.php');
}



/*
 * Sets language info in $_SESSION and returns it
 */
function detect_language(){
    return include(__DIR__.'/handlers/system_detect_language.php');
}



/*
 * Switch to specified site type, and redirect back
 */
function switch_type($type, $redirect = ''){
    return include(__DIR__.'/handlers/system_switch_type.php');
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
 *
 */
function get_global_data_path($section = '', $writable = true){
    return include(__DIR__.'/handlers/system_get_global_data_path.php');
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
 * Return the specified $date with the specified $interval applied.
 * If $date is null, the default date from date_convert() will be used
 * $interval must be a valid ISO 8601 specification (see http://php.net/manual/en/dateinterval.construct.php)
 * If $interval is "negative", i.e. preceded by a - sign, the interval will be subtraced. Else the interval will be added
 * Return date will be formatted according to date_convert() $format
 */
function date_interval($date, $interval, $format = null){
    try{
        $date = date_convert($date, 'd-m-Y');
        $date = new DateTime($date);

        if(substr($interval, 0, 1) == '-'){
            $date->sub(new DateInterval(substr($interval, 1)));

        }else{
            $date->add(new DateInterval($interval));
        }

        return date_convert($date, $format);

    }catch(Exception $e){
        throw new bException('date_interval(): Failed', $e);
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
 * Show the correct HTML flash error message
 */
function error_message($e, $messages = array(), $default = null){
    return include(__DIR__.'/handlers/system_error_message.php');
}



/*
 *
 */
function process_runs($process_name){
    try{
        exec('pgrep '.$process_name, $output, $return);
        return !$return;

    }catch(Exception $e){
        throw new bException('process_runs(): Failed', $e);
    }
}



/*
 *
 */
function run_background($cmd, $log = true, $single = true){
    try{
        $args = str_from ($cmd, ' ');
        $cmd  = str_until($cmd, ' ');
        $path = dirname($cmd);
        $path = slash($path);
        $cmd  = basename($cmd);

        if($path == './'){
            $path = ROOT.'scripts/';

        }elseif(str_ends_not(str_starts_not($path, '/'), '/') == 'base'){
            $path = ROOT.'scripts/base/';
        }

        if($single and process_runs($cmd)){
            return false;
        }

        if(!file_exists($path.$cmd)){
            throw new bException(tr('run_background(): Specified command ":cmd" does not exists', array(':cmd' => $path.$cmd)), 'not-exist');
        }

        if(!is_file($path.$cmd)){
            throw new bException(tr('run_background(): Specified command ":cmd" is not a file', array(':cmd' => $path.$cmd)), 'notfile');
        }

        if(!is_executable($path.$cmd)){
            throw new bException(tr('run_background(): Specified command ":cmd" is not executable', array(':cmd' => $path.$cmd)), 'notexecutable');
        }

        if($log === true){
            $log = $cmd;
        }

        load_libs('file');
        file_ensure_path(ROOT.'data/run-background');
        file_ensure_path(ROOT.'data/log');

//showdie(sprintf('nohup %s >> '.ROOT.'data/log/%s 2>&1 & echo $! > %s', $path.$cmd.' '.$args, $log, ROOT.'data/run-background/'.$cmd));
        if($log){
            exec(sprintf('nohup %s >> '.ROOT.'data/log/%s 2>&1 & echo $! > %s', $path.$cmd.' '.$args, $log, ROOT.'data/run-background/'.$cmd));

        }else{
            exec(sprintf('nohup %s > /dev/null 2>&1 & echo $! > %s', $path.$cmd.' '.$args, ROOT.'data/run-background/'.$cmd));
        }

        return exec(sprintf('cat %s; rm %s', ROOT.'data/run-background/'.$cmd, ROOT.'data/run-background/'.$cmd));

    }catch(Exception $e){
        throw new bException('run_background(): Failed', $e);
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



// :OBSOLETE: With the new CDN system, all this old really crappy CDN functionality no longer is needed
///*
// *
// */
//function get_this_cdn_id(){
//    try{
//        if(empty($GLOBALS['cdn_id'])){
//            $GLOBALS['cdn_id'] = str_until($_SERVER['SERVER_NAME'], '.');
//
//            if(!is_numeric($GLOBALS['cdn_id'])){
//                throw new bException(tr('get_this_cdn_id(): This is not a numeric CDN server'), 'invalid');
//            }
//        }
//
//        return $GLOBALS['cdn_id'];
//
//    }catch(Exception $e){
//        throw new bException(tr('get_this_cdn_id(): Failed'), $e);
//    }
//}
//
//
//
///*
// * Use this function to get resources from multiple CDN servers if resources
// * are not limited to only one CDN server
// *
// * At first call, it will return the first CDN server from the
// * $_CONFIG['cdn']['servers'] list. At subsequent calls, it will return the
// * next CDN server in the $_CONFIG['cdn']['servers'] list. Once at the end of
// * the list, it will start from the beginning again
// *
// * This way, a single page will request data from multiple CDN servers, and so
// * increase page load speed. Since for each page, the order of CDN servers is
// * the same for each load, for each CDN server, most file requests will be the
// * same as well, so the server will be able to use its file cache more
// * efficient as well.
// */
//function get_next_cdn_id(){
//    global $_CONFIG;
//    static $current_id;
//
//    try{
//        if(empty($current_id)){
//            reset($_CONFIG['cdn']['servers']);
//            $current_id = current($_CONFIG['cdn']['servers']);
//
//        }else{
//            $current_id = array_next_value($_CONFIG['cdn']['servers'], $current_id, false, true);
//        }
//
//        return $current_id;
//
//    }catch(Exception $e){
//        throw new bException(tr('get_next_cdn_id(): Failed'), $e);
//    }
//}



///*
// *
// */
//function cdn_prefix($path, $id = null, $force_environment = false){
//    return cdn_domain($path);
//
//    global $_CONFIG;
//
//    try{
//        if($force_environment){
//            $config = get_config('', $force_environment);
//            $cdn    = $config['cdn'];
//
//        }else{
//            $cdn    = $_CONFIG['cdn'];
//        }
//
//        if(!$id){
//            $id = get_next_cdn_id();
//        }
//
//// :URGENT: Implement correct CDN support! MUST WORK WITH WHITELABEL SYSTEM!!!!
////show(str_replace(':id', $id, slash($cdn['prefix'])).str_starts_not($path, '/'));
//        return str_replace(':id', $id, slash($cdn['prefix'])).str_starts_not($path, '/');
//
//    }catch(Exception $e){
//        throw new bException(tr('cdn_prefix(): Failed'), $e);
//    }
//}



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
function get_next_api_id(){
    global $_CONFIG;
    static $current_id;

    try{
        if(empty($current_id)){
            reset($_CONFIG['api']['servers']);
            $current_id = current($_CONFIG['api']['servers']);

        }else{
            $current_id = array_next_value($_CONFIG['api']['servers'], $current_id, false, true);
        }

        return $current_id;

    }catch(Exception $e){
        throw new bException(tr('get_next_api_id(): Failed'), $e);
    }
}



/*
 *
 */
function api_prefix($id = null, $force_environment = false){
    global $_CONFIG;

    try{
        if($force_environment){
            $config = get_config('', $force_environment);
            $api    = $config['api'];

        }else{
            $api    = $_CONFIG['api'];
        }

        if(!$id){
            $id = get_next_api_id();
        }

        return str_replace(':id', $id, $api['prefix']);

    }catch(Exception $e){
        throw new bException(tr('api_prefix(): Failed'), $e);
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
 * Return NULL if specified variable is considered "empty", like 0, "", array(), etc.
 * If not, return the specified variable unchanged
 */
function get_null($source){
    if($source){
        return $source;
    }

    return null;
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
function get_process_user(){
    try{
        if(is_executable('posix_getpwuid')){
            $id   = posix_geteuid();
            $user = posix_getpwuid($id);
            $user = $user['name'];

        }else{
            $user = safe_exec('whoami');
            $user = array_pop($user);
        }

        return $user;

    }catch(Exception $e){
        throw new bException(tr('get_process_user(): Failed'), $e);
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
 * Return TRUE if the user of the current process is the root user
 */
function detect_root(){
    try{
        if(!is_executable('posix_getuid')){
            throw new bException(tr('detect_root(): The PHP posix module is not installed. Do note that this function only works on Linux machines!'), 'not-installed');
        }

        return posix_getuid() == 0;

    }catch(Exception $e){
        throw new bException(tr('detect_root(): Failed'), $e);
    }
}



/*
 * Return TRUE if the user of the current process has sudo available
 */
function detect_sudo(){
    try{
// :TODO: Implement function
    }catch(Exception $e){
        throw new bException(tr('detect_sudo(): Failed'), $e);
    }
}



/*
 *
 */
function multilingual(){
    global $_CONFIG;

    try{
        return count($_CONFIG['language']['supported']) > 1;

    }catch(Exception $e){
        throw new bException(tr('multilingual(): Failed'), $e);
    }
}



/*
 *
 */
function language_lock($language, $script = null){
    static $checked   = false;
    static $incorrect = false;

    try{
        if(is_array($script)){
            /*
             * Script here will contain actually a list of all scripts for
             * each language. This can then be used to determine the name
             * of the script in the correct language to build linksx
             */
            $GLOBALS['scripts'] = $script;
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
 * DEBUG FUNCTIONS BELOW HERE
 */



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
function show($data = null, $trace_offset = null){
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



/*
 * OBSOLETE FUNCTIONS AND WRAPPERS BE HERE BELOW
 */



/*
 * WRAPPER FOR cli_log()
 */
function log_console($message, $type = '', $color = null, $newline = true, $filter_double = false){
    load_libs('cli');
    return cli_log($message, $color, $newline, $filter_double);
}
?>
