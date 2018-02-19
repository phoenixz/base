<?php
/*
 * Statistics library
 *
 * This library is a generic statistics gathering toolkit library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
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
            throw new bException(tr('statistics_add(): No resource1 specified, error caused because of resource2 was specified'), 'not-specified');
        }

        sql_query('INSERT INTO `statistics` (`createdby`, `remote`, `event`, `details`, `resource1`, `resource2`)
                   VALUES                   (:createdby , :remote , :event , :details , :resource1 , :resource2 )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':remote'    => (PLATFORM_HTTP ? $_SERVER['REMOTE_ADDR'] : 'CLI'),
                         ':event'     => $event,
                         ':details'   => $details,
                         ':resource1' => $resource1,
                         ':resource2' => $resource2));

    }catch(Exception $e){
        throw new bException('statistics_add(): Failed', $e);
    }
}
?>