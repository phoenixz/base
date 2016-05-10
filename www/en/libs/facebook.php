<?php
/*
 * Facebook library
 *
 * This library contains various facebook functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 *
 */
// :TEST: Are both ext/facebook,ext/fb needed? or only one?
load_libs('ext/facebook');

use Facebook\HttpClients\FacebookHttpable;
use Facebook\HttpClients\FacebookCurl;
use Facebook\HttpClients\FacebookCurlHttpClient;

use Facebook\Entities\AccessToken;
use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\FacebookSDKException;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;
use Facebook\FacebookOtherException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphObject;
use Facebook\GraphSessionInfo;



/*
 *
 */
function facebook_post_message($msg, $token) {
    global $_CONFIG;

    try{
        $app_id     = $_CONFIG['sso']['facebook']['appid'];
        $app_secret = $_CONFIG['sso']['facebook']['secret'];

        FacebookSession::setDefaultApplication($app_id, $app_secret);

        $message  = array ('message' => $msg,
                           'link'    => domain('/'));

        $session  = new FacebookSession($token);
        $request  = new FacebookRequest($session, 'POST', '/me/feed', $message);
        $response = $request->execute()->getGraphObject()->asArray();

        return $response;

    }catch(Exception $e){
        throw new bException('facebook_post_message(): Failed', $e);
    }
}



/*
 *
 */
function facebook_redirect_to_authorize($return = false) {
    global $_CONFIG;

    try{
        $app_id     = $_CONFIG['sso']['facebook']['appid'];
        $app_secret = $_CONFIG['sso']['facebook']['secret'];
        $scope      = $_CONFIG['sso']['facebook']['scope'];
        $redirect   = $_CONFIG['sso']['facebook']['redirect'];

        FacebookSession::setDefaultApplication($app_id, $app_secret);
        $helper = new FacebookRedirectLoginHelper($redirect);

        if($return){
            return $helper->getLoginUrl($scope);
        }

        redirect($helper->getLoginUrl($scope));

    }catch(Exception $e){
        throw new bException('facebook_redirect_to_authorize(): Failed', $e);
    }
}



/*
 *
 */
function facebook_get_user_token() {
    global $_CONFIG;

    try{
        $app_id     = $_CONFIG['sso']['facebook']['appid'];
        $app_secret = $_CONFIG['sso']['facebook']['secret'];
        $redirect   = $_CONFIG['sso']['facebook']['redirect'];

        FacebookSession::setDefaultApplication($app_id, $app_secret);

        $helper  = new FacebookRedirectLoginHelper($redirect);
        $session = $helper->getSessionFromRedirect();

        if($session){
            return $session->getToken();
        }

    }catch(FacebookRequestException $e) {
        throw new bException('facebook_get_user_token(): Failed with FacebookRequestException', $e);

    }catch(\Exception $e) {
        throw new bException('facebook_get_user_token(): Failed', $e);
    }
}



/*
 *
 */
function facebook_user_info($token) {
    global $_CONFIG;

    try{
        $app_id     = $_CONFIG['sso']['facebook']['appid'];
        $app_secret = $_CONFIG['sso']['facebook']['secret'];

        FacebookSession::setDefaultApplication($app_id, $app_secret);

        $session  = new FacebookSession($token);
        $request  = new FacebookRequest($session, 'GET', '/me');
        $response = $request->execute()->getGraphObject(GraphUser::className());

        return $response;

    }catch(Exception $e){
        throw new bException('facebook_user_info(): Failed', $e);
    }
}



/*
 *
 */
