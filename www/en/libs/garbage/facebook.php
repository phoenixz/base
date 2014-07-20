<?php
/*
 * Facebooklibrary
 *
 * This library contains facebook related functions
 *
 * Written and Copyright by Sven Oostenbrink
 */



/*
 * Parse a facebook Signed Request
 *
 * Taken from http://developers.facebook.com/docs/authentication/signed_request/
 *
 * Modified by Sven Oostenbrink for use in site
 */
function facebook_parse_signed_request($signed_request){
    global $_CONFIG;

    $secret = $_CONFIG['auth']['facebook']['secret'];

    list($encoded_sig, $payload) = explode('.', $signed_request, 2);

    /*
     * Decode the data
     */
    $sig  = base64_url_decode($encoded_sig);
    $data = json_decode(base64_url_decode($payload), true);

    if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
        throw new isException('fb_parse_signed_request: Unknown algorithm. Expected HMAC-SHA256');
    }

    /*
     * Check signature
     */
    $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);

    if ($sig !== $expected_sig){
        throw new isException('fb_parse_signed_request: Bad Signed JSON signature!');
    }

    return $data;
}


/*
 * Return users facebook avatar URL
 */
function facebook_get_avatar_url($user){
    try{
        load_libs('users');

        if(!is_array($user)){
            $user = users_get($user);
        }
// :TODO: Implement
        return '';

    }catch(Exception $e){
        throw new lsException('facebook_get_avatar_url(): Failed', $e);
    }
}



/*
 * Download the users facebook avatar and set it as its own avatar.
 */
function facebook_set_users_avatar($user){
    try{
        load_libs('users');

        if(!is_array($user)){
            $user = users_get($user);
        }

        users_add_avatar($user, facebook_get_avatar_url($user));

    }catch(Exception $e){
        throw new lsException('facebook_set_users_avatar(): Failed', $e);
    }
}
?>
