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
 *
 */
function proxy_insert($root_hostname, $new_hostname, $target_hostname, $location){
    try{
        $root = servers_get($root_hostname, false, true, false);

        switch($location){
            case 'before':
                /*
                 * Get next server
                 */
                foreach($root['proxies'] as $proxy){
                    if($proxy['hostname'] == $target_hostname){
                        $next = servers_get($proxy['id']);
                        $prev = prev($root['proxies']);
                        break;
                    }
                }

                if(empty($next)){
                    throw new bException(tr(''), 'not-found');
                }

                if(empty($prev)){
                    throw new bException(tr(''), 'not-found');
                }

                break;

            case 'after':
                /*
                 * Get previous server
                 */
                if(){

                }else{
                    foreach($root['proxies'] as $proxy){
                        if($proxy['hostname'] == $target_hostname){
                            $next = servers_get($proxy['id']);
                            break;
                        }
                    }
                }

                if(empty($next)){
                    throw new bException(tr(''), 'not-found');
                }

                /*
                 * Get previous server from proxies list
                 */
                $prev = servers_get($next['proxies_id']);

                if(empty($prev)){
                    throw new bException(tr(''), 'not-found');
                }

                break;

            default:
                throw new bException(tr(), 'unknown');
        }

        $new_server    = proxy_get($current);
        $target_server = proxy_get($target_hostname);
        $proxy_server  = proxy_get($target_server, $operation);

        if(empty($proxy_server)){
            if($operation == 'after'){
                /*
                 * Set rules to allow request from new server to target server
                 */
                csf_allow_rule($target_server['hostname'], 'tcp', 'in', 80   , $new_server['ipv4']);
                csf_allow_rule($target_server['hostname'], 'tcp', 'in', 40220, $new_server['ipv4']);

               /*
                * Set redirects to target server from new server
                */
                route_add_prerouting ($new_server['hostname'], 'tcp', 80, 4001, $target_server['ipv4']);
                route_add_postrouting($new_server['hostname'], 'tcp', 4001, $target_server['ipv4']);

            }else{
                /*
                 * Set rules to allow request from new server to target server
                 */
                csf_allow_rule($new_server['hostname'], 'tcp', 'in', 80   , $target_server['ipv4']);
                csf_allow_rule($new_server['hostname'], 'tcp', 'in', 40220, $target_server['ipv4']);

                /*
                 * Set rules to allow request from new server to target server
                 */
                csf_allow_rule($target_server['hostname'], 'tcp', 'in', 80   , $new_server['ipv4']);
                csf_allow_rule($target_server['hostname'], 'tcp', 'in', 40220, $new_server['ipv4']);

               /*
                * Set redirects to target server from new server
                */
                route_add_prerouting ($target_server['hostname'], 'tcp', 80, 4001, $new_server['ipv4']);
                route_add_postrouting($target_server['hostname'], 'tcp', 4001, $new_server['ipv4']);
            }

        }else{
            /*
             * Set rules in new server to allow requests from proxy server
             */
            csf_allow_rule($new_server['hostname'], 'tcp', 'in', 80   , $proxy_server['ipv4']);
            csf_allow_rule($new_server['hostname'], 'tcp', 'in', 40220, $proxy_server['ipv4']);

            /*
             * Set rules in proxy server to allow requests from new server
             */
            csf_allow_rule($proxy_server['hostname'], 'tcp', 'in', 80   , $new_server['ipv4']);
            csf_allow_rule($proxy_server['hostname'], 'tcp', 'in', 40220, $new_server['ipv4']);

            if($operation == 'after'){
                /*
                 * Set rules to redirect requests from new server to target server
                 */
                route_add_prerouting ($new_server['hostname'], 'tcp', 80, 4001, $target_server['ipv4']);
                route_add_postrouting($new_server['hostname'], 'tcp', 4001, $target_server['ipv4']);

               /*
                * Set rules to redirect requests from proxy server to new server. This will remove redirects to target server from proxy server.
                */
                route_add_prerouting ($proxy_server['hostname'], 'tcp', 80, 4001, $new_server['ipv4']);
                route_add_postrouting($proxy_server['hostname'], 'tcp', 4001, $new_server['ipv4']);

            } else {
                /* When new server is inserted before another server
                 * Set rules to redirect requests from new server to previous proxy for target server
                 */
                route_add_prerouting ($new_server['hostname'], 'tcp', 80, 4001, $proxy_server['ipv4']);
                route_add_postrouting($new_server['hostname'], 'tcp', 4001, $proxy_server['ipv4']);

                /*
                 * Set rules to redirect requests from new target server to new server
                 */
                route_add_prerouting ($target_server['hostname'], 'tcp', 80, 4001, $new_server['ipv4']);
                route_add_postrouting($target_server['hostname'], 'tcp', 4001, $new_server['ipv4']);
            }

            /*
             * Remove rules on target server to stop accepting requests from proxy
             */
            csf_remove_allow_rule($target_server['hostname'], 'tcp', 'in', 80, $proxy_server['ipv4']);
            csf_remove_allow_rule($target_server['hostname'], 'tcp', 'in', 40220, $proxy_server['ipv4']);

        }

    }catch(Exception $e){
        throw new bException('proxy_insert(): Failed', $e);
    }
}



/*
 * @param string $hostname
 */
function proxy_remove($hostname){
    try{
        $server         = proxy_get($hostname);
        $next_proxy     = proxy_get_next($server);
        $previous_proxy = proxy_get_previous($server['servers_id']);

        /*
         * If previous proxy exists and next proxy exist
         * We must update rules on previous to start accepting request from next proxy.
         */

        /*
         * If previous proxy exists and next proxy exist
         * Update rules on next proxy to start accepting request from previous proxy.
         */

        /*
         * If previous proxy exists and next proxy exist
         * Redirect request from next proxy to previous proxy
         */

        /*
         * If previous proxy exists and next proxy exist
         * Remove rules on previous proxy to stop acceting request from removed server
         */

        /*
         * If previous proxy exists and next proxy exist
         * Remove rules on next proxy to stop acceting request from removed server
         */

    }catch(Exception $e){
        throw new bException('proxy_remove(): Failed', $e);
    }
}



///*
// * Returns proxy information for specified server
// * @param array $server, server information provided by servers_get()
// * @return array $next_proxy
// */
//function proxy_get_next($server){
//    try{
//        $next_proxy = array();
//
//        if(isset($server['proxies']) and isset($server['proxies'][0])){
//            $next_proxy = $server['proxies'][0];
//        }
//
//        return $next_proxy;
//
//    }catch(Exception $e){
//        throw new bException('proxy_get_next(): Failed', $e);
//    }
//}
//
//
//
///*
// * @param integer $servers_id, server's id
// * @see proxy_get_previous()
// */
//function proxy_get_previous($servers_id){
//    try{
//        if(empty($servers_id)){
//            throw new bException(tr('proxy_get_previous(): No server id specified'), 'not-specified');
//        }
//
//        $query  =  'SELECT    `servers`.`id` AS `server_id`,
//                              `servers`.`hostname`,
//                              `servers`.`port`,
//                              `servers`.`ssh_accounts_id`,
//                              `servers`.`ssh_proxies_id`,
//                              `ssh_accounts`.`username`,
//                              `ssh_accounts`.`ssh_key`
//
//                    FROM      `servers`
//
//                    LEFT JOIN `ssh_accounts`
//                    ON        `servers`.`ssh_accounts_id` = `ssh_accounts`.`id`
//
//                    WHERE       `servers`.`ssh_proxies_id` = :servers_id';
//
//        $previous = sql_get($query, array(':servers_id'=>$servers_id));
//
//        return $previous;
//
//    }catch(Exception $e){
//        throw new bException('proxy_get_previous(): Failed', $e);
//    }
//}
//
//
//
///*
// * @param string $hostname
// * @return array $server
// */
//function proxy_get_current($hostname){
//    try{
//        if(empty($hostname)){
//            throw new bException(tr('proxy_get_current(): No hostname specified'), 'not-specified');
//        }
//
//        $server = servers_get($hostname);
//
//        if(empty($server)){
//            throw new bException(tr('proxy_get_current(): Specified hostname ":hostname" does not exist', array(':hostname' => $hostname)), 'not-exist');
//        }
//
//        return $server;
//
//    }catch(Exception $e){
//        throw new bException('proxy_get_current(): Failed', $e);
//    }
//}
//
//
//
///*
// * Return proxy according to operation type ,if operation type is equal to before
// * then returns previous proxy from target server, otherwise return next proxy for target server.
// *
// * @param array $server, response provided by sergers_get()
// * @param string $location, operation must be 'before' or 'after'
// * @return array
// */
//function proxy_get($server, $location){
//    try{
//        switch($location){
//            case 'current':
//                $proxy_server = proxy_get_previous($server['servers_id']);
//                break;
//
//            case 'previous':
//                $proxy_server = proxy_get_previous($server['servers_id']);
//                break;
//
//            case 'next':
//                $proxy_server = proxy_get_next($server);
//                break;
//
//            default:
//                throw new bException(tr('proxy_get(): Unknown location ":location" specified', array(':location' => $location)), 'unknown');
//        }
//
//        return $proxy_server;
//
//    }catch(Exception $e){
//        throw new bException('proxy_get(): Failed', $e);
//    }
}
?>