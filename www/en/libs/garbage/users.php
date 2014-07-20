<?php
/*
 * Users library
 *
 * This is the users library file, it contains user functions. nifty huh?
 */



/*
 * Return an array containing the columns of the users table
 */
function users_columns(){
    return array('added',
                 'status',
                 'username',
                 'password',
                 'name',
                 'avatar',
                 'email',
                 'validation',
                 'fb_id',
                 'fb_token',
                 'gp_id',
                 'tw_id',
                 'li_id',
                 'language',
                 'gender',
                 'bd_day',
                 'bd_month',
                 'bd_year',
                 'country',
                 'state',
                 'city',
                 'lat',
                 'lng',
                 'address1',
                 'address2',
                 'address3',
                 'zipcode',
                 'telephone',
                 'cellphone');
}



/*
 * Return if the specified user exists or not
 */
function users_exists($user){
    global $pdo;

    try{
        if(empty($user)){
            throw new lsException('users_exists(): No user specified');

        }elseif(is_numeric($user)){
            $query  = 'SELECT `users`.`email` FROM `users` WHERE `users`.`id`    = :user';

        }elseif(is_string($user)){
            $query = 'SELECT `users`.`id`     FROM `users` WHERE `users`.`email` = :user';

        }else{
            throw new lsException('users_exists(): Invalid user "'.str_safe($user).'" specified, must be either numeric, or string');
        }

        $q = $pdo->prepare($query);
        $q->execute(array(':user' => $user));

        if(!$q->rowCount()){
            return false;
        }

        return $q->fetchColumn(0);

    }catch(Exception $e){
        throw new lsException('users_exists(): Failed', $e);
    }
}



/*
 * Insert new user in the database
 */
