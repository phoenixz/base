<?php
/*
 * Forwarding library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Capmega <copyright@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function forwards_library_init(){
    try{
        load_libs('csf,route');

    }catch(Exception $e){
        throw new bException('forwards_library_init(): Failed', $e);
    }
}



/*
 * Inserts a new forwarding rule
 *
 * @param array $forward
 * @param integer $createdby
 * @return integer, id for the new created record
 */
function forwards_insert($forward, $createdby = null){
    try{
        array_ensure($forward, '');
        array_default($forward, 'apply', false);

        $forward = forwards_validate($forward);

        sql_query('INSERT INTO `forwards` (`createdby`, `source_ip`, `source_port`, `source_id`, `target_ip`, `target_port`, `target_id`, `protocol`, `description`)
                       VALUES                (:createdby, :source_ip, :source_port, :source_id, :target_ip, :target_port, :target_id, :protocol, :description)',

                       array(':createdby'   => $createdby,
                             ':source_ip'   => $forward['source_ip'],
                             ':source_port' => $forward['source_port'],
                             ':source_id'   => $forward['source_id'],
                             ':target_ip'   => $forward['target_ip'],
                             ':target_port' => $forward['target_port'],
                             ':target_id'   => $forward['target_id'],
                             ':protocol'    => $forward['protocol'],
                             ':description' => $forward['description']));

        $forward_id = sql_insert_id();
        if($forward_id and $forward['apply']){
            forwards_insert_apply($forward);
        }

        return $forward_id;

    }catch(Exception $e){
        throw new bException('forwards_insert(): Failed', $e);
    }
}



/*
 * Applies to server new forwarding rule
 *
 * @param array
 * @return void
 */
function forwards_insert_apply($forward){
    try{
        array_ensure($forward, '');
        array_default($forward, 'apply', true);

        if($forward['apply']){
            switch($forward['protocol']){
                case 'smtp':
                    //FALLTHROUGH
                case 'imap':
                    //FALLTHROUGH
                case 'http':
                    //FALLTHROUGH
                case 'https':
                    /*
                     * Redirect request to target server
                     */
                    route_add_prerouting( $forward['target_id'], 'tcp', $forward['source_port'], $forward['target_port'], $forward['target_ip']);
                    route_add_postrouting($forward['target_id'], 'tcp', $forward['target_port'], $forward['target_ip']);

                    csf_allow_rule($forward['target_id'], 'tcp', 'in', $forward['target_port'], $forward['source_ip']);
                    csf_allow_rule($forward['source_id'], 'tcp', 'out', $forward['source_port'], $forward['target_ip']);
                    break;

                case 'ssh':
                    /*
                     * Allow connections
                     */
                    csf_allow_rule($forward['target_id'], 'tcp', 'in', $forward['target_port'], $forward['source_ip']);
                    csf_allow_rule($forward['source_id'], 'tcp', 'in', $forward['source_port'], $forward['target_ip']);
                    /*
                    * Reload rules for server csf_restart()
                    */
                    csf_restart($forward['source_id']);
                    csf_restart($forward['target_id']);
                    break;

                default:

            }
        }

    }catch(Exception $e){
        throw new bException('forwards_insert_apply(): Failed', $e);
    }
}



/*
 * Deletes a forwarding rule
 *
 * @param array $forward
 * @retur void
 */
function forwards_delete($forward){
    try{
        array_ensure($forward, '');
        array_default($forward, 'apply', true);

        sql_query('DELETE FROM `forwards`
                   WHERE  id = :id',

                   array(':id' => $forward['id']));

        if($forward['apply']){
            forwards_delete_apply($forward);
        }

    }catch(Exception $e){
        throw new bException('forwards_delete(): Failed', $e);
    }
}



/*
 * Removes rules for a deleted forwarding record on database
 * @param array $forward
 * @return void
 */
