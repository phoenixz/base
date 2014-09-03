<?php
try{
    global $_CONFIG;

    if(PLATFORM != 'apache'){
        throw new bException('redirect(): This function can only be called on webservers');
    }

    if(!$target){
        $target = $_CONFIG['redirects']['index'];
    }

    if($target == 'self'){
        /*
         * Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid "confirm post submissions"
         */
        $target = $_SERVER['REQUEST_URI'];
    }

    if($clear_session_redirect){
        unset($_SESSION['redirect']);
        unset($_SESSION['sso_referrer']);
    }

    if((substr($target, 0, 1) != '/') and (substr($target, 0, 7) != 'http://') and (substr($target, 0, 8) != 'https://')){
        $target = $_CONFIG['root'].$target;
    }

    header("Location:".$target);
    die();

}catch(Exception $e){
    throw new bException('redirect(): Failed', $e);
}
?>
