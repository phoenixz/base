<?php
/*
 * Services library
 *
 * This library manages what services run on what servers that are registered in the servers library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package services
 * @see package servers
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
 * @package services
 *
 * @return void
 */
function services_library_init(){
    try{
        load_libs('servers');
        load_config('services');

    }catch(Exception $e){
        throw new bException('services_library_init(): Failed', $e);
    }
}



/*
 * Scan what services are ran by the specified server, and register it in the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param mixed $server:
 * @return natural The amount of scanned servers
 */
function services_scan($server = null){
    try{
        if(!$server){
            /*
             * Scan ALL servers
             */
            $domains = sql_query('SELECT `domain` FROM `servers` WHERE `status` IS NULL');

            while($domain = sql_fetch($domains, true)){
                $count++;
                services_scan($domain);
            }

            return $count++;
        }

        /*
         * Scan the server
         */
        $server   = servers_get($server);
        $services = services_list();

        foreach($services as $service){
            $results = servers_exec();
        }


        services_update_server($server, $services);
        return 1;

    }catch(Exception $e){
        throw new bException('services_scan(): Failed', $e);
    }
}



/*
 * Validate the specified service
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $service
 * @return params The specified service array
 */
function services_validate($service){
    try{

    }catch(Exception $e){
        throw new bException('services_validate(): Failed', $e);
    }
}



/*
 * Get and return all database information for the specified service
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $service
 * @return params The specified service array
 */
function services_get($service){
    try{

    }catch(Exception $e){
        throw new bException('services_get(): Failed', $e);
    }
}



/*
 * Insert a new service in the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $service
 * @return params The specified service array
 */
function services_insert($service){
    try{

    }catch(Exception $e){
        throw new bException('services_insert(): Failed', $e);
    }
}



/*
 * Update an existing service in the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $service
 * @return params The specified service array
 */
function services_update($service){
    try{

    }catch(Exception $e){
        throw new bException('services_update(): Failed', $e);
    }
}



/*
 * Set the specified services for the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $server
 * @param array $services
 * @return natural The amount of services set for the specified server
 */
function services_update_server($server, $services){
    try{

    }catch(Exception $e){
        throw new bException('services_update_server(): Failed', $e);
    }
}



/*
 * Clear all services for the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $server
 * @return natural The amount of services that were cleared for the specified server
 */
function services_clear($server){
    try{

    }catch(Exception $e){
        throw new bException('services_clear(): Failed', $e);
    }
}



/*
 * Add the specified service for the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package services
 *
 * @param params $server
 * @param array $services
 * @return natural The database table insert id for the specified server / service record
 */
function services_add($server, $service){
    try{

    }catch(Exception $e){
        throw new bException('services_add(): Failed', $e);
    }
}



/*
 * Return HTML for a services select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available services
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package services
 * @see html_select()
 *
 * @param array $params The parameters required
 * @paramkey $params name
 * @paramkey $params class
 * @paramkey $params extra
 * @paramkey $params tabindex
 * @paramkey $params empty
 * @paramkey $params none
 * @paramkey $params selected
 * @paramkey $params parents_id
 * @paramkey $params status
 * @paramkey $params orderby
 * @paramkey $params resource
 * @return string HTML for a services select box within the specified parameters
 */
function services_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'    , 'seoservice');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No services available'));
        array_default($params, 'none'    , tr('Select a service'));
        array_default($params, 'tabindex', 0);
        array_default($params, 'extra'   , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby' , '`domain`');

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status']).' :status ';
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seoname`, `name` FROM `services` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('services_select(): Failed', $e);
    }
}
?>
