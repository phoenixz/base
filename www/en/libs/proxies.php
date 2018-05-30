<?php
/*
 * Proxies library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Capmega <copyright@capmega.com>
 */



/*
 *
 */
function proxies_library_init(){
    try{
        load_libs('servers,forwards');

    }catch(Exception $e){
        throw new bException('proxy_library_init(): Failed', $e);
    }
}



/**
* @param $root_hostname initial server on chain
* @param $insert_hostname
*/
function proxies_insert($root_hostname, $insert_hostname, $target_hostname, $location){
	try{

		$root     = proxies_get_server($root_hostname, true);
		$insert   = proxies_get_server($insert_hostname, false);
        $on_chain = proxies_validate_on_chain($root['proxies'], $insert_hostname);

		$next   = array();
		$prev   = array();

        list($prev, $next) = proxies_get_prev_next_insert($root_hostname, $target_hostname, $root['proxies'], $location);

        if($prev){
            /*
             * Exist prev and next server
             */
            $random_port  = mt_rand(1025, 65535);

            /*
             * We must apply forwarding rules for prev server on inserted server
             */
            $prev_forwards = forwards_list($prev['id']);
            foreach($prev_forwards as $id => $forward){
                $random_port    = mt_rand(1025, 65535);
                $insert_forward = $forward;


                $insert_forward['apply']      = true;
                $insert_forward['source_ip']  = $insert['ipv4'];
                $insert_forward['source_id']  = $insert['id'];
                $insert_forward['source_port']= $random_port;
                $insert_forward['servers_id'] = $insert['id'];


                /*
                 * Applying rule on inserted server to start sending traffic to next server
                 */
                log_console('Applying forwarding rule on inserted server', 'white');

                forwards_insert($insert_forward);

                /*
                 * Deleting rule on database
                 */
                $forward['id']    = $id;
                $forward['apply'] = false;

                log_console('Deleting rule from database for prev server', 'white');
                forwards_delete($forward);

                /*
                 * Updating target_port for prev server
                 */
                $prev_forwards[$id]['apply']       = true;
                $prev_forwards[$id]['target_port'] = $random_port;
                $prev_forwards[$id]['target_ip']   = $insert['ipv4'];
                $prev_forwards[$id]['target_id']   = $insert['id'];

                /*
                 * Start redirecting traffic from prev server to inserted server
                 */
                log_console('Inserting and applying rule for prev serv', 'white');
                forwards_insert($prev_forwards[$id]);

                /*
                 * Removing forwarding rule on next server
                 */
                log_console('Removing rule on prev serv', 'white');
                /*
                 *this is nor removing rule on prev server
                 */
                $forward['apply'] = true;
                forwards_delete_apply($forward);
            }

            /*
             * Updating proxy chain
             */
            log_console('Updating proxy chain on data base');

            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$root['id'],':proxy_id'   => $insert['id']));
            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$insert['id'],':proxy_id' => $prev['id']));


        } else{
            log_console('Inserted server at the front, no prev proxy', 'white');
        	/*
	        * If prev does not exist, inserted server is going to be at the front of everything.
	        * So we configure inserted server to start sending traffic to next server
	        */
	        $random_port  = mt_rand(1025, 65535);

            $forward      = array(
                'apply'       => true,
				'servers_id'  => $insert['id'],
				'source_port' => 80,
				'source_id'   => $insert['id'],
				'source_ip'   => $insert['ipv4'],
				'target_id'   => $next['id'],
				'target_port' => $random_port,
				'target_ip'   => $next['ipv4'],
				'protocol'    => 'http',
				'description' => 'Added by proxies library');

            /*$exist_forward = forwards_exists($forward);

            if($exist_forward){
                throw new bException(tr('proxies_insert(): Forwarding for source port ":source_port" on host ":host" already exists', array(':source_port' => 80, ':host' => $insert_hostname)), 'invalid');
            }*/


            /*
             * Insert and apply new forwarding rule on inserted server
             */
            log_console('Applying forwarding rule on inserted server', 'white');
			forwards_insert($forward);

            /*
            * Redirect traffic for new random port on next server
            */
			$next_forward = forwards_get_by_protocol_and_sourceip('http', $next['ipv4']);

            $forward = array(
                'apply'       => true,
                'servers_id'  => $next['id'],
                'source_id'   => $next['id'],
				'source_port' => $random_port,
				'source_ip'   => $next['ipv4'],
                'target_id'   => $next_forward['target_id'],
				'target_port' => $next_forward['target_port'],
				'target_ip'   => $next_forward['target_ip'],
                'protocol'    => 'http',
				'description' => 'Added by proxies library');

            log_console('Applying forwarding rule on next server', 'white');
            /*
             * Insert and apply new forwarding rule on next server
             */
            forwards_insert($forward);

            /*
             * Delete previous redirect traffic
             */
            log_console('Removing old forwarding rule on next server', 'white');
            forwards_delete($next_forward);

            /*
             * Update relation of proxies
             */
            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$next['id'],':proxy_id'   => $insert['id']));
	    }

	}catch(Exception $e){
		throw new bException('proxies_insert(): Failed', $e);
	}
}



