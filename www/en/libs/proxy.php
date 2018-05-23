<?php
/*
 * Proxy library
 *
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
function proxy_insert($hostname, $operation , $target_host){
    try{
        $new_server    = proxy_get_server($hostname);
        $target_server = proxy_get_server($target_host);
        $proxy_server  = proxy_get_proxy($target_server, $operation);

        if(empty($proxy_server)){
            if($operation == 'after'){
                /*
                 * Set rules to allow request from new server to target server
                 */
                csf_allow_rule($target_server['hostname'], 'tcp', 'in', 80,  $new_server['ipv4']);
                csf_allow_rule($target_server['hostname'], 'tcp', 'in', 40220,  $new_server['ipv4']);

               /*
                * Set redirects to target server from new server
                */
                route_add_prerouting ($new_server['hostname'], 'tcp', 80, 4001, $target_server['ipv4']);
                route_add_postrouting($new_server['hostname'], 'tcp', 4001, $target_server['ipv4']);
            }else{
                /*
                 * Set rules to allow request from new server to target server
                 */
                csf_allow_rule($new_server['hostname'], 'tcp', 'in', 80,  $target_server['ipv4']);
                csf_allow_rule($new_server['hostname'], 'tcp', 'in', 40220,  $target_server['ipv4']);

                /*
                 * Set rules to allow request from new server to target server
                 */
                csf_allow_rule($target_server['hostname'], 'tcp', 'in', 80,  $new_server['ipv4']);
                csf_allow_rule($target_server['hostname'], 'tcp', 'in', 40220,  $new_server['ipv4']);

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
            csf_allow_rule($new_server['hostname'], 'tcp', 'in', 80,  $proxy_server['ipv4']);
            csf_allow_rule($new_server['hostname'], 'tcp', 'in', 40220,  $proxy_server['ipv4']);

            /*
             * Set rules in proxy server to allow requests from new server
             */
            csf_allow_rule($proxy_server['hostname'], 'tcp', 'in', 80,  $new_server['ipv4']);
            csf_allow_rule($proxy_server['hostname'], 'tcp', 'in', 40220,  $new_server['ipv4']);

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
        $server         = proxy_get_server($hostname);
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



/*
 * Returns proxy information for specified server
 * @param array $server, server information provided by servers_get()
 * @return array $next_proxy
 */
function proxy_get_next($server){
    try{
        $next_proxy = array();

        if(isset($server['proxies']) and isset($server['proxies'][0])){
            $next_proxy = $server['proxies'][0];
        }

        return $next_proxy;

    }catch(Exception $e){
        throw new bException('proxy_get_next(): Failed', $e);
    }
}



/*
 * @param integer $servers_id, server's id
 * @see proxy_get_previous()
 */
function proxy_get_previous($servers_id){
    try{
        if(empty($servers_id)){
            throw new bException(tr('proxy_get_previous(): No server id specified'), 'not-specified');
        }

        $query  =  'SELECT    `servers`.`id` AS `server_id`,
                              `servers`.`hostname`,
                              `servers`.`port`,
                              `servers`.`ssh_accounts_id`,
                              `servers`.`ssh_proxies_id`,
                              `ssh_accounts`.`username`,
                              `ssh_accounts`.`ssh_key`

                    FROM      `servers`

                    LEFT JOIN `ssh_accounts`
                    ON        `servers`.`ssh_accounts_id` = `ssh_accounts`.`id`

                    WHERE       `servers`.`ssh_proxies_id` = :servers_id';

        $previous = sql_get($query, array(':servers_id'=>$servers_id));

        return $previous;

    }catch(Exception $e){
        throw new bException('proxy_get_previous(): Failed', $e);
    }
}



/*
 * @param string $hostname
 * @return array $server
 */
function proxy_get_server($hostname){
    try{
        if(empty($hostname)){
            throw new bException(tr('proxy_get_server(): No hostname specified'), 'not-specified');
        }

        $server = servers_get($hostname);
        if(empty($server)){
            throw new bException(tr('proxy_get_server(): No data found for hostname ":hostname"', array(':hostname' => $hostname)), 'not-found');
        }

        return $server;

    }catch(Exception $e){
        throw new bException('proxy_get_server(): Failed', $e);
    }
}



/*
 * Return proxy according to operation type ,if operation type is equal to before
 * then returns previous proxy from target server, otherwise return next proxy for target server.
 * @param array $server, response provided by sergers_get()
 * @param string $operation, operation must be 'before' or 'after'
 * @return array $proxy_server
 */
function proxy_get_proxy($server, $operation){
    try{
        $operation = proxy_validate_operation($operation);

        switch($operation){
            case 'before':
                $proxy_server = proxy_get_previous($server['servers_id']);
                break;

            case 'after':
                $proxy_server = proxy_get_next($server);
                break;

            default:
                throw new bException(tr('proxy_get_proxy(): Unknown operation ":operation" specified', array(':operation' => $operation)), 'unknown');
        }

        return $proxy_server;

    }catch(Exception $e){
        throw new bException('proxy_get_proxy(): Failed', $e);
    }
}



/*
 * @param string $operation, valid operations: before and after
 * @return string $operation
 * @see proxy_validate_operation()
 */
function proxy_validate_operation($operation){
    try{
        if(empty($operation)){
            throw new bException(tr('proxy_validate_operation(): No operation specified'), 'not-specified');
        }

        $operation = strtolower($operation);

        switch($operation){
            case 'before':
                // FALLTHROUGH
            case 'after':
                //valid operations
                break;

            default:
                throw new bException(tr('proxy_validate_operation(): Unknown operation ":operation" specified', array(':operation' => $operation)), 'unknown');
        }

        return $operation;

    }catch(Exception $e){
        throw new bException('proxy_validate_operation(): Failed', $e);
    }
}
?>