function forwards_delete_apply($forward){
    try{
        if($forward['apply']){
            switch($forward['protocol']){
                case 'http':
                    //FALLTHROUGH
                case 'https':
                    /*
                     * Redirect request to target server
                     */
                    route_flush_all($forward['source_id']);
                    route_flush_all($forward['target_id']);

                    csf_remove_allow_rule($forward['target_id'], 'tcp', 'in', $forward['target_port'], $forward['source_ip']);
                    csf_remove_allow_rule($forward['source_id'], 'tcp', 'out', $forward['source_port'], $forward['target_ip']);
                    csf_restart($forward['source_id']);
                    csf_restart($forward['targest_id']);
                    break;

                case 'ssh':
                    /*
                     * Allow connections
                     */
                    csf_allow_rule($forward['target_id'], 'tcp', 'in', $forward['target_port'], $forward['source_ip']);
                    csf_allow_rule($forward['source_id'], 'tcp', 'in', $forward['source_port'], $forward['target_ip']);

                    csf_remove_allow_rule($forward['target_id'], 'tcp', 'in', $forward['target_port'], $forward['source_ip']);
                    csf_remove_allow_rule($forward['source_id'], 'tcp', 'in', $forward['source_port'], $forward['target_ip']);

                    csf_restart($forward['source_id']);
                    csf_restart($forward['target_id']);
                    break;

                default:

            }
        }
    }catch(Exception $e){
        throw new bException('forwards_delete_apply(): Failed', $e);
    }
}



/*
 * Updates forwarding rule
 *
 * @param array $forward
 * @param integer $createdby
 * @return void
 */
function forwards_update($forward, $modifiedby = null){
    try{
        $forward     = forwards_validate($forward);
        $old_forward = sql_get('SELECT
                                    `forwards`.`id`,
                                    `forwards`.`source_ip`,
                                    `forwards`.`source_port`,
                                    `forwards`.`source_id`,
                                    `forwards`.`target_ip`,
                                    `forwards`.`target_port`,
                                    `forwards`.`target_id`,
                                    `forwards`.`protocol`

                                FROM      `forwards`

                                WHERE     `forwards`.`id`    = :id', array(':id' => $forward['id']));

        sql_query('UPDATE `forwards`

                       SET    `modifiedby`  = :modifiedby,
                              `modifiedon`  = NOW(),
                              `source_ip`   = :source_ip,
                              `source_port` = :source_port,
                              `source_id`   = :source_id,
                              `target_ip`   = :target_ip,
                              `target_port` = :target_port,
                              `target_id`   = :target_id,
                              `protocol`    = :protocol,
                              `description` = :description

                       WHERE  `id`          = :id',

                       array(':id'          => $forward['id'],
                             ':modifiedby'  => $modifiedby,
                              'source_ip'   => $forward['source_ip'],
                              'source_port' => $forward['source_port'],
                              'source_id'   => $forward['source_id'],
                              'target_ip'   => $forward['target_ip'],
                              'target_port' => $forward['target_port'],
                              'target_id'   => $forward['target_id'],
                              'protocol'    => $forward['protocol'],
                              'description' => $forward['description']));

        if($forward['apply']){
            forwards_update_apply($forward, $old_forward);
        }

    }catch(Exception $e){
        throw new bException('forwards_update(): Failed', $e);
    }
}



/*
 * Appies forwarding rule after been updated
 * @param array $forward
 * @return void
 */
function forwards_update_apply($forward, $old_forward){
    try{
        if($forward['apply']){
            switch($forward['protocol']){
                case 'http':
                    //FALLTHROUGH
                case 'https':
                    /*
                     * Remove old rules on csf
                     */
                    csf_remove_allow_rule($old_forward['target_id'], 'tcp', 'in', $old_forward['target_port'], $old_forward['source_ip']);
                    csf_remove_allow_rule($old_forward['source_id'], 'tcp', 'out', $old_forward['source_port'], $old_forward['target_ip']);

                    /*
                     * Redirect request to target server
                     */
                    route_add_prerouting( $forward['target_id'], 'tcp', $forward['source_port'], $forward['target_port'], $forward['target_ip']);
                    route_add_postrouting($forward['target_id'], 'tcp', $forward['target_port'], $forward['target_ip']);

                    /*
                     * Removed old rules
                     */
                    break;

                case 'ssh':
                    /*
                     * Allow connections
                     */
                    csf_allow_rule($forward['target_id'], 'tcp', 'in', $forward['target_port'], $forward['source_ip']);
                    csf_allow_rule($forward['source_id'], 'tcp', 'in', $forward['source_port'], $forward['target_ip']);

                    csf_remove_allow_rule($old_forward['target_id'], 'tcp', 'in', $old_forward['target_port'], $old_forward['source_ip']);
                    csf_remove_allow_rule($old_forward['source_id'], 'tcp', 'in', $old_forward['source_port'], $old_forward['target_ip']);
                    break;

                default:

            }
        }
    }catch(Exception $e){
        throw new bException('forwards_update_apply(): Failed', $e);
    }
}



/*
 * Validates information in order to insert or update record on database
 *
 * @param array $forward
 * @return array
 */
function forwards_validate($forward){
    try{
        load_libs('validate');
        array_params($forward);

        $v = new validate_form($forward, 'source_ip,source_port,target_ip,target_port');
        $v->isNotEmpty($forward['source_ip'],   tr('Please specifiy a source ip'));
        $v->isNotEmpty($forward['source_port'], tr('Please specifiy a source port'));

        if(!is_natural($forward['source_port']) or ($forward['source_port'] > 65535)){
            $v->setError(tr('Please specify a valid port for source port field'));
        }

        $v->isFilter($forward['source_ip'], FILTER_VALIDATE_IP, tr('Please specify a valid IP address for source IP field'));

        if($forward['source_id']){
            $exists = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $forward['source_id']), true);

            if(!$exists){
                $v->setError(tr('Specified proxy ":source" does not exist', array(':source' => $forward['source_id'])));

            }else{
                $forward['source_id'] = $exists;

            }
        }

        if($forward['target_id']){
            $exists = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $forward['target_id']), true);

            if(!$exists){
                $v->setError(tr('Specified proxy ":source" does not exist', array(':source' => $forward['target_id'])));
            }else{
                $forward['target_id'] = $exists;
            }
        }


        if(!empty($forward['target_port']) and (!is_natural($forward['target_port']) or ($forward['target_port'] > 65535))){
            $v->setError(tr('Please specify a valid port for target port field'));
        }

        $v->isFilter($forward['target_ip'], FILTER_VALIDATE_IP, tr('Please specify a valid IP address for target IP field'));

        if($forward['protocol']){
            switch($forward['protocol']){
                case 'ssh':
                    // FALLTHROUGH
                case 'http':
                    // FALLTHROUGH
                case 'https':
                    // FALLTHROUGH
                case 'smtp':
                    // FALLTHROUGH
                case 'imap':
                    // FALLTHROUGH
                    break;

                case '':
                    $v->setError(tr('Please specify a protocol'));

                default:
                    $v->setError(tr('Please specify a valid protocol (ssh, http, https, smtp, imap)'));
            }
        }

        $v->isValid();

        return $forward;

    }catch(Exception $e){
        throw new bException('forwards_validate(): Failed', $e);
    }
}



