<?php
/*
 * HTTP library, containing all sorts of HTTP functions
 *
 * This library contains functions to manage BASE HTTP
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package http
 */



/*
 * Return $_POST[dosubmit] value, and reset it to be sure it won't be applied twice
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 *
 * @return mixed The value found in $_POST['dosubmit']
 */
function get_dosubmit(){
    try{
        if(empty($_POST['dosubmit'])){
            return '';
        }

        $dosubmit = strtolower(isset_get($_POST['dosubmit'], ''));
        unset($_POST['dosubmit']);

        return $dosubmit;

    }catch(Exception $e){
        throw new bException('get_dosubmit(): Failed', $e);
    }
}



/*
 * Redirect to the specified $target
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 *
 * @param string $target
 * @param natural $http_code
 * @param boolean $clear_session_redirect
 * @param natural $time_delay
 * @return void (dies)
 */
function redirect($target = '', $http_code = null, $clear_session_redirect = true, $time_delay = null){
    return include(__DIR__.'/handlers/http-redirect.php');
}



/*
 * Return the specified URL with a redirect URL stored in $core->register['redirect']
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 * @note If no URL is specified, the current URL will be used
 * @see domain()
 * @see core::register
 * @see url_add_query()
 *
 * @param string $url
 * @return string The specified URL (if not specified, the current URL) with $core->register['redirect'] added to it (if set)
 */
function redirect_url($url = null){
    try{
        if(!$url){
            /*
             * Default to this page
             */
            $url = domain(true);
        }

        if(empty($core->register['redirect'])){
            return $url;
        }

        return url_add_query($url, 'redirect='.urlencode($core->register['redirect']));

    }catch(Exception $e){
        throw new bException('redirect_url(): Failed', $e);
    }
}



/*
 * Redirect if the session redirector is set
 */
