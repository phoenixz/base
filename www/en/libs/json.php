<?php
/*
 * JSON library
 *
 * This library contains JSON functions
 *
 * All function names contain the json_ prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */
load_config('json');



/*
 * Custom JSON encoding function
 */
function json_encode_custom($source = false){
    try{
        if(is_null($source)){
            return 'null';
        }

        if($source === false){
            return 'false';
        }

        if($source === true){
            return 'true';
        }

        if(is_scalar($source)){
            if(is_numeric($source)){
                if(is_float($source)){
                    // Always use "." for floats.
                    $source = floatval(str_replace(',', '.', strval($source)));
                }

                // Always use "" for numerics.
                return '"'.strval($source).'"';
            }

            if(is_string($source)){
                static $json_replaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"'.str_replace($json_replaces[0], $json_replaces[1], $source).'"';
            }

            return $source;
        }

        $is_list = true;

        for($i = 0, reset($source); $i < count($source); $i++, next($source)){
            if(key($source) !== $i){
                $is_list = false;
                break;
            }
        }

        $result = array();

        if($is_list){
            foreach ($source as $v){
                $result[] = json_encode_custom($v);
            }

            return '['.join(',', $result).']';
        }

        foreach ($source as $k => $v){
            $result[] = json_encode_custom($k).':'.json_encode_custom($v);
        }

        return '{'.join(',', $result).'}';

    }catch(Exception $e){
        throw new bException('json_encode_custom(): Failed', $e);
    }
}



/*
 * Send correct JSON reply
 */
