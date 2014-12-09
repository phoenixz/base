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
        throw new bException('user_data(): Failed', $e);
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
        throw new bException('user_avatar(): Failed', $e);
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
                throw new bException('user_update_avatar(): Invalid user specified');
            }

            $user = $user['id'];
        }

        sql_query('UPDATE `users` SET `avatar` = "'.cfm($avatar).'" WHERE `id` = '.$user);

        return $avatar;

    }catch(Exception $e){
        throw new bException('user_update_avatar(): Failed', $e);
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
                throw new bException('user_find_avatar(): Invalid user specified');
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
        throw new bException('user_find_avatar(): Failed', $e);
    }
}



/*
 * `cate the specified user with the specified password
 */
function user_authenticate($username, $password) {
    global $_CONFIG;

    try{
        if(!is_scalar($username)){
            throw new bException('user_authenticate(): Specified username is not valid', 'invalid');
        }

        if(!$user = sql_get('SELECT * FROM `users` WHERE `email` = :email OR `username` = :username', array(':email' => $username, ':username' => $username))){
            throw new bException('user_authenticate(): Specified user "'.str_log($username).'" not found', 'notfound');
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
                throw new bException('user_authenticate(): Unknown encryption type "'.str_log($encryption).'" in user password specification', 'unknown');
        }

        if($encryption != str_rfrom($user['password'], '*')){
            throw new bException('user_authenticate(): Specified password does not match stored password', 'password');
        }


        /*
         * Apply IP locking system
         */
        if($_CONFIG['security']['signin']['ip_lock'] and (PLATFORM == 'apache')){
            $ip = $_CONFIG['security']['signin']['ip_lock'];

            if($ip === true){
                /*
                 * Get the last locked IP from the database
                 * If there is none, then it's not a problem, it will never match, and
                 * require a user with iplock rights to set
                 */
                $ip = sql_get('SELECT `ip` FROM `ip_locks` ORDER BY `id` DESC LIMIT 1', 'ip');
            }

            if($ip != $_SERVER['REMOTE_ADDR']){
                if(!has_rights('iplock', $user)){
                    throw new bException('user_authenticate(): Your current IP "'.str_log($_SERVER['REMOTE_ADDR']).'" is not allowed to login', 'iplock');
                }

                /*
                 * This user can reset the iplock by simply logging in
                 */
                sql_query('INSERT INTO `ip_locks` (`createdby`, `ip`)
                           VALUES                 (:createdby , :ip )',

                           array(':createdby' => $user['id'],
                                 ':ip'        => $_SERVER['REMOTE_ADDR']));

                html_flash_set(log_database('Updated IP lock to "'.str_log($_SERVER['REMOTE_ADDR']).'"', 'ip_locks_updated'), 'info');
            }
        }

        /*
         * Use two factor authentication, the user has to authenticate by SMS as well
         */
        if($_CONFIG['security']['signin']['two_factor']){
            if(empty($user['phone'])){
                throw new bException('user_autohenticate(): Two factor authentication impossible for user "'.$user['id'].' / '.$user['name'].'" because no phone is registered', 'twofactor_nophone');
            }

            $user['authenticated'] = 'two_factor';
            $user['two_factor']    = uniqid();

            load_libs('twilio');
            $twilio = twilio_load();
            $twilio->account->messages->sendMessage($_CONFIG['security']['signin']['twofactor'], $user['phone'], 'The "'.$_CONFIG['name'].'"authentication code is "'.$user['two_factor'].'"');

            return $user;
        }

        $user['authenticated'] = true;
        return $user;

    }catch(Exception $e){
        /*
         * Wait a little bit so the authentication failure cannot be timed
         */
        usleep(mt_rand(1000, 2000000));

        if($e->getCode() == 'password'){
            if($date = sql_get('SELECT `createdon` FROM `passwords` WHERE `users_id` = :users_id AND `password` = :password', 'id', array(':users_id' => isset_get($user['id']), ':password' => isset_get($encryption)))){
                $date = new DateTime($date);
                throw new bException('user_authenticate(): Your password was updated on "'.str_log($date->format($_CONFIG['formats']['human_date'])).'"', 'oldpassword');
            }
        }

        throw new bException('user_authenticate(): Failed', $e);
    }
}



