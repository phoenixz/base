<?php
/*
 * Sessions library
 *
 * This library file contains all kinds of session related functions
 */



/*
 * Sign the user up for this site
 */
function sessions_signup($user){

}



/*
 * Sign the user in and create a session
 */
function sessions_signin($credentials){
    global $pdo, $_CONFIG;

    try{
        if(empty($credentials['username'])){
            throw new bException('sessions_signin(): No username specified', 'notspecified');
        }

        if(empty($credentials['password'])){
            throw new bException('sessions_signin(): No password specified', 'notspecified');
        }

        //sessions_validate_username($credentials['username']);
        //sessions_validate_password($credentials['password']);

        load_libs('users');

        if(!$user = users_get($credentials['username'])){
            throw new bException('sessions_signin(): Specified user "'.$credentials['username'].'" does not exist', 'notexist');
        }

        if(sessions_compare_passwords($credentials['password'], $user['password'])){
            $_SESSION['user']           = $user;
            $_SESSION['user']['rights'] = array_flip(get_rights($user));

        }else{
            throw new bException('sessions_signin(): Password incorrect', 'password');
        }

        /*
         * If "next" was set, then redirect there
         */
        if(!empty($_GET['next']) and $_CONFIG['sessions']['signin']['allow_next']){
            redirect($_GET['next']);
        }

        redirect($_CONFIG['redirects']['aftersignin']);

    }catch(Exception $e){
        throw new bException('sessions_signin(): Failed', $e);
    }
}



/*
 * Log the current user out
 */
function sessions_signout(){
    global $_CONFIG;

    try{
        session_destroy();
        unset($_SESSION);

        if($_CONFIG['sessions']['signin']['force']){
            redirect($_CONFIG['redirects']['signin']);

        }else{
            redirect($_CONFIG['redirects']['aftersignout']);
        }

    }catch(Exception $e){
        throw new bException('sessions_signout(): Failed');
    }
}



/*
 * Close the specified session, or all sessions for the specified user.
 */
function sessions_close($session){

}



/*
 * Switch user for current session to another (the requested) user
 */
function sessions_switch_user($user, $session = null){

}



/*
 * Hijack (take over) the requested session
 */
function sessions_hijack($session){

}



/*
 * Compare hashed passwords
 */
function sessions_compare_passwords($userpass, $dbpass){
    if(strpos($dbpass, '*') !== false){
        /*
         * The database password contains hash and seed information
         */
        $seed   = (str_until($dbpass, '#') == 'seed' ? true : false);
        $hash   = str_until(str_from($dbpass, '#'), '*');
        $dbpass = str_from($dbpass, '*');

    }else{
        $seed = false;
        $hash = 'sha1';
    }

    return $dbpass == sessions_password_hash($userpass, $seed, $hash, false);
}



/*
 * Correctly hash specified password
 */
function sessions_password_hash($value, $useseed = 'auto', $hash = 'auto', $usemeta = 'auto'){
    if(!$value){
        /*
         * Hash can't process this
         */
        throw new bException('sessions_password_hash(): No value specified');
    }

    global $seed, $_CONFIG;

    /*
     * Apply defaults
     */
    if($hash == 'auto'){
        $hash = $_CONFIG['password']['hash'];
    }

    if($useseed == 'auto'){
        $useseed = $_CONFIG['password']['useseed'];
    }

    if($usemeta == 'auto'){
        $usemeta = $_CONFIG['password']['usemeta'];
    }

    /*
     * Apply seed, if specified
     */
    if($useseed){
        $value = $seed.$value;
    }

    /*
     * Apply and return specified hash
     */
    switch($hash){
        case 'auto':
            // FALLTHROUGH
        case 'sha1':
            $retval = sha1($value);
            break;

        case 'sha256':
throw new bException('sessions_password_hash: sha256 support is not yet implemented');
            $retval = sha256($value);
            break;

        default:
            throw new bException('sessions_password_hash(): Unknown hash "'.$hash.'" specified');

    }

    if($usemeta){
        return ($useseed ? 'seed' : '').'#'.$hash.'*'.$retval;
    }

    return $retval;
}



/*
 * Give special god rights to current user, IF he/she is configured to be so.
 */
function sessions_mark_god(){
    global $_CONFIG;

    try{
        if(empty($_SESSION['user'])){
            return false;
        }

        $_SESSION['user']['god'] = false;

        /*
         * We can only continue with all info available
         */
        if(empty($_CONFIG['god_users'])) {
            /*
             * No god users configuration found
             */
            return false;
        }

        if(in_array($_CONFIG['god_users'], $_SESSION['user']['email'])){
            $_SESSION['user']['god'] = true;
            return true;
        }

        return false;

    }catch(Exception $e){
        throw new bException('sessions_mark_god(): Failed', $e);
    }
}
?>
