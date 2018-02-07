<?php
/*
 * Groupss library
 *
 * This is the groups library file, it contains groups functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copygroup Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Return requested data for specified groups
 */
function groups_get($group, $createdby = null){
    try{
        if(!$group){
            throw new bException(tr('groups_get(): No group specified'), 'not-specified');
        }

        if(!is_scalar($group)){
            throw new bException(tr('groups_get(): Specified group ":group" is not scalar', array(':group' => $group)), 'invalid');
        }

        $query = 'SELECT    `groups`.`id`,
                            `groups`.`name`,
                            `groups`.`status`,
                            `groups`.`description`,

                            `createdby`.`name`   AS `createdby_name`,
                            `createdby`.`email`  AS `createdby_email`,
                            `modifiedby`.`name`  AS `modifiedby_name`,
                            `modifiedby`.`email` AS `modifiedby_email`

                  FROM      `groups`

                  LEFT JOIN `users` AS `createdby`
                  ON        `groups`.`createdby`  = `createdby`.`id`

                  LEFT JOIN `users` AS `modifiedby`
                  ON        `groups`.`modifiedby` = `modifiedby`.`id`

                  WHERE    (`groups`.`id`   = :group
                  OR        `groups`.`name` = :group)';

        $execute = array(':group' => $group);

        if($createdby){
            $query .= 'AND `groups`.`createdby` = :createdby';
            $execute[':createdby'] = $createdby;
        }

        $retval = sql_get($query, $execute);

        return $retval;

    }catch(Exception $e){
        throw new bException('groups_get(): Failed', $e);
    }
}



/*
 *
 */
function groups_validate($group, $old_group = null){
    try{
        load_libs('validate,seo');

        if($old_group){
            $group = array_merge($old_group, $group);
        }

        $v = new validate_form($group, 'name,description');
        $v->isNotEmpty ($group['name']    , tr('No groups name specified'));
        $v->hasMinChars($group['name'],  2, tr('Please ensure the group\'s name has at least 2 characters'));
        $v->hasMaxChars($group['name'], 64, tr('Please ensure the group\'s name has less than 64 characters'));

        if(!$group['description']){
            $group['description'] = '';

        }else{
            $v->hasMinChars($group['description'],    2, tr('Please ensure the group\'s description has at least 2 characters'));
            $v->hasMaxChars($group['description'], 2047, tr('Please ensure the group\'s description has less than 2047 characters'));
        }

        if(is_numeric(substr($group['name'], 0, 1))){
            $v->setError(tr('Please ensure that the groups\'s name does not start with a number'));
        }

        /*
         * Does the group already exist?
         */
        if(empty($group['id'])){
            if($id = sql_get('SELECT `id` FROM `groups` WHERE `name` = :name', array(':name' => $group['name']))){
                $v->setError(tr('The group ":group" already exists with id ":id"', array(':group' => $group['name'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `groups` WHERE `name` = :name AND `id` != :id', array(':name' => $group['name'], ':id' => $group['id']))){
                $v->setError(tr('The group ":group" already exists with id ":id"', array(':group' => $group['name'], ':id' => $id)));
            }
        }

        $group['seoname'] = seo_unique($group['name'], 'groups', isset_get($group['id']));

        $v->isValid();

        return $group;

    }catch(Exception $e){
        throw new bException(tr('groups_validate(): Failed'), $e);
    }
}



/*
 *
 */
function groups_get_users($group){
    try{
        $retval = sql_get('SELECT    `groups`.`id`,
                                     `groups`.`name`,
                                     `groups`.`status`,
                                     `groups`.`description`,

                                     `createdby`.`name`   AS `createdby_name`,
                                     `createdby`.`email`  AS `createdby_email`,
                                     `modifiedby`.`name`  AS `modifiedby_name`,
                                     `modifiedby`.`email` AS `modifiedby_email`

                           FROM      `groups`

                           LEFT JOIN `users` AS `createdby`
                           ON        `groups`.`createdby`  = `createdby`.`id`

                           LEFT JOIN `users` AS `modifiedby`
                           ON        `groups`.`modifiedby` = `modifiedby`.`id`

                           WHERE    (`groups`.`id`      = :group
                           OR        `groups`.`seoname` = :group)', array(':group' => $group));

        if(empty($retval)){
            throw new bException(tr('groups_get_users(): Specified group ":group" does not exist', array(':group' => $group)), 'invalid');
        }

        $users = sql_query('SELECT    `users`.`id`,
                                      `users`.`name`,
                                      `users`.`username`

                            FROM      `users`

                            LEFT JOIN `users_groups`
                            ON        `users_groups`.`users_id` = `users`.`id`

                            WHERE     `users_groups`.`groups_id` = :groups_id', array(':groups_id' => $retval['id']));

        $retval['users'] = array();

        while($user = sql_fetch($users)){
            array_push($retval['users'], $user);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('groups_get_users(): Failed'), $e);
    }
}
?>
