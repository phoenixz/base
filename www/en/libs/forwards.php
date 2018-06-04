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
        load_libs('iptables');

    }catch(Exception $e){
        throw new bException('forwards_library_init(): Failed', $e);
    }
}



/*
 * Apply all forwarding rules for the specified server
 *
 * @param mixed $server
 * @return void
 */
function forwards_apply_server($server){
    try{
        $forwards = forwards_list($server);

        if($forwards){
            foreach($forwards as $forward){
                forwards_apply_rule($forward);
            }
        }

    }catch(Exception $e){
        throw new bException('forwards_apply_server(): Failed', $e);
    }
}



/*
 * Removes all forwarding rules for the specified server
 *
 * @param mixed $server
 * @return void
 */
function forwards_remove_server($server){
    try{
        $forwards = forwards_list($server);

        if($forwards){
            foreach($forwards as $forward){
                forwards_delete($forward);
            }
        }

    }catch(Exception $e){
        throw new bException('forwards_remove_server(): Failed', $e);
    }
}



/*
 * Adds a new rule on iptables
 *
 * @param array $forward
 * $return void
 */
function forwards_apply_rule($forward){
    try{
        if($forward['protocol'] != 'ssh'){
            iptables_set_forward(IPTABLES_BUFFER);
            iptables_set_prerouting(IPTABLES_BUFFER,         'tcp', $forward['source_port'], $forward['target_port'], $forward['target_ip']);
            iptables_set_postrouting($forward['servers_id'], 'tcp', $forward['target_port'], $forward['source_ip'],   $forward['target_ip']);
        }

        if($forward['target_id']){
            /*
             * Set rules on target server to start acceting the request from source server
             */
             forwards_only_accept_traffic($forward);
        }

    }catch(Exception $e){
        iptables_exec(IPTABLES_CLEAR);
        throw new bException('forwards_apply_rule(): Failed', $e);
    }
}



/*
 * Checks if forwarding rule is already set up on the host
 *
 * @param array
 * @return boolean
 */
function forwards_exists($forward){
    try{
        /*
         * Checking if prerouting exist
         */
        $result = servers_exec($forward['servers_id'], 'if sudo iptables -t nat -L -n -v|grep "DNAT.*tcp[[:space:]]dpt:'.$forward['source_port'].'[[:space:]]to:"; then echo 1; else echo 0; fi');

        if($result[0]){
            return true;
        }

        return false;

    }catch(Exception $e){
        throw new bException('forwards_exists(): Failed', $e);
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

        sql_query('INSERT INTO `forwards` (`createdby`, `servers_id`, `source_ip`, `source_port`, `source_id`, `target_ip`, `target_port`, `target_id`, `protocol`, `description`)
                   VALUES                 (:createdby ,  :servers_id, :source_ip , :source_port , :source_id , :target_ip , :target_port , :target_id , :protocol , :description )',

                       array(':createdby'   => $createdby,
                             ':servers_id'  => $forward['servers_id'],
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
            forwards_apply_rule($forward);
        }

        return $forward_id;

    }catch(Exception $e){
        throw new bException('forwards_insert(): Failed', $e);
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
        array_ensure($forward , '');
        array_default($forward, 'apply', true);

        sql_query('DELETE FROM `forwards` WHERE `id` = :id', array(':id' => $forward['id']));

        if($forward['apply']){
            forwards_delete_apply($forward);
        }

    }catch(Exception $e){
        throw new bException('forwards_delete(): Failed', $e);
    }
}



/*
 * Removes rules for a deleted forwarding record on database
 *
 * @param array $forward
 * @return void
 */
function forwards_delete_apply($forward){
    try{
        if($forward['protocol'] != 'ssh'){
            /*
             * Removing forwarding
             */
            $exists = iptables_prerouting_exists($forward['id'], $forward['source_port'], $forward['target_port'], $forward['target_ip']);
            if($exists){
                iptables_set_prerouting (IPTABLES_BUFFER,        'tcp', $forward['source_port'], $forward['target_port'], $forward['target_ip'], 'removed');
                iptables_set_postrouting($forward['servers_id'], 'tcp', $forward['target_port'], $forward['source_ip'],   $forward['target_ip'], 'removed');

            }

        }

        if($forward['target_id']){
            /*
             * Stop accepting traffic
             */
            iptables_stop_accepting_traffic($forward['target_id'], $forward['source_ip'], $forward['target_port'], 'tcp');
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
        array_ensure($forward , '');
        array_default($forward, 'apply', true);

        $forward     = forwards_validate($forward);

        $old_forward = sql_get('SELECT `id`,
                                       `servers_id`,
                                       `source_ip`,
                                       `source_port`,
                                       `source_id`,
                                       `target_ip`,
                                       `target_port`,
                                       `target_id`,
                                       `protocol`

                                FROM   `forwards`

                                WHERE  `id` = :id',

                                array(':id' => $forward['id']));

        sql_query('UPDATE `forwards`

                   SET    `modifiedby`  = :modifiedby,
                          `modifiedon`  = NOW(),
                          `servers_id`  = :servers_id,
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
                         ':servers_id'  => $forward['servers_id'],
                         ':source_ip'   => $forward['source_ip'],
                         ':source_port' => $forward['source_port'],
                         ':source_id'   => $forward['source_id'],
                         ':target_ip'   => $forward['target_ip'],
                         ':target_port' => $forward['target_port'],
                         ':target_id'   => $forward['target_id'],
                         ':protocol'    => $forward['protocol'],
                         ':description' => $forward['description']));

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
        /*
         * Remove old rule
         */
        forwards_delete_apply($old_forward);

        /*
         * Add new rule
         */
        forwards_apply_rule($forward);

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

        $v = new validate_form($forward, 'source_ip,source_port,target_ip,target_port,servers_id');
        $v->isNotEmpty($forward['servers_id'], tr('Please specifiy a server'));
        $v->isNotEmpty($forward['source_ip'], tr('Please specifiy a source ip'));
        $v->isNotEmpty($forward['source_port'], tr('Please specifiy a source port'));

        if(!is_natural($forward['source_port']) or ($forward['source_port'] > 65535)){
            $v->setError(tr('Please specify a valid port for source port field'));
        }

        $v->isFilter($forward['source_ip'], FILTER_VALIDATE_IP, tr('Please specify a valid IP address for source IP field'));

        if($forward['servers_id']){

            if(is_natural($forward['servers_id'])){
                $exists = sql_get('SELECT `id` FROM `servers` WHERE `id` = :id AND `status` IS NULL', array(':id' => $forward['servers_id']), true);

            }else{
                $exists = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $forward['servers_id']), true);
            }

            if(!$exists){
                $v->setError(tr('Specified proxy ":source" does not exist', array(':source' => $forward['servers_id'])));

            }else{
                $forward['servers_id'] = $exists;

            }
        }

        if($forward['source_id']){
            if(is_natural($forward['source_id'])){
                $exists = sql_get('SELECT `id` FROM `servers` WHERE `id` = :id AND `status` IS NULL', array(':id' => $forward['source_id']), true);

            }else{
                $exists = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $forward['source_id']), true);
            }

            if(!$exists){
                $v->setError(tr('Specified proxy ":source" does not exist', array(':source' => $forward['source_id'])));

            }else{
                $forward['source_id'] = $exists;

            }
        }

        if($forward['target_id']){
            if(is_natural($forward['target_id'])){
                $exists = sql_get('SELECT `id` FROM `servers` WHERE `id` = :id AND `status` IS NULL', array(':id' => $forward['target_id']), true);

            }else{
                $exists = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $forward['target_id']), true);
            }

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
                                      `forwards`.`servers_id`,
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

                            WHERE     `forwards`.`id`         = :id
                            AND       `forwards`.`status` IS NULL',

                            array(':id' => $forwards_id));

        return $forward;

    }catch(Exception $e){
        throw new bException('forwards_get(): Failed', $e);
    }
}



