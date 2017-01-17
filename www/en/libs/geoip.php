<?php
/*
 * GEOIP library
 *
 * This library contains functions to manage GEO IP detection
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Get the requested data for the specified IP address
 */
function geoip_get($ip = null, $column = '*'){
    try{
        if(!$ip){
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $data = sql_get('SELECT `geoip_locations`.'.$column.'

                         FROM   `geoip_blocks`,
                                `geoip_locations`

                         WHERE  INET_ATON(:ip) BETWEEN `geoip_blocks`.`start_ip` AND `geoip_blocks`.`end_ip`
                         AND    `geoip_blocks`.`id` = `geoip_locations`.`id`

                         LIMIT  1',

                         array(':ip' => $ip));

        if(!$data){
            /*
             * No results? Do we actually have geo ip table contents?
             */
            $count = sql_get('SELECT COUNT(*) AS `count` FROM `geoip_locations`');

            if(!$count){
                throw new bException(tr('geoip_get(): geoip_locations table is empty'), 'empty');
            }

            return null;
        }

        if(count($data) == 1){
            return array_shift($data);
        }

        return $data;

    }catch(Exception $e){
        if(!sql_get('SHOW TABLES LIKE "geoip_locations"')){
            throw new bException('geoip_get(): `geoip_locations` table not found, please run the ./scripts/base/importers/geoip script to import the GEO IP data', $e);
        }

        throw new bException('geoip_get(): Failed', $e);
    }
}



/*
 * Return the country for the specified IP address
 */
function geoip_get_country($ip){
    try{
        return geoip_get($ip, 'country');

    }catch(Exception $e){
        throw new bException('geoip_get_country(): Failed', $e);
    }
}



/*
 * Return the city for the specified IP address
 */
function geoip_get_city($ip){
    try{
        return geoip_get($ip, 'city');

    }catch(Exception $e){
        throw new bException('geoip_get_city(): Failed', $e);
    }
}
?>
