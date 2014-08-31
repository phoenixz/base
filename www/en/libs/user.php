<?php
/*
 * Users library
 *
 * This library contains user functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



 /*
  * Get user data with MC write through cache
  */
function user_data($id) {
    global $_CONFIG;

    try{
        load_libs('memcached');

        $key  = 'USER-'.$id;
        $user = mc_get($key);

        if(empty($user)) {
            $user = sql_get("SELECT * FROM users WHERE id = ".cfi($id).";");
            mc_put($key, $user);

        } else {
            return $user;
        }

    }catch(Exception $e){
        throw new lsException('user_data(): Failed', $e);
    }
}



/*
 * Make sure some avatar is being displayed
 */
function user_avatar($avatar, $type) {
    global $_CONFIG;

    try{
        if(empty($avatar)) {
            return $_CONFIG['avatars']['default'].'_'.$type.'.jpg';
        }

        return $avatar.'_'.$type.'.jpg';

    }catch(Exception $e){
        throw new lsException('user_avatar(): Failed', $e);
    }
}



/*
 * Update the user avatar in the database
 */
function user_update_avatar($user, $avatar) {
    global $_CONFIG;

    try{
        if(!is_numeric($user)){
            if(!is_array($user) or empty($user['id'])){
                throw new lsException('user_update_avatar(): Invalid user specified');
            }

            $user = $user['id'];
        }

        sql_query('UPDATE `users` SET `avatar` = "'.cfm($avatar).'" WHERE `id` = '.$user);

        return $avatar;

    }catch(Exception $e){
        throw new lsException('user_update_avatar(): Failed', $e);
    }
}



/*
 * Find an avatar for this user
 */
function user_find_avatar($user) {
    global $_CONFIG;

    try{
        if(!is_array($user)){
            if(!is_numeric($user)){
                throw new lsException('user_find_avatar(): Invalid user specified');
            }

            $user = sql_get('SELECT * FROM `users` WHERE id = '.cfi($user));
        }

        /*
         * Try getting an avatar from Facebook, Google, Microsoft (or Gravatar maybe?)
         */
        if(!empty($user['fb_id'])){
            load_libs('facebook');
            return facebook_get_avatar($user);

        }elseif(!empty($user['gp_id'])){
            load_libs('google');
            return google_get_avatar($user);

        }elseif(!empty($user['ms_id'])){
            load_libs('microsoft');
            return microsoft_get_avatar($user);

// :TODO: Implement one day in the future
//		}elseif($_CONFIG['gravatar']){
//			load_libs('gravatar');
//			return gravatar_get_avatar($user);

        }else{
            return '';
        }


    }catch(Exception $e){
        throw new lsException('user_find_avatar(): Failed', $e);
    }
}



/*
 * `cate the specified user with the specified password
 */
function user_authenticate($user, $password) {
    try{
        if(!is_scalar($user)){
            throw new lsException('user_authenticate(): Specified username is not valid', 'invalid');
        }

        if(!$user = sql_get('SELECT * FROM `users` WHERE `email` = :email OR `username` = :username', array(':email' => $user, ':username' => $user))){
            throw new lsException('user_authenticate(): Specified user "'.str_log($user).'" not found', 'notfound');
        }

        if(substr($user['password'], 0, 1) != '*'){
            /*
             * No encryption method specified, user default SHA1
             */
            $encryption = 'sha1';

        }else{
            $encryption = str_until(str_from($user['password'], '*'), '*');
        }

        switch($encryption){
            case 'sha1':
                $encryption = sha1(SEED.$password);
                break;

            case 'sha256':
                $encryption = sha256(SEED.$password);
                break;

            default:
                throw new lsException('user_authenticate(): Unknown encryption type "'.str_log($encryption).'" in user password specification', 'unknown');
        }

        if($encryption != str_rfrom($user['password'], '*')){
            throw new lsException('user_authenticate(): Specified password does not match stored password', 'password');
        }

        return $user;

    }catch(Exception $e){
        /*
         * Wait a little bit so the authentication failure cannot be timed
         */
        usleep(mt_rand(1000, 2000000));
        throw new lsException('user_authenticate(): Failed', $e);
    }
}