/*
 * Do a user signin
 */
function user_signin($user, $extended = false, $redirect = '/') {
    global $_CONFIG;

    try{
        if(!is_array($user)){
            throw new bException('user_signin(): Specified user variable is not an array', 'invalid');
        }

        /*
         * HTTP signin requires cookie support and an already active session!
         * Shell signin requires neither
         */
        if((PLATFORM == 'apache') and (empty($_COOKIE) or !session_id())){
            throw new bException('user_signin(): This user has no active session or no session id, so probably has no cookies', 'cookiesrequired');
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
        throw new bException('user_signin(): Failed', $e);
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
        throw new bException('user_signout(): Failed', $e);
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
        throw new bException('user_create_extended_session(): Failed', $e);
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
            throw new bException('user_set_verification_code(): Invalid user specified');
        }

        if(!sql_affected_rows()){
            throw new bException('user_set_verification_code(): Specified user "'.str_log($user).'" does not exist');
        }

        return $code;

    }catch(Exception $e){
        throw new bException('user_set_verification_code(): Failed', $e);
    }
}



/*
 * Returns if some of the userdata is blacklisted or not
 */
function user_check_blacklisted($name){
    try{
//:TODO: Implement. THROW EXCEPTION IF BLACKLISTED!

    }catch(Exception $e){
        throw new bException('user_blacklisted(): Failed', $e);
    }
}



/*
 * Add a new user
 */
function user_signup($params){
    try{
        if(!is_array($params)){
            throw new bException(tr('user_signup(): Invalid params specified'), 'invalid');
        }

        if(empty($params['email'])){
            throw new bException(tr('user_signup(): Please specify an email address'), 'notspecified');
        }

        if(empty($params['password'])){
            throw new bException(tr('user_signup(): Please specify a password'), 'notspecified');
        }

        if(user_get($params, 'id')){
            throw new bException(tr('user_signup(): User with username "%name%" or email "%email%" already exists', array('%name%' => str_log(isset_get($params['username'])), '%email%' => str_log(isset_get($params['email'])))), 'exists');
        }

        sql_query('INSERT INTO `users` (`status`, `createdby`, `username`, `password`, `name`, `email`)
                   VALUES              ("empty" , :createdby , :username , :password , :name , :email )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':username'  => isset_get($params['username']),
                         ':name'      => isset_get($params['name']),
                         ':password'  => password($params['password']),
                         ':email'     => $params['email']));

        return sql_insert_id();

    }catch(Exception $e){
        throw new bException('user_signup(): Failed', $e);
    }
}


// :DELETE: There should not be an update function for tables!!!! This should be implemented in the pages directly
///*
// * Update specified user
// */
//function user_update($params){
//    try{
//        array_params($params);
//        array_default($params, 'validated', false);
//
//        if(!is_array($params)){
//            throw new bException('user_update(): Invalid params specified', 'invalid');
//        }
//
//        if(empty($params['email'])){
//            throw new bException('user_update(): No email specified', 'notspecified');
//        }
//
//        if(empty($params['id'])){
//            throw new bException('user_update(): No users id specified', 'notspecified');
//        }
//
//        if(empty($params['name'])){
//            if(!empty($params['username'])){
//                $params['name'] = isset_get($params['username']);
//
//            }else{
//                $params['name'] = isset_get($params['email']);
//            }
//        }
//
//        if(!$user = user_get($params['id'])){
//            throw new bException('user_update(): User with id "'.str_log($params['id']).'" does not exists', 'notexists');
//        }
//
//        $exists = sql_get('SELECT `id`, `email`, `username`
//                           FROM   `users`
//                           WHERE  `id`      != :id
//                           AND   (`email`    = :email
//                           OR     `username` = :username )',
//
//                           array(':id'       => $params['id'],
//                                 ':email'    => $params['email'],
//                                 ':username' => $params['username']));
//
//        if($exists){
//            if($exists['username'] == $params['username']){
//                throw new bException('user_update(): Another user already has the username "'.str_log($params['username']).'"', 'exists');
//
//            }else{
//                throw new bException('user_update(): Another user already has the email "'.str_log($params['email']).'"', 'exists');
//            }
//        }
//
//        $r = sql_query('UPDATE `users`
//                        SET    `username`  = :username,
//                               `name`      = :name,
//                               `email`     = :email,
//                               `language`  = :language,
//                               `gender`    = :gender,
//                               `latitude`  = :latitude,
//                               `longitude` = :longitude,
//                               `country`   = :country
//                        WHERE  `id`        = :id',
//
//                        array(':id'        => isset_get($params['id']),
//                              ':username'  => isset_get($params['username']),
//                              ':name'      => $params['name'],
//                              ':email'     => $params['email'],
//                              ':language'  => isset_get($params['language']),
//                              ':gender'    => isset_get($params['gender']),
//                              ':latitude'  => isset_get($params['latitude']),
//                              ':longitude' => isset_get($params['longitude']),
//                              ':country'   => isset_get($params['country'])));
//
//        return $r->rowCount();
//
//    }catch(Exception $e){
//        throw new bException('user_update(): Failed', $e);
//    }
//}



