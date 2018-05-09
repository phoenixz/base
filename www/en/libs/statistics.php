<?php
/*
 * Statistics library
 *
 * This library is a generic statistics gathering toolkit library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Add a new statistical item
 */
function statistics_add($params){
    try{
        array_params($params);
        array_default($params, 'event'    , '');
        array_default($params, 'details'  , '');
        array_default($params, 'resource1', null);
        array_default($params, 'resource2', null);
        array_default($params, 'unique'   , false);

        if(empty($params['event'])){
            throw new bException(tr('statistics_add(): No event specified'), 'not-specified');
        }

        if(empty($params['details'])){
            throw new bException(tr('statistics_add(): No details specified'), 'not-specified');
        }

        if(empty($params['resource1'])){
            throw new bException(tr('statistics_add(): No resource1 specified'), 'not-specified');
        }

        if($params['resource2'] and empty($params['resource1'])){
            throw new bException(tr('statistics_add(): No resource2 specified without resource1'), 'not-specified');
        }

        if(!is_natural($params['resource1'], 1, true)){
            throw new bException(tr('statistics_add(): Invalid resource1 specified, please ensure it is a natural number'), 'invalid');
        }

        if(!empty($params['resource2']) and !is_natural($params['resource2'], 1, true)){
            throw new bException(tr('statistics_add(): Invalid resource2 specified, please ensure it is a natural number'), 'invalid');
        }

        sql_query('INSERT INTO `statistics` (`createdby`, `remote`, `event`, `details`, `resource1`, `resource2`)
                   VALUES                   (:createdby , :remote , :event , :details , :resource1 , :resource2 )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':remote'    => (PLATFORM_HTTP ? $_SERVER['REMOTE_ADDR'] : 'CLI'),
                         ':event'     => $params['event'],
                         ':details'   => $params['details'],
                         ':resource1' => $params['resource1'],
                         ':resource2' => $params['resource2']));

        return sql_insert_id();

    }catch(Exception $e){
        throw new bException('statistics_add(): Failed', $e);
    }
}
?>