/*
 * Returns a record from the database
 *
 * @param integer $forward, id on database to get the information from
 * @return array
 */
function forwards_get($forwards_id){
    try{
        if(empty($forwards_id)){
            throw new bException(tr('forwards_get(): No forwarding specified'), 'not-specified');
        }

        $forward = sql_get('SELECT    `forwards`.`id`,
                                      `forwards`.`createdby`,
                                      `forwards`.`source_ip`,
                                      `forwards`.`source_port`,
                                      `forwards`.`target_ip`,
                                      `forwards`.`target_port`,
                                      `forwards`.`protocol`,
                                      `forwards`.`description`,

                                      `source_servers`.`seohostname` AS `source_id`,
                                      `target_servers`.`seohostname` AS `target_id`,
                                      `createdby`.`name`             AS `createdby_name`

                            FROM      `forwards`

                            LEFT JOIN `servers` AS `source_servers`
                            ON        `forwards`.`source_id`  = `source_servers`.`id`

                            LEFT JOIN `servers` AS `target_servers`
                            ON        `forwards`.`target_id`  = `target_servers`.`id`

                            LEFT JOIN `users` AS `createdby`
                            ON        `forwards`.`createdby`  = `createdby`.`id`

                            WHERE     `forwards`.`id`         = :id',

                            array(':id' => $forwards_id));

        return $forward;

    }catch(Exception $e){
        throw new bException('forwards_get(): Failed', $e);
    }
}
?>