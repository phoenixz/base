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
 * ..................
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package statistics
 *
 * @param array $params
 * returns array The specified parameters, now validated and clean
 */
function statistics_add($params){
    try{
        $params = statistics_validate($params);

        sql_query('INSERT INTO `statistics` (`createdby`, `remote`, `event`, `details`, `resource1`, `resource2`)
                   VALUES                   (:createdby , :remote , :event , :details , :resource1 , :resource2 )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':remote'    => (PLATFORM_HTTP ? $_SERVER['REMOTE_ADDR'] : 'CLI'),
                         ':event'     => $params['event'],
                         ':subevent'  => $params['event'],
                         ':details'   => $params['details'],
                         ':resource1' => $params['resource1'],
                         ':resource2' => $params['resource2']));

        return sql_insert_id();

    }catch(Exception $e){
        throw new bException('statistics_add(): Failed', $e);
    }
}



/*
 * ..................
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package statistics
 *
 * @param array $params
 * returns array The specified parameters, now validated and clean
 */
function statistics_validate($params){
    try{
        load_libs('validate');
        $v = new validate_form($params, 'event,subevent,details,resource1,resource2');

        $v->isNotEmpty($params['event'], tr('Please specify an event'));
        $v->isNotEmpty($params['details'], tr('Please specify event details'));

        if(empty($params['subevent'])){
            $params['subevent'] = null;
        }

        if(empty($params['resource1'])){
            $params['resource1'] = null;

            if($params['resource2']){
                $v->setError(tr('Resource2 cannot be specified without resource1'));
            }

        }else{
            $v->isNatural($params['resource1'], 1, tr('statistics_add(): Invalid resource1 specified, please ensure it is a natural number'));

            if($params['resource2']){
                $v->isNatural($params['resource2'], 1, tr('statistics_add(): Invalid resource2 specified, please ensure it is a natural number'));
            }
        }

        $v->isValid();

        return $params;

    }catch(Exception $e){
        throw new bException('statistics_validate(): Failed', $e);
    }
}
?>