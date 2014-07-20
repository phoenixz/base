<?php
/*
 * Store the POST data temporarily in $_SESSION to post it later when the page returns
 *
 * This system is designed to avoid this process: POST > Oops, session expired > goto sign in > return to original page > Damnit, have to fill out that form again!
 *
 * By design, it REQUIRES that the immediate next page is the $redirect, and the immediate next page is the target SCRIPT. If not, the POST will be cleared.
 */
try{
    if(empty($_POST)){
        throw new lsException(tr('store_post(): $_POST is empty'), 'postempty');
    }

    $_SESSION['post'] = array('redirect' => $redirect,
                              'target'   => SCRIPT,
                              'type'     => ($GLOBALS['page_is_admin'] ? 'admin' : ($GLOBALS['page_is_mobile'] ? 'mobile' : 'normal')),
                              'post'     => $_POST);

}catch(Exception $e){
    throw new lsException('store_post(): Failed', $e);
}
?>
