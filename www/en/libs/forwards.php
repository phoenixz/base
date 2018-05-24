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
function forwards_insert($forward){
    try{
        $forward = forwards_validate($forward);

        sql_query('INSERT INTO `forwarrds` (``, ``)
                   VALUES                  (: , : )',

                   array());

    }catch(Exception $e){
        throw new bException('forwards_insert(): Failed', $e);
    }
}



/*
 *
 */
function forwards_insert_apply(){
    try{

    }catch(Exception $e){
        throw new bException('forwards_insert_apply(): Failed', $e);
    }
}


/*
 *
 */
function forwards_delete($forward){
    try{
        array_ensure($forward, '');
        array_default($forward, 'apply', true);

        sql_query('DELETE FROM `forwarrds`
                   WHERE  ',

                   array());

        if($forward['apply']){
            forwards_delete_apply($forward);
        }

    }catch(Exception $e){
        throw new bException('forwards_delete(): Failed', $e);
    }
}



/*
 *
 */
function forwards_delete_apply(){
    try{

    }catch(Exception $e){
        throw new bException('forwards_delete_apply(): Failed', $e);
    }
}



/*
 *
 */
function forwards_update($forward){
    try{
        $forward = forwards_validate($forward);

        sql_query('UPDATE `forwarrds`
                   WHERE  ',

                   array());


    }catch(Exception $e){
        throw new bException('forwards_update(): Failed', $e);
    }
}



/*
 *
 */
function forwards_update_apply(){
    try{

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
            }
        }

        if($forward['target_id']){
            $exists = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $forward['target_id']), true);

            if(!$exists){
                $v->setError(tr('Specified proxy ":source" does not exist', array(':source' => $forward['target_id'])));
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
        if(empty($forward)){
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