<?php
/*
 * dns library
 *
 * This library contains functions to manage a powerdns DNS server
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
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
 * @package dns
 *
 * @return void
 */
function dns_library_init(){
    try{

    }catch(Exception $e){
        throw new bException('dns_library_init(): Failed', $e);
    }
}



/*
 * Add an A record for the specified domain
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package dns
 *
 * @param string $domain
 * @param string $subdomain
 * @param string $ip
 * @param numeric $ttl
 * @return
 */
function dns_add_record_a($domain, $subdomain, $ip, $ttl){
    try{

    }catch(Exception $e){
        throw new bException('dns_add_record_a(): Failed', $e);
    }
}



/*
 * Add an AAAA record for the specified domain
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package dns
 *
 * @param string $domain
 * @param string $subdomain
 * @param string $ip
 * @param numeric $ttl
 * @return
 */
function dns_add_record_aaaa($domain, $subdomain, $ip, $ttl){
    try{

    }catch(Exception $e){
        throw new bException('dns_add_record_aaaa(): Failed', $e);
    }
}



/*
 * Add CNAME record for the specified domain
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package dns
 *
 * @param string $domain
 * @param string $subdomain
 * @param string $cname
 * @param numeric $ttl
 * @return
 */
function dns_add_record_cname($domain, $subdomain, $cname, $ttl){
    try{

    }catch(Exception $e){
        throw new bException('dns_add_record_cname(): Failed', $e);
    }
}
?>
