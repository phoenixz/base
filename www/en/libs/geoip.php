<?php
/*
 * GEOIP library
 *
 * This library contains functions to manage GEO IP detection
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geoip
 */



/*
 * Get the requested data for the specified IP address
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geoip
 * @exception When no IP was specified, and no remote client IP was found either
 * @see geoip_get_country()
 * @see geoip_get_city()
 *
 * @param string $ip The IP to be tested. If no IP is specified, the remote client's IP will be used (if available)
 * @param string $columns
 * @return string The geo data about where this IP is registered, null if not found
 */
function geoip_get($ip = null, $columns = '*'){
    try{
        if($ip === null){
            $ip = isset_get($_SERVER['REMOTE_ADDR']);

            if(!$ip){
                throw new bException(tr('geoip_get_country(): No IP specified and no remote client IP found either'), 'not-available');
            }
        }

        $data = sql_get('SELECT `geoip_locations`.'.$columns.'

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
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geoip
 * @exception When no IP was specified, and no remote client IP was found either
 * @see geoip_get()
 *
 * @param string $ip The IP to be tested. If no IP is specified, the remote client's IP will be used (if available)
 * @return string The country where this IP is registered, null if not found
 */
function geoip_get_country($ip = null){
    try{
        return geoip_get($ip, 'country');

    }catch(Exception $e){
        throw new bException('geoip_get_country(): Failed', $e);
    }
}



/*
 * Return the city for the specified IP address
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geoip
 * @exception When no IP was specified, and no remote client IP was found either
 * @see geoip_get()
 *
 * @param string $ip The IP to be tested. If no IP is specified, the remote client's IP will be used (if available)
 * @return string The city where this IP is registered, null if not found
 */
function geoip_get_city($ip){
    try{
        return geoip_get($ip, 'city');

    }catch(Exception $e){
        throw new bException('geoip_get_city(): Failed', $e);
    }
}



/*
 * Returns true if the specified IP is european
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package geoip
 * @exception When no IP was specified, and no remote client IP was found either
 * @see geoip_get()
 * @see geoip_get_country()
 *
 * @param string $ip The IP to be tested. If no IP is specified, the remote client's IP will be used (if available)
 * @return boolean True if the specified IP is registered to be located in a european country, false otherwise
 */
function geoip_is_european($ip){
    try{
        $country   = geoip_get_country($ip);
        $countries = array('austria',
                           'belgium',
                           'bulgaria',
                           'croatia',
                           'cyprus',
                           'czech republic',
                           'denmark',
                           'estonia',
                           'finland',
                           'france',
                           'germany',
                           'greece',
                           'hungary',
                           'ireland',
                           'italy',
                           'latvia',
                           'lithuania',
                           'luxembourg',
                           'malta',
                           'netherlands',
                           'poland',
                           'portugal',
                           'romania',
                           'slovakia',
                           'slovenia',
                           'spain',
                           'sweden',
                           'united kingdom');

        return in_array($country, $countries);

    }catch(Exception $e){
        throw new bException('geoip_is_european(): Failed', $e);
    }
}
?>