/*
 * Removes a server from the proxy chain
 *
 * @param string $root_hostname
 * @param string $remove_hostname
 * @return void
 */
function proxies_remove($root_hostname, $remove_hostname){
    try{
        if($root_hostname == $remove_hostname){
            throw new bException(tr('proxies_remove(): You can not removed main host of the chain'), 'invalid');
        }

        $root     = proxies_get_server($root_hostname, true);
		$remove   = proxies_get_server($remove_hostname, false);

        if(empty($root['proxies'])){
            throw new bException(tr('proxies_remove(): There are not proxies on the chain'), 'invalid');
        }

        $prev = array();
        $next = array();

        foreach($root['proxies'] as $position => $proxy){
            if($proxy['hostname'] == $remove_hostname){
                if(isset($root['proxies'][$position - 1])){
                    $next = proxies_get_server($root['proxies'][$position - 1]['id'], false);
                }else{
                    $next = $root;
                }

                /*
                 * Getting prev
                 */
                if(isset($root['proxies'][$position + 1])){
                    $prev = proxies_get_server($root['proxies'][$position + 1]['id'], false);
                }
                break;
            }

        }

        if($prev){
            log_console('There are prev and next servers', 'white');
            $prev_forwards = forwards_list($prev['id']);

            if(empty($prev_forwards)){
                throw new bException(tr('proxies_remove(): There are not rules for prev proxy, please verify server'), 'invalid');
            }

            foreach($prev_forwards as $id => $forward){

                $new_forward_prev = $forward;

                $new_forward_prev['apply']     = true;
                //$new_forward_prev['target_ip'] = $next['ipv4'];
                //$new_forward_prev['target_id'] = $next['id'];

                $next_forward = forwards_get_by_protocol_and_sourceip($forward['protocol'],  $next['ipv4']);

                /*
                 * Configuring next server to start accepting traffic from prev server
                 */
                $next_forward['apply']       = true;
                $next_forward['source_port'] = $new_forward_prev['target_port'];
                log_console('Applying redirect on next server', 'white');
                forwards_insert($next_forward);

                /*
                 * Applygin new rule on prev server to start redirecting to next server
                 */

                log_console('Applying redirect on prev server', 'white');
                forwards_insert($new_forward_prev);

                /*
                 * Remove rule on prev server
                 */
                log_console('Removing old rules for redirect on prev and next server', 'white');
                $forward['id']    = $id;
                $forward['apply'] = true;
                forwards_delete($forward);
                forwards_delete($next_forward);

            }

            /*
             * Getting removed rules and remove them form the next server
             */
            $remove_forwards = forwards_list($remove['id']);
            log_console('Removing rules on removed server', 'white');
            foreach($remove_forwards as $id => $forward){
                $forward['apply'] = true;
                $forward['id']    = $id;
                forwards_delete($forward);
            }

            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$next['id'],':proxy_id' => $prev['id']));
            /*
             * Updating ssh_proxies_id for removed server
             */
            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$remove['id'],':proxy_id' => NULL));
        }else{

            log_console('Removing server at the front', 'white');
            $remove_forwards = forwards_list($remove['id']);

            /*
             * The server that is at the front always listen on port 80
             * Add rule to start accepting traffic on port 80 on next server
             */
            $forward = forwards_get_by_protocol_and_sourceip('http',  $next['ipv4']);
            $new_forward                = $forward;
            $new_forward['source_port'] = 80;
            $new_forward['apply']       = true;

            log_console('Applying forwarding rule on next server to start accepting traffic on port 80', 'white');
            forwards_insert($new_forward);

            /*
             * Delete old rule on next server, stop accepting traffic from removed server
             */
            log_console('Removing forwarding rule on next server to stop accepting traffic from removed server', 'white');

            $forward['apply'] = true;
            forwards_delete($forward);

            /*
             * Remove rules on removed server
             */
            foreach($remove_forwards as $id => $forward){
                $forward['id'] = $id;
                forwards_delete($forward);
            }

            /*
             * Updateing proxy chain on database
             */
            log_console('Updating proxy chain on database', 'white');
            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$next['id'],':proxy_id' => NULL));
            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$remove['id'],':proxy_id' => NULL));

        }

    }catch(Exception $e){
		throw new bException('proxies_remove(): Failed', $e);
	}
}



