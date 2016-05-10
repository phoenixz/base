<?php
/*
 * This is the main system library, it contains all kinds of system functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
 */

/*
 * Extend normal exception to automatically log to error log
 */
class bException extends Exception{
    private $messages = array();
    private $data     = null;
    public  $code     = null;

    function __construct($messages, $code = null, $e = null, $data = null){
        /*
         *
         */
        if(!empty($code)){
            if(is_object($code)){
                $data = $e;
                $e    = $code;
                $code = null;
            }
        }

        if(!$messages){
            throw new Exception('bException: No exception message specified in file "'.current_file(1).'" @ line "'.current_line(1).'"');
        }

        if(!is_array($messages)){
            $messages = array($messages);
        }

        try{
            foreach($messages as $message){
                log_database($message, 'exception');
            }

        }catch(Exception $e){
            /*
             * Exception database logging failed. Ignore, since from here on there is little to do
             */

// :TODO: Add notifications!
        }

        if(!empty($e)){
            if($e instanceof bException){
                $this->messages = $e->getMessages();

            }else{
                if(!is_object($e) or !($e instanceof Exception)){
                    throw new bException(tr('bException: Specified exception object for exception ":message" is not valid (either not object or not an exception object)', array(':message' => str_log($messages))), 'invalid');
                }

                $this->messages[] = $e->getMessage();
            }

            $orgmessage = $e->getMessage();

            if(method_exists($e, 'getData')){
                $this->data = $e->getData();
            }

        }else{
            $orgmessage = reset($messages);
            $this->data = $data;
        }

        if(!$code){
            if(is_object($e) and ($e instanceof bException)){
                $code = $e->getCode();
            }
        }

        parent::__construct($orgmessage, null);
        $this->code       = str_log($code);

        /*
         * If there are any more messages left, then add them as well
         */
        if($messages){
            foreach($messages as $id => $message){
                $this->messages[] = $message;

                if(PLATFORM == 'http'){
                    error_log('Exception ['.$id.']: '.$message);
                }
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
}



/*
 * Send notifications of the specified class
 */
function notify($event, $message, $classes = null){
    load_libs('notifications');

    try{
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
    return include(dirname(__FILE__).'/handlers/system_php_error_handler.php');
}



/*
 * Display a fatal error
 */
function uncaught_exception($e, $die = 1){
    return include(dirname(__FILE__).'/handlers/system_uncaught_exception.php');
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
            if(!$_CONFIG['production'] and $verify){
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
            throw new bException('cfm(): Specified variable should be datatype "string" but has datatype "'.gettype($string).'"', 'invalid');
        }
    }

    if($utf8){
        load_libs('utf8');
        return addslashes(mb_trim(html_entity_decode(utf8_unescape(strip_tags(utf8_escape($string))))));
    }

    return addslashes(mb_trim(html_entity_decode(strip_tags($string))));

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
            $replace['###SITENAME###'] = str_capitalize($_CONFIG['domain']);
        }

        if(!isset($replace['###DOMAIN###'])){
            $replace['###DOMAIN###']   = $_CONFIG['domain'];
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
        if($busy) return false;
        $busy = true;

        if(!empty($GLOBALS['no-db'])){
            /*
             * Don't log to DB, there is no DB
             */
            return false;
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
            return;
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
function log_file($messages, $class = 'messages', $type = 'unknown'){
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

        foreach(array_force($messages, "\n") as $key => $message){
            fwrite($h[$class], '['.$type.'] '.$key.' => '.$message."\n");
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
        if(is_string($libraries)){
            $libraries = explode(',', $libraries);
        }

        if(defined('LIBS')){
            $libs = LIBS;

        }else{
            /*
             * Oops, LIBS is not defined yet
             *
             * This (probably) means that something went wrong
             * in the startup, which caused an exception which
             * caused a library being loaded..?
             */
// :TODO: In theory, this should not be happening... ?
            $libs = dirname(__FILE__).'/';
        }

        foreach($libraries as $library){
            if(!$library){
                throw new bException('load_libs(): Empty library specified', 'emptyspecified');
            }

            include_once($libs.$library.'.php');
        }

    }catch(Exception $e){
        throw new bException('load_libs(): Failed to load libraries "'.str_log($libraries).'"', $e);
    }
}



/*
 * Load specified configuration file
 */
function load_config($files){
    global $_CONFIG;
    static $paths;

    try{
        if(!$paths){

            $paths = array(ROOT.'config/base/',
                           ROOT.'config/production_',
                           ROOT.'config/'.ENVIRONMENT.'_');
        }

        $files = array_force($files);

        foreach($files as $file){
            $loaded = false;

            /*
             * Include first the default configuration file, if available, then
             * production configuration file, if available, and then, if
             * available, the environment file
             */
            foreach($paths as $path){
                $path .= $file.'.php';

                if(file_exists($path)){
                    include($path);
                    $loaded = true;
                }
            }

            if(!$loaded){
                throw new bException('load_config(): No configuration file was found for requested configuration "'.str_log($file).'"');
            }
        }

    }catch(Exception $e){
        throw new bException('load_config(): Failed to load some or all of config file(s) "'.str_log($files).'"', $e);
    }
}



/*
 * Returns if site is running in debug mode or not
 */
function debug($class = null){
    global $_CONFIG;

    if($class === null){
        return (boolean) $_CONFIG['debug'];
    }

    if($class === true){
        /*
         * Force debug to be true. This may be useful in production situations where some bug needs quick testing.
         */
        $_CONFIG['debug'] = true;
        load_libs('debug');
        return true;
    }

    if(!isset($_CONFIG['debug'][$class])){
        throw new bException('debug(): Unknown debug class "'.str_log($class).'" specified', 'unknown');
    }

    return $_CONFIG['debug'][$class];
}



/*
 * Execute shell commands with exception checks
 */
function safe_exec($command, $ok_exitcodes = null, $route_errors = true){
    return include(dirname(__FILE__).'/handlers/system_safe_exec.php');
}



/*
 * Execute the specified script from the ROOT/scripts directory
 */
function script_exec($script, $argv = null, $ok_exitcodes = null){
    return include(dirname(__FILE__).'/handlers/system_script_exec.php');
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

        error_log($_CONFIG['domain'].'-'.str_log($code).($details ? ' "'.str_log($details).'"' : ''));

    }catch(Exception $e){
        throw new bException('add_stat(): Failed', $e);
    }
}



/*
 * Calculate the DB password hash
 */
function password($password, $algorithm = null){
    global $_CONFIG;

    try{
        if(!$algorithm){
            $algorithm = $_CONFIG['security']['passwords']['algorithm'];
        }

        switch($algorithm){
            case 'sha1':
                return '*sha1*'.sha1(SEED.$password);

            case 'sha256':
                return '*sha256*'.sha256(SEED.$password);

            default:
                throw new bException(tr('password(): Unknown algorithm ":algorithm" specified', array(':algorithm' => str_log($algorithm))), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('password(): Failed', $e);
    }
}


/*
 * Return complete domain with HTTP and all
 */
function domain($current_url = false, $query = null){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['domain'])){
            throw new bException(tr('domain(): $_CONFIG[domain] is not configured'), 'not-specified');
        }

        if($_CONFIG['domain'] == 'auto'){
            $_CONFIG['domain'] = $_SERVER['SERVER_NAME'];
        }

        if(!$current_url){
            $retval = $_CONFIG['protocol'].$_CONFIG['domain'].$_CONFIG['root'];

        }elseif($current_url === true){
            $retval = $_CONFIG['protocol'].$_CONFIG['domain'].$_SERVER['REQUEST_URI'];

        }else{
            $retval = $_CONFIG['protocol'].$_CONFIG['domain'].$_CONFIG['root'].str_starts($current_url, '/');
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
            if($right === 'admin'){
                /*
                 * Admin right also requires that the current admin script is defined in production_admin menu
                 */
                $fail = true;

                foreach($_CONFIG['admin']['pages'] as $data){
                    if(!empty($data['subs'])){
                        foreach($data['subs'] as $sub){
                            if(isset_get($subs['script']) === SCRIPT){
                                /*
                                 * Script is defined, so just check the normal access rights
                                 */
                                unset($script);
                                break;
                            }
                        }

                        if(empty($script)){
                            break;
                        }

                    }elseif(isset_get($data['script']) === SCRIPT){
                        /*
                         * Script is defined, so just check the normal access rights
                         */
                        unset($script);
                        break;
                    }
                }
            }

            if(empty($user['rights'][$right]) or !empty($user['rights']['devil']) or !empty($fail)){
                if(PLATFORM == 'shell'){
                    load_libs('user');
                    log_message('has_rights(): Access denied for user "'.str_log(user_name($_SESSION['user'])).'" in page "'.str_log($_SERVER['PHP_SELF']).'" for missing right "'.str_log($right).'"', 'accessdenied', 'yellow');
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
                redirect(str_replace('%page%', str_log(urlencode($_SERVER['SCRIPT_NAME'])), isset_get($_CONFIG['redirects']['signin'], 'signin.php')));
            }

            if(!empty($_SESSION['lock'])){
                /*
                 * Session is, but locked
                 * Redirect all pages EXCEPT the lock page itself!
                 */
                if($_CONFIG['redirects']['lock'] !== str_cut($_SERVER['REQUEST_URI'], '/', '?')){
                    redirect(str_replace('%page%', str_log(urlencode($_SERVER['SCRIPT_NAME'])), isset_get($_CONFIG['redirects']['lock'], 'lock.php')));
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
 * Either a right is logged in or the person will be redirected to the specified URL
 */
function rights_or_access_denied($rights){
    global $_CONFIG;

    try{
        user_or_signin();

        if(PLATFORM_SHELL or has_rights($rights)){
            return $_SESSION['user'];
        }

        page_show($_CONFIG['redirects']['accessdenied']);

    }catch(Exception $e){
        throw new bException('rights_or_access_denied(): Failed', $e);
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
function client_detect(){
    return include(dirname(__FILE__).'/handlers/system_client_detect.php');
}



/*
 * Switch to specified site type, and redirect back
 */
function switch_type($type, $redirect = ''){
    return include(dirname(__FILE__).'/handlers/system_switch_type.php');
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
        throw new bException('pick_random(): Invalid count "'.str_log($count).'" specified for "'.count($args).'" arguments');

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
 * Wrapper for debug_value
 */
function value($format, $size = null){
    if(!debug()) return '';

    load_libs('debug');
    return debug_value($format, $size);
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
function get_global_data_path($section = '', $force = true){
    static $global_path;

    /*
     * Cached value
     */
    if(!empty($global_path)){
        return $global_path;
    }

    return include(dirname(__FILE__).'/handlers/system_get_global_data_path.php');
}



/*
 * Will return $return if the specified item id is in the specified source.
 */
function in_source($source, $key, $return = true){
    try{
        if(!is_array($source)){
            throw new bException('in_source(): Specified source should be an array', 'invalid');
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
function system_date_format($date = null, $requested_format = 'human_datetime'){
    global $_CONFIG;

    try{
        /*
         * Ensure we have some valid date string
         */
        if(!$date){
            $date = date('Y-m-d H:i:s');

        }elseif(is_numeric($date)){
            $date = date('Y-m-d H:i:s', $date);
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
         * Format
         */
        $date   = new DateTime($date);
        return $date->format($format);

    }catch(Exception $e){
        if(!isset($_CONFIG['formats'][$requested_format]) and ($requested_format != 'mysql')){
            throw new bException('system_date_format(): Unknown format "'.str_log($requested_format).'" specified', 'unknown');
        }

        if(isset($format)){
            throw new bException(tr('system_date_format(): Either :error, or Invalid format ":format" specified', array(':error' => $e->getMessage(), ':format' => str_log($format))), 'invalid');
        }

        throw new bException('system_date_format(): Failed', $e);
    }
}



/*
 *
 */
function is_natural($number, $start = 1){
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
}



/*
 *
 */
function force_natural($number, $default = 1){
    if(!is_numeric($number)){
        return $default;
    }

    if($number < 1){
        return $default;
    }

    if(!is_int($number)){
        return round($number);
    }

    return $number;
}



/*
 * Show the correct HTML flash error message
 */
function error_message($e, $messages = array(), $default = null){
    return include(dirname(__FILE__).'/handlers/system_error_message.php');
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
function run_background($cmd, $single = true, $log = false){
    try{
        $path = dirname($cmd);
        $args = str_from (basename($cmd), ' ');
        $cmd  = str_until(basename($cmd), ' ');

        if($path == '.'){
            $path = ROOT.'scripts/';

        }elseif(str_starts_not($path, '/') == 'base'){
            $path = ROOT.'scripts/base/';
        }

        if($single and process_runs($cmd)){
            return false;
        }

        $path = slash($path);

        if(!file_exists($path.$cmd)){
            throw new bException(tr('run_background(): Specified command ":cmd" does not exists', array(':cmd' => $path.$cmd)), 'not-exist');
        }

        if(!is_file($path.$cmd)){
            throw new bException(tr('run_background(): Specified command ":cmd" is not a file', array(':cmd' => $path.$cmd)), 'notfile');
        }

        if(!is_executable($path.$cmd)){
            throw new bException(tr('run_background(): Specified command ":cmd" is not executable', array(':cmd' => $path.$cmd)), 'notexecutable');
        }

        load_libs('file');
        file_ensure_path(ROOT.'data/run');
        file_ensure_path(ROOT.'data/log');

//show(sprintf('nohup %s >> '.ROOT.'data/log/%s 2>&1 & echo $! > %s', $path.$cmd.' '.$args, $cmd, ROOT.'data/run/'.$cmd));

        if($log){
            exec(sprintf('nohup %s >> '.ROOT.'data/log/%s 2>&1 & echo $! > %s', $path.$cmd.' '.$args, $cmd, ROOT.'data/run/'.$cmd));

        }else{
            exec(sprintf('nohup %s > /dev/null 2>&1 & echo $! > %s', $path.$cmd.' '.$args, ROOT.'data/run/'.$cmd));
        }

        return exec(sprintf('cat %s', ROOT.'data/run/'.$cmd));

    }catch(Exception $e){
        throw new bException('run_background(): Failed', $e);
    }
}



/*
 * Return a code that is guaranteed unique
 */
function unique_code($hash = 'sha256'){
    global $_CONFIG;

    try{
        return hash($hash, uniqid('', true).microtime(true).$_CONFIG['security']['seed']);

    }catch(Exception $e){
        throw new bException('unique_code(): Failed', $e);
    }
}



/*
 *
 */
function get_this_cdn_id(){
    try{
        if(empty($GLOBALS['cdn_id'])){
            $GLOBALS['cdn_id'] = str_until($_SERVER['SERVER_NAME'], '.');

            if(!is_numeric($GLOBALS['cdn_id'])){
                throw new bException(tr('get_this_cdn_id(): This is not a numeric CDN server'), 'invalid');
            }
        }

        return $GLOBALS['cdn_id'];

    }catch(Exception $e){
        throw new bException(tr('get_this_cdn_id(): Failed'), $e);
    }
}



/*
 * Use this function to get resources from multiple CDN servers if resources
 * are not limited to only one CDN server
 *
 * At first call, it will return the first CDN server from the
 * $_CONFIG['cdn']['servers'] list. At subsequent calls, it will return the
 * next CDN server in the $_CONFIG['cdn']['servers'] list. Once at the end of
 * the list, it will start from the beginning again
 *
 * This way, a single page will request data from multiple CDN servers, and so
 * increase page load speed. Since for each page, the order of CDN servers is
 * the same for each load, for each CDN server, most file requests will be the
 * same as well, so the server will be able to use its file cache more
 * efficient as well.
 */
function get_next_cdn_id(){
    global $_CONFIG;
    static $current_id;

    try{
        if(empty($current_id)){
            reset($_CONFIG['cdn']['servers']);
            $current_id = current($_CONFIG['cdn']['servers']);

        }else{
            $current_id = array_next_value($_CONFIG['cdn']['servers'], $current_id, false, true);
        }

        return $current_id;

    }catch(Exception $e){
        throw new bException(tr('get_this_cdn_id(): Failed'), $e);
    }
}



/*
 *
 */
function cdn_prefix($id = null, $force_environment = false){
    global $_CONFIG;

    try{
        if($force_environment){
            $config = get_config('', $force_environment);
            $cdn    = $config['cdn'];

        }else{
            $cdn    = $_CONFIG['cdn'];
        }

        if(!$id){
            $id = get_next_cdn_id();
        }

        return str_replace(':id', $id, $cdn['prefix']);

    }catch(Exception $e){
        throw new bException(tr('cdn_prefix(): Failed'), $e);
    }
}



/*
 * Return deploy configuration for the specified environment
 */
function get_config($file, $environment = null){
    try{
        if(!$environment){
            $environment = ENVIRONMENT;
        }

        $_CONFIG = array('deploy' => array());

        if($file){
            $file = '_'.$file;
        }

        include(ROOT.'config/production'.$file.'.php');
        include(ROOT.'config/'.$environment.$file.'.php');

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
function name($user = null, $key_prefix = ''){
    try{
        if($user){
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
                if(!$user = sql_get('SELECT `name` `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $user))){
                   throw new bException('name(): Specified user id ":id" does not exist', array(':id' => str_log($user)), 'not-exist');
                }
            }

            if(!is_array($user)){
                throw new bException(tr('name(): Invalid data specified, please specify either user id, name, or an array containing username, email and or id'), 'invalid');
            }

            $user = not_empty(isset_get($user[$key_prefix.'name']), isset_get($user[$key_prefix.'username']), isset_get($user[$key_prefix.'email']), isset_get($user[$key_prefix.'id']));

            if($user){
                return $user;
            }
        }

        /*
         * No user data found, assume guest user.
         */
        return tr('Guest');

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
 * OBSOLETE FUNCTIONS AND WRAPPERS BE HERE BELOW
 */



/*
 * WRAPPER FOR cli_log()
 */
function log_console($message, $type = '', $color = null, $newline = true, $filter_double = false){
    return cli_log($message, $color, $newline, $filter_double);
}

/*
 * IN CASE ANY PROJECT STILL USES THE OLD ONE
 */
function is_natural_number($number){
    return is_natural($number);
}

function rights_or_redirect($rights, $no_user_url = null, $no_rights_page = null, $method = 'http'){
    return rights_or_access_denied($rights, $no_user_url, $no_rights_page, $method);
}

function user_or_redirect($url = null, $method = 'http'){
    return user_or_signin($url, $method);
}

function force_natural_number($number, $default = 1){
    return force_natural($number, $default);
}
?>
