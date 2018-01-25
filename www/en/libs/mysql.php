<?php
/*
 * PDO library
 *
 * This file contains various functions to access databases over PDO
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 * @copyright Ismael Haro <support@ingiga.com>
 *
 */
load_libs('ssh');


function mysql_master_replication_setup($server){
    try{
        ssh_exec($server, 'uptime');

    }catch(Exception $e){
        throw new bException(tr('mysql_master_replication_setup(): Failed'), $e);
    }
}
?>