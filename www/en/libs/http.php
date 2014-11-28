<?php
/*
 * HTTP library, containing all sorts of HTTP functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Return complete current domain with HTTP and all
 */
function current_domain($current_url = false, $protocol = null){
    global $_CONFIG;

    try{
        if(!$protocol){
            $protocol = $_CONFIG['protocol'];
        }

        if(empty($_SERVER['SERVER_NAME'])){
            $server_name = $_CONFIG['domain'];

        }else{
            $server_name = $_SERVER['SERVER_NAME'];
        }


        if(!$current_url){
            return $protocol.$server_name.$_CONFIG['root'];
        }

        if($current_url === true){
            return $protocol.$server_name.$_SERVER['REQUEST_URI'];
        }

        return $protocol.$server_name.$_CONFIG['root'].str_starts($current_url, '/');

    }catch(Exception $e){
        throw new bException('current_domain(): Failed', $e);
    }
}



/*
 * Ensure that the $_GET values with the specied keys are also available in $_POST
 */
function http_get_to_post($keys, $overwrite = true){
    try{
        foreach(array_force($keys) as $key){
            if(isset($_GET[$key]) and ($overwrite or empty($_POST[$key]))){
                $_POST[$key] = $_GET[$key];
            }
        }

    }catch(Exception $e){
        throw new bException('http_get_to_post(): Failed', $e);
    }
}



/*
 * Build URL
 */
function http_build_url($url, $query){
    if(!$query){
        return $url;
    }

    if(strpos($url, '?') !== false){
        return $url.'&'.$query;
    }

    return $url.'?'.$query;
}



/*
 * Return status message for specified code
 */
function http_status_message($code){
    static $messages = array(  0 => 'Nothing',
                             200 => 'OK',
                             304 => 'Not Modified',
                             400 => 'Bad Request',
                             401 => 'Unauthorized',
                             403 => 'Forbidden',
                             404 => 'Not Found',
                             406 => 'Not Acceptable',
                             500 => 'Internal Server Error',
                             502 => 'Bad Gateway',
                             503 => 'Service Unavailable');

    if(!is_numeric($code) or ($code < 0) or ($code > 1000)){
        throw new bException('http_status_message(): Invalid code "'.str_log($code).'" specified');
    }

    if(!isset($messages[$code])){
        throw new bException('http_status_message(): Specified code "'.str_log($code).'" is not supported');
    }

    return $messages[$code];
}



/*
 * Send HTTP header for the specified code
 */
function http_header($code){
    try{
        header('HTTP/1.1 '.$code.' '.http_status_message($code));

    }catch(Exception $e){
        throw new bException('http_header(): Failed', $e);
    }
}



/*
 * Send all HTTP heaers for the required data
 */
function http_start($params){
    global $_CONFIG;

    if(PLATFORM != 'apache'){
        /*
         * This is only necesary on web servers
         */
        return false;
    }

    array_params($params, 'type');
    array_default($params, 'type', 'html');
    array_default($params, 'cors', '');

    switch($params['type']){
        case 'html':
            header('Content-Type: text/html; charset='.$_CONFIG['charset']);

            if($_CONFIG['cors'] or $params['cors']){
                /*
                 * Add CORS / Access-Control-Allow-Origin header
                 */
                header('Access-Control-Allow-Origin: '.($_CONFIG['cors'] ? str_ends($_CONFIG['cors'], ',') : '').$params['cors']);
            }

            break;

        default:
            throw new bException('http_start(): Unknown type "'.str_log($type).'" specified');
    }
}
?>
