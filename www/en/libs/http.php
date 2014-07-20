<?php
/*
 * HTTP library, containing all sorts of HTTP functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



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
        throw new lsException('http_status_message(): Invalid code "'.str_log($code).'" specified');
    }

    if(!isset($messages[$code])){
        throw new lsException('http_status_message(): Specified code "'.str_log($code).'" is not supported');
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
        throw new lsException('http_header(): Failed', $e);
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
            throw new lsException('http_start(): Unknown type "'.str_log($type).'" specified');
    }
}
?>
