<?php
/*
 * Restore a POST
 */
try{
    if(!is_array($_SESSION['post'])){
        throw new bException(tr('restore_post(): Invalid POST data found'), 'invalidpostdata');
    }

    /*
     * Check page type, either admin, normal or mobile, and the session stored
     * type has to match the current page type!
     */
    switch(isset_get($_SESSION['post']['type'])){
        case 'normal':
            if(!empty($GLOBALS['page_is_admin']) or !empty($GLOBALS['page_is_mobile'])){
                /*
                 * Stored post type is for normal pages, but we're either on an admin page or on a mobile page. Dump post data from session!
                 */
                unset($_SESSION['post']);
                return false;
            }

            break;

        case 'admin':
            if(empty($GLOBALS['page_is_admin']) or !empty($GLOBALS['page_is_mobile'])){
                /*
                 * Stored post type is for admin pages, but we're either on a normal page or on a mobile page. Dump post data from session!
                 */
                unset($_SESSION['post']);
                return false;
            }

            break;

        case 'mobile':
            if(!empty($GLOBALS['page_is_admin']) or empty($GLOBALS['page_is_mobile'])){
                /*
                 * Stored post type is for normal pages, but we're either on an admin page or on a normal page. Dump post data from session!
                 */
                unset($_SESSION['post']);
                return false;
            }

            break;

        default:
            throw new bException(tr('restore_post(): Invalid POST data type "'.str_log(isset_get($_SESSION['post']['type'])).'" found'), 'invalidposttype');
    }

    /*
     * Type is alright, now check if we're either on the redirect page, OR target page
     *
     * NOTE: Due to mod_rewrite and such, redirect MAY be completely different from SCRIPT (which basically is
     * just the current start script name without extension), and depending on redirect rules, "redirect" MAY be
     * completely different and non related to SCRIPT at all. For now, the restore post ONLY supports redirections
     * to pages that have the same basename (that is, file name ignoring the extension) as SCRIPT
     */
    if(str_until(isset_get($_SESSION['post']['redirect']), '.') == SCRIPT){
        /*
         * We're on the redirect page, do nothing
         */
        return false;
    }

    /*
     * Current page is NOT the redirect page. This MUST mean we are on the target page again, OR we will clear this post from $_SESSION!
     */
    if(isset_get($_SESSION['post']['target']) != SCRIPT){
        unset($_SESSION['post']);
        return false;
    }

    /*
     * All okay, restore $_POST!
     */
    $_POST = $_SESSION['post']['post'];
    unset($_SESSION['post']);
    return true;

}catch(Exception $e){
    throw new bException('restore_post(): Failed', $e);
}
?>
