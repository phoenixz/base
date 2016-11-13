<?php
/*
 * Custom servers library
 *
 * This library contains functions to manage toolkit servers
 *
 * Written and Copyright by Sven Oostenbrink
 */

/*
 *ssh validate ssh accounts
 */

function ssh_validate($ssh, $old_right = null){
    try{
        load_libs('validate');

        if($old_right){
            $ssh = array_merge($old_right, $ssh);
        }

        $v = new validate_form($ssh, 'name,description');
        $v->isNotEmpty ($ssh['name']    , tr('No rights name specified'));
        $v->hasMinChars($ssh['name'],  2, tr('Please ensure the right\'s name has at least 2 characters'));
        $v->hasMaxChars($ssh['name'], 32, tr('Please ensure the right\'s name has less than 32 characters'));

        $v->isNotEmpty ($ssh['description']      , tr('No right\'s description specified'));
        $v->hasMinChars($ssh['description'],    2, tr('Please ensure the right\'s description has at least 2 characters'));
        $v->hasMaxChars($ssh['description'], 2047, tr('Please ensure the right\'s description has less than 2047 characters'));

        if(is_numeric(substr($ssh['name'], 0, 1))){
            $v->setError(tr('Please ensure that the rights\'s name does not start with a number'));
        }

        /*
         * Does the right already exist?
         */
        if(empty($ssh['id'])){
            if($id = sql_get('SELECT `id` FROM `rights` WHERE `name` = :name', array(':name' => $ssh['name']))){
                $v->setError(tr('The right ":right" already exists with id ":id"', array(':right' => $ssh['name'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `rights` WHERE `name` = :name AND `id` != :id', array(':name' => $ssh['name'], ':id' => $ssh['id']))){
                $v->setError(tr('The right ":right" already exists with id ":id"', array(':right' => $ssh['name'], ':id' => $id)));
            }

            /*
             * Also check if this is not the god right. If so, it CAN NOT be
             * updated
             */
            $name = sql_get('SELECT `name` FROM `rights` WHERE `id` = :id', 'name', array(':id' => $ssh['id']));

            if($name === 'god'){
                $v->setError(tr('The right "god" cannot be modified'));
            }
        }

        $v->isValid();

        return $ssh;

    }catch(Exception $e){
        throw new bException(tr('ssh_validate(): Failed'), $e);
    }
}

/*
 *Get data ssh accounts
 */
function ssh_get($ssh){
    try{
        if(!$ssh){
            throw new bException(tr('ssh_get(): No right specified'), 'not-specified');
        }

        if(!is_scalar($ssh)){
            throw new bException(tr('ssh_get(): Specified right ":right" is not scalar', array(':right' => $ssh)), 'invalid');
        }

        $retval = sql_get('SELECT    `ssh_keys`.`id`,
                                     `ssh_keys`.`name`,
                                     `ssh_keys`.`username`,
                                     `ssh_keys`.`ssh_key`,
                                     `ssh_keys`.`status`,
                                     `ssh_keys`.`description`,

                                     `createdby`.`name`   AS `createdby_name`,
                                     `createdby`.`email`  AS `createdby_email`,
                                     `modifiedby`.`name`  AS `modifiedby_name`,
                                     `modifiedby`.`email` AS `modifiedby_email`

                           FROM      `ssh_keys`

                           LEFT JOIN `users` AS `createdby`
                           ON        `ssh_keys`.`createdby`  = `createdby`.`id`

                           LEFT JOIN `users` AS `modifiedby`
                           ON        `ssh_keys`.`modifiedby` = `modifiedby`.`id`

                           WHERE     `ssh_keys`.`id`   = :ssh
                           OR        `ssh_keys`.`name` = :ssh',

                           array(':ssh' => $ssh));
        return $retval;

    }catch(Exception $e){
        throw new bException('ssh_get(): Failed', $e);
    }
}
?>
