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
 * Send correct JSON reply
 */
function json_reply($payload = null, $result = 'OK', $http_code = null, $after = 'die'){
    global $_CONFIG;

    try{
        if(!$payload){
            $payload = array_force($payload);
        }

        /*
         * Auto assume result = "OK" entry if not specified
         */
        if($result === false){
            /*
             * Do NOT add result to the reply
             */

        }elseif(strtoupper($result) == 'REDIRECT'){
            $payload = array('redirect' => $payload,
                             'result'   => 'REDIRECT');

        }elseif(!is_array($payload) or empty($payload['data'])){
            if($_CONFIG['json']['obsolete_support']){
                $payload = array('result'  => strtoupper($result),
                                 'message' => $payload,
                                 'data'    => $payload);
            }else{
                $payload = array('result'  => strtoupper($result),
                                 'data'    => $payload);
            }

        }

        if(empty($payload['result'])){
            $payload['result'] = $result;
        }

        $payload['result'] = strtoupper($payload['result']);
        $payload           = json_encode_custom($payload);

        $params = array('http_code' => $http_code,
                        'mimetype'  => 'application/json');

        load_libs('http');
        http_headers($params, strlen($payload));

        echo $payload;

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
function json_error($message, $data = null, $result = null, $http_code = 500){
    global $_CONFIG;

    try{
        if(!$message){
            $message = tr('json_error(): No exception specified in json_error() array');

        }elseif(is_scalar($message)){

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
                    log_console('json_error(): No exception object specified for following error', 'yellow');
                    log_console($message, 'yellow');

                }else{
                    if(count($message) == 1){
                        $message = array_pop($message);
                    }
                }

            }else{
                if($_CONFIG['production']){
                    log_console($message['e']);

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

            $message = trim(str_from($message, '():'));

        }elseif(is_object($message)){
            /*
             * Assume this is an bException object
             */
            if(!($message instanceof bException)){
                if(!($message instanceof Exception)){
                    $type = gettype($message);

                    if($type === 'object'){
                        $type .= '/'.get_class($message);
                    }

                    throw new bException(tr('json_error(): Specified message must either be a string or an bException ojbect, or PHP Exception ojbect, but is a ":type"', array(':type' => $type)), 'invalid');
                }

                $code = $message->getCode();

                if(debug()){
                    /*
                     * This is a user visible message
                     */
                    $message = $message->getMessage();

                }elseif(!empty($default)){
                    $message = $default;
                }

            }else{
                $result = $message->getCode();

                switch($result){
                    case 'access-denied':
                        $http_code = '403';
                        break;

                    case 'ssl-required':
                        $http_code = '403.4';
                        break;

                    default:
                        $http_code = '500';
                }

                if(debug()){
                    /*
                     * This is a user visible message
                     */
                    $messages = $message->getMessages();

                    foreach($messages as $id => &$message){
                        $message = trim(str_from($message, '():'));

                        if($message == tr('Failed')){
                            unset($messages[$id]);
                        }
                    }

                    unset($message);

                    $data = implode("\n", $messages);

                }elseif(!empty($default)){
                    $message = $default;
                }
            }
        }

        $data = array_force($data);

        if(!empty($message)){
            $data['message'] = $message;
        }

        json_reply(array_force($data), ($result ? $result : 'ERROR'), $http_code);

    }catch(Exception $e){
        throw new bException('json_error(): Failed', $e);
    }
}



/*
 * Custom JSON encoding function
 */
function json_encode_custom($source = false, $internal = true){
    try{
        if($internal){
            $source = json_encode($source);

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
                     * PHP and UTF, yay!
                     */
                    load_libs('mb');
                    return json_encode_custom(mb_utf8ize($source), true);

                default:
                    throw new bException('json_decode_custom(): Unknown JSON error occured', 'error');
            }

            return $source;

        }else{
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
        }

    }catch(Exception $e){
        throw new bException('json_encode_custom(): Failed', $e);
    }
}



/*
 * Validate the given JSON string
 */
function json_decode_custom($json, $as_array = true){
    try{
        if($json === null){
            return null;
        }

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
                throw new bException('json_decode_custom(): Syntax error, UTF8 issue', 'invalid', $json);

            default:
                throw new bException('json_decode_custom(): Unknown JSON error occured', 'error');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('json_decode_custom(): Failed', $e);
    }
}
?>
