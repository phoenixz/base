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



function mysql_master_replication_setup(){
    try{

    }catch(Exception $e){
        throw new bException(tr('mysql_master_replication_setup(): Failed'), $e);
    }
}
?>