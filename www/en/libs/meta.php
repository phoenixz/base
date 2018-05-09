<?php
/*
 * Meta library
 *
 * Can store meta information about other database records
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Add specified action to meta history for the specified meta_id
 */
function meta_action($meta_id = null, $action = null, $data = null){
    try{
        if(!$meta_id){
            $action = 'create';

            sql_query('INSERT INTO `meta` (`id`)
                       VALUES             (null)');

            $meta_id = sql_insert_id();

        }else{
            if(!is_numeric($meta_id)){
                throw new bException(tr('meta_action(): Invalid meta_id ":meta_id" specified', array(':meta_id' => $meta_id)), 'invalid');
            }
        }

        return meta_add_history($meta_id, $action, $data);

    }catch(Exception $e){
        throw new bException('meta_action(): Failed', $e);
    }
}



/*
 * Add specified action to meta history for the specified meta_id
 */
function meta_add_history($meta_id, $action, $data = null){
    try{
        sql_query('INSERT INTO `meta_history` (`createdby`, `meta_id`, `action`, `data`)
                   VALUES                     (:createdby , :meta_id , :action , :data )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => $meta_id,
                         ':action'    => $action,
                         ':data'      => json_encode($data)));

        return $meta_id;

    }catch(Exception $e){
        throw new bException('meta_add_history(): Failed', $e);
    }
}



/*
 * Return array with all the history for the specified meta_id
 */
function meta_history($meta_id){
    try{
        $history = sql_list('SELECT    `meta_history`.`id`,
                                       `meta_history`.`createdby`,
                                       `meta_history`.`createdon`,
                                       `meta_history`.`action`,
                                       `meta_history`.`data`,

                                       `users`.`name`,
                                       `users`.`email`,
                                       `users`.`username`,
                                       `users`.`nickname`

                             FROM      `meta_history`

                             LEFT JOIN `users`
                             ON        `users`.`id` = `meta_history`.`createdby`

                             WHERE     `meta_id` = :meta_id

                             ORDER BY  `meta_history`.`createdon` DESC, `meta_history`.`id` DESC ',

                             array(':meta_id' => $meta_id));

        return $history;

    }catch(Exception $e){
        throw new bException('meta_history(): Failed', $e);
    }
}



/*
 * Erase the meta entry
 * NOTE: Due to foreign key restraints, ensure that the referencing table entry
 * has been erased first!
 */
function meta_erase($meta_id){
    try{
        sql_query('DELETE FROM `meta_history` WHERE `meta_id` = :meta_id', array(':meta_id' => $meta_id));
        sql_query('DELETE FROM `meta`         WHERE `id`      = :id'     , array(':id'      => $meta_id));

        return $meta_id;

    }catch(Exception $e){
        throw new bException('meta_erase(): Failed', $e);
    }
}



/*
 *
 */
function meta_clear($meta_id, $views_only = false){
    try{
        if($views_only){
            sql_query('DELETE FROM `meta_history` WHERE `meta_id` = :meta_id AND `action` = "view"', array(':meta_id' => $meta_id));
            meta_action($meta_id, 'clear-views');

        }else{
            sql_query('DELETE FROM `meta_history` WHERE `meta_id` = :meta_id', array(':meta_id' => $meta_id));
            meta_action($meta_id, 'clear-history');
        }

        return $meta_id;

    }catch(Exception $e){
        throw new bException('meta_erase(): Failed', $e);
    }
}
?>