/*
 * Do a user signin
 */
function user_signin($user, $extended = false, $redirect = '/') {
    global $_CONFIG;

    try{
        if(!is_array($user)){
            throw new lsException('user_signin(): Specified user variable is not an array', 'invalid');
        }

        /*
         * HTTP signin requires cookie support and an already active session!
         * Shell signin requires neither
         */
        if((PLATFORM == 'apache') and (empty($_COOKIE) or !session_id())){
            throw new lsException('user_signin(): This user has no active session or no session id, so probably has no cookies', 'cookiesrequired');
        }

        $_SESSION['user'] = $user;

        /*
         * Store last login
         */
        sql_query('UPDATE `users` SET `last_login` = DATE(NOW()) WHERE `id` = '.cfi($user['id']).';');

        if($extended){
            user_create_extended_session($user['id']);
        }

        if(empty($user['avatar'])){
            try{
                $user['avatar'] = user_find_avatar($user);

            }catch(Exception $e){
// :TODO: Add notifications somewhere?
                log_error($e, 'avatar');
            }
        }

        if($redirect and (PLATFORM == 'apache')){
            session_redirect('http', $redirect);
        }

    }catch(Exception $e){
        throw new lsException('user_signin(): Failed', $e);
    }
}



/*
 * Do a user signout
 */
function user_signout() {
    global $_CONFIG;

    try{
        if(isset($_COOKIE['extsession'])) {
            //remove cookie
            setcookie('extsession', 'stub', 1);

            if(isset($_SESSION['user'])){
                sql_query('DELETE FROM `extended_sessions` WHERE `user_id` = '.cfi($_SESSION['user']['id']).';');
            }
        }

        //remove session info
        unset($_SESSION['user']);

        session_destroy();

    }catch(Exception $e){
        throw new lsException('user_signout(): Failed', $e);
    }
}



/*
 * Create an extended login that can survive beyond the standard short lived PHP sessions
 */
