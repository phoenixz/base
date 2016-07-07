<?php
/*
 *     Oauth.php
 *
 *    Created by Jon Hurlock on 2013-03-20.
 *    Modified for use in sven Base by Sven Oostenbrink on 2013 06 03
 *
 *    Jon Hurlock's Twitter Application-only Authentication App by Jon Hurlock (@jonhurlock)
 *    is licensed under a Creative Commons Attribution-ShareAlike 3.0 Unported License.
 *    Permissions beyond the scope of this license may be available at http://www.jonhurlock.com/.
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_libs('ext/twitteroauth/twitteroauth');



/*
 *    Get the Bearer Token, this is an implementation of steps 1&2
 *    from https://dev.twitter.com/docs/auth/application-only-auth
 */
function twitter_get_bearer_token(){
    global $_CONFIG;

    try{
        // Step 1
        // step 1.1 - url encode the consumer_key and consumer_secret in accordance with RFC 1738
        $encoded_consumer_key    = urlencode($_CONFIG['sso']['twitter']['appid']);
        $encoded_consumer_secret = urlencode($_CONFIG['sso']['twitter']['secret']);

        // step 1.2 - concatinate encoded consumer, a colon character and the encoded consumer secret
        $bearer_token = $encoded_consumer_key.':'.$encoded_consumer_secret;

        // step 1.3 - base64-encode bearer token
        $base64_encoded_bearer_token = base64_encode($bearer_token);

        // step 2
        $url = "https://api.twitter.com/oauth2/token"; // url to send data to for authentication
        $headers = array(
            "POST /oauth2/token HTTP/1.1",
            "Host: api.twitter.com",
            "User-Agent: jonhurlock Twitter Application-only OAuth App v.1",
            "Authorization: Basic ".$base64_encoded_bearer_token."",
            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8",
            "Content-Length: 29"
        );

        $ch = curl_init();  // setup a curl

        curl_setopt($ch, CURLOPT_URL           , $url);  // set url to send to
        curl_setopt($ch, CURLOPT_HTTPHEADER    , $headers); // set custom headers
        curl_setopt($ch, CURLOPT_POST          , 1); // send as post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return output
        curl_setopt($ch, CURLOPT_POSTFIELDS    , "grant_type=client_credentials"); // post body/fields to be sent

        $header        = curl_setopt($ch, CURLOPT_HEADER, 1); // send custom headers
        $httpcode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $retrievedhtml = curl_exec ($ch); // execute the curl

        curl_close($ch); // close the curl

        $output       = explode("\n", $retrievedhtml);
        $bearer_token = '';

        foreach($output as $line){
            if(!$line){
                // there was no bearer token

            }else{
                $bearer_token = $line;
            }
        }

        load_libs('json');

        $bearer_token = json_decode_custom($bearer_token);
        return $bearer_token->{'access_token'};

    }catch(Exception $e){
        throw new bException('twitter_get_bearer_token(): Failed', $e);
    }
}



/*
 * Invalidates the Bearer Token
 * Should the bearer token become compromised or need to be invalidated for any reason,
 * call this method/function.
 */
function twitter_invalidate_bearer_token($bearer_token){
    global $_CONFIG;

    try{
        $encoded_consumer_key          = urlencode($_CONFIG['sso']['twitter']['appid']);
        $encoded_consumer_secret       = urlencode($_CONFIG['sso']['twitter']['secret']);
        $consumer_token                = $encoded_consumer_key.':'.$encoded_consumer_secret;
        $base64_encoded_consumer_token = base64_encode($consumer_token);

        // step 2
        $url = "https://api.twitter.com/oauth2/invalidate_token"; // url to send data to for authentication

        $headers = array('POST /oauth2/invalidate_token HTTP/1.1',
                         'Host: api.twitter.com',
                         'User-Agent: jonhurlock Twitter Application-only OAuth App v.1',
                         'Authorization: Basic '.$base64_encoded_consumer_token,
                         'Accept: */*',
                         'Content-Type: application/x-www-form-urlencoded',
                         'Content-Length: '.(strlen($bearer_token)+13));

        $ch = curl_init();  // setup a curl

        curl_setopt($ch, CURLOPT_URL           ,$url);                              // set url to send to
        curl_setopt($ch, CURLOPT_HTTPHEADER    , $headers);                         // set custom headers
        curl_setopt($ch, CURLOPT_POST          , 1);                                // send as post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                             // return output
        curl_setopt($ch, CURLOPT_POSTFIELDS    , "access_token=".$bearer_token.""); // post body/fields to be sent

        $header        = curl_setopt($ch, CURLOPT_HEADER, 1); // send custom headers
        $httpcode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $retrievedhtml = curl_exec ($ch); // execute the curl

        curl_close($ch); // close the curl
        return $retrievedhtml;

    }catch(Exception $e){
        throw new bException('twitter_invalidate_bearer_token(): Failed', $e);
    }
}