function session_redirect($method = 'http', $force = false){
    try{
        if(!empty($force)){
            /*
             * Redirect by force value
             */
            $redirect = $force;

        }elseif(!empty($_GET['redirect'])){
            /*
             * Redirect by _GET redirect
             */
            $redirect = $_GET['redirect'];
            unset($_GET['redirect']);

        }elseif(!empty($_SESSION['redirect'])){
            /*
             * Redirect by _SESSION redirect
             */
            $redirect = $_SESSION['redirect'];

            unset($_SESSION['redirect']);
            unset($_SESSION['sso_referrer']);
        }

        switch($method){
            case 'json':
                if(!function_exists('json_reply')){
                    load_libs('json');
                }

                /*
                 * Send JSON redirect. json_reply() will end script, so no break needed
                 */
                json_reply(isset_get($redirect, '/'), 'redirect');

            case 'http':
                /*
                 * Send HTTP redirect. redirect() will end script, so no break
                 * needed
                 *
                 * Also, no need to unset SESSION redirect and sso_referrer,
                 * since redirect() will also do this
                 */
                redirect($redirect);

            default:
                throw new bException(tr('session_redirect(): Unknown method ":method" specified. Please speficy one of "json", or "http"', array(':method' => $method)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('session_redirect(): Failed', $e);
    }
}



/*
 * Store post data in $_SESSION
 */
function store_post($redirect){
    return include(__DIR__.'/handlers/system_store_post.php');
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



//:OBSOLETE: Use http_response_code() instead
///*
// * Return status message for specified code
// */
//function http_status_message($code){
//    static $messages = array(  0 => 'Nothing',
//                             200 => 'OK',
//                             304 => 'Not Modified',
//                             400 => 'Bad Request',
//                             401 => 'Unauthorized',
//                             403 => 'Forbidden',
//                             404 => 'Not Found',
//                             406 => 'Not Acceptable',
//                             500 => 'Internal Server Error',
//                             502 => 'Bad Gateway',
//                             503 => 'Service Unavailable');
//
//    if(!is_numeric($code) or ($code < 0) or ($code > 1000)){
//        throw new bException('http_status_message(): Invalid code "'.str_log($code).'" specified');
//    }
//
//    if(!isset($messages[$code])){
//        throw new bException('http_status_message(): Specified code "'.str_log($code).'" is not supported');
//    }
//
//    return $messages[$code];
//}



/*
 * Send HTTP header for the specified code
 */
function http_headers($params, $content_length){
    global $_CONFIG, $core;
    static $sent = false;

    if($sent) return false;
    $sent = true;

    try{
        array_params($params, 'http_code');
        array_default($params, 'http_code', $core->register['http_code']);
        array_default($params, 'etag'     , null);
        array_default($params, 'cors'     , false);
        array_default($params, 'mimetype' , $core->register['accepts']);
        array_default($params, 'headers'  , array());
        array_default($params, 'cache'    , array());

        $headers = $params['headers'];

        if($_CONFIG['security']['expose_php'] === false){
            header_remove('X-Powered-By');

        }elseif($_CONFIG['security']['expose_php'] !== true){
            /*
             * Send custom expose header to fake X-Powered-By header
             */
            $headers[] = 'X-Powered-By: '.$_CONFIG['security']['expose_php'];
        }

        $headers[] = 'Content-Type: '.$params['mimetype'].'; charset='.$_CONFIG['charset'];

        if(defined('LANGUAGE')){
            $headers[] = 'Content-Language: '.LANGUAGE;
        }

        if($content_length){
            $headers[] = 'Content-Length: '.$content_length;
        }

        if($params['http_code'] == 200){
            if(empty($params['last_modified'])){
                $headers[] = 'Last-Modified: '.date_convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT').' GMT';

            }else{
                $headers[] = 'Last-Modified: '.date_convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT').' GMT';
            }
        }

        if($_CONFIG['cors'] or $params['cors']){
            /*
             * Add CORS / Access-Control-Allow-.... headers
             */
            $params['cors'] = array_merge($_CONFIG['cors'], array_force($params['cors']));

            foreach($params['cors'] as $key => $value){
                switch($key){
                    case 'origin':
                        if($value == '*.'){
                            /*
                             * Origin is allowed from all sub domains
                             */
                            $origin = str_from(isset_get($_SERVER['HTTP_ORIGIN']), '://');
                            $length = strlen(isset_get($_SESSION['domain']));

                            if(substr($origin, -$length, $length) === isset_get($_SESSION['domain'])){
                                /*
                                 * Sub domain matches. Since CORS does
                                 * not support sub domains, just show
                                 * the current sub domain.
                                 */
                                $value = $_SERVER['HTTP_ORIGIN'];

                            }else{
                                /*
                                 * Sub domain does not match. Since CORS does
                                 * not support sub domains, just show no
                                 * allowed origin domain at all
                                 */
                                $value = '';
                            }
                        }

                        // FALLTHROUGH

                    case 'methods':
                        // FALLTHROUGH
                    case 'headers':
                        if($value){
                            $headers[] = 'Access-Control-Allow-'.str_capitalize($key).': '.$value;
                        }

                        break;

                    default:
                        throw new bException(tr('http_headers(): Unknown CORS header ":header" specified', array(':header' => $key)), 'unknown');
                }
            }
        }

        $headers = http_cache($params, $params['http_code'], $params['etag'], $headers);

        /*
         * Remove incorrect headers
         */
        header_remove('X-Powered-By');
        header_remove('Expires');
        header_remove('Pragma');

        /*
         * Set correct headers
         */
        http_response_code($params['http_code']);

        if($params['http_code'] != 200){
            log_file(tr('Base sent HTTP:http for URL ":url"', array(':http' => $params['http_code'], ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])), 'warning');
        }

        foreach($headers as $header){
            header($header);
        }

        if(strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD'){
            /*
             * HEAD request, do not return a body
             */
            die();
        }

        return true;

    }catch(Exception $e){
        /*
         * http_headers() itself crashed. Since http_headers()
         * would send out http 500, and since it crashed, it no
         * longer can do this, send out the http 500 here.
         */
        http_response_code(500);
        throw new bException('http_headers(): Failed', $e);
    }
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
            return $url.'&'.urlencode($key).'='.urlencode($value);
        }

        return $url.'?'.urlencode($key).'='.urlencode($value);

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



/*
 * Test HTTP caching headers
 *
 * Sends out 304 - Not modified header if ETag matches
 *
 * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
 * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
 */
function http_cache_test($etag = null){
    global $_CONFIG, $core;

    try{
        $core->register['etag'] = md5(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']).$etag);

        if(!$_CONFIG['cache']['http']['enabled']){
            return false;
        }

        if($core->callType('ajax') or $core->callType('api')){
            return false;
        }

        if((strtotime(isset_get($_SERVER['HTTP_IF_MODIFIED_SINCE'])) == filemtime($_SERVER['SCRIPT_FILENAME'])) or trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == $core->register['etag']){
            if(empty($core->register['flash'])){
                /*
                 * The client sent an etag which is still valid, no body (or anything else) necesary
                 */
// :TODO: Check if http_response_code(304) is good enough, or if header("HTTP/1.1 304 Not Modified") is required
//                header("HTTP/1.1 304 Not Modified");

// :TODO: Should the next lines be deleted or not? Investigate if 304 should again return the etag or not
//                header('Cache-Control: '.$params['cache']['policy']);
//                header('ETag: "'.$core->register['etag'].'"');
                http_response_code(304);
                die();
            }
        }

        return true;

    }catch(Exception $e){
        throw new bException('http_cache_test(): Failed', $e);
    }
}



/*
 * Return HTTP caching headers
 *
 * Returns headers Cache-Control and ETag
 *
 * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
 * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
 */
function http_cache($params, $http_code, $etag, $headers = array()){
    global $_CONFIG, $core;

    try{
        array_params($params);
        array_default($params['cache'], 'max_age', $_CONFIG['cache']['http']['max_age']);

        if($_CONFIG['cache']['http']['enabled'] === 'auto'){
            /*
             * PHP will take care of the cache headers
             */

            if(!$_CONFIG['cache']['http']['enabled'] or (($http_code != 200) and ($http_code != 304))){
                /*
                 * Non HTTP 200 / 304 pages should NOT have cache enabled!
                 * For example 404, 505 max-age etc...
                 */
                $params['cache']['policy']       = 'no-store';
                $params['cache']['expires']      = '0';

                $core->register['etag'] = null;
                $expires                = 0;

            }else{
                if(empty($core->register['etag'])){
                    if(!empty($core->register['flash'])){
                        $core->register['etag'] = md5(str_random());
                        $params['cache']['policy']       = 'no-store';

                    }else{
                        $core->register['etag'] = md5(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']).isset_get($etag));
                    }
                }

                if(!empty($core->callType('ajax')) or !empty($core->callType('api'))){
                    $params['cache']['policy'] = 'no-store';
                    $expires          = '0';

                }else{
                    if(!empty($core->callType('admin')) or !empty($_SESSION['user']['id'])){
                        array_default($params['cache'], 'policy', 'no-store');

                    }else{
                        array_default($params['cache'], 'policy', $_CONFIG['cache']['http']['policy']);
                    }

                    /*
                     * Extract expires time from cache-control header
                     */
                    preg_match_all('/max-age=(\d+)/', $params['cache']['policy'], $matches);

                    $expires = new DateTime();
                    $expires = $expires->add(new DateInterval('PT'.isset_get($matches[1][0], 0).'S'));
                    $expires = $expires->format('D, d M Y H:i:s \G\M\T');
                }
            }

            if(empty($params['cache']['policy'])){
                if($core->callType('admin')){
                    /*
                     * Admin pages, never store, always private
                     */
                    $params['cache']['policy'] = 'no-store';

                }elseif(empty($_SESSION['user'])){
                    /*
                     * Anonymous user, all can be stored
                     */
                    $params['cache']['policy'] = 'public';

                }else{
                    /*
                     * User session, must always be private!
                     */
                    $params['cache']['policy'] = 'no-cache, private';
                }
            }

            switch($params['cache']['policy']){
                case 'no-store':
                    $headers[] = 'Cache-Control: '.$params['cache']['policy'];
                    break;

                case 'no-cache':
                    // FALLTHROUGH
                case 'public':
                    // FALLTHROUGH
                case 'private':
                    // FALLTHROUGH
                case 'no-cache, public':
                    // FALLTHROUGH
                case 'no-store, no-cache, must-revalidate':
                    $headers[] = 'Cache-Control: '.$params['cache']['policy'].', max-age='.$params['cache']['max_age'];
                    $headers[] = 'Expires: '.$expires;
                    $headers[] = 'Cache-Control: post-check='.$params['cache']['post-check'].', pre-check='.$params['cache']['pre-check'].', false';

                    if(!empty($core->register['etag'])){
                        $headers[] = 'ETag: "'.$core->register['etag'].'"';
                    }

                case 'no-cache, private':
                    $headers[] = 'Cache-Control: '.$params['cache']['policy'].', max-age='.$params['cache']['max_age'];
                    $headers[] = 'Expires: '.$expires;

                    if(!empty($core->register['etag'])){
                        $headers[] = 'ETag: "'.$core->register['etag'].'"';
                    }

                    break;

                default:
                    throw new bException(tr('http_cache(): Unknown cache policy ":policy" detected', array(':policy' => $params['cache']['policy'])), 'unknown');
            }
        }

        return $headers;

    }catch(Exception $e){
        throw new bException('http_cache(): Failed', $e);
    }
}



/*
 * Return the URL the client requested
 */
function requested_url(){
    try{
        return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    }catch(Exception $e){
        throw new bException('requested_url(): Failed', $e);
    }
}



// :TODO: Use domain() for this
///*
// * Redirect to the specified URL with the required langauge
// *
// * ....
// *
// * @author Sven Olaf Oostenbrink <sven@capmega.com>
// * @copyright Copyright (c) 2018 Capmega
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package http
// *
// * @param string $url The URL that should be redirected to. This URL shoudl contain the string ":language" which will be replaced with the correct language code
// * @return void This function will end script execution
// */
//function http_language_redirect($url, $language = null){
//    global $_CONFIG;
//
//    try{
//        if(!$_CONFIG['language']['supported']){
//            throw new bException(tr('http_language_redirect(): Multiple languages is not supported by configuration, see $_CONFIG[language][supprted]'), 'not-supported');
//        }
//
//        /*
//         * If language wasn't specified, then detect requested language. If that
//         * is not specified, then see if the user has a current language in
//         * their session. If that isn't specified either, then just get the
//         * default language for this website
//         */
//        if(empty($language)){
//            if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
//                $language = str_cut($_SERVER['HTTP_ACCEPT_LANGUAGE'], ',', ';');
//
//            }elseif(!empty($_SESSION['language'])){
//                $language = $_SESSION['language'];
//
//            }else{
//                $language = $_CONFIG['language']['default'];
//            }
//
//        }else{
//            /*
//             * Validate the requested language
//             */
//            if(!preg_match('/^[a-z]{2}/$', $language)){
//                throw new bException(tr('http_language_redirect(): Invalid language ":language" specified, it should be an ISO 639-1 code', array(':language' => $language)), 'invalid');
//            }
//
//            if(empty($_CONFIG['language']['supported'][$language])){
//                throw new bException(tr('http_language_redirect(): Unknown language ":language" specified, see $_CONFIG[language][supprted]', array(':language' => $language)), 'not-supported');
//            }
//        }
//
//        /*
//         * Is the requested language supported?
//         */
//        if(empty($_CONFIG['language']['supported'][$language])){
//            /*
//             * Nop, redirect to default language
//             */
//            notify(array('' => tr(''),
//                         '' => tr(''),
//                         '' => tr('')));
//            $language = $_CONFIG['language']['default'];
//        }
//
//        redirect(str_replace(':language', $language, $url));
//
//    }catch(Exception $e){
//        throw new bException('http_language_redirect(): Failed', $e);
//    }
//}



/*
 * Sets and returns $_GET[count] data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 *
 * @return integer The
 */
function set_count(){
    try{
        $_GET['limit'] = force_natural(isset_get($_GET['count'], 1));
        return $_GET['limit'];

    }catch(Exception $e){
        throw new bException(tr('set_count(): Failed'), $e);
    }
}



/*
 * Sets and returns $_GET[limit] data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 *
 * @return integer The
 */
function set_limit(){
    global $_CONFIG;

    try{
        $_GET['count'] = (integer) ensure_value(isset_get($_GET['limit'], $_CONFIG['paging']['limit']), array_keys($_CONFIG['paging']['list']), $_CONFIG['paging']['limit']);
        return $_GET['count'];

    }catch(Exception $e){
        throw new bException(tr('set_limit(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 *
 *
 */
function http_done(){
    global $core;

    try{
        if(!isset($core)){
            /*
             * We died very early in startup. For more information see either
             * the ROOT/data/log/syslog file, or your webserver log file
             */
            die('Exception: See log files');
        }

        if($core === false){
            /*
             * Core wasn't created yet, but uncaught exception handler basically
             * is saying that's okay, just warning stuff
             */
            die();
        }

        if($core and empty($core->register['ready'])){
            /*
             * We died before the $core was ready. For more information, see
             * the ROOT/data/log/syslog file, or your webserver log file
             */
            die('Exception: See log files');
        }

        $exit_code = isset_get($core->register['exit_code'], 0);

        if(!defined('ENVIRONMENT')){
            /*
             * Oh crap.. Environment hasn't been defined, so we died VERY soon.
             */
            return false;
        }

        /*
         * Do we need to run other shutdown functions?
         */
        shutdown();

    }catch(Exception $e){
        throw new bException('http_done(): Failed', $e);
    }
}
?>
