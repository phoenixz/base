<?php
/*
 * Rights library
 *
 * This is the rights library file, it contains rights functions
 */



/*
 * Return an array containing the columns of the rights table
 */
function rights_columns(){
    return array('name',
                 'description');
}



/*
 * Return if the specified right exists or not
 */
function rights_exists($right){
    global $pdo;

    try{
        if(empty($right)){
            throw new bException('rights_exists(): No right specified');

        }elseif(is_numeric($right)){
            $query  = 'SELECT `rights`.`name` FROM `rights` WHERE `rights`.`id`   = :right';

        }elseif(is_string($right)){
            $query = 'SELECT `rights`.`id`    FROM `rights` WHERE `rights`.`name` = :right';

        }else{
            throw new bException('rights_exists(): Invalid right "'.str_safe($right).'" specified, must be either numeric, or string');
        }

        $q = $pdo->prepare($query);
        $q->execute(array(':right' => $right));

        if(!$q->rowCount()){
            return false;
        }

        return $q->fetchColumn(0);

    }catch(Exception $e){
        throw new bException('rights_exists(): Failed', $e);
    }
}



/*
 * Insert new right in the database
 */
function rights_insert($right, $description = ''){
    global $pdo;

    try{
        if(!is_array($right)){
            if(!is_string($right)){
                throw new bException('right_insert(): right should be specified as a string or an array containing "name"');
            }

            $right = array('name'        => $right,
                           'description' => $description);
        }

        if(empty($right['name'])){
            throw new bException('right_insert(): No name specified');
        }

        if($right['name'] == 'devil'){
            throw new bException('right_insert(): The devil right can not be added to the system');
        }

        $pdo_data = pdo_insert($right, rights_columns());

        $r        = $pdo->prepare('INSERT INTO `rights` ('.$pdo_data['fields'].')
                                   VALUES               ('.$pdo_data['values'].')');

        $r->execute($pdo_data['execute']);

        return $pdo->lastInsertId();

    }catch(Exception $e){
        throw new bException('rights_insert(): Failed', $e);
    }
}



/*
 * Update an existing right with the specified data
 */
function rights_update(){
    global $pdo;

    try{
        if(!is_array($right)){
            throw new bException('rights_update(): right was not specified as array');
        }

        if(!empty($right['name']) and($right['name'] == 'devil')){
            throw new bException('rights_update(): The devil right can not be added to the system');
        }

        $pdo_data = pdo_update($right, rights_columns(), 'id');

        $q        = $pdo->prepare('UPDATE `rights`
                                   SET    '.$pdo_data['set'].'
                                   WHERE  `rights`.`id` = :id');

        $q->execute($pdo_data['execute']);

        return $q->rowCount();

    }catch(Exception $e){
        throw new bException('rights_update(): Failed', $e);
    }
}



/*
 * Delete the specified right
 */
function rights_delete($right){
    global $pdo;

    try{
        if(is_array($right)){
            if(empty($right['id'])){
                throw new bException('rights_delete(): User specified as array, but id missing');
            }

            $right = $right['id'];
        }

        if(empty($right)){
            throw new bException('rights_delete(): No right specified');

        }elseif(is_numeric($right)){
            $query   = 'UPDATE `rights` SET `rights`.`status` = -1  WHERE `rights`.`id`   = :right';

        }elseif(is_string($right)){
            $query   = 'UPDATE `rights` SET `rights`.`status` = -1  WHERE `rights`.`name` = :right';

        }else{
            throw new bException('rights_delete(): Invalid right "'.str_safe($right).'" specified, must be either numeric, or string');
        }

        $q = $pdo->prepare($query);
        $q->execute(array(':right' => $right));

        return $q->rowCount();

    }catch(Exception $e){
        throw new bException('rights_delete(): Failed', $e);
    }
}



/*
 * Erase the specified right from the database
 */
function rights_erase($right){
    global $pdo;

    try{
        if(is_array($right)){
            if(empty($right['id'])){
                throw new bException('rights_erase(): User specified as array, but id missing');
            }

            $right = $right['id'];
        }

        if(empty($right)){
            throw new bException('rights_erase(): No right specified');

        }elseif(is_numeric($right)){
            $query   = 'DELETE FROM `rights` WHERE `rights`.`id` = :right';

        }elseif(is_string($right)){
            $query   = 'DELETE FROM `rights` WHERE `rights`.`name` = :right';

        }else{
            throw new bException('rights_erase(): Invalid right "'.str_safe($right).'" specified, must be either numeric, or string');
        }

        $q = $pdo->prepare($query);
        $q->execute(array(':right' => $right));

        return $q->rowCount();


    }catch(Exception $e){
        throw new bException('rights_erase(): Failed', $e);
    }
}



/*
 * Get all requested columns from the specified right
 */
function rights_get($right, $columns = null){
    global $pdo;

    try{
        if(!$columns){
            $columns = rights_columns();
            array_unshift($columns, 'id');

        }elseif(!is_array($columns)){
            if(!is_string($columns)){
                throw new bException('rights_get(): Invalid columns specified, should be either CSV string or array', 'invalid');
            }

            $columns = explode(',', $columns);
        }

        if(empty($right)){
            throw new bException('rights_get(): No right specified', 'notspecified');

        }elseif(is_numeric($right)){
            $query  = 'SELECT '.implode(',', $columns).' FROM `rights` WHERE `rights`.`id`   = :right';

        }elseif(is_string($right)){
            $query = 'SELECT '.implode(',', $columns).' FROM `rights` WHERE (`rights`.`name` = :right)';

        }else{
            throw new bException('rights_get(): Invalid right "'.str_safe($right).'" specified, must be either numeric, or string', 'invalid');
        }

        $q = $pdo->prepare($query);
        $q->execute(array(':right' => $right));

        switch($q->rowCount()){
            case 0:
                return false;

            case 1:
                return $q->fetch(PDO::FETCH_ASSOC);
        }

        throw new bException('rights_get(): Found multiple results for right "'.str_log($right).'"');

    }catch(Exception $e){
        throw new bException('rights_get(): Failed', $e);
    }
}



/*
 * Return an array with all
 */
function rights_list($columns = null, $count = null, $offset = null, $getcount = false){
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
            $columns = rights_columns();

        }elseif(!is_array($columns)){
            if(!is_string($columns)){
                throw new bException('rights_list(): Columns should be specified either as string or array', 'invalid');
            }

            $columns = explode(',', $columns);
        }

        if(!in_array('id', $columns)){
            $columns[] = 'id';
        }

        $q = $pdo->query('SELECT '.implode(',', $columns).' FROM `rights`'.(($count or $offset) ? ' LIMIT '.$offset.($count ? ', '.$count : '') : ''));

        /*
         * Gather data
         */
        $retval = array();

        while($right = $q->fetch(PDO::FETCH_ASSOC)){
            $retval[$right['id']] = $right;
        }

        /*
         * Get total count?
         */
       if($getcount){
            $c = $pdo->query('SELECT COUNT(id) AS count FROM `rights`');
            $c = $c->fetch(PDO::FETCH_ASSOC);

            return array('count' => $c['count'],
                         'data'  => $retval);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('rights_list(): Failed', $e);
    }
}



/*
 * Update the specified user with the specified rights
 */
function rights_users_update($user, $rights = null){
    global $pdo;

    try{
        if(empty($_SESSION['user'])){
            $session_user = NULL;

        }else{
            $session_user = $_SESSION['user']['id'];
        }

        /*
         * Rights may be specified separately
         */
        if($rights){
            $user['rights'] = $rights;
        }

        /*
         * Make sure we have a real user
         */
        if(!is_array($user)){
            $user = users_get($user);
        }

        /*
         * Make sure rights are specified as array
         */
        if(!is_array($user['rights'])){
            if(!is_string($user['rights'])){
                throw('rights_users_update(): Rights should be specified as array or CSV string');
            }

            $user['rights'] = str_explode(',', $user['rights']);
        }

        /*
         * Make sure that all rights are available as ID
         */
        foreach($user['rights'] as $key => &$right){
            if(!$right){
                throw new bException('rights_users_update(): Empty right specified', 'emptyspecified');
            }


            if(!is_numeric($right)){
                $right = rights_get($right);
                $right = $right['id'];
            }
        }

        unset($right);

        /*
         * Get the rights the user currently has
         */
        $current = get_rights($user);

        /*
         * Prepare queries
         */
        $r_inert = $pdo->prepare('INSERT INTO `users_rights` (`added_by`, `users_id`, `rights_id`)
                                  VALUES                     (:added_by , :users_id , :rights_id)');

        $r_erase = $pdo->prepare('DELETE FROM `users_rights`
                                  WHERE       `users_id`  = :users_id,
                                  AND         `rights_id` = :rights_id');

        /*
         * Calculate new rights and insert then
         */
        foreach(array_diff_assoc($user['rights'], $current) as $insert_right){
            $r_inert->execute(array(':added_by'  => $session_user,
                                    ':users_id'  => $user['id'],
                                    ':rights_id' => $insert_right));
        }

        /*
         * Calculate revoked rights and erase them
         */
        foreach(array_diff_assoc($current, $user['rights']) as $erase_right){
            $r_erase->execute(array(':users_id'  => $user['id'],
                                    ':rights_id' => $erase_right));
        }

    }catch(Exception $e){
        throw new bException('rights_users_update(): Failed', $e);
    }
}



/*
 * Return an HTML select containing all posisble rights
 */
function rights_select($select = '', $name = 'rights_id', $god = true){
    global $pdo;

    try{
        if($retval = cache_read('rights_'.$name.'_'.$select.($god ? '_all' : ''))){
            return $retval;
        }

        $retval = '<select class="categories" name="'.$name.'">';

        if($god){
            $retval .= '<option value="0"'.(!$select ? ' selected' : '').'>All categories</option>';
        }

        foreach(rights_list() as $right){
            $retval .= '<option value="'.$right['id'].'"'.(($right['id'] == $select) ? ' selected' : '').'>'.str_replace('_', ' ', str_camelcase($right['name'])).'</option>';
        }

        return cache_write('rights_'.$name.'_'.$select.($god ? '_all' : ''), $retval.'</select>');

    }catch(Exception $e){
        throw new bException('rights_select(): Failed', $e);
    }
}
?>
