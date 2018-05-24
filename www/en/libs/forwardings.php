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
 *
 */
function forwardings_insert(){
    try{

    }catch(Exception $e){
        throw new bException('forwardings_insert(): Failed', $e);
    }
}



/*
 *
 */
function forwardings_delete(){
    try{

    }catch(Exception $e){
        throw new bException('forwardings_delete(): Failed', $e);
    }
}



/*
 *
 */
function forwardings_update(){
    try{

    }catch(Exception $e){
        throw new bException('forwardings_update(): Failed', $e);
    }
}



/*
 * Validates information in order to insert or update record on database
 *
 * @param array $forwarding
 * @return array
 */
function forwardings_validate($forwarding){
    try{
        load_libs('validate');
        array_params($forwarding);

        $v = new validate_form($forwarding, 'source_ip, source_port, target_ip, target_port');
        $v->isNotEmpty($forwarding['source_ip'],   tr('forwardings_validate(): Please specifiy a source ip'));
        $v->isNotEmpty($forwarding['source_port'], tr('forwardings_validate(): Please specifiy a source port'));

        if(!is_natural($forwarding['source_port']) or ($forwarding['source_port'] > 65535)){
            $v->setError(tr('forwardings_validate(): Please specify a valid port for source port field'));
        }

        if(filter_var($forwarding['source_ip'], FILTER_VALIDATE_IP) === false){
            $v->setError(tr('forwardings_validate(): Please specify a valid ip for source ip field'));
        }

        if($forwarding['source_id']){
            $forwarding['source_id'] = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $forwarding['source_id']), true);

            if(!$forwarding['source_id']){
                $v->setError(tr('forwardings_validate(): Specified proxy ":source" does not exist', array(':source' => $forwarding['source_id'])));
            }
        }

        if($forwarding['target_id']){
            $forwarding['target_id'] = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $forwarding['target_id']), true);

            if(!$forwarding['target_id']){
                $v->setError(tr('forwardings_validate(): Specified proxy ":source" does not exist', array(':source' => $forwarding['target_id'])));
            }
        }


        if(!empty($forwarding['target_port']) and (!is_natural($forwarding['target_port']) or ($forwarding['target_port'] > 65535))){
            $v->setError(tr('forwardings_validate(): Please specify a valid port for target port field'));
        }

        if(!empty($forwarding['target_ip']) and filter_var($forwarding['target_ip'], FILTER_VALIDATE_IP) === false){
            $v->setError(tr('forwardings_validate(): Please specify a valid ip for target ip field'));
        }

        if($forwarding['protocol']){

            switch($forwarding['protocol']){
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

                default:
                    $v->setError(tr('forwardings_validate(): Please specify a protocol'));
            }
        }

        $v->isValid();

        return $forwarding;

    }catch(Exception $e){
        throw new bException('forwardings_validate(): Failed', $e);
    }
}



/*
 * Returns a record from the database
 *
 * @param integer $forwarding, id on database to get the information from
 * @return array
 */
function forwardings_get($forwarding){
    try{
        if(empty($forwarding) or !is_numeric($forwarding)){
            throw new bException(tr('forwardings_get(): No forwarding specified'), 'not-specified');
        }

        $forwarding = sql_get('SELECT    `forwardings`.`id`,
                                         `forwardings`.`createdby`,
                                         `forwardings`.`source_ip`,
                                         `forwardings`.`source_port`,
                                         `forwardings`.`target_ip`,
                                         `forwardings`.`target_port`,
                                         `forwardings`.`protocol`,
                                         `forwardings`.`description`,

                                         `source_servers`.`seohostname` AS `source_id`,

                                         `target_servers`.`seohostname` AS `target_id`,

                                         `createdby`.`name` AS `createdby_name`

                            FROM      `forwardings`

                            LEFT JOIN `servers` AS `source_servers`
                            ON        `forwardings`.`source_id` = `source_servers`.`id`

                            LEFT JOIN `servers` AS `target_servers`
                            ON        `forwardings`.`target_id` = `target_servers`.`id`

                            LEFT JOIN `users` AS `createdby`
                            ON        `forwardings`.`createdby`  = `createdby`.`id`

                            WHERE     `forwardings`.`id`         = :forwarding',

                            array(':forwarding' => $forwarding));

        return $forwarding;

    }catch(Exception $e){
        throw new bException('forwardings_get(): Failed', $e);
    }
}



/*
 *
 */
function forwardings_apply(){
    try{

    }catch(Exception $e){
        throw new bException('forwardings_apply(): Failed', $e);
    }
}
?>