function users_insert($user, $password = '', $rights = ''){
    global $pdo;

    try{
        if(!is_array($user)){
            if(!is_string($user)){
                throw new lsException('user_insert(): user was not specified as array');
            }

            $user = array('email'    => $user,
                          'password' => $password);
        }

        if(empty($user['email'])){
            throw new lsException('user_insert(): No email specified');
        }

        if(empty($user['username'])){
            throw new lsException('user_insert(): No username specified');
        }

        if(empty($user['password'])){
            throw new lsException('user_insert(): No password specified');
        }

        load_libs('sessions');

        $user['password'] = sessions_password_hash($user['password']);

        users_check_blacklisted($user);

        $pdo_data = pdo_insert($user, users_columns());

        $r        = $pdo->prepare('INSERT INTO `users` ('.$pdo_data['fields'].')
                                   VALUES              ('.$pdo_data['values'].')');

        $r->execute($pdo_data['execute']);

        $user['id'] = $pdo->lastInsertId();

        /*
         * Add the users rights
         */
        if($rights){
            $user['rights'] = $rights;
        }

        if(!empty($user['rights'])){
            load_libs('rights');
            rights_users_update($user);
        }

        return $user['id'];

    }catch(Exception $e){
        throw new lsException('users_insert(): Failed', $e);
    }
}



/*
 * Update an existing user with the specified data
 */
function users_update(){
    global $pdo;

    try{
        if(!is_array($user)){
            throw new lsException('users_update(): user was not specified as array');
        }

        if(empty($user['name'])){
            throw new lsException('users_update(): No user name specified');
        }

        if(empty($user['id'])){
            throw new lsException('users_update(): No user id specified');
        }

        unset($user['status']);
        unset($user['password']);
        unset($user['validation']);
        unset($user['syspassword']);

        users_check_blacklisted($user);

        $pdo_data = pdo_update($user, users_columns(), 'id');

        $q        = $pdo->prepare('UPDATE `users`
                                   SET    '.$pdo_data['set'].'
                                   WHERE  `users`.`id` = :id');

        $q->execute($pdo_data['execute']);

        return $q->rowCount();

    }catch(Exception $e){
        throw new lsException('users_update(): Failed', $e);
    }
}



/*
 * Delete the specified user
 */
function users_delete($user){
    global $pdo;

    try{
        if(is_array($user)){
            if(empty($user['id'])){
                throw new lsException('users_delete(): User specified as array, but id missing');
            }

            $user = $user['id'];
        }

        if(empty($user)){
            throw new lsException('users_delete(): No user specified');

        }elseif(is_numeric($user)){
            $query   = 'UPDATE `users` SET `users`.`status` = -1  WHERE `users`.`id`   = :user';
            $execute = array(':user' => $user);

        }elseif(is_string($user)){
            $query   = 'UPDATE `users` SET `users`.`status` = -1  WHERE (`users`.`name` = :user) OR (`users`.`email` = :email)';
            $execute = array(':user'  => $user,
                             ':email' => $user);

        }else{
            throw new lsException('users_delete(): Invalid user "'.str_safe($user).'" specified, must be either numeric, or string');
        }

        if(is_god($user) and !is_god()){
            throw new lsException('users_delete(): User "'.str_safe($user).'" is a god user and cannot be deleted');
        }

        $q = $pdo->prepare($query);
        $q->execute($execute);

        return $q->rowCount();

    }catch(Exception $e){
        throw new lsException('users_delete(): Failed', $e);
    }
}



/*
 * Erase the specified user from the database
 */
function users_erase($user){
    global $pdo;

    try{
        if(is_array($user)){
            if(empty($user['id'])){
                throw new lsException('users_erase(): User specified as array, but id missing');
            }

            $user = $user['id'];
        }

        if(empty($user)){
            throw new lsException('users_erase(): No user specified');

        }elseif(is_numeric($user)){
            $query   = 'DELETE FROM `users` WHERE `users`.`id` = :user';
            $execute = array(':user' => $user);

        }elseif(is_string($user)){
            $query   = 'DELETE FROM `users` WHERE (`users`.`name` = :user) OR (`users`.`email` = :email)';
            $execute = array(':user'  => $user,
                             ':email' => $user);

        }else{
            throw new lsException('users_erase(): Invalid user "'.str_safe($user).'" specified, must be either numeric, or string');
        }

        if(is_god($user) and !is_god()){
            throw new lsException('users_erase(): User "'.str_safe($user).'" is a god user and cannot be erased');
        }

        $q = $pdo->prepare($query);
        $q->execute($execute);

        return $q->rowCount();


    }catch(Exception $e){
        throw new lsException('users_erase(): Failed', $e);
    }
}



/*
 * Get all requested columns from the specified user
 */
function users_get($user, $columns = ''){
    global $pdo;

    try{
        if(!is_array($columns)){
            if(!is_string($columns)){
                throw new lsException('users_get(): Invalid columns specified, should be either CSV string or array', 'invalid');
            }

            if($columns == 'all'){
                $columns    = null;
                $get_rights = true;
            }

            $columns = str_explode(',', $columns);
        }

        /*
         * Get default columns
         */
        if(!$columns){
            $columns = users_columns();
            array_unshift($columns, 'id');
        }

        /*
         * Want right column? They have to be retrieved separately
         */
        if($key = array_search('rights', $columns)){
            unset($columns[$key]);
            $get_rights = true;
        }

        if(empty($user)){
            throw new lsException('users_get(): No user specified', 'notspecified');

        }elseif(is_numeric($user)){
            $query   = 'SELECT '.implode(',', $columns).' FROM `users` WHERE `users`.`id` = :user';
            $execute = array(':user'  => $user);

        }elseif(is_string($user)){
            $query   = 'SELECT '.implode(',', $columns).' FROM `users` WHERE (`users`.`username` = :user) OR (`users`.`email` = :email)';
            $execute = array(':user'  => $user,
                             ':email' => $user);

        }else{
            throw new lsException('users_get(): Invalid user "'.str_safe($user).'" specified, must be either numeric, or string', 'invalid');
        }

        if($columns){
            $q = $pdo->prepare($query);
            $q->execute($execute);

            switch($q->rowCount()){
                case 1:
                    $user = $q->fetch(PDO::FETCH_ASSOC);
                    break;

                case 0:
                    throw new lsException('users_get(): Specified user "'.str_log($user).'" does not exist', 'notexist');

                default:
                    throw new lsException('users_get(): Found multiple results for user "'.str_log($user).'"', 'multiple');
            }


        }elseif(isset($get_rights)){
            /*
             * Appears only rights column was requested
             */
            $user = array();

        }else{
            /*
             * Wut? No columns requested?
             * In THEORY this could not even happen.. but just in case
             */
            throw new lsException('users_get(): No columns specified', 'nocolumns');
        }

        /*
         * Were user rights also requested?
         */
        if(isset($get_rights)){
            $user['rights'] = array_flip(get_rights($user));
        }

        return $user;


    }catch(Exception $e){
        throw new lsException('users_get(): Failed', $e);
    }
}



/*
 * Return an array with all
 */
function users_list($columns = null, $count = null, $offset = null, $getcount = false){
    global $pdo, $_CONFIG;

    try{
        /*
         * Validate parameters
         */
        if(!is_numeric($count) or $count < 0){
            $count = $_CONFIG['paging']['count'];
        }

        if(!is_numeric($offset) or $offset < 0){
            $offset = 0;
        }

        if(!$columns){
            $columns = users_columns();

        }elseif(!is_array($columns)){
            if(!is_string($columns)){
                throw new lsException('rights_list(): Columns should be specified either as string or array', 'invalid');
            }

            $columns = explode(',', $columns);
        }

        if(!in_array('id', $columns)){
            $columns[] = 'id';
        }

        $q = $pdo->query('SELECT '.implode(',', $columns).' FROM `users`'.(($count or $offset) ? ' LIMIT '.$offset.($count ? ', '.$count : '') : ''));

        /*
         * Gather data
         */
        $retval = array();

        while($user = $q->fetch(PDO::FETCH_ASSOC)){
            $retval[$user['id']] = $user;
        }

        /*
         * Get total count?
         */
        if($getcount){
            $c = $pdo->query('SELECT COUNT(id) AS count FROM `users`');
            $c = $c->fetch(PDO::FETCH_ASSOC);

            return array('count' => $c['count'],
                         'data'  => $retval);
        }

        return $retval;

    }catch(Exception $e){
        throw new lsException('users_list(): Failed', $e);
    }
}



/*
 * Register the specified user as a "requested to register"
 */
function users_register($user){
    try{
        $user['validation_code'] = str_random(32);

        return users_insert($user);

    }catch(Exception $e){
        throw new lsException('users_register(): Failed', $e);
    }
}



/*
 * Authorize registration request for specified user
 */
function users_authorize($user, $code){
    try{
        $vuser = users_get($user, 'validation');

        if($vuser['validation'] == $code){

        }

    }catch(Exception $e){
        throw new lsException('users_authorize(): Failed', $e);
    }
}



/*
 * Authorize registration request for specified user
 */
function users_set_password($user, $code){
    try{
        $vuser = users_get($user, 'validation');

        if($vuser['validation'] == $code){

        }

    }catch(Exception $e){
        throw new lsException('users_set_password(): Failed', $e);
    }
}



/*
 * Authorize registration request for specified user
 */
function users_set_syspassword($user, $code){
    try{
        $vuser = users_get($user, 'validation');

        if($vuser['validation'] == $code){

        }

    }catch(Exception $e){
        throw new lsException('users_set_syspassword(): Failed', $e);
    }
}



/*
 * Authorize registration request for specified user
 */
function users_request_password($user){
    try{
        $vuser = users_get($user, 'validation');

        if($vuser['validation'] == $code){

        }

    }catch(Exception $e){
        throw new lsException('users_request_password(): Failed', $e);
    }
}



/*
 * Returns if some of the userdata is blacklisted or not
 */
function users_check_blacklisted($user){
    try{
//:TODO: Implement. THROW EXCEPTION IF BLACKLISTED!

    }catch(Exception $e){
        throw new lsException('users_blacklisted(): Failed', $e);
    }
}



/*
 * Returns if some of the userdata is blacklisted or not
 */
function users_add_avatar($user, $avatar){
    global $_CONFIG;

    try{
        if(!is_array($user)){
            $user = users_get($user);
        }

        $file = file_move_to_target($avatar, ROOT.'data/avatars/users');

        $r = $pdo->prepare('INSERT INTO `users_avatars` (``)
                            VALUES                      ()');

    }catch(Exception $e){
        throw new lsException('users_add_avatar(): Failed', $e);
    }
}



/*
 * Returns if some of the userdata is blacklisted or not
 */
function users_set_default_avatar($user, $avatar){
    global $pdo;

    try{
        if(!is_array($user)){
            $user = users_get($user);
        }

        /*
         * First check if the avatar is registered already. If not, add it first using users_add_avatar()
         */
        $r = $pdo->prepare('SELECT `id` FROM `users_avatars` WHERE `file` = :file');

        $r->execute(array(':file' => $avatar));

        if($r->rowCount()){
            $id = $r->fetchColumn(0);

        }else{
            /*
             * Add the avatar to the system
             */
            $id = users_add_avatar($user, $avatar);
        }

        $r = $pdo->query('UPDATE `users_avatars` SET `default` = false WHERE `users_id` = :users_id');

        $r->execute(array(':users_id' => $user['id']));

        $r = $pdo->query('UPDATE `users_avatars` SET `default` = true  WHERE `id` = :id');

        $r->execute(array(':id' => $id));

    }catch(Exception $e){
        throw new lsException('users_set_default_avatar(): Failed', $e);
    }
}
?>
