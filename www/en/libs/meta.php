<?php
/*
 * Meta library
 *
 * Can store meta information about other database records
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Add specified action to meta history for the specified meta_id
 */
function meta_action($meta_id, $action, $data = null){
    try{
        if(!$meta_id){
            sql_query('INSERT INTO `meta` VALUES (`id`),
                       VALUES                    (null)');

            $meta_id = sql_insert_id();
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
        sql_query('INSERT INTO `meta_history` VALUES (`createdby`, `meta_id`, `action`, `data`)
                   VALUES                            (:createdby , :meta_id , :action , :data )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => $meta_id,
                         ':action'    => $action,
                         ':data'      => json_encode($data)));

        return $meta_id;

    }catch(Exception $e){
        throw new bException('meta_action(): Failed', $e);
    }
}



/*
 * Return array with all the history for the specified meta_id
 */
function meta_list_history($meta_id){
    try{
        return sql_list('SELECT `action`, `data` FROM `meta_history` WHERE `meta_id` = :meta_id', array(':meta_id' => $meta_id));

    }catch(Exception $e){
        throw new bException('meta_list_history(): Failed', $e);
    }
}
?>
