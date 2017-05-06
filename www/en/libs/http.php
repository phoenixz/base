<?php
/*
 * HTTP library, containing all sorts of HTTP functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Return complete current domain with HTTP and all
 */
function current_domain($current_url = false, $query = null, $root = null){
    global $_CONFIG;

    try{
        if($root === null){
            $root = $_CONFIG['root'];
        }

        $root = unslash($root);

        if(PLATFORM_HTTP){
            if(empty($_SERVER['SERVER_NAME'])){
                $server_name = $_SESSION['domain'];

            }else{
                $server_name = $_SERVER['SERVER_NAME'];
            }

        }else{
            $server_name = $_CONFIG['domain'];
        }

        if(!$current_url){
            $retval = $_CONFIG['protocol'].$server_name.$root;

        }elseif($current_url === true){
            $retval = $_CONFIG['protocol'].$server_name.$_SERVER['REQUEST_URI'];

        }else{
            $retval = $_CONFIG['protocol'].$server_name.str_ends(str_starts($root, '/'), '/').str_starts_not($current_url, '/');
        }

        if($query){
            load_libs('inet');
            $retval = url_add_query($retval, $query);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('current_domain(): Failed', $e);
    }
}



/*
 * Return $_POST[dosubmit] value, and reset it to be sure it won't be applied twice
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
 * Redirect
 */
function redirect($target = '', $http_code = null, $clear_session_redirect = true){
    return include(__DIR__.'/handlers/http_redirect.php');
}



/*
 * Give URL with redirect value, IF specified
 */
function redirect_url($url = null){
    try{
        if(!$url){
            /*
             * Default to this page
             */
            $url = domain(true);
        }

        if(empty($GLOBALS['redirect'])){
            return $url;
        }

        return url_add_query($url, 'redirect='.urlencode($GLOBALS['redirect']));

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
 * Restore post data from $_SESSION IF available
 */
function restore_post(){
    if(empty($_SESSION['post'])){
        return false;
    }

    return include(__DIR__.'/handlers/system_restore_post.php');
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
    global $_CONFIG;
    static $sent = false;

    if($sent) return false;
    $sent = true;

    try{
        array_params($params, 'http_code');
        array_default($params, 'http_code', 200);
        array_default($params, 'cors'     , false);
        array_default($params, 'mimetype' , 'text/html');
        array_default($params, 'headers'  , array());

//header("HTTP/1.0 404 Not Found");
//die('TEST');
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
                $headers[] = 'Last-Modified: '.system_date_format(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT').' GMT';

            }else{
                $headers[] = 'Last-Modified: '.system_date_format($params['last_modified'], 'D, d M Y H:i:s', 'GMT').' GMT';
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
                        throw new bException(tr('http_headers(: Unknown CORS header "%header%" specified', array('%header%' => $key)), 'unknown');
                }
            }
        }

        $headers = http_cache($params, $headers);

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
 * For PHP < 5.4.0, where http_response_code will be missing
 * Taken from http://php.net/manual/en/function.http-response-code.php
 * Thanks to craig at craigfrancis dot co dot uk
 */
if (!function_exists('http_response_code')){
    include(__DIR__.'/handlers/http_response_code.php');
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
    global $_CONFIG;

    try{
        $GLOBALS['etag'] = md5(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']).$etag);

        if(!$_CONFIG['cache']['http']['enabled']){
            return false;
        }

        if($GLOBALS['page_is_ajax'] or $GLOBALS['page_is_api']){
            return false;
        }

        if((strtotime(isset_get($_SERVER['HTTP_IF_MODIFIED_SINCE'])) == filemtime($_SERVER['SCRIPT_FILENAME'])) or trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == $GLOBALS['etag']){
            if(empty($GLOBALS['flash'])){
                /*
                 * The client sent an etag which is still valid, no body (or anything else) necesary
                 */
// :TODO: Check if http_response_code(304) is good enough, or if header("HTTP/1.1 304 Not Modified") is required
//                header("HTTP/1.1 304 Not Modified");

// :TODO: Should the next lines be deleted or not? Investigate if 304 should again return the etag or not
//                header('Cache-Control: '.$params['policy']);
//                header('ETag: "'.$GLOBALS['etag'].'"');
                http_response_code(304);
                die();
            }
        }

        return true;

    }catch(Exception $e){
        throw new bException('http_cache(): Failed', $e);
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
function http_cache($params, $headers = array()){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'max_age', $_CONFIG['cache']['http']['max_age']);

        if(!$_CONFIG['cache']['http']['enabled'] or (($params['http_code'] != 200) and ($params['http_code'] != 304))){
            /*
             * Non HTTP 200 / 304 pages should NOT have cache enabled!
             * For example 404, 505, etc...
             */
            $params['policy']     = 'no-store';
            $params['expires']    = '0';

            $GLOBALS['etag']      = null;
            $expires              = 0;

        }else{
            if(empty($GLOBALS['etag'])){
                if(!empty($GLOBALS['flash'])){
                    $GLOBALS['etag']  = md5(str_random());
                    $params['policy'] = 'no-store';

                }else{
                    $GLOBALS['etag'] = md5(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']).isset_get($params['etag']));
                }
            }

            if(!empty($GLOBALS['page_is_ajax']) or !empty($GLOBALS['page_is_api'])){
                $params['policy'] = 'no-store';
                $expires          = '0';

            }else{
                if(!empty($GLOBALS['page_is_admin']) or !empty($_SESSION['user']['id'])){
                    array_default($params, 'policy', 'no-store');

                }else{
                    array_default($params, 'policy', $_CONFIG['cache']['http']['policy']);
                }

                /*
                 * Extract expires time from cache-control header
                 */
                preg_match_all('/max-age=(\d+)/', $params['policy'], $matches);

                $expires = new DateTime();
                $expires = $expires->add(new DateInterval('PT'.isset_get($matches[1][0], 0).'S'));
                $expires = $expires->format('D, d M Y H:i:s \G\M\T');
            }
        }

        if(empty($params['policy'])){
            if($GLOBALS['page_is_admin']){
                /*
                 * Admin pages, never store, always private
                 */
                $params['policy'] = 'no-store';

            }elseif(empty($_SESSION['user'])){
                /*
                 * Anonymous user, all can be stored
                 */
                $params['policy'] = 'public';

            }else{
                /*
                 * User session, must always be private!
                 */
                $params['policy'] = 'no-cache, private';
            }
        }

        switch($params['policy']){
            case 'no-store':
                $headers[] = 'Cache-Control: '.$params['policy'];
                break;

            case 'no-cache':
                // FALLTHROUGH
            case 'public':
                // FALLTHROUGH
            case 'private':
                // FALLTHROUGH
            case 'no-cache, public':
                // FALLTHROUGH
            case 'no-cache, private':
                $headers[] = 'Cache-Control: '.$params['policy'].', max-age='.$params['max_age'];
                $headers[] = 'Expires: '.$expires;

                if(!empty($GLOBALS['etag'])){
                    $headers[] = 'ETag: "'.$GLOBALS['etag'].'"';
                }

                break;

            default:
                throw new bException(tr('http_cache(): Unknown cache policy ":policy" detected', array(':policy' => $params['policy'])), 'unknown');
        }

        /*
         * Disable PHP caching stuff
         */
        session_cache_limiter('');
        session_cache_expire(0);

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



/*
 * Check for URL's with queries. Depending on configuration, 301 direct to URL without query
 */
function http_redirect_query_url(){
    global $_CONFIG;

    try{
        if(!empty($GLOBALS['page_is_ajax']) or !empty($GLOBALS['page_is_admin']) or !empty($GLOBALS['no_query_url_redirect']) or !empty($GLOBALS['no_redirect_http_queries'])){
            return true;
        }

        if($GLOBALS['page_is_admin']){
            if(!$_CONFIG['redirects']['query']){
                /*
                 * No need to auto redirect URL's with queries
                 */
                return true;
            }

        }else{
            if(!$_CONFIG['redirects']['query']){
                /*
                 * No need to auto redirect URL's with queries
                 */
                return true;
            }
        }

        if(($pos = strpos($_SERVER['REQUEST_URI'], '?')) === false){
            /*
             * URL contains no ? query mark
             */
            return true;
        }

        redirect(current_domain(substr($_SERVER['REQUEST_URI'], 0, $pos)));

    }catch(Exception $e){
        throw new bException('http_redirect_query_url(): Failed', $e);
    }
}



/*
 * Redirect to the requested langauge
 */
function http_language_redirect($url, $language = null){
    global $_CONFIG;

    try{
        /*
         * If language wasn't specified, then detect requested language. If that
         * is not specified, then see if the user has a current language in
         * their session. If that isn't specified either, then just get the
         * default language for this website
         */
        if(empty($language)){
            if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
                $language = str_cut($_SERVER['HTTP_ACCEPT_LANGUAGE'], ',', ';');

            }elseif(!empty($_SESSION['language'])){
                $language = $_SESSION['language'];

            }else{
                $language = $_CONFIG['language']['default'];
            }
        }

        /*
         * Is the requested language supported?
         */
        if(empty($_CONFIG['language']['supported'][$language])){
            /*
             * Nop, redirect to default language
             */
            $language = $_CONFIG['language']['default'];
        }

        redirect(str_replace(':language', $language, $url));

    }catch(Exception $e){
        throw new bException('http_language_redirect(): Failed', $e);
    }
}
?>
