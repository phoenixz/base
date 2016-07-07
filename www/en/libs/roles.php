<?php
/*
 * Roles library
 *
 * This library contains funtions to work with the user roles
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Return data for the specified user role
 */
function roles_get($role, $columns = 'id,name,description'){
    try{
        if(!$role){
            throw new bException(tr('roles_get(): No role specified'), 'not-specified');
        }

        if(!is_scalar($role)){
            throw new bException(tr('roles_get(): Specified role "%$role%" is not scalar', array('%$role%' => $role)), 'invalid');
        }

        $retval = sql_get('SELECT '.$columns.'

                           FROM   `roles`

                           WHERE  `id`   = :role
                           OR     `name` = :role2', $columns,

                           array(':role'  => $role,
                                 ':role2' => $role));

        if(!$retval){
            throw new bException('roles_get(): Specified role "'.str_log($role).'" does not exist', 'not-exist');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('roles_get(): Failed', $e);
    }
}



/*
 *
 */
function roles_validate($role, $old_role = null){
    try{
        load_libs('validate');

        if($old_role){
            $role = array_merge($old_role, $role);
        }

        $v = new validate_form($role, 'name,description');
        $v->isNotEmpty ($role['name']     , tr('No roles name specified'));
        $v->hasMinChars($role['name'],   2, tr('Please ensure the role\'s name has at least 2 characters'));
        $v->hasMaxChars($role['name'],  32, tr('Please ensure the role\'s name has less than 32 characters'));
        $v->hasNoChars ($role['name'], ' ', tr('Please ensure the role\'s name contains no spaces'));

        $v->isNotEmpty ($role['description']      , tr('No role\'s description specified'));
        $v->hasMinChars($role['description'],    2, tr('Please ensure the role\'s description has at least 2 characters'));
        $v->hasMaxChars($role['description'], 2047, tr('Please ensure the role\'s description has less than 2047 characters'));

        if(is_numeric(substr($role['name'], 0, 1))){
            $v->setError(tr('Please ensure that the role\'s name does not start with a number'));
        }

        if(empty($role['id'])){
            if($id = sql_get('SELECT `id` FROM `roles` WHERE `name` = :name', array(':name' => $role['name']))){
                $v->setError(tr('The role "%role%" already exists with id "%id%"', array('%role%' => str_log($role['name']), '%id%' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `roles` WHERE `name` = :name AND `id` != :id', array(':name' => $role['name'], ':id' => $role['id']))){
                $v->setError(tr('The role "%role%" already exists with id "%id%"', array('%role%' => str_log($role['name']), '%id%' => $id)));
            }
        }

        $v->isValid();

        return $role;

    }catch(Exception $e){
        throw new bException(tr('roles_validate(): Failed'), $e);
    }
}
?>
