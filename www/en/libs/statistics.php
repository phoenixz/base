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
function statistics_add($event, $details = ''){
    try{
        sql_query('INSERT INTO `statistics` (`createdby`, `remote`, `event`, `details`)
                   VALUES                   (:createdby , :remote , :event , :details )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':remote'    => (PLATFORM_HTTP ? $_SERVER['REMOTE_ADDR'] : 'CLI'),
                         ':event'     => $event,
                         ':details'   => $details));

    }catch(Exception $e){
        throw new bException('statistics_add(): Failed', $e);
    }
}
?>