// :TODO: Update so that this works okay for base project!
function facebook_signin(){
    global $_CONFIG;

    try{
        //Create our Application instance (replace this with your appId and secret).
        $facebook = new Facebook(array('appId'  => $_CONFIG['sso']['facebook']['appid'],
                                       'secret' => $_CONFIG['sso']['facebook']['secret'],
                                       'cookie' => false));

        $fbuser   = $facebook->getUser();

        if($fbuser) {
            $fb_data          = $facebook->api('/me');

            //access token!
            $access_token     = $facebook->getAccessToken();

            //store for later use
            $fb_data['token'] = $access_token;

            return $fb_data;

            ////check if facebook email has some matching account on one of the servers.
            //$user = sql_get("SELECT * FROM users WHERE fb_id = '".cfm($fb_data['id'])."'");
            //
            //if($user['uid'] > 0) {
            //    //known fb user
            //    sql_query("update users set fb_token='".cfm($access_token)."' where uid='".cfm($user['uid'])."'");
            //
            //    if($user['verified']==0) {
            //        sql_query("update users set verified='".time()."' where uid='".cfm($user['uid'])."'");
            //    }
            //
            //    add_stat('USER_FB_LOGIN');
            //    user_login($user);
            //    //do extended login
            //    user_create_extended_session($user['uid']);
            //    redirect('index.php',false,true);
            //
            //} else {
            //    //unknown fbuser
            //    //find matching user by email
            //    $user = sql_get("select * from users where email='".cfm($fb_data['email'])."';");
            //
            //    if($user['uid'] > 0) {
            //        sql_query("update users set fb_id='".cfm($fb_data['id'])."',fb_token='".cfm($access_token)."',verified=1 where uid='".cfm($user['uid'])."'");
            //        add_stat('USER_FB_LINKED_TO_EXISITING_USER');
            //        user_login($user);
            //        //do extended login
            //        user_create_extended_session($user['uid']);
            //        redirect('index.php',false,true);
            //
            //    } else {
            //        //need to make up some username
            //        $found    = false;
            //        $username = sgapps-ics-20120429-signed.ziptolower(preg_replace("/[^A-Za-z0-9]/", '', $fb_data['name']));
            //        $tel      = 1;
            //
            //        while(!$found and !strlen($username) and !in_array($username,$_CONFIG['bl_usernames'])) {
            //            $test = sql_get("select uid from users where username='".cfm($username)."';");
            //
            //            if($test['uid']>0) {
            //                $username = strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $fb_data['name'])).$tel;
            //
            //            } else {
            //                $found    = true;
            //            }
            //        }
            //
            //        //Location
            //        $ccode    = country_from_ip();
            //        $location = sql_get("select country_es as country from countries where ccode='".strtolower($ccode)."';");
            //
            //        // 'Unable to match facebook account with user, create account
            //        sql_query("insert into users (
            //            email
            //            ,name
            //            ,username
            //            ,password
            //            ,fb_id
            //            ,fb_token
            //            ,date_created
            //            ,ccode
            //            ,verified
            //            ,location
            //        ) values (
            //            '".cfm($fb_data['email'])."'
            //            ,'".cfm($fb_data['name'])."'
            //            ,'".$username."'
            //            ,'".sha1($_CONFIG['SHA1_PASSWORD_SEED'].str_random(8))."'
            //            ,'".cfm($fb_data['id'])."'
            //            ,'".cfm($access_token)."'
            //            ,'".time()."'
            //            ,'".$ccode."'
            //            ,1
            //            ,'".$location['country']."'
            //        );");
            //
            //        $uid = sql_insert_id();
            //
            //        //set ccode filter
            //        set_user_ccode_filter('all',$uid);
            //
            //        //fbpost
            //        queue_newuser_post($uid);
            //
            //        //Queue the friend-check.. later we will see if this user already has some friends on estasuper
            //        sql_query("insert into fb_friend_check_queue (fb_id, uid) values ('".cfm($fb_data['id'])."',".cfi($uid).");");
            //
            //        //add to memcached
            //        mc_add_username($username,$uid);
            //
            //        add_stat('USER_FB_NEW_USER');
            //
            //        if($uid > 0) {
            //            //delete email from pending verifcation requests
            //            sql_query("delete from email_verify_requests where email='".cfm($fb_data['email'])."';");
            //
            //            //get avatar
            //            $tmp = '/tmp/tmpavatarfb-'.$uid.'.jpg';
            //            file_put_contents($tmp, file_get_contents('http://graph.facebook.com/'.$fb_data['id'].'/picture?type=large'));
            //
            //            if(file_exists($tmp)) {
            //                //generate target file/location
            //                if($newfile = get_upload_location('avatars',5,'')) {
            //                    //create small avatar
            //                    convert_image($tmp,ROOT.'/'.$newfile.'_small.png',50,50,'thumb-circle');
            //
            //                    //create large avatar
            //                    convert_image($tmp,ROOT.'/'.$newfile.'_big.png',200,200,'thumb-circle');
            //                    sql_query("update users set avatar='".$newfile."' where uid=".cfi($uid).";");
            //                }
            //
            //                unlink($tmp);
            //            }
            //
            //            //log user in
            //            $user = sql_get("select * from users where uid='".cfm($uid)."';");
            //
            //            user_login($user);
            //
            //            //do extended login
            //            user_create_extended_session($user['uid']);
            //            redirect('index.php',false,true);
            //
            //        } else {
            //            add_system_msg(('Unable to create your user account'),'ERROR');
            //            //this should never happen
            //            redirect('index.php',false,true);
            //        }
            //    }
            //}

        } else {
            //Try to login into facebook and get authorization for Mex.tl application.
            redirect($facebook->getLoginUrl(array('scope'        => $_CONFIG['sso']['facebook']['scope'],
                                                  'redirect_uri' => $_CONFIG['sso']['facebook']['redirect'])), false);
        }

    }catch(Exception $e){
        throw new bException('facebook_connect(): Failed', $e);
    }
}



