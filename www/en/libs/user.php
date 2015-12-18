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
function user_authenticate($username, $password, $columns = '*') {
    global $_CONFIG;

    try{
        if(!is_scalar($username)){
            throw new bException('user_authenticate(): Specified username is not valid', 'invalid');
        }

        if(!$user = sql_get('SELECT '.$columns.' FROM `users` WHERE `email` = :email OR `username` = :username', array(':email' => $username, ':username' => $username))){
            log_database(tr('user_authenticate(): Specified user "%username%" not found', array('%username%' => str_log($username))), 'authentication/notfound');
            throw new bException(tr('user_authenticate(): Specified user "%username%" not found', array('%username%' => str_log($username))), 'notfound');
        }

        if(substr($user['password'], 0, 1) != '*'){
            /*
             * No encryption method specified, user default SHA1
             */
            $encryption = 'sha1';

        }else{
            $encryption = str_cut($user['password'], '*', '*');
        }

        switch($encryption){
            case 'sha1':
                $encryption = sha1(SEED.$password);
                break;

            case 'sha256':
                $encryption = sha256(SEED.$password);
                break;

            default:
                throw new bException(tr('user_authenticate(): Unknown encryption type "%type%" in user password specification', array('%type%' => str_log($encryption))), 'unknown');
        }

        if($encryption != str_rfrom($user['password'], '*')){
            log_database(tr('user_authenticate(): Specified password does not match stored password for user "%username%"', array('%username%' => $username)), 'authentication/failed');
            throw new bException(tr('user_authenticate(): Specified password does not match stored password'), 'password');
        }


        /*
         * Apply IP locking system
         */
        if($_CONFIG['security']['signin']['ip_lock'] and (PLATFORM == 'http')){
            include(dirname(__FILE__).'/handlers/user_ip_lock.php');
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

        log_database(tr('user_authenticate(): Authenticated user "%username%"', array('%username%' => $username)), 'authentication/success');

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
function user_signin($user, $extended = false, $redirect = '/', $html_flash = null) {
    global $_CONFIG;

    try{
        if(!is_array($user)){
            throw new bException('user_signin(): Specified user variable is not an array', 'invalid');
        }

        /*
         * HTTP signin requires cookie support and an already active session!
         * Shell signin requires neither
         */
        if((PLATFORM == 'http') and (empty($_COOKIE) or !session_id())){
            throw new bException('user_signin(): This user has no active session or no session id, so probably has no cookies', 'cookiesrequired');
        }

        if(session_status() == PHP_SESSION_ACTIVE){
            /*
             * Reset session data
             */
            session_destroy();
            session_start();
        }

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

        $_SESSION['user'] = $user;

        if($html_flash){
            html_flash_set(isset_get($html_flash['text']), isset_get($html_flash['type']), isset_get($html_flash['class']));
        }

        if($redirect and (PLATFORM == 'http')){
            session_redirect('http', $redirect);
        }

        log_database(tr('user_signin(): Signed in user "%user%"', array('%user%' => user_name($user))), 'signin/success');

    }catch(Exception $e){
        log_database(tr('user_signin(): User sign in failed for user "%user%" because "%message%"', array('%user%' => user_name($user), '%message%' => $e->getMessage())), 'signin/failed');
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
        if(empty($params['email'])){
            throw new bException(tr('user_signup(): Please specify an email address'), 'notspecified');
        }

        if(empty($params['password'])){
            throw new bException(tr('user_signup(): Please specify a password'), 'notspecified');
        }

        $dbuser = sql_get('SELECT `id`,
                                  `username`,
                                  `email`

                           FROM   `users`

                           WHERE  `username` = :username
                           OR     `email`    = :email',

                           array(':username' => isset_get($params['username']),
                                 ':email'    => $params['email']));

        if($dbuser){
            if(!empty($dbuser['email']) and !empty($dbuser['username'])){
                throw new bException(tr('user_signup(): User with username "%name%" or email "%email%" already exists', array('%name%' => str_log(isset_get($params['username'])), '%email%' => str_log(isset_get($params['email'])))), 'exists');

            }elseif(!empty($dbuser['email'])){
                throw new bException(tr('user_signup(): User with email "%email%" already exists', array('%email%' => str_log(isset_get($params['email'])))), 'exists');

            }else{
                throw new bException(tr('user_signup(): User with username "%name%" already exists', array('%name%' => str_log(isset_get($params['username'])))), 'exists');
            }
        }

        sql_query('INSERT INTO `users` (`status`, `createdby`, `username`, `password`, `name`, `email`)
                   VALUES              (NULL    , :createdby , :username , :password , :name , :email )',

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
 * Update user password. This can be used either by the current user, or by an
 * admin user updating the users password
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

        if(empty($params['cpassword'])){
            throw new bException(tr('user_update_password(): Please specify the current password'), 'not_specified');
        }

        if(empty($params['password'])){
            throw new bException(tr('user_update_password(): Please specify a password'), 'not_specified');
        }

        if(empty($params['password2'])){
            throw new bException(tr('user_update_password(): No validation password specified'), 'not_specified');
        }

        /*
         * Check if current password is equal to cpassword
         */
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
function user_get($user, $columns = '*'){
    global $_CONFIG;

    try{
        if(!$user){
            throw new bException(tr('user_get(): No user specified'), 'notspecified');
        }

        if(!is_scalar($user)){
            throw new bException(tr('user_get(): Specified user data "%data%" is not scalar', array('%data%' => str_log($user))), 'invalid');
        }

        if(is_numeric($user)){
            $retval = sql_get('SELECT '.$columns.'

                               FROM   `users`

                               WHERE  `id` = :user',

                               array(':user' => $user));

        }else{
            $retval = sql_get('SELECT '.$columns.'

                               FROM   `users`

                               WHERE  `email`    = :user
                               OR     `username` = :user2',

                               array(':user'  => $user,
                                     ':user2' => $user));

        }

        if(!$retval){
            throw new bException(tr('user_get(): Specified user "%user%" does not exist', array('%user%' => str_log($user))), 'notexists');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('user_get(): Failed', $e);
    }
}



/*
 * Return either (in chronological order) name, username, or email for the user
 */
function user_name($user = null, $key_prefix = ''){
    try{
        /*
         * Compatibility
         */
        if($key_prefix === null){
            throw new bException('user_name(): WARNING! user_name() ARGUMENTS HAVE CHANGED, $guest is no longer supported, and has been removed. Adjust your function call accordingly');
        }

        if($user){
            if(is_scalar($user)){
                if(!is_numeric($user)){
                    /*
                     * String, assume its a username
                     */
                    return $user;
                }

                /*
                 * This is not a user assoc array, but a user ID.
                 * Fetch user data from DB, then treat it as an array
                 */
                if(!$user = sql_get('SELECT `name` `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $user))){
                   throw new bException('user_name(): Specified user id "'.str_log($user).'" does not exist', 'notexist');
                }
            }

            if(!is_array($user)){
                throw new bException(tr('user_name(): Invalid data specified, please specify either user id, name, or an array containing username, email and or id'), 'invalid');
            }

            $user = not_empty(isset_get($user[$key_prefix.'name']), isset_get($user[$key_prefix.'username']), isset_get($user[$key_prefix.'email']), isset_get($user[$key_prefix.'id']));

            if($user){
                return $user;
            }
        }

        /*
         * No user data found, assume guest user.
         */
        return tr('Guest');

    }catch(Exception $e){
        throw new bException(tr('user_name(): Failed'), $e);
    }
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



/*
 * Simple function to test password strength
 * Found on http://www.phpro.org/examples/Password-Strength-Tester.html
 *
 * Rewritten for use in BASE project by Sven Oostenbrink
 */
// :TODO: Improve. This function uses some bad algorithms that could cause false high ranking passwords
function user_password_strength($password){
    try{
        /*
         * Get the length of the password
         */
        $strength = 0;
        $length   = strlen($password);

        if($length < 8){
            if(!$length){
                throw new bException('user_password_strength(): Empty passw', 'password_to_short');
            }

            throw new bException('user_password_strength(): Specified password is too short', 'password_to_short');
        }

        /*
         * Check if password is not all lower case
         */
        if(strtolower($password) != $password){
            $strength += 1;
        }

        /*
         * Check if password is not all upper case
         */
        if(strtoupper($password) == $password){
            $strength += 1;
        }

        /*
         * Check string length
         */
        if($length <= 15){
            $strength += 1;

        }elseif($length <= 35){
            $strength += 2;

        }else{
            $strength += 3;
        }

        /*
         * Get the numbers in the password
         */
        preg_match_all('/[0-9]/', $password, $numbers);
        $strength += count($numbers[0]);

        /*
         * Check for special chars
         */
        preg_match_all('/[|!@#$%&*\/=?,;.:\-_+~^\\\]/', $password, $specialchars);
        $strength += sizeof($specialchars[0]);

        /*
         * Get the number of unique chars
         */
        $chars            = str_split($password);
        $num_unique_chars = sizeof(array_unique($chars));
        $strength += $num_unique_chars * 2;

        /*
         * Strength is a number 1-10;
         */
        $strength = $strength > 99 ? 99 : $strength;
        $strength = floor($strength / 10 + 1);

        return $strength;

    }catch(Exception $e){
        throw new bException('user_password_strength(): Failed', $e);
    }
}



/*
 *
 */
function users_validate($user, $old_user = null){
    global $_CONFIG;

    try{
        if($old_user){
            $user = array_merge($old_user, $user);
        }

        load_libs('validate');

        $v     = new validate_form($user, 'name,username,email,status,password,latitude,longitude,type');
        $v->isNotEmpty ($user['name']     , tr('No name specified'), 'notspecified');
        $v->hasMinChars($user['name'],   2, tr('Please ensure the name has at least 2 characters'));
        $v->hasMaxChars($user['name'], 255, tr('Please ensure the name has less than 255 characters'));

        $v->isNotEmpty ($user['username']    , tr('No username specified'), 'notspecified');
        $v->hasMinChars($user['username'],  2, tr('Please ensure the username has at least 2 characters'));
        $v->hasMaxChars($user['username'], 64, tr('Please ensure the username has less than 255 characters'));

        if(is_numeric(substr($user['username'], 0, 1))){
            $v->setError(tr('Please ensure that the users name does not start with a number'));
        }

        $v->isNotEmpty  ($user['email'], tr('No email specified'), 'notspecified');
        $v->isValidEmail($user['email'], tr('Specified email "%email%" is not a valid email address', array('%email%' => $user['email'])));

        if(strlen($user['status']) > 16){
            $v->setError(tr('Specified status "%status%" is not valid, it should be less than 16 characters', array('%status%' => $user['status'])));
        }

        if(!$user['password']){
            $v->setError(tr('No password specified'), 'not_specified');
        }

        if(empty($user['id'])){
            if($test = sql_get('SELECT `id`, `username`, `name` FROM `users` WHERE (`name` = :name OR `email` = :email)', array(':name' => $user['name'], ':email' => $user['email']))){
                if($user['username'] == $test['username']){
                    throw new bException(tr('The username "%username%" is already in use', array('%username%' => $user['username'])), 'exists');
                }

                throw new bException(tr('The email "%email%" is already in use', array('%email%' => $user['email'])), 'exists');
            }

        }else{
            if($test = sql_get('SELECT `id`, `username`, `name` FROM `users` WHERE (`name` = :name OR `email` = :email) AND `id` != :id', array(':name' => $user['name'], ':email' => $user['email'], ':id' => $user['id']))){
                if($user['username'] == $test['username']){
                    throw new bException(tr('The username "%username%" is already in use', array('%username%' => $user['username'])), 'exists');
                }

                throw new bException(tr('The email "%email%" is already in use', array('%email%' => $user['email'])), 'exists');
            }
        }

        $v->isValid();

        return $user;

    }catch(Exception $e){
        throw new bException(tr('users_validate(): Failed'), $e);
    }
}



/*
 * Get user unique key. If none exist, create one on the fly
 */
function user_get_key($user = null, $force = false){
    try{
        if(!$user){
            $user = $_SESSION['user']['username'];
        }

        if(is_numeric($user)){
            $dbuser = sql_get('SELECT `id`, `username`, `key` FROM `users` WHERE `id`       = :id       AND `status` IS NULL', array(':id' => $user));

        }else{
            $dbuser = sql_get('SELECT `id`, `username`, `key` FROM `users` WHERE (`username` = :username OR `email` = :email) AND `status` IS NULL', array(':username' => $user, ':email' => $user));
        }

        if(!$dbuser){
            throw new bException(tr('user_get_key(): Specified user "%user%" does not exist', array('%user%' => str_log($user))), 'notexist');
        }

        if(!$dbuser['key'] or $force){
            $dbuser['key']           = unique_code();
            $_SESSION['user']['key'] = $dbuser['key'];

            sql_query('UPDATE `users`

                       SET    `key` = :key

                       WHERE  `id`  = :id',

                       array(':id'  => $dbuser['id'],
                             ':key' => $dbuser['key']));
        }

        $timestamp = microtime(true);

        return array('user'      => $dbuser['username'],
                     'timestamp' => $timestamp,
                     'key'       => hash('sha256', $dbuser['key'].SEED.$timestamp));

    }catch(Exception $e){
        throw new bException(tr('user_get_key(): Failed'), $e);
    }
}



/*
 * Check if the key supplied for the specified users id matches
 */
function user_check_key($user, $key, $timestamp){
    try{
// :TODO: Make the future and past time differences configurable
        $future = 2;
        $past   = 1800;

        if(is_numeric($user)){
            $dbkey = sql_get('SELECT `key` FROM `users` WHERE `id`        = :id'      , 'key', array(':id' => $user));

        }elseif(is_string($user)){
            $dbkey = sql_get('SELECT `key` FROM `users` WHERE `username` = :username', 'key', array(':username' => $user));

        }else{
            /*
             * Assume user is an array and contains at least the key
             */
            $dbkey = $user['key'];
        }

        if(!$dbkey){
            /*
             * This user doesn't exist, or doesn't have a key yet!
             */
            return false;
        }

        $diff = microtime(true) - $timestamp;

        if($diff > $past){
            /*
             * More then N seconds differece between timestamps is NOT allowed
             */
            notify('user_check_key()', tr('Received user key check request with timestamp of "%timestamp%" seconds which is larger than the maximum past time of "%max%" seconds', array('%max%' => $past, '%timestamp%' => $timestamp)), 'security');
            return false;
        }

        if(-$diff > $future){
            /*
             * More then N seconds differece between timestamps is NOT allowed
             */
            notify('user_check_key()', tr('Received user key check request with timestamp of "%timestamp%" seconds which is larger than the maximum future time of "%max%" seconds', array('%max%' => $future, '%timestamp%' => $timestamp)), 'security');
            return false;
        }

        $dbkey = hash('sha256', $dbkey.SEED.$timestamp);

        return $dbkey === $key;

    }catch(Exception $e){
        throw new bException(tr('user_check_key(): Failed'), $e);
    }
}



/*
 * Return HTML hidden input form fields containing user key data
 */
function user_key_form_fields($user = null, $prefix = ''){
    try{
        if(!$user){
            $user = $_SESSION['user']['username'];
        }

        $key    = user_get_key($user);

        $retval = ' <input type="hidden" class="'.$prefix.'timestamp" name="'.$prefix.'timestamp" value="'.$key['timestamp'].'">
                    <input type="hidden" class="'.$prefix.'user" name="'.$prefix.'user" value="'.$key['user'].'">
                    <input type="hidden" class="'.$prefix.'key" name="'.$prefix.'key" value="'.$key['key'].'">';

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('user_key_form_fields(): Failed'), $e);
    }
}



/*
 *
 */
function user_get_from_key($user, $key, $timestamp){
    try{
        $user = user_get($user);

        if(user_check_key($user, $key, $timestamp)){
            return $user;
        }

        return false;

    }catch(Exception $e){
        throw new bException(tr('user_get_from_key(): Failed'), $e);
    }
}



/*
 *
 */
function user_key_or_redirect($user, $key = null, $timestamp = null, $redirect = null, $columns = '*'){
    global $_CONFIG;

    try{
        if(is_array($user)){
            /*
             * Assume we got an array, like $_POST, and extract data from there
             */
            $redirect  = $key;
            $key       = isset_get($user['key']);
            $timestamp = isset_get($user['timestamp']);
            $user      = isset_get($user['user']);
        }

        $user = user_get($user, $columns);

        if(user_check_key($user, $key, $timestamp)){
            return $user;
        }

        if(!$redirect){
            $redirect = $_CONFIG['redirects']['signin'];
        }

        /*
         * Send JSON redirect. json_reply() will end script, so no break needed
         */
        load_libs('json');
        json_reply(isset_get($redirect, $_CONFIG['root']), 'signin');

    }catch(Exception $e){
        throw new bException(tr('user_get_from_key(): Failed'), $e);
    }
}
?>
