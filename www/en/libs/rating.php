<?php
/*
 * Rating library
 *
 * Library to manage HTML star ratings, google ratings, etc
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Show specified rating
 */
function rating_html($stars){
    try{
//    $(".star").raty({
//        starOff: "pub/img/base/raty/star-off.png",
//        starOn : "pub/img/base/raty/star-on.png"
//    });


    }catch(Exception $e){
        throw new bException('rating(): Failed', $e);
    }
}
?>
