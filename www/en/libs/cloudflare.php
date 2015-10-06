<?php
/*
 * Cloud Flare library
 *
 * This library contains all functions to communicate easily with the Cloud Flare service
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */

load_config('cloudflare');
load_libs('ext/cloudflare');

/*
*
*/
function cf_init(){
    try{
        global $_CONFIG;

        if(!empty($GLOBALS['cf_connector'])){
            return null;
        }

        $cf = new cloudflare_api($_CONFIG['cloudflare']['email'], $_CONFIG['cloudflare']['API-key']);
        $GLOBALS['cf_connector'] = $cf;

    }catch(Exception $e){
        throw new bException('cf_init(): Failed', $e);
    }
}



/*
 *  Returns an associative array  which elements are : domain => zone_identifier
 */
function cf_zone_list(){
    try{
        cf_init();
        $response = $GLOBALS['cf_connector']->zone_load_multi();

        if($response->result != 'success'){
            throw new bException('cf_zone_list(): Response from CloudFlare was unsuccessfull');
        }

        $zones = array();

        foreach ($response->response->zones->objs as $zone) {
            $zones[$zone->zone_name] = $zone->zone_id;
        }

        return $zones;

    } catch(Exception $e){
        throw new bException('cf_zone_list(): Failed', $e);
    }
}

/*
 *   Adds an IP to the whitelist
 *   Adding an IP to the whitelist automatically removes it from blacklist
 *   Notice that domain support is not yet implemented
 */
function cf_whitelist($ip, $domain=null){
    try{
        cf_init();
        $response = $GLOBALS['cf_connector']->wl($ip);

        if($response->result != 'success'){
            throw new bException('cf_whitelist(): Response from CloudFlare was unsuccessfull');
        }

    } catch(Exception $e){
        throw new bException('cf_whitelist(): Failed', $e);
    }
}



/*
 *   Adds an IP to the blacklist
 *   Adding an IP to the blacklist automatically removes it from whitelist
 *   Notice that domain support is not yet implemented
 */
function cf_blacklist($ip, $domain=null){
    try{
        cf_init();
        $response = $GLOBALS['cf_connector']->ban($ip);

        if($response->result != 'success'){
            throw new bException('cf_blacklist(): Response from CloudFlare was unsuccessfull');
        }

    } catch(Exception $e){
        throw new bException('cf_blacklist(): Failed', $e);
    }
}



/*
 *   Removes an IP from whitelist or from blacklist depending on where it is located
 *   Notice that domain support is not yet implemented
 */
function cf_unwhitelist($ip, $domain=null){
    try{
        cf_init();
        $response = $GLOBALS['cf_connector']->nul($ip);

        if($response->result != 'success'){
            throw new bException('cf_unwhitelist(): Response from CloudFlare was unsuccessfull');
        }

    } catch(Exception $e){
        throw new bException('cf_unwhitelist(): Failed', $e);
    }
}



/*
 *   Removes an IP from whitelist or from blacklist depending on where it is located
 *   Notice that domain support is not yet implemented
 */
function cf_unblacklist($ip, $domain=null){
    try{
        cf_init();
        $response = $GLOBALS['cf_connector']->nul($ip);

        if($response->result != 'success'){
            throw new bException('cf_unblacklist(): Response from CloudFlare was unsuccessfull');
        }

    } catch(Exception $e){
        throw new bException('cf_unblacklist(): Failed', $e);
    }
}



/*
 *
 */
function cf_clear_cache($domain){
    try{
        cf_init();
        $response = $GLOBALS['cf_connector']->fpurge_ts($domain);

        if($response->result != 'success'){
            throw new bException('cf_clear_cache(): Response from CloudFlare was unsuccessfull');
        }
    } catch(Exception $e){
        throw new bException('cf_clear_cache(): Failed', $e);
    }
}
?>