/*
 * Update user password
 */
function user_update_password($params){
    try{
        array_params($params);
        array_default($params, 'validated', false);

        if(!is_array($params)){
            throw new bException(tr('user_update_password(): Invalid params specified'), 'invalid');
        }

        if(empty($params['id'])){
            throw new bException(tr('user_update_password(): No users id specified'), 'not_specified');
        }

        if(empty($params['password'])){
            throw new bException(tr('user_update_password(): Please specify a password'), 'not_specified');
        }

        if(empty($params['password2'])){
            throw new bException(tr('user_update_password(): No validation password specified'), 'not_specified');
        }

        if($params['password'] != $params['password2']){
            throw new bException(tr('user_update_password(): Specified password does not match the validation password'), 'mismatch');
        }

        $password = password($params['password']);

        $r = sql_query('UPDATE `users`

                        SET    `password` = :password

                        WHERE  `id`       = :id',

                        array(':id'       => $params['id'],
                              ':password' => $password));

        if(!$r->rowCount()){
            /*
             * Nothing was updated. This may be because the password remained the same, OR
             * because the user does not exist. check for this!
             */
            if(!sql_get('SELECT `id` FROM `users` WHERE `id` = :id', 'id', array(':id' => $params['id']))){
                throw new bException(tr('user_update_password(): The specified users_id "'.str_log($params['id']).'" does not exist'), 'notexist');
            }

            /*
             * Password remains the same, no problem
             */
        }


        /*
         * Add the new password to the password storage
         */
        sql_query('INSERT INTO `passwords` (`createdby`, `users_id`, `password`)
                   VALUES                  (:createdby , :users_id , :password )',

                   array(':createdby' => $_SESSION['user']['id'],
                         ':users_id'  => $params['id'],
                         ':password'  => $password));

    }catch(Exception $e){
        throw new bException('user_update_password(): Failed', $e);
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
            throw new bException('user_get() No valid usercolumns specified (either id, and or username, and or email)', 'invalid');
        }

        return sql_get('SELECT '.($columns ? $columns : '*').'
                        FROM   `users`
                        WHERE  '.implode(' OR ', $where), $columns, $execute);

    }catch(Exception $e){
        throw new bException('user_get(): Failed', $e);
    }
}



/*
 * Return either (in chronological order) name, username, or email for the user
 */
function user_name($user = null, $guest = null){
    if(!$user){
        $user = $_SESSION['user'];
    }

    /*
     * Default to current session user name
     */
    if(empty($_SESSION['user']['name']) and empty($_SESSION['user']['email'])){
        /*
         * There is no session user logged in
         */
        return not_empty($guest, tr('Guest'));
    }

    if(is_numeric($user)){
        /*
         * This is not a user assoc array, but a user ID.
         * Fetch user data from DB
         */
        if(!$user = sql_get('SELECT `name` `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $user))){
           throw new bException('user_name(): Specified user id "'.str_log($user).'" does not exist', 'notexist');
        }
    }

    return not_empty(isset_get($user['name']), isset_get($user['username']), isset_get($user['email']), isset_get($user['id']));
}



// :DELETE: has_rights() can now perform the same functionality
///*
// * Returns true either if the user has the specified right and not the devil right, or the "god" right
// */
//function user_has_rights($user, $right) {
//    try{
//        if(!isset($user['rights'])){
//            $user['rights'] = user_load_rights($user);
//        }
//
//        if(!empty($user['rights']['god'])){
//            return true;
//        }
//
//        return !empty($user['rights'][$right]) and empty($user['rights']['devil']);
//
//    }catch(Exception $e){
//        throw new bException('user_has_rights(): Failed', $e);
//    }
//}



/*
 * Load the rights for the specified user
 */
function user_load_rights($user){
    try{
        if(!is_numeric($user)){
            if(!is_array($user)){
                throw new bException('user_load_rights(): Invalid user, please specify either users_id or user array with id', 'invalid');
            }

            $user = isset_get($user['id']);
        }

        return sql_list('SELECT `name`, `name` AS `right`
                         FROM   `users_rights`
                         WHERE  `users_id` = :users_id', array(':users_id' => $user));

    }catch(Exception $e){
        throw new bException('user_load_rights(): Failed', $e);
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
            throw new bException('user_switch(): The specified user "'.str_log($username).'" does not exist', 'usernotexist');
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
        throw new bException('user_switch(): Failed', $e);
    }
}



/*
 * Find the password field when browser password saving has been disabled
 */
function user_process_signin_fields($post){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['security']['signin']['save_password'])){
            /*
             * Clear username and password fields, to ensure they are not being used
             */
            unset($post['username']);
            unset($post['password']);

            /*
             * Password field is password********
             */
            foreach(array_max($post) as $key => $value){
                if((substr($key, 0, 8) == 'password') and (strlen($key) == 16)){
                    /*
                     * This is the password field, set it.
                     */
                    $post['password'] = $post[$key];
                    unset($post[$key]);

                }elseif((substr($key, 0, 8) == 'username') and (strlen($key) == 16)){
                    /*
                     * This is the username field, set it.
                     */
                    $post['username'] = $post[$key];
                    unset($post[$key]);
                    continue;
                }

                if(isset($post['username']) and isset($post['password'])){
                    break;
                }
            }
        }

        return $post;

    }catch(Exception $e){
        throw new bException('user_process_signin_fields(): Failed', $e);
    }
}



/*
 * Update the rights for this user.
 * Requires a user array with $user['id'], and $user['roles_id']
 */
function user_update_rights($user){
    try{
        if(empty($user['id'])){
            throw new bException('user_update_rights(): Cannot update rights, no user specified', 'not_specified');
        }

        if(empty($user['roles_id'])){
            throw new bException('user_update_rights(): Cannot update rights, no role specified', 'not_specified');
        }

        /*
         * Get new rights, delete all old rights, and prepare the query to insert these new rights
         */
        sql_query('DELETE FROM `users_rights` WHERE `users_id` = :users_id', array(':users_id' => $user['id']));

        $rights  = sql_list('SELECT    `rights`.`id`,
                                       `rights`.`name`

                             FROM      `roles_rights`

                             LEFT JOIN `rights`
                             ON        `rights`.`id` = `roles_rights`.`rights_id`

                             WHERE     `roles_id` = :roles_id',

                             array(':roles_id' => $user['roles_id']));

        $p       = sql_prepare('INSERT INTO `users_rights` (`users_id`, `rights_id`, `name`)
                                VALUES                     (:users_id , :rights_id , :name )');

        $execute = array(':users_id' => $user['id']);

        foreach($rights as $id => $name){
            $execute[':rights_id'] = $id;
            $execute[':name']      = $name;

            $p->execute($execute);
        }

    }catch(Exception $e){
        throw new bException('user_update_rights(): Failed', $e);
    }
}
?>