function user_create_extended_session($users_id) {
    global $_CONFIG;

    try{
        if(!$_CONFIG['sessions']['extended']) {
            return false;
        }

        /*
         * Create new code
         */
        $code = sha1($users_id.'-'.uniqid($_CONFIG['domain'], true).'-'.time());

        //remove old entries
        if($_CONFIG['sessions']['extended']['clear'] != false) {
            sql_query('DELETE FROM `extended_sessions` WHERE `user_id` = '.cfi($users_id).';');
        }

        //add to db
        sql_query('INSERT INTO `extended_sessions` (      `user_id`  ,  `session_key`,  `ip`)
                   VALUES                          ('.cfi($users_id).', "'.$code.'"   , '.ip2long($_SERVER['REMOTE_ADDR']).')');

        //set cookie
        setcookie('extsession', $code, (time() + $_CONFIG['sessions']['extended']['age']));
        return $code;

    }catch(Exception $e){
        throw new lsException('user_create_extended_session(): Failed', $e);
    }
}



/*
 * Set a users verification code
 */
function user_set_verification_code($user){
    try{
        if(is_array($user)){
            if(!empty($user['id'])){
                $user = $user['id'];
            }elseif(!empty($user['email'])){
                $user = $user['email'];
            }
        }
        /*
         * Create a unique code.
         */
        $code = uniqid(SUBENVIRONMENT, true);
        /*
         * Update user validation with that code
         */
        if(is_numeric($user)){
            sql_query('UPDATE `users` SET `validated` = "'.cfm($code).'" WHERE `id` = '.cfi($user));

        }elseif(is_string($user)){
            sql_query('UPDATE `users` SET `validated` = "'.cfm($code).'" WHERE `email` = '.cfi($user));

        }else{
            throw new lsException('user_set_verification_code(): Invalid user specified');
        }

        if(!sql_affected_rows()){
            throw new lsException('user_set_verification_code(): Specified user "'.str_log($user).'" does not exist');
        }

        return $code;

    }catch(Exception $e){
        throw new lsException('user_set_verification_code(): Failed', $e);
    }
}



/*
 * Returns if some of the userdata is blacklisted or not
 */
function user_check_blacklisted($name){
    try{
//:TODO: Implement. THROW EXCEPTION IF BLACKLISTED!

    }catch(Exception $e){
        throw new lsException('user_blacklisted(): Failed', $e);
    }
}



/*
 * Add a new user
 */
function user_insert($params){
    try{
        if(!is_array($params)){
            throw new lsException('user_insert(): Invalid params specified', 'invalid');
        }

        if(empty($params['email'])){
            throw new lsException('user_insert(): No email specified', 'notspecified');
        }

        if(empty($params['name'])){
            if(!empty($params['username'])){
                $params['name'] = isset_get($params['username']);

            }else{
                $params['name'] = isset_get($params['email']);
            }
        }

        //if(empty($params['username'])){
        //    throw new lsException('user_insert(): No username specified', 'notspecified');
        //}

        if(empty($params['password'])){
            throw new lsException('user_insert(): No password specified', 'notspecified');
        }

        if(user_get($params, 'id')){
            throw new lsException('user_insert(): User with username "'.str_log(isset_get($params['username'])).'" or email "'.str_log(isset_get($params['email'])).'" already exists', 'exists');
        }

        array_default($params, 'validated', false);

        $params['password'] = password($params['password']);

        sql_query('INSERT INTO `users` (`status`, `username`, `password`, `name`, `email`, `avatar`, `validated`, `date_validated`                             , `fb_id`, `fb_token`, `gp_id`, `gp_token`, `ms_id`, `ms_token_authentication`, `ms_token_access`, `tw_id`, `tw_token`, `yh_id`, `yh_token`, `language`, `gender`, `latitude`, `longitude`, `country`)
                   VALUES              (:status , :username , :password , :name , :email , :avatar , :validated , '.($params['validated'] ? 'NOW()' : 'NULL').', :fb_id , :fb_token , :gp_id , :gp_token , :ms_id , :ms_token_authentication , :ms_token_access , :tw_id , :tw_token , :yh_id , :yh_token , :language , :gender , :latitude , :longitude, :country )',

                   array(':username'                => isset_get($params['username']),
                         ':password'                => $params['password'],
                         ':email'                   => $params['email'],
                         ':name'                    => $params['name'],
                         ':status'                  => isset_get($params['status']),
                         ':avatar'                  => isset_get($params['avatar']),
                         ':validated'               => ($params['validated'] ? str_random(32) : null),
                         ':fb_id'                   => isset_get($params['fb_id']),
                         ':fb_token'                => isset_get($params['fb_token']),
                         ':gp_id'                   => isset_get($params['gp_id']),
                         ':gp_token'                => isset_get($params['gp_token']),
                         ':ms_id'                   => isset_get($params['ms_id']),
                         ':ms_token_authentication' => isset_get($params['ms_token_authentication']),
                         ':ms_token_access'         => isset_get($params['ms_token_access']),
                         ':tw_id'                   => isset_get($params['tw_id']),
                         ':tw_token'                => isset_get($params['tw_token']),
                         ':yh_id'                   => isset_get($params['yh_id']),
                         ':yh_token'                => isset_get($params['yh_token']),
                         ':language'                => isset_get($params['language']),
                         ':gender'                  => isset_get($params['gender']),
                         ':latitude'                => isset_get($params['latitude']),
                         ':longitude'               => isset_get($params['longitude']),
                         ':country'                 => isset_get($params['country'])));

        return sql_insert_id();

    }catch(Exception $e){
        throw new lsException('user_insert(): Failed', $e);
    }
}



/*
 * Update specified user
 */
function user_update($params){
    try{
        array_params($params);
        array_default($params, 'validated', false);

        if(!is_array($params)){
            throw new lsException('user_update(): Invalid params specified', 'invalid');
        }

        if(empty($params['email'])){
            throw new lsException('user_update(): No email specified', 'notspecified');
        }

        if(empty($params['id'])){
            throw new lsException('user_update(): No users id specified', 'notspecified');
        }

        if(empty($params['name'])){
            if(!empty($params['username'])){
                $params['name'] = isset_get($params['username']);

            }else{
                $params['name'] = isset_get($params['email']);
            }
        }

        if(!$user = user_get($params['id'])){
            throw new lsException('user_update(): User with id "'.str_log($params['id']).'" does not exists', 'notexists');
        }

        $exists = sql_get('SELECT `id`, `email`, `username`
                           FROM   `users`
                           WHERE  `id`      != :id
                           AND   (`email`    = :email
                           OR     `username` = :username )',

                           array(':id'       => $params['id'],
                                 ':email'    => $params['email'],
                                 ':username' => $params['username']));

        if($exists){
            if($exists['username'] == $params['username']){
                throw new lsException('user_update(): Another user already has the username "'.str_log($params['username']).'"', 'exists');

            }else{
                throw new lsException('user_update(): Another user already has the email "'.str_log($params['email']).'"', 'exists');
            }
        }

        $r = sql_query('UPDATE `users`
                        SET    `username`  = :username,
                               `name`      = :name,
                               `email`     = :email,
                               `language`  = :language,
                               `gender`    = :gender,
                               `latitude`  = :latitude,
                               `longitude` = :longitude,
                               `country`   = :country
                        WHERE  `id`        = :id',

                        array(':id'        => isset_get($params['id']),
                              ':username'  => isset_get($params['username']),
                              ':name'      => $params['name'],
                              ':email'     => $params['email'],
                              ':language'  => isset_get($params['language']),
                              ':gender'    => isset_get($params['gender']),
                              ':latitude'  => isset_get($params['latitude']),
                              ':longitude' => isset_get($params['longitude']),
                              ':country'   => isset_get($params['country'])));

        return $r->rowCount();

    }catch(Exception $e){
        throw new lsException('user_update(): Failed', $e);
    }
}



/*
 * Update specified user
 */
function user_update_password($params){
    try{
        array_params($params);
        array_default($params, 'validated', false);

        if(!is_array($params)){
            throw new lsException('user_update_password(): Invalid params specified', 'invalid');
        }

        if(empty($params['id'])){
            throw new lsException('user_update_password(): No users id specified', 'idnotspecified');
        }

        if(empty($params['password'])){
            throw new lsException('user_update_password(): No password specified', 'passwordnotspecified');
        }

        if(empty($params['password2'])){
            throw new lsException('user_update_password(): No validation password specified', 'validationnotspecified');
        }

        if($params['password'] != $params['password2']){
            throw new lsException('user_update_password(): Specified password does not match the validation password', 'passwordmismatch');
        }

        $r = sql_query('UPDATE `users`

                        SET    `password` = :password

                        WHERE  `id`       = :id',

                        array(':id'       => $params['id'],
                              ':password' => password($params['password'])));

        if(!$r->rowCount()){
            /*
             * Nothing was updated. This may be because the password remained the same, OR
             * because the user does not exist. check for this!
             */
            if(!sql_get('SELECT `id` FROM `users` WHERE `id` = :id', 'id', array(':id' => $params['id']))){
                throw new lsException('user_update_password(): The specified users_id "'.str_log($params['id']).'" does not exist', 'notexist');
            }

            /*
             * Password remains the same, no problem
             */
        }

    }catch(Exception $e){
        throw new lsException('user_update_password(): Failed', $e);
    }
}



/*
 * Return requested data for specified user
 */
function user_get($params, $columns = false){
    try{
        array_params($params, 'username', 'id');

        if(!isset($params['email']) and isset($params['username'])){
            $params['email'] = $params['username'];
        }

        foreach(array('id', 'email', 'username') as $key){
            if(isset_get($params[$key])){
                $where[]           = '`'.$key.'` = :'.$key;
                $execute[':'.$key] = $params[$key];
            }
        }

        if(empty($where)){
            throw new lsException('user_get() No valid usercolumns specified (either id, and or username, and or email)', 'invalid');
        }

        return sql_get('SELECT '.($columns ? $columns : '*').'
                        FROM   `users`
                        WHERE  '.implode(' OR ', $where), $columns, $execute);

    }catch(Exception $e){
        throw new lsException('user_get(): Failed', $e);
    }
}



/*
 *
 */
function user_signup($params){
    try{
        array_params($params);
        array_default($params, 'validated'     , false);
        array_default($params, 'date_validated', null);

        if(empty($params['email'])){
            throw new lsException('user_signup(): No email specified', 'notspecified');
        }

        if($params['email'] != isset_get($params['email2'])){
            throw new lsException('user_signup(): Validation email is not equal to email', 'notspecified');
        }

        if(empty($params['password'])){
            throw new lsException('user_signup(): No password specified', 'notspecified');
        }

        if($params['password'] != isset_get($params['password2'])){
            throw new lsException('user_signup(): Validation password is not equal to password', 'notspecified');
        }

        if(empty($params['terms'])){
            throw new lsException('user_signup(): Terms not accepted', 'terms');
        }

//:TODO: Add more validations

        $users_id = user_insert($params);
//:TODO: Send verification email!
        return user_get($users_id);

    }catch(Exception $e){
        throw new lsException('user_signup(): Failed', $e);
    }
}



/*
 * Return either (in chronological order) name, username, or email for the user
 */
function user_name($user = null, $guest = null){
    if(!$user){
        /*
         * Default to current session user name
         */
        if(empty($_SESSION['user'])){
            /*
             * There is no session user logged in
             */
            return not_empty($guest, tr('Guest'));
        }

        $user = $_SESSION['user'];
    }

    if(is_numeric($user)){
        /*
         * This is not a user assoc array, but a user ID.
         * Fetch user data from DB
         */
        if(!$user = sql_get('SELECT `name` `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $user))){
           throw new lsException('user_name(): Specified user id "'.str_log($user).'" does not exist', 'notexist');
        }
    }

    return not_empty(isset_get($user['name']), isset_get($user['username']), isset_get($user['email']), isset_get($user['id']));
}



/*
 * Returns true either if the user has the specified right and not the devil right, or the "god" right
 */
function user_has_right($user, $right) {
    try{
        if(!isset($user['rights'])){
            $user['rights'] = user_load_rights($user);
        }

        if(!empty($user['rights']['god'])){
            return true;
        }

        return !empty($user['rights'][$right]) and empty($user['rights']['devil']);

    }catch(Exception $e){
        throw new lsException('user_has_right(): Failed', $e);
    }
}



/*
 * Load the rights for the specified user
 */
function user_load_rights($user){
    try{
        if(!is_numeric($user)){
            if(!is_array($user)){
                throw new lsException('user_load_rights(): Invalid user, please specify either users_id or user array with id', 'invalid');
            }

            $user = isset_get($user['id']);
        }

        return sql_list('SELECT `name`, `name` AS `right`
                         FROM   `users_rights`
                         WHERE  `users_id` = :users_id', array(':users_id' => $user));

    }catch(Exception $e){
        throw new lsException('user_load_rights(): Failed', $e);
    }
}



/*
 * Make the current session the specified user
 */
function user_switch($username){
    try{
        /*
         * Does the specified user exist?
         */
        if(!$user = sql_get('SELECT *, `email` FROM `users` WHERE `name` = :name', array(':name' => $username))){
            throw new lsException('user_switch(): The specified user "'.str_log($username).'" does not exist', 'usernotexist');
        }

        /*
         * Switch the current session to the new user
         */
        $_SESSION['user'] = $user;

        /*
         * Store last login
         */
        sql_query('UPDATE `users` SET `last_login` = DATE(NOW()) WHERE `id` = '.cfi($user['id']).';');

    }catch(Exception $e){
        throw new lsException('user_switch(): Failed', $e);
    }
}
?>
