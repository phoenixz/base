<?php
try{
    switch($type){
        case 'normal':
            $_SESSION['mobile']['site'] = false;
            break;

        case 'mobile':
            $_SESSION['mobile']['site'] = true;
            break;

        default:
            throw new bException('switch_type(): Unknown type "'.$type.'" specified', 'unknown');
    }

    if(!empty($redirect)){
        if(isset_get($_SESSION['redirect']) != $redirect){
            /*
             * Remember this one to avoid endless redirecting (Lookin at you there, google talk!)
             */
            $_SESSION['redirect'] = $redirect;
            redirect($redirect, false);
        }

        /*
         * Going for an endless loop, clear all, and go to main page
         */
        unset($_SESSION['redirect']);
    }

    if(!empty($_SERVER['HTTP_REFERER'])){
        if(isset_get($_SESSION['redirect']) != $_SERVER['HTTP_REFERER']){
            /*
             * Remember this one to avoid endless redirecting (Lookin at you there, google talk!)
             */
            $_SESSION['redirect'] = $_SERVER['HTTP_REFERER'];
            redirect($_SERVER['HTTP_REFERER'], false);
        }

        /*
         * Going for an endless loop, clear all, and go to main page
         */
        unset($_SESSION['redirect']);
    }

    if(!empty($_SESSION['redirect'])){
        redirect($_SESSION['redirect']);
    }

    redirect();

}catch(Exception $e){
    throw new bException('switch_type(): Failed', $e);
}
?>
