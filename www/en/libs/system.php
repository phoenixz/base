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

    function __construct($message, $code = null, $e = null, $data = null){
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

        if(!$message){
            throw new Exception('bException: No exception message specified in file "'.current_file(1).'" @ line "'.current_line(1).'"');
        }

        if(is_array($message)){
            $message = implode("\n", $message);
        }

        if(!empty($e)){
            if($e instanceof bException){
                $this->messages = $e->getMessages();

            }else{
                $this->messages[] = $e->getMessage();
            }

            $orgmessage = $e->getMessage();

            if(method_exists($e, 'getData')){
                $this->data = $e->getData();
            }

        }else{
            $orgmessage = $message;
            $this->data = $data;
        }

        if(!$code){
            if(is_object($e) and ($e instanceof bException)){
                $code = $e->getCode();
            }
        }

        parent::__construct($orgmessage, null);
        $this->code       = (string) $code;
        $this->messages[] = $message;
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
function notify($event, $message, $classes = null, $alternate_subenvironment = null){
    load_libs('notifications');

    try{
        notifications_do($event, $message, $classes, $alternate_subenvironment);

    }catch(Exception $e){
        /*
         * Notification failed!
         *
         * Now what?
         */
// :TODO: Implement
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
 * for translations
 */
function tr($text, $from = false, $to = false){
    try{
        if($from != false){
            if($to != false){
                return str_replace($from, $to, $text);
            }

            return str_replace(array_keys($from), array_values($from), $text);

        }else{
            return $text;
        }

    }catch(Exception $e){
        throw new bException('tr(): Failed with text "'.str_log($text).'". Check the $from and $to configuration!', $e);
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
//	return mysqli_escape_string(trim(decode_entities(mb_strip_tags($str))));
}



/*
 * Force integer
 */
function cfi($str){
    return (integer) $str;
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
 * Is email valid?
 */
function is_valid_email($email){
    if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", $email)){
        return false;
    }

    return true;
}



/*
 * Load html templates from disk
 */
function load_content($file, $from = false, $to = false, $language = null, $autocreate = null, $validate = true){
    global $_CONFIG;

    load_libs('file');

    try{
        /*
         * Set default values
         */
        if($language === null){
            $language = LANGUAGE;
        }

        if(!$from){
            $from = array();
        }

        if(!$to){
            $to   = array();
        }

        if(!isset($from['###SITENAME###'])){
            $from[] = '###SITENAME###';
            $to[]   = str_capitalize($_CONFIG['domain']);
        }

        if(!isset($from['###DOMAIN###'])){
            $from[] = '###DOMAIN###';
            $to[]   = $_CONFIG['domain'];
        }

        /*
         * Simple validation of search / replace values
         */
        if($validate and (count($from) != count($to))){
            throw new bException('load_content(): search count "'.count($from).'" is not equal to replace count "'.count($to).'"', 'searchreplacecounts');
        }

        /*
         * Check if content file exists
         */
        if($realfile = realpath(ROOT.'data/content/'.(SUBENVIRONMENTNAME ? SUBENVIRONMENTNAME.'/' : '').LANGUAGE.'/'.cfm($file).'.html')){
            /*
             * File exists, we're okay, get and return contents.
             */
            $retval = str_replace($from, $to, file_get_contents($realfile));

            /*
             * Make sure no replace markers are left
             */
            if($validate and preg_match('/###.*?###/i', $retval, $matches)){
                /*
                 * Oops, specified $from array does not contain all replace markers
                 */
                throw new bException('load_content(): Missing markers "'.str_log($matches).'" for content file "'.str_log($realfile).'"', 'missingmarkers');
            }

            return $retval;
        }

        $realfile = ROOT.'data/content/'.cfm($language).'/'.cfm($file).'.html';

        /*
         * From here, the file does not exist.
         */
        if($autocreate === null){
            $autocreate = $_CONFIG['content']['autocreate'];
        }

        if(!$autocreate){
            throw new bException('load_content(): Specified file "'.str_log($file).'" does not exist for language "'.str_log($language).'"', 'notexist');
        }

        /*
         * Make content directory exists
         */
        file_ensure_path(dirname($realfile));

        $default  = 'File created '.$file.' by '.realpath(PWD.$_SERVER['PHP_SELF'])."\n";
        $default .= print_r($from, true);
        $default .= print_r($to  , true);

        file_put_contents($realfile, $default);

        return str_replace($from, $to, $default);

    }catch(Exception $e){
        notify('error', "LOAD_CONTENT() FAILED [".$e->getCode()."]\n".implode("\n", $e->getMessages()));
        error_log("LOAD_CONTENT() FAILED [".$e->getCode()."]\n".implode("\n", $e->getMessages()));

        switch($e->getCode()){
            case 'notexist':
                log_database('load_content(): File "'.cfm($language).'/'.cfm($file).'" does not exist', 'warning');

            case 'missingmarkers':
                log_database('load_content(): File "'.cfm($language).'/'.cfm($file).'" still contains markers after replace', 'warning');

            case 'searchreplacecounts':
                log_database('load_content(): Search count does not match replace count', 'warning');
        }

        throw new bException('load_content(): Failed', $e);
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
function log_error($message, $type = 'unknown', $color = null){
    if(is_object($message) and is_a($message, '')){
        foreach($message->getMessages() as $key => $value){
            log_error($key.': '.$value, $code);
        }

    }else{
        log_message($message, 'error/'.$type, $color);
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

                return;

            }elseif($message instanceof Exception){
// :TODO: This will very likely cause an endless loop!
throw new bException('log_message(): DEVELOPMENT FIX! This exception is here to stop an endless loop', 'fatal');
                return log_message($realmessage, 'error', 'red');
            }
        }

        if(PLATFORM == 'apache'){
            error_log($message);
        }

        log_database($message, $type);
        return log_screen($message, $type, $color);

    }catch(Exception $e){
        unset($_CONFIG['db']['pass']);
        log_screen($message.' (NODB '.print_r($_CONFIG['db'], true).')', $type, $color);
        return $message;
    }
}



/*
 * Log specified message to screen (console or apache)
 */
function log_screen($message, $type = 'info', $color = null){
    static $last;

    if($message == $last){
        /*
        * We already displayed this message, skip!
        */
        return;
    }

    $last = $message;

    if(PLATFORM == 'shell'){
        return log_console($message, $type, $color);

    }elseif(ENVIRONMENT != 'production'){
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
 * Log specified message to console, but only if we are in console mode!
 */
function log_console($message, $type = 'info', $color = null, $newline = true, $filter_double = false){
    static $c, $last;

    try{
        if(($filter_double == true) and ($message == $last)){
            /*
            * We already displayed this message, skip!
            */
            return;
        }

        $last = $message;

        if(PLATFORM != 'shell') return false;

        if($type){
            if((strpos($type, 'error') !== false) and ($color === null)){
                $color   = 'red';
                $message = '['.$type.'] '.$message;

            }elseif((strpos($type, 'warning') !== false) and ($color === null)){
                $color   = 'yellow';
                $message = '['.$type.'] '.$message;

            }else{
                if(strpos($message, '():') !== false){
                    $message = '['.$type.'] '.ltrim(str_from($message, '():'));

                }else{
                    $message = '['.$type.'] '.$message;
                }
            }
        }

        if($color){
            load_libs('cli');
            $c = cli_init_color();

            if($color == 'error'){
                $color = 'red';
            }

            $message = $c->$color($message);
        }

        echo stripslashes(br2nl($message)).($newline ? "\n" : "");

        return true;

    }catch(Exception $e){
        throw new bException('log_console: Failed', $e, array('message' => $message));
    }
}



/*
 * Log specified message to database, but only if we are in console mode!
 */
function log_database($message, $type){
    static $q, $last;

    try{
        if($message == $last){
            /*
            * We already displayed this message, skip!
            */
            return;
        }

        $last = $message;

        if(is_numeric($type)){
            throw new bException('log_database(): Type cannot be numeric');
        }

        sql_query('INSERT DELAYED INTO `log` (`createdby`, `ip`, `type`, `message`)
                   VALUES                    (:createdby , :ip , :type , :message )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':ip'        => isset_get($_SERVER['REMOTE_ADDR']),
                         ':type'      => cfm($type),
                         ':message'   => $message));

    }catch(Exception $e){
// :TODO: Add Notifications!
        log_console('log_database(): Failed to log message "'.str_log($message).'" to database', 'error');

        /*
         * Don't exception here because the exception may cause another log_database() call and loop endlessly
         */
    }

    return $message;
}



/*
 * Log specified message to file.
 */
function log_file($message, $type, $class = 'messages'){
    global $_CONFIG;

    static $h = array();

    if(!is_scalar($class)){
        load_libs('json');
        throw new bException('log_file(): Specified class "'.str_truncate(json_encode_custom($class), 20).'" is not scalar');
    }

    if(!$h[$class]){
        if(!$_CONFIG['log']['path'] = str_slash(realpath($_CONFIG['log']['path']))){
            $_CONFIG['log']['path'] = ROOT;
        }

        $h[$class] = fopen($_CONFIG['log']['path'].'log/', 'a+');
    }

    fputs($h[$class], '['.$type.'] '.$message, "\n");
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

    try{
        $files = array_force($files);

        foreach($files as $file){
            /*
             * Include first the default configuration file, if available, then
             * production configuration file, if available, and then, if
             * available, the environment file
             */
            foreach(array(ROOT.'config/base/'.$file.'.php', ROOT.'config/production_'.$file.'.php', ROOT.'config/'.ENVIRONMENT.'_'.$file.'.php') as $path){
                if(file_exists($path)){
                    include($path);
                }
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
 * Show the 404 page
 */
function page_404($force = false, $data = null) {
    page_show('404', true, $force, $data);
}



/*
 * Show the maintenance page
 */
function page_maintenance($reason, $force = false, $data = null) {
    return include(dirname(__FILE__).'/handlers/system_page_maintenance.php');
}



/*
 * Show the specified page
 */
function page_show($pagename, $die = false, $force = false, $data = null) {
    global $_CONFIG;

    try{
        if(!defined('LANGUAGE')){
            define('LANGUAGE', 'en');
        }

        if(($force != 'html') and (substr($_SERVER['PHP_SELF'], 0, 6) == '/ajax/')){
            // Execute ajax page
            return include(ROOT.'www/'.LANGUAGE.'/ajax/'.$pagename.'.php');

        }else{
            // Execute HTML page
            return include(ROOT.'www/'.LANGUAGE.'/'.(!empty($GLOBALS['page_is_mobile']) ? 'mobile/' : '').$pagename.'.php');
        }

        if($die){
            die();
        }

    }catch(Exception $e){
        throw new bException('page_show(): Failed to show page "'.str_log($pagename).'"', $e);
    }
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
    return include(dirname(__FILE__).'/handlers/system_add_stat.php');
}



/*
 * Calculate the DB password hash
 */
function password($password, $algorithm = 'sha1'){
    switch($algorithm){
        case 'sha1':
            return '*sha1*'.sha1(SEED.$password);

        case 'sha256':
            return '*sha256*'.sha1(SEED.$password);

        default:
            throw new bException('password(): Unknown algorithm "'.str_log($algorithm).'" specified', 'unknown');
    }
}


/*
 * Return complete domain with HTTP and all
 */
function domain($current_url = false, $protocol = null){
    global $_CONFIG;

    try{
        if(!$protocol){
            $protocol = $_CONFIG['protocol'];
        }

        if(!$current_url){
            return $protocol.$_CONFIG['domain'].$_CONFIG['root'];
        }

        if($current_url === true){
            return $protocol.$_CONFIG['domain'].$_SERVER['REQUEST_URI'];
        }

        return $protocol.$_CONFIG['domain'].$_CONFIG['root'].str_starts($current_url, '/');

    }catch(Exception $e){
        throw new bException('domain(): Failed', $e);
    }
}



/*
 * Either a user is logged in or the person will be redirected to the specified URL
 */
function user_or_redirect($url = false, $method = 'http'){
    global $_CONFIG;

    try{
        if(empty($_SESSION['user']['id'])){
            if($url === false){
                /*
                 * No redirect requested, just wanted to know if there is a logged in user.
                 */
                throw new bException('user_or_redirect(): No user for this session', 'redirect');
            }

            if((PLATFORM == 'shell')){
                /*
                 * Hey, we're not in a browser!
                 */
                if(!$url){
                    $url = tr('A user sign in is required');
                }

                throw new bException($url, 'nouser');
            }

            if(!$url){
                $url = str_replace('%page%', str_log(urlencode($_SERVER['SCRIPT_NAME'])), isset_get($_CONFIG['redirects']['signin'], 'signin.php'));
            }

            $url = $_CONFIG['root'].$url;

            switch($method){
                case 'json':
                    $_SESSION['redirect'] = $_SERVER['HTTP_REFERER'];

                    if(!function_exists('json_reply')){
                        load_libs('json');
                    }

                    /*
                     * Send JSON redirect. json_reply() will end script, so no break needed
                     */
                    json_reply(isset_get($url, $_CONFIG['root']), 'signin');

                case 'http':
                    if(!$GLOBALS['page_is_404']){
                        $_SESSION['redirect'] = current_domain(true);
                    }

                    /*
                     * Are we doing a POST or GET request? GET can be simply redirected, POST will first have to store POST data in $_SESSION
                     */
                    if(!empty($_POST)){
                        /*
                         * POST request
                         */
                        store_post($url);
                    }

                    redirect($url, false);

                default:
                    throw new bException('user_or_redirect(): Unknown method "'.str_log($method).'" specified. Please speficy one of "json", or "http"', 'unknown');
            }
        }

        return $_SESSION['user'];

    }catch(Exception $e){
        if($e->getCode() == 'redirect') {
            throw $e;
        }

        throw new bException('user_or_redirect(): Failed', $e);
    }
}



/*
 * Returns true if the current session user has the specified right
 * This function will automatically load the rights for this user if
 * they are not yet in the session variable
 */
function has_rights($rights, &$user = null){
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
            if(empty($user['rights'][$right]) or !empty($user['rights']['devil'])){
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
 * Either a right is logged in or the person will be redirected to the specified URL
 */
function rights_or_redirect($rights, $url = null, $method = 'http'){
    global $_CONFIG;

    try{
        user_or_redirect($url, $method);

        if(!has_rights($rights)){
            if((PLATFORM == 'shell')){
                /*
                 * Hey, we're not in a browser!
                 */
                if(!$url){
                    $url = 'rights_or_redirect(): The "'.str_log($rights).'" rights are required for this';
                }

                throw new bException($url, 'noright');
            }

            if(!$url){
                $url = str_replace('%page%', str_log(urlencode($_SERVER['SCRIPT_NAME'])), isset_get($_CONFIG['redirects']['forbidden'], 'signin.php'));
            }

            $url = $_CONFIG['root'].$url;

            $_SESSION['redirect'] = current_domain(true);

            switch($method){
                case 'json':
                    if(!function_exists('json_reply')){
                        load_libs('json');
                    }

                    // Send JSON redirect. json_reply() will end script, so no break needed
                    json_reply(isset_get($url, $_CONFIG['root']), 'signin');

                case 'http':
                    // Send HTTP redirect. redirect() will end script, so no break needed
                    redirect($url, false);

                default:
                    throw new bException('rights_or_redirect(): Unknown method "'.str_log($method).'" specified. Please speficy one of "json", or "http"', 'unknown');
            }
        }

        return $_SESSION['user'];

    }catch(Exception $e){
        throw new bException('rights_or_redirect(): Failed', $e);
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
        //	$api = (strtolower(substr($_SERVER['SCRIPT_NAME'], 0, 5)) == '/api/');
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
                    //	redirect($_SERVER['REQUEST_URI']);
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
function in_source($source, $key, $return){
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
?>
