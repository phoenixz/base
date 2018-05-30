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
                if($root_hostname == $target_hostname){
                    throw new bException(tr('proxy_insert(): You can not insert a new server before the root chain'), 'unknown');
                }

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
         * If previous proxy exists
         */
        if($prev){

            /*
            * We have the previous server, the next server, and the insert server
            */
            $forwards = forwards_get_from_ip($prev['ipv4']);

           /*
            * Setting forwards for inserted server
            */
           $forwards_insert = array();

           if($forwards){
               foreach($forwards as $id => $forward){
                   /*
                    * For source port, generate a random number
                    */
                   $port = mt_rand(1025, 65535);

                   $forwards_insert[$id] = array('servers_id'  => $insert['id'],
                                                 'source_id'   => $insert['id'],
                                                 'source_ip'   => $insert['ipv4'],
                                                 'source_port' => $port,
                                                 'target_id'   => $forward['target_id'],
                                                 'target_ip'   => $forward['target_ip'],
                                                 'protocol'    => $forward['protocol'],
                                                 'description' => $forward['description'],
                                                 'target_port' => $forward['target_port']);

                   forwards_apply_rule($forwards_insert[$id]);

               }

               foreach($forwards as $id => $forward){
                   $old_forward = $forward;

                   /*
                    * Applying new rule
                    */
                   $forwards[$id]['target_id']   = $insert['id'];
                   $forwards[$id]['target_ip']   = $insert['ipv4'];
                   $forwards[$id]['target_port'] = $forwards_insert[$id]['source_port'];

                   log_console('Applying rule for server '.$insert['hostname'], 'white');
                   forwards_apply_rule($forwards[$id]);

                   /*
                    * Deleting rule
                    */
                   log_console('Removing rule for server', 'white');
                   forwards_delete_apply($old_forward);
               }

               /*
                * Delete from database old rules
                */
               sql_query('DELETE from `forwards` where source_ip = :source_ip', array(':source_ip' => $prev['ipv4']));

               /*
                * Insert new rules for prev server
                */
               if($forwards){
                   foreach($forwards as $id => $forward){
                       $forward['apply'] = false;
                       forwards_insert($forward);

                   }
               }

               /*
                * Update new relation for proxies
                */
               sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$next['id'],':proxy_id'   => $insert['id']));
               sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$insert['id'],':proxy_id' => $prev['id']));
           }

        } else{
            /*
             * Domain must be redirected to new inserted server, for this case
             * we only need to apply redirected rules on inserted server
             * we don't know which protocols must be redirected so we apply only one http
             */
            $forwards_insert[] = array('servers_id'  => $insert['id'],
                                       'source_id'   => $insert['id'],
                                       'source_ip'   => $insert['ipv4'],
                                       'source_port' => 80,
                                       'target_id'   => $next['id'],
                                       'target_ip'   => $next['ipv4'],
                                       'protocol'    => 'http',
                                       'target_port' => 80,
                                       'description' => 'New server at the front of everything');

            forwards_apply_rule($forwards_insert[0]);

            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$next['id'],':proxy_id' => $insert['id']));
        }

        /*
         * Insert new rules
         */
        if($forwards_insert){
            foreach($forwards_insert as $forward){

                $forward['apply'] = false;
                forwards_insert($forward);
            }
        }


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

        $forwards_insert = array();

        if($prev){
            /*
             * if prev exists we must redirect traffic to next server
             */
            $remove_forwards = forwards_get_from_ip($removed['ipv4']);
            //if($remove_forwards){
            //    foreach($forwards as $id => $forward){
            //        $forwards_insert[$id] = array('servers_id'  => $prev['id'],
            //                                      'source_id'   => $prev['id'],
            //                                      'source_ip'   => $prev['ipv4'],
            //                                      'source_port' => $prev[''],
            //                                      'target_id'   => $forward['target_id'],
            //                                      'target_ip'   => $forward['target_ip'],
            //                                      'target_port' => $forward['target_port']);
            //
            //    }
            //}

        }

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