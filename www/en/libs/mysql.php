<?php
/*
 * MySQL library
 *
 * This library contains various functions to manage mysql databases and servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 * @copyright Ismael Haro <support@ingiga.com>
 *
 */



/*
 *
 */
function mysql_dump($params){
    try{
        array_ensure($params, 'host,user,pass,ssh_server');

        if($params['ssh_server']){
            /*
             * Execute this over SSH on a remote server
             */
            load_libs('ssh');

        }else{
            /*
             * Execute this directly on localhost
             */

        }

    }catch(Exception $e){
        throw new bException(tr('mysql_dump(): Failed'), $e);
    }
}



/*
 *
 */
function mysql_master_replication_setup($server){
    try{
        load_libs('ssh');
        ssh_exec($server, 'uptime');

    }catch(Exception $e){
        throw new bException(tr('mysql_master_replication_setup(): Failed'), $e);
    }
}
?>