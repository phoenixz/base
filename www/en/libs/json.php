<?php
/*
 * JSON library
 *
 * This library contains JSON functions
 *
 * All function names contain the json_ prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
 */


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
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"'.str_replace($jsonReplaces[0], $jsonReplaces[1], $source).'"';
            }

            return $source;
        }

        $isList = true;

        for($i = 0, reset($source); $i < count($source); $i++, next($source)){
            if(key($source) !== $i){
                $isList = false;
                break;
            }
        }

        $result = array();

        if($isList){
            foreach ($source as $v){
                $result[] = json_encode_custom($v);
            }

            return '['.join(',', $result).']';

        }else{
            foreach ($source as $k => $v){
                $result[] = json_encode_custom($k).':'.json_encode_custom($v);
            }

            return '{'.join(',', $result).'}';
        }

    }catch(Exception $e){
        throw new bException('json_encode_custom(): Failed', $e);
    }
}



/*
 * Send correct JSON reply
 */
function json_reply($reply = null, $result = 'OK', $http_code = null){
    try{
        if(!$reply){
            $reply = array('result' => $result);
        }

        /*
         * Auto assume result = "OK" entry if not specified
         */
        if(strtoupper($result) == 'REDIRECT'){
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
                        'headers'   => array('Content-Type: application/json',
                                             'Content-Type: text/html; charset=utf-8'));

        load_libs('http');
        http_headers($params, strlen($reply));

        echo $reply;
        die();

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
                throw new bException('json_decode_custom(): Maximum stack depth exceeded');

            case JSON_ERROR_STATE_MISMATCH:
                throw new bException('json_decode_custom(): Underflow or the modes mismatch');

            case JSON_ERROR_CTRL_CHAR:
                throw new bException('json_decode_custom(): Unexpected control character found');

            case JSON_ERROR_SYNTAX:
                throw new bException('json_decode_custom(): Syntax error, malformed JSON');

            case JSON_ERROR_UTF8:
                /*
                 * Only PHP 5.3+
                 */
                throw new bException('json_decode_custom(): Malformed UTF-8 characters, possibly incorrectly encoded');

            default:
                throw new bException('json_decode_custom(): Unknown JSON error occured');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('json_decode_custom(): Failed', $e);
    }
}
?>
