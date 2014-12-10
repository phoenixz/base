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
function http_headers($params){
    try{
        array_params($params, 'http_code');
        array_default($params, 'http_code', 200);

        http_response_code($params['http_code']);

        if($params['http_code'] == 200){
            header('Last-Modified: '.gmdate('r', filemtime($_SERVER['SCRIPT_FILENAME'])));
//            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($_SERVER['SCRIPT_FILENAME'])).' GMT', true, 200);
        }

    }catch(Exception $e){
        throw new bException('http_headers(): Failed', $e);
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



/*
 * Depreciated, HTTP codes are now sent by http_headers()
 */
function http_header(){
    throw new bException('http_header() is no longer supported, set the http_code in html_header($params) which will send it to http_headers()', 'depreciated');
}



/*
 * For PHP < 5.4.0, where http_response_code will be missing
 * Taken from http://php.net/manual/en/function.http-response-code.php
 * Thanks to craig at craigfrancis dot co dot uk
 */
if (!function_exists('http_response_code')){
    include(dirname(__FILE__).'/handlers/http_response_code.php');
}



/*
 * Add a variable to the specified URL
 */
function http_add_variable($url, $key, $value){
    try{
        if(!$key or !$value){
            return $url;
        }

        if(strpos($url, '?') !== false){
            return $url.'&'.urlencode($key.'='.$value);
        }

        return $url.'?'.urlencode($key.'='.$value);

    }catch(Exception $e){
        throw new bException('http_add_variable(): Failed', $e);
    }
}



/*
 * Remove a variable from the specified URL
 */
function http_remove_variable($url, $key){
    try{
throw new bException('http_remove_variable() is under construction!');
        //if(!$key){
        //    return $url;
        //}
        //
        //if($pos = strpos($url, $key.'=') === false){
        //    return $url;
        //}
        //
        //if($pos2 = strpos($url, '&', $pos) === false){
        //    return substr($url, 0, $pos).;
        //}
        //
        //return substr($url, 0, );

    }catch(Exception $e){
        throw new bException('http_remove_variable(): Failed', $e);
    }
}



/*
 * Here be depreciated wrappers
 */
function http_build_url($url, $query){
    try{
        return http_add_variable($url, str_until($query, '='), str_from($query, '='));

    }catch(Exception $e){
        throw new bException('http_build_url(DEPRECIATED): Failed', $e);
    }
}
?>
