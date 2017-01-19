<?php
/*
 * Google library
 *
 * This library contains all kinds of basic google related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Get the avatar from the google account of the specified user
 */
function google_get_avatar($user){
    global $_CONFIG;

    try{
        load_libs('file,image,user');

        if(is_array($user)){
            if(empty($user['gp_id'])){
                if(empty($user['id'])){
                    throw new bException('google_get_avatar: Specified user array contains no "id" or "gp_id"');
                }

                $user = sql_get('SELECT `gp_id` FROM `users` WHERE `id` = '.cfi($user['id']));
            }

            /*
             * Assume this is a user array
             */
            $user = $user['gp_id'];
        }

        if(!$user){
            throw new bException('google_get_avatar(): No google ID specified');
        }

        // Avatars are on http://graph.facebook.com/USERID/picture
        $file   = TMP.file_move_to_target('http://graph.facebook.com/'.$user.'/picture?type=large', TMP, '.jpg');

        // Create the avatars, and store the base avatar location
        $retval = image_create_avatars($file);

        // Clear the temporary file and cleanup paths
        file_clear_path($file);

        // Update the user avatar
        return user_update_avatar($user, $retval);

    }catch(Exception $e){
        throw new bException('facebook_get_avatar(): Failed', $e);
    }
}



/*
 * Returns the necessary javascript for adding a google analytics code to a page
 */
function google_get_analytics($code){
    global $_CONFIG;

    try{
        $analytics_js = "<script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','/pub/google/analytics.js','ga');

        ga('create', '".$code."', 'auto');
        ga('send', 'pageview');
        </script>";

        return $analytics_js;

    }catch(Exception $e){
        throw new bException('google_get_analytics(): Failed', $e);
    }
}
?>