/*
 * Return prev and next server for new insert on proxy chain when a new server is added
 *
 * @param string $target_hostname
 * @parma array $proxies
 * @param string $location
 * @return array
 */
function proxies_get_prev_next_insert($root_hostname, $target_hostname, $proxies, $location){
    try{
        $prev = array();
        $next = array();
        switch ($location) {
                case 'before':
                    if($root_hostname == $target_hostname){
                        throw new bException(tr('proxies_get_prev_next(): Server can not be inserted before main host', array(':location' => $location)), 'unkown');
                    }
                    $prev    = proxies_get_server($target_hostname);
                    $proxies = array_reverse($proxies);

                    foreach($proxies as $position => $proxy){
                        if($proxy['hostname'] == $target_hostname){
                            if(isset($proxies[$position + 1 ])){
                                $next = proxies_get_server($proxies[$position + 1 ]['id']);
                            }else{
                                $next = proxies_get_server($root_hostname);
                            }
                            break;
                        }
                    }
                    break;

                case 'after':
                    $next = proxies_get_server($target_hostname);
                    if(($root_hostname == $target_hostname) and !empty($proxies)){
                        $prev = $proxies[0];

                    } else{
                        foreach ($proxies as $position => $proxy) {
                            if(($target_hostname == $proxy['hostname'])){
                                if(isset($root['proxies'][$position + 1])){
                                    $prev = $root['proxies'][$position + 1];
                                }
                                break;
                            }
                        }

                    }

                    break;

                default:
                    throw new bException(tr('proxies_get_prev_next(): Unknown location ":location"', array(':location' => $location)), 'unkown');
            }
        return array($prev, $next);

    }catch(Exception $e){
		throw new bException('proxies_get_prev_next(): Failed', $e);
	}
}



/*
 * Returns server information for a specified hostname
 *
 * @param string $hostname
 * @param boolean $return_proxies
 * @return array
 */
function proxies_get_server($hostname, $return_proxies = false){
	try{
		$server = servers_get($hostname, false, $return_proxies);
		if(empty($server)){
			throw new bException(tr('proxies_get_server(): No server found for hostname ":hostname"', array(':hostname' => $hostname)), 'not-found');
		}

		return $server;

	}catch(Exception $e){
		throw new bException('proxies_get_server(): Failed', $e);
	}
}




/*
 * Checks if a host is already on the proxy chain
 *
 * @param array $proxies
 * @param string $search_hostname
 * @return void
 */
function proxies_validate_on_chain($proxies, $search_hostname){
    try{
		$on_chain = false;
        foreach($proxies as $proxy){
            if($proxy['hostname'] == $search_hostname){
                throw new bException(tr('proxy_validate_on_chain(): Host ":hostname" already on proxies chain', array(':hostname' => $search_hostname)), 'invalid');
            }
        }

	}catch(Exception $e){
		throw new bException('proxy_on_chain(): Failed', $e);
	}
}
?>