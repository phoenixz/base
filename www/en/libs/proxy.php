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
        load_libs('servers,forwards');

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
function proxy_insert($root_hostname, $insert_hostname, $target_hostname, $location){
    try{
        $root   = proxy_get_server($root_hostname, true);
        $insert = proxy_get_server($insert_hostname);

        /*
         * Checkcing if new insert server is already on the chain
         */
        if($root['proxies']){
           foreach($root['proxies'] as $proxy){
                if(strcasecmp($proxy['hostname'], $insert_hostname) == 0){
                    throw new bException(tr('proxy_insert(): Insert hostname is already on the chain for ":roothostname"', array(':roothostname'=>$root_hostname)), 'invalid');
                }
           }
        }

        /*
         * Identify the next and previous servers in this chain
         */
        switch($location){
            case 'before':
                /*
                 * Get next server
                 */
                $next = null;
                $prev = proxy_get_server($target_hostname);

                foreach($root['proxies'] as $index => $proxy){
                    $next = ($index > 0) ? $root['proxies'][$index - 1]['id'] : null;

                    if($proxy['hostname'] == $target_hostname){
                        break;
                    }

                }

                if($next === null){
                    $next = $root;

                }else{
                    $next = proxy_get_server($next);
                }

                break;

            case 'after':
                /*
                 * Get previous server
                 */
                $next = proxy_get_server($target_hostname);
                $prev = null;

                foreach($root['proxies'] as $proxy){
                    $prev = next($root['proxies']);
                    $prev = proxy_get_server($prev['id'], false);

                    if($proxy['hostname'] == $target_hostname){
                        break;
                    }

                }

                break;

            default:
                throw new bException(tr('proxy_insert(): Unknown location ":location"', array(':location'=>$location)), 'unknown');
        }

        /*
         * We have the previous server, the next server, and the insert server
         */

        /*
         * Step 1: Configure target server to receive traffic for the specified
         * protocol from the insert server (must be on same port as source
         * server)
         */
        log_console('Configuring target server '.$insert['hostname'].' to receive traffic from '.$prev['hostname'],'green');
        forwards_accept_traffic($next, $insert, $prev);

        /*
         * Step 2: Configure insert server to send out the specified protocol
         * from the target_port on source to the port of the next server
         */
        log_console('Configuring insert server '.$insert['hostname'].' to send out traffic to '.$next['hostname'],'white');
        $forwards = forwards_list($prev['id']);

        if($forwards){
            /*
             * prev server could have several protocols redirecting, so
             * we do not assigne the same port for different protocols
             */
            $assigned_ports = array();

            foreach($forwards as $forward){
                $port = mt_rand(1025, 65535);
                while(in_array($port, $assigned_ports)){
                    $port = mt_rand(1025, 65535);
                }

                $forward['servers_id']  = $insert['id'];
                $forward['source_ip']   = $insert['ipv4'];
                $forward['source_port'] = $forward['target_port'];
                $forward['source_id']   = $forward['servers_id'];
                $forward['target_ip']   = $next['ipv4'];
                $forward['target_port'] = $port;
                $forward['target_id']   = $next['id'];
                $forward['protocol']    = $forward['protocol'];
                $forward['description'] = $forward['description'].'. Adding new server';

                forwards_insert($forward);
                $assigned_ports[] = $port;
            }
        }
        /*
         * Step 3: Apply two iptable rules
         * A Route all traffic for specified protocol to server 2
         * B Stop routing all traffic for specified protocol to target server
         */
        log_console('Configuring prev server to send out traffic to  '.$insert['hostname'].' and removing routing to prev server '.$prev['hostname'], 'yellow');
        $prev_forwards = forwards_list($prev['id']);

        if($prev_forwards){
            foreach($prev_forwards as $id=> $forward){

                forwards_delete_apply($forward);

                $forward['target_ip'] = $insert['ipv4'];

                forwards_apply_rule($forward);

                $forward['apply'] = false;

                sql_query('UPDATE `forwards` SET `target_ip` = :target_ip WHERE `id` = :id', array(':id'=>$id,':target_ip' => $insert['ipv4']));
            }
        }

        /*
         * Update database for new proxy relation
         */
        sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$prev['id'],':proxy_id' => $insert['id']));

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

        foreach($root['proxies'] as $index => $proxy){
            if($proxy['hostname'] == $remove_hostname){
                $prev = (isset($root['proxies'][$index - 1]) ? servers_get($root['proxies'][$index - 1]['id']) : $root);
                $next = (isset($root['proxies'][$index + 1]) ? servers_get($root['proxies'][$index + 1]['id']) : array());
                break;
            }
        }

show($next);
show($removed);
show($prev);

die;

        /*
         * Step 1: Prepare next server to receive traffic from source server for
         * specified source protocol
         */
        forwards_apply_server($next);

        /*
         * Step 2: Apply two iptable rules at once on the source server
         * A Route all traffic for specified protocol to source server
         * B Stop routing all traffic for specified protocol to remove server
         */


        /*
         * Step 3: Cleanup. Remove all our forwarding rules from remove server
         */


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