<?php
/*
 * Monitors library
 *
 * This library implements the capmega monitoring system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package monitors monitors
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package monitors
 *
 * @return void
 */
function monitors_library_init(){
    try{
        load_config('monitors');


    }catch(Exception $e){
        throw new bException('monitors_library_init(): Failed', $e);
    }
}



/*
 * Validates the specified monitoring host
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package monitors
 *
 * @param
 * @return
 */
function monitors_validate_host($host){
    try{


    }catch(Exception $e){
        throw new bException('monitors_validate_host(): Failed', $e);
    }
}



/*
 * Inserts the specified monitoring host in the database after validation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package monitors
 *
 * @param params $host
 * @params string ip
 * @params string hostname
 * @return The specified host, but validated, with the table id added
 */
function monitors_insert_host($host){
    try{
        $host = monitors_validate_host($host);

        sql_query('INSERT INTO `monitors` (``, ``)
                   VALUES                 (: , : )',

                   array(':' => $host[''],
                         ));

        $host['id'] = sql_insert_id();

        return $host;

    }catch(Exception $e){
        throw new bException('monitors_insert_host(): Failed', $e);
    }
}



/*
 * Updates the specified monitoring host in the database after validation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package monitors
 *
 * @param params $host
 * @params string ip
 * @params string hostname
 * @return The specified host, but validated
 */
function monitors_update_host($host){
    try{
        $host = monitors_validate_host($host);

        sql_query('UPDATE `monitors`

                   SET    ``

                   WHERE  ``',

                   array(':' => $host[''],
                         ));

        return $result;

    }catch(Exception $e){
        throw new bException('monitors_update_host(): Failed', $e);
    }
}



/*
 * Returns a monitor from the database that matches specified IP or hostname
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package monitors
 *
 * @params mixed $host
 * @return params The found host, or null if no host was found
 */
function monitors_get_host($host){
    try{
        if(is_numeric($host)){
            $where   = ' WHERE  `id` = :id ';
            $execute = array(':id' => $host);

        }elseif(is_string($host)){
            $where   = ' WHERE  `ip`       = :ip
                        OR      `hostname` = :hostname';
            $execute = array(':id'       => $host,
                             ':hostname' => $host);

        }else{
            throw new bException(tr(''), 'invalid');
        }

        $result = sql_get('SELECT `id`,
                          `createdby`,
                          `createdon`,
                          `meta_id`,
                          `ip`,

                   FROM   `monitors`'.$where,

                   $execute);

        return $host;

    }catch(Exception $e){
        throw new bException('monitors_get_host(): Failed', $e);
    }
}



/*
 * Update the available network interfaces to the monitoring program on the specified host
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package monitors
 *
 * @params mixed $host
 * @params array $interfaces
 * @return
 */
function monitors_update_interfaces($host, $interfaces){
    try{
        if(!is_array($host)){
            $host = monitors_get_host($host);
        }




    }catch(Exception $e){
        throw new bException('monitors_update_interfaces(): Failed', $e);
    }
}
?>