/**
* Search
* Basic Search of the Search API
* Based on https://dev.twitter.com/docs/api/1.1/get/search/tweets
*/
function twitter_search_for_a_term($bearer_token, $query, $result_type='mixed', $count='15'){
    try{
        $url        = "https://api.twitter.com/1.1/search/tweets.json"; // base url
        $q          = urlencode(trim($query)); // query term
        $formed_url = '?q='.$q; // fully formed url

        if($result_type!='mixed'){$formed_url = $formed_url.'&result_type='.$result_type;} // result type - mixed(default), recent, popular
        if($count!='15'){$formed_url = $formed_url.'&count='.$count;} // results per page - defaulted to 15

        $formed_url = $formed_url.'&include_entities=true'; // makes sure the entities are included, note @mentions are not included see documentation

        $headers = array('GET /1.1/search/tweets.json".$formed_url." HTTP/1.1',
                         'Host: api.twitter.com',
                          'User-Agent: jonhurlock Twitter Application-only OAuth App v.1',
                         'Authorization: Bearer '.$bearer_token);

        $ch = curl_init();  // setup a curl

        curl_setopt($ch, CURLOPT_URL           ,$url.$formed_url);  // set url to send to
        curl_setopt($ch, CURLOPT_HTTPHEADER    , $headers);         // set custom headers
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);             // return output

        $retrievedhtml = curl_exec ($ch); // execute the curl

        curl_close($ch); // close the curl
        return $retrievedhtml;

    }catch(Exception $e){
        throw new bException('twitter_search_for_a_term(): Failed', $e);
    }
}


/*
// lets run a search.
$bearer_token = get_bearer_token(); // get the bearer token
print search_for_a_term($bearer_token, "test"); //  search for the work 'test'
invalidate_bearer_token($bearer_token); // invalidate the token
*/



/*
 *
 */
function twitter_user_info($token, $secret){
    global $_CONFIG;

    try{
        $app_id     = $_CONFIG['sso']['twitter']['appid'];
        $app_secret = $_CONFIG['sso']['twitter']['secret'];

        $finalTw    = new TwitterOAuth($app_id, $app_secret, $token, $secret);
        $response   = $finalTw->get('account/verify_credentials');

        if($finalTw->http_code != 200){
            throw new bException('twitter_user_info(): Twitter returned HTTP code "'.str_log($finalTw->http_code).'"', 'HTTP'.$finalTw->http_code);
        }

        return $response;

    }catch(Exception $e){
        throw new bException('twitter_user_info(): Failed', $e);
    }
}



/*
 *
 */
function twitter_post_message($msg, $token, $secret){
    global $_CONFIG;

    try {
        $app_id     = $_CONFIG['sso']['twitter']['appid'];
        $app_secret = $_CONFIG['sso']['twitter']['secret'];

        $finalTw    = new TwitterOAuth($app_id, $app_secret, $token, $secret);

        $finalTw->get('account/verify_credentials');

        $response = $finalTw->post('statuses/update', array('status' => $msg));

        if(!($response instanceof stdClass)){
            throw new bException('twitter_user_info(): Response should be of class "stdClass" but instead is of clas "'.get_class($response).'"', 'unknown_class');
        }

        return $response;

    }catch (Exception $e){
        throw new bException('twitter_post_message(): Failed', $e);
    }
}



/*
 *
 */
function twitter_redirect_to_authorize(){
    global $_CONFIG;

    try {
        $app_id     = $_CONFIG['sso']['twitter']['appid'];
        $app_secret = $_CONFIG['sso']['twitter']['secret'];
        $authz_link = null;

        $tw_tmp     = new TwitterOAuth($app_id, $app_secret);
        $token_tmp  = $tw_tmp->getRequestToken(domain('/twitter.html'));

        $_SESSION['twitter'] = array('oauth_token'        => $token_tmp['oauth_token'],
                                     'oauth_token_secret' => $token_tmp['oauth_token_secret']);

        $authz_link = $tw_tmp->getAuthorizeURL($token_tmp);

        if($tw_tmp->http_code != 200){
            throw new bException('twitter_redirect_to_authorize(): Twitter returned HTTP code "'.str_log($finalTw->http_code).'"', 'HTTP'.$finalTw->http_code);
        }

        return $authz_link;

    }catch (Exception $e){
        throw new bException('twitter_redirect_to_authorize(): Failed', $e);
    }
}



/*
 *
 */
function twitter_get_user_token(){
    global $_CONFIG;

    try {
        $app_id       = $_CONFIG['sso']['twitter']['appid'];
        $app_secret   = $_CONFIG['sso']['twitter']['secret'];
        $token        = $_SESSION['user']['oauth_token'];
        $secret       = $_SESSION['user']['oauth_token_secret'];
        $access_token = null;

        if(empty($app_id) or empty($app_secret) or empty($token) or empty($secret)){
            throw new bException('Incomplete or bad params in get_user_token', 'badparams');
        }

        $tw_tmp       = new TwitterOAuth($app_id, $app_secret, $token, $secret);
        $access_token = $tw_tmp->getAccessToken($_GET['oauth_verifier']);

        if($tw_tmp->http_code != 200){
            throw new bException('twitter_get_user_token(): Twitter returned HTTP code "'.str_log($finalTw->http_code).'"', 'HTTP'.$finalTw->http_code);
        }

        return $access_token;

    }catch (Exception $e){
        throw new bException('twitter_get_user_token(): Failed', $e);
    }
}
?>