/*
 * Returns a list of forwards programmed for the specified $server
 *
 * @param mixed server Either servers_id, or hostname of specified server
 * @return array
 */
function forwards_list($server){
    try{
        if(!is_numeric($server)){
            if(!is_string($server)){
                throw new bException(tr('forwards_list(): Server ":server" is not valid. Must be an id or a hostname.'), 'invalid');
            }

            $server = servers_get($server, false, false);
            $server = $server['id'];
        }

        /*
         * From here, $server contains the servers_id
         */

        $forwards = sql_list('SELECT `id`,
                                     `servers_id`,
                                     `source_id`,
                                     `source_ip`,
                                     `source_port`,
                                     `target_id`,
                                     `target_ip`,
                                     `target_port`,
                                     `protocol`,
                                     `description`

                              FROM   `forwards`

                              WHERE  `servers_id` = :servers_id
                              AND    `status` IS NULL',

                              array(':servers_id' => $server),

                              true);

        return $forwards;

    }catch(Exception $e){
        throw new bException('forwards_list(): Failed', $e);
    }
}



/*
 * For ssh we only need to accept traffic from a specified ip, do not forward
 *
 * @param array $forward
 * @return void
 */
function forwards_only_accept_traffic($forward){
    try{
        /*
         * Accept traffic from source ip to target port on target_ip
         */
        iptables_accept_traffic($forward['target_id'], $forward['source_ip'], $forward['target_port'], 'tcp');

    }catch(Exception $e){
        throw new bException('forwards_only_accept_traffic(): Failed', $e);
    }
}



/*
 * Removes forward rules from database and also deletes them from server
 *
 * @param array $forwards, array of forward rules
 * @return void
 */
function forwards_delete_list($forwards, $apply = true){
    try{
        if(empty($forwards)){
            throw new bException(tr('forwards_delete_list(): No forwards specified'), 'not-specified');
        }

        foreach($forwards as $forward){
            $forward['apply'] = $apply;
            forwards_delete($forward);
        }

    }catch(Exception $e){
        throw new bException('forwards_delete_list(): Failed', $e);
    }
}



/*
 * Flush iptables rules and nat rules
 *
 * @param meixed, server id or hostname for specified server
 * @return void
 */
function forwards_destroy($server){
    try{
        iptables_flush_all(IPTABLES_BUFFER);
        iptables_clean_chain_nat($server);
    }catch(Exception $e){
        throw new bException('forwards_destroy(): Failed', $e);
    }
}
?>