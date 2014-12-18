<?php
try{
    global $_CONFIG;

    if(PLATFORM != 'apache'){
        throw new bException('redirect(): This function can only be called on webservers');
    }

    /*
     * Special targets?
     */
    if(($target === true) or ($target === 'self')){
        /*
         * Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid "confirm post submissions"
         */
        $target = $_SERVER['REQUEST_URI'];

    }elseif($target === false){
        /*
         * Special redirect. Redirect to this very page, but without query
         */
        $target = $_SERVER['PHP_SELF'];

    }elseif(!$target){
        /*
         * No target specified, redirect to index page
         */
        $target = $_CONFIG['redirects']['index'];
    }

    if(is_numeric($clear_session_redirect)){
        $http_code              = $clear_session_redirect;
        $clear_session_redirect = true;

    }else{
        $http_code              = 301;
    }

    /*
     * Validate the specified http_code, must be one of
     *
     * 301 Moved Permanently
     * 302 Found
     * 303 See Other
     * 307 Temporary Redirect
     */
    switch($http_code){
        case 301:
            // FALLTHROUGH
        case 302:
            // FALLTHROUGH
        case 303:
            // FALLTHROUGH
        case 307:
            /*
             * All valid
             */
            break;

        default:
            throw new bException('redirect(): Invalid HTTP code "'.str_log($http_code).'" specified', 'invalid_http_code');
    }

    if($clear_session_redirect){
        unset($_SESSION['redirect']);
        unset($_SESSION['sso_referrer']);
    }

    if((substr($target, 0, 1) != '/') and (substr($target, 0, 7) != 'http://') and (substr($target, 0, 8) != 'https://')){
        $target = $_CONFIG['root'].$target;
    }

    header("Location:".$target, true, $http_code);
    die();

}catch(Exception $e){
    throw new bException('redirect(): Failed', $e);
}
?>
