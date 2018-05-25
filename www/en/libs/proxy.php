<?php
/*
 * Proxy library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Capmega <copyright@capmega.com>
 */



/*
 *
 */
function proxy_library_init(){
    try{
        load_libs('servers,csf,route');

    }catch(Exception $e){
        throw new bException('proxy_library_init(): Failed', $e);
    }
}



/*
 * Inserts a new server
 *
 * @param string $root_hostname, first server of the proxy chain
 * @param string $new_hostname, host to be inserted
 * @param string $target_hostname, hostname after or before new server is going to be inserted
 * @param string $location, location for the new server(before  or after $target_hostname)
 */
function proxy_insert($root_hostname, $new_hostname, $target_hostname, $location){
    try{
        $root = proxy_get_server($root_hostname, true);
        $new  = proxy_get_server($new_hostname);

        switch($location){
            case 'before':
                /*
                 * Get next server
                 */
                $prev = null;
                $next = proxy_get_server($target_hostname);

                foreach($root['proxies'] as $index => $proxy){
                    $prev = ($index > 0) ? $root['proxies'][$index - 1]['id'] : null;

                    if($proxy['hostname'] == $target_hostname){
                        break;
                    }

                }

                if($prev === null){
                    $prev = $root;

                }else{
                    $prev = proxy_get_server($prev);
                }

                break;

            case 'after':
                /*
                 * Get previous server
                 */
                $prev = proxy_get_server($target_hostname);
                $next = null;

                foreach($root['proxies'] as $proxy){
                    $next = next($root['proxies']);
                    $next = proxy_get_server($next['id'], false);

                    if($proxy['hostname'] == $target_hostname){
                        break;
                    }

                }

                break;

            default:
                throw new bException(tr('proxy_insert(): Unknown location ":location"', array(':location'=>$location)), 'unknown');
        }

        /*
         * Setting rules for prev to start accepting requests for new server
         */
        csf_allow_rule($prev['hostname'], 'tcp', 'in', 80, $new['ipv4']);
        csf_allow_rule($prev['hostname'], 'tcp', 'in', 80, $new['ipv4']);

        /*
         * Setting rules for new server to start redirecting requests to prev server
         */
        route_add_prerouting ($new['hostname'], 'tcp', 80,   4001, $prev['ipv4']);
        route_add_postrouting($new['hostname'], 'tcp', 4001, $prev['ipv4']);

        /*
         * Setting rules for next sever to start redirecting requests to new server
         */
        route_add_prerouting ($next['hostname'], 'tcp', 80,   4001, $new['ipv4']);
        route_add_postrouting($next['hostname'], 'tcp', 4001, $new['ipv4']);

        /*
         * Update database for new proxy relation
         */
        sql_query('UPDATE `servers` SET `ssh_proxies_id` = ":proxy_id" WHERE `id` = :id', array(':proxy_id' => $new['id']));
        sql_query('UPDATE `servers` SET `ssh_proxies_id` = ":proxy_id" WHERE `id` = :id', array(':proxy_id' => $next['id']));

    }catch(Exception $e){
        throw new bException('proxy_insert(): Failed', $e);
    }
}



/*
 * Removes a server form the proxy chain
 *
 * @param string $root_hostname
 * @param string $remove_hostname
 */
function proxy_remove($root_hostname, $remove_hostname){
    try{
        if($root_hostname == $remove_hostname){
            throw new bException(tr('proxy_remove(): You can not remove the root server for proxy chain'), 'invalid');
        }

        $root = proxy_get_server($root_hostname, true);

        /*
         * Checking if removed hostname is on the proxy chain for the root hostname
         */
        $belongs_to_chain = false;

        foreach($root['proxies'] as $proxy){
            if($proxy['hostname'] == $remove_hostname){
                $belongs_to_chain = true;
                $removed          = servers_get($remove_hostname, false, false);
                break;
            }

        }

        if(!$belongs_to_chain){
            throw new bException(tr('proxy_remove(): Remove hostname does not belong to the chain for ":root_hostname"', array(':root_hostname'=>$root_hostname)), 'invalid');
        }

        /*
         * Getting prev server and next server
         */
        $prev = array();
        $next = array();

        foreach($root['proxies'] as $index=>$proxy){

            if($proxy['hostname'] == $remove_hostname){

                $prev = isset($root['proxies'][$index-1])?servers_get($root['proxies'][$index-1]['id']):$root;
                $next = isset($root['proxies'][$index+1])?servers_get($root['proxies'][$index+1]['id']):array();

                break;
            }

        }

        /*
         * Update rules on prev server to start accepting request for next server
         */
        csf_allow_rule($prev['hostname'], 'tcp', 'in', 80, $next['ipv4']);
        csf_allow_rule($prev['hostname'], 'tcp', 'in', 80, $next['ipv4']);

        /*
         * Update next server to start redirecting request to prev server instead or removed server
         */
        route_add_prerouting ($next['hostname'], 'tcp', 80, 4001, $prev['ipv4']);
        route_add_postrouting($next['hostname'], 'tcp', 4001,     $prev['ipv4']);


        /*
         * Start accepting request from removed server on prev server
         */
        csf_remove_allow_rule($prev['hostname'], 'tcp', 'in', 80,    $removed['ipv4']);
        csf_remove_allow_rule($prev['hostname'], 'tcp', 'in', 40220, $removed['ipv4']);


        /*
         * Update data base with new proxy relations
         */
        sql_query('UPDATE `servers` SET `ssh_proxies_id` = ":proxy_id" WHERE `id` = :id', array(':proxy_id' => $removed['id']));
        sql_query('UPDATE `servers` SET `ssh_proxies_id` = ":proxy_id" WHERE `id` = :id', array(':proxy_id' => $next['id']));

    }catch(Exception $e){
        throw new bException('proxy_remove(): Failed', $e);
    }
}



/*
 * Returns server information for a specified hostname
 *
 * @param string $hostname
 * @param boolean $return_proxies
 * @return array
 */
function proxy_get_server($hostname, $return_proxies = false){
    try{
        $server = servers_get($hostname, false, $return_proxies);

        if(empty($server)){
            throw new bException(tr('proxy_get_server(): No server found for hostname ":hostname"', array(':hostname' => $hostname)), 'not-found');
        }

        return $server;

    }catch(Exception $e){
        throw new bException('proxy_get_server(): Failed', $e);
    }
}
?>