/*
 * Get the avatar from the facebook account of the specified user
 */
function facebook_get_avatar($user){
    global $_CONFIG;

    try{
        load_libs('file,image,user');

        if(is_array($user)){
            if(empty($user['fb_id'])){
                if(empty($user['id'])){
                    throw new bException('facebook_get_avatar: Specified user array contains no "id" or "fb_id"');
                }

                $user = sql_get('SELECT `fb_id` FROM `users` WHERE `id` = '.cfi($user['id']));
            }

            /*
             * Assume this is a user array
             */
            $user = $user['fb_id'];
        }

        if(!$user){
            throw new bException('facebook_get_avatar(): No facebook ID specified');
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



// :TODO:SVEN:20130712: These functions all came from estasuper, could / should these be generic functions or not? Might not be a bad idea, INVESTIGATE!
///*
// * Load friends from facebook
// */
//function facebook_get_and_store_friends($token, $uid) {
//    global $_CONFIG;
//
//    $facebook = new Facebook(array('appId'  => $_CONFIG['sso']['facebook']['appid'],
//                                   'secret' => $_CONFIG['sso']['facebook']['secret'],
//                                   'cookie' => false));
//
//    try {
//        $facebook->setAccessToken($token);
//        $friends = $facebook->api('/me/friends');
//
//        sql_query("UPDATE users SET last_fb_friend_check = ".time()." WHERE uid=".cfi($uid).";");
//        sleep(3);
//
//        if(is_array($friends['data'])) {
//            //remove old fb_friends data (except the ones that have been notified)
//            sql_query("DELETE FROM fb_friends WHERE uid=".cfi($uid)." AND notified IS NULL;");
//
//            //add new friends
//            foreach($friends['data'] as $key => $friend) {
//                sql_query("INSERT IGNORE INTO fb_friends (uid,fb_id,name) VALUES (".cfi($uid).",".cfi($friend['id']).",'".cfm($friend['name'])."');");
//            }
//
//            return count($friends['data']);
//
//        } else {
//            return 0;
//        }
//
//    } catch(Exception $e) {
//        throw new bException('facebook_get_and_store_friends(): Failed', $e);
//    }
//}
//
//
//
///*
// * add to fb post queue
// */
//function facebook_queue_product_post(&$product,$uid,$username='') {
//    global $_CONFIG;
//
//    try{
//        if(empty($username)) {
//            $user     = load_user_data($uid);
//            $username = $user['username'];
//        }
//        // mail('jcgeuze@gmail.com','test : '.product_url($product['pid'],$product['title'],$product['first_parent']),print_r($product,true));
//    //'message' => str_replace("###PRODUCTNAME###",$product['title'],('Guarda tu producto "###PRODUCTNAME###" en tu lista de cosas deseadas y comparte con tus amigos en EstáSúper!')),
//
//        $message = array('message' => array_get_random(array('Me encanta', 'Me gusta', 'Me fascina', 'Me gustaría tener', 'Lo quiero', 'Cosas que me gustaria tener')),
//                         'link'    => product_url($product['pid'],$product['title'],$product['first_parent']),
//                         'name'    => $product['title'],
//                         'picture' => 'http://'.domain().'/'.$product['image'].'_big.jpg');
//
//        sql_query("INSERT INTO fb_posts_queue (uid,type,data_array,date_added) VALUES (".cfi($uid).",'PRODUCT','".addslashes(serialize($message))."',".time().");");
//
//    } catch(Exception $e) {
//        throw new bException('facebook_queue_product_post(): Failed', $e);
//    }
//}
//
//
//
///*
// *
// */
//function facebook_queue_newuser_post($uid) {
//    try{
//        $message = array('message' => ('I have just signed up to EstáSúper!'),
//                         'link'    => 'http://'.domain(),
//                         'name'    => ('Follow me on EstáSúper!'),
//                         'picture' => 'http://'.domain().'/style/images/esta_super_logo.png');
//
//        sql_query("INSERT INTO fb_posts_queue (uid,type,data_array,date_added) VALUES (".cfi($uid).",'NEWUSER','".addslashes(serialize($message))."',".time().");");
//
//    } catch(Exception $e) {
//        throw new bException('facebook_queue_newuser_post(): Failed', $e);
//    }
//}
//
//
//
///*
// * add follow user post
// */
//function facebook_queue_follow_user_post($uid,$url) {
//    try{
//        sql_query("INSERT INTO fb_posts_queue (uid,type,data_array,date_added) VALUES (".cfi($uid).",'FOLLOW_USER','".$url."',".time().");");
//
//    } catch(Exception $e) {
//        throw new bException('facebook_queue_follow_user_post(): Failed', $e);
//    }
//}
//
//
//
///*
// * add follow user post
// */
//function facebook_queue_follow_collection_post($uid,$url) {
//    try{
//        sql_query("INSERT INTO fb_posts_queue (uid,type,data_array,date_added) VALUES (".cfi($uid).",'FOLLOW_COLLECTION','".$url."',".time().");");
//
//    } catch(Exception $e) {
//        throw new bException('facebook_queue_follow_collection_post(): Failed', $e);
//    }
//}

function facebook_sdk_js() {
    return '<div id="fb-root"></div>
            <script>(function(d, s, id) {
              var js, fjs = d.getElementsByTagName(s)[0];
              if (d.getElementById(id)) return;
              js = d.createElement(s); js.id = id;
              js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.4";
              fjs.parentNode.insertBefore(js, fjs);
            }(document, \'script\', \'facebook-jssdk\'));</script>';
}

function facebook_button($params){
    try{
        /*
         * See https://developers.facebook.com/docs/plugins/like-button
         *     https://developers.facebook.com/docs/plugins/share-button
         */
        array_params($params);
        array_default($params, 'type'      , 'like');    // possible values : 'like' or 'share'
        array_default($params, 'width'     , 225);       // this is the minimal
        array_default($params, 'layout'    , 'button');  // each button type has its own layouts
        array_default($params, 'show-faces', 'false');



        $html = '';

        foreach(array_force($params['type']) as $type){
            switch($type) {
                case 'share':
                    $html .= '<div class="fb-share-button" data-href="'.$params['url'].'"data-layout="'.$params['layout'].'">
                               </div>';
                    break;

                case 'like':
                    $html .= '<div class="fb-like" data-href="'.$params['url'].'"
                                            data-width="'.$params['width'].'" data-layout="'.$params['layout'].'" data-action="like"
                                            data-show-faces="'.$params['show-faces'].'" data-share="false">
                              </div>';
                    break;

                default:
                    throw new bException(tr('facebook_button(): Unknown type "%type%" specified', array('%type%' => $type)), 'unknown');
            }
        }

        return $html;

    }catch(Exception $e){
        throw new bException('facebook_button(): Failed', $e);
    }
}
?>