function json_reply($reply = null, $result = 'OK', $http_code = null, $after = 'die'){
    try{
        if(!$reply){
            $reply = array_force($reply);
        }

        /*
         * Auto assume result = "OK" entry if not specified
         */
        if($result === false){
            /*
             * Do NOT add result to the reply
             */

        }elseif(strtoupper($result) == 'REDIRECT'){
            $reply = array('redirect' => $reply,
                           'result'   => 'REDIRECT');

        }elseif(!is_array($reply)){
            $reply = array('result'  => strtoupper($result),
                           'message' => $reply);

        }else{
            if(empty($reply['result'])){
                $reply['result'] = $result;
            }

            $reply['result'] = strtoupper($reply['result']);
        }

        $reply  = json_encode_custom($reply);

        $params = array('http_code' => $http_code,
                        'mimetype'  => 'application/json');

        load_libs('http');
        http_headers($params, strlen($reply));

        echo $reply;

        switch($after){
            case 'die':
                /*
                 * We're done, kill the connection % process (default)
                 */
                die();

            case 'continue':
                /*
                 * Continue running
                 */
                return;

            case 'close_continue':
                /*
                 * Close the current HTTP connection but continue in the background
                 */
                session_write_close();
                fastcgi_finish_request();
                return;

            default:
                throw new bException(tr('json_reply(): Unknown after ":after" specified. Use one of "die", "continue", or "close_continue"', array(':after' => $after)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('json_reply(): Failed', $e);
    }
}



/*
 * Send correct JSON reply
 */
function json_error($message, $data = array(), $result = 'ERROR', $http_code = 500){
    global $_CONFIG;

    try{
        if(!$message){
            $message = tr('json_error(): No exception specified in json_error() array');

        }elseif(is_array($message)){
            if(empty($message['default'])){
                $default = tr('Something went wrong, please try again later');

            }else{
                $default = $message['default'];
                unset($message['default']);
            }

            if(empty($message['e'])){
                if($_CONFIG['production']){
                    $message = $default;
                    log_error('json_error(): No exception object specified for following error');
                    log_error($message);

                }else{
                    if(count($message) == 1){
                        $message = array_pop($message);
                    }
                }

            }else{
                if($_CONFIG['production']){
                    log_error($message['e']);

                    $code = $message['e']->getCode();

                    if(empty($message[$code])){
                        $message = $default;

                    }else{
                        $message = $message[$code];
                    }

                }else{
                    $message = $message['e']->getMessages("\n<br>");
                }
            }

        }elseif(is_object($message)){
            /*
             * Assume this is an bException object
             */
            if(!($message instanceof bException)){
                throw new bException('json_error(): Specified message must either be a string or an bException ojbect, but is neither');
            }

            $code = $message->code;

    //        if(debug('messages') and (substr($code, 0, 5) == 'user/') or ($code == 'user')){
            if(debug()){
                /*
                 * This is a user visible message
                 */
                $message = $message->getMessages("\n");

            }elseif(!empty($default)){
                $message = $default;
            }
        }

        $data            = array_force($data);
        $data['message'] = $message;

        json_reply($data, $result, $http_code);

    }catch(Exception $e){
        throw new bException('json_error(): Failed', $e);
    }
}



/*
 * Validate the given JSON string
 */
function json_decode_custom($json, $as_array = true){
    try{
        /*
         * Decode the JSON data
         */
        $retval = json_decode($json, $as_array);

        /*
         * Switch and check possible JSON errors
         */
        switch(json_last_error()){
            case JSON_ERROR_NONE:
                break;

            case JSON_ERROR_DEPTH:
                throw new bException('json_decode_custom(): Maximum stack depth exceeded', 'invalid');

            case JSON_ERROR_STATE_MISMATCH:
                throw new bException('json_decode_custom(): Underflow or the modes mismatch', 'invalid');

            case JSON_ERROR_CTRL_CHAR:
                throw new bException('json_decode_custom(): Unexpected control character found', 'invalid');

            case JSON_ERROR_SYNTAX:
                throw new bException('json_decode_custom(): Syntax error, malformed JSON', 'invalid', $json);

            case JSON_ERROR_UTF8:
                /*
                 * Only PHP 5.3+
                 */
                throw new bException('json_decode_custom(): Malformed UTF-8 characters, possibly incorrectly encoded', 'invalid');

            default:
                throw new bException('json_decode_custom(): Unknown JSON error occured', 'error');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('json_decode_custom(): Failed', $e);
    }
}



/*
 *
 */
function json_authenticate($key){
    global $_CONFIG;

    try{
        if($_CONFIG['production']){
            /*
             * This is a production platform, only allow JSON API key
             * authentications over a secure connection
             */
            if($_CONFIG['protocol'] !== 'https://'){
                throw new bException(tr('json_authenticate(): No API key authentication allowed on unsecure connections over non HTTPS connections'), 'not-allowed');
            }
        }

        if(empty($key)){
            throw new bException(tr('json_authenticate(): No auth key specified'), 'not-specified');
        }

        /*
         * Authenticate using the supplied key
         */
        if(empty($_CONFIG['webdom']['auth_key'])){
            /*
             * Check in database if the authorization key exists
             */
            $user = sql_get('SELECT * FROM `users` WHERE `api_key` = :api_key', array(':api_key' => $key));

            if(!$user){
                throw new bException(tr('json_authenticate(): Specified auth key is not valid'), 'access-denied');
            }

        }else{
            /*
             * Use one system wide API key
             */
            if($key !== $_CONFIG['webdom']['auth_key']){
                throw new bException(tr('json_authenticate(): Specified auth key is not valid'), 'access-denied');
            }
        }

        /*
         * Yay, auth worked, create session and send client the session token
         */
        session_destroy();
        session_start();
        session_regenerate_id();
        session_reset_domain();

        $_SESSION['json_session_start'] = time();

        if(!empty($user)){
            $_SESSION['user'] = $user;
        }

        return session_id();

    }catch(Exception $e){
        throw new bException('json_authenticate(): Failed', $e);
    }
}



/*
 *
 */
function json_start_session(){
    global $_CONFIG;

    try{
        /*
         * Check session token
         */
        if(empty($_POST['PHPSESSID'])){
            throw new bException(tr('json_start_session(): No auth key specified'), 'not-specified');
        }

        /*
         * Yay, we have an actual token, create session!
         */
        session_write_close();
        session_id($_POST['PHPSESSID']);
        session_start();

        if(empty($_SESSION['json_session_start'])){
            /*
             * Not a valid session!
             */
            session_destroy();
            session_reset_domain();

            json_reply(tr('json_start_session(): Specified token ":token" has no session', array(':token' => $_POST['PHPSESSID'])), 'signin');
        }

        return session_id();

    }catch(Exception $e){
        throw new bException('json_start_session(): Failed', $e);
    }
}



/*
 *
 */
function json_stop_session($token){
    global $_CONFIG;

    try{
        /*
         * Check session token
         */
        if(empty($token)){
            throw new bException(tr('json_stop_session(): No auth key specified'), 'not-specified');
        }

        /*
         * Yay, we have an actual token, create session!
         */
        session_id($_POST['PHPSESSID']);
        session_start();
        session_destroy();
        session_reset_domain();
        return true;

    }catch(Exception $e){
        throw new bException('json_stop_session(): Failed', $e);
    }
}
?>
