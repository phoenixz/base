<?php
/*
 * Proxies library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Capmega <copyright@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function proxies_library_init(){
    try{
        load_libs('servers,forwards');

    }catch(Exception $e){
        throw new bException('proxy_library_init(): Failed', $e);
    }
}



/*
 * Inserts a new server at the front of the proxies chain or as first server on chain.
 * When a new server is inserted at the front, protocols alwasy must be specified
 *
 * @param array $prev
 * @param array $insert
 * @param array $protocols
 * return void
 */
function proxies_insert_front($prev, $insert, $protocols, $apply){
    try{
        /*
         * If there are not proxies, protocols must be specified otherwise throw exception
         */
        if(empty($protocols)){
            throw new bException(tr('proxies_insert(): In order to insert host ":insert_hostname" at the front of the proxy chain, you must provide which protocols must be configured', array(':insert_hostname'=>$insert['hostname'])), 'invalid');
        }

        $prev_forwards = forwards_list($prev['id']);

        if($prev_forwards){

            log_console('Inserting at the front with forwards for previous server');

            foreach($prev_forwards as $forward){
                $default_source_port = proxies_get_default_port($forward['protocol']);

                if($forward['protocol'] == 'ssh'){
                    /*
                     * For ssh we only accept traffic from
                     */
                    $new_forward              = $forward;
                    $new_forward['apply']     = true;
                    $new_forward['source_id'] = $insert['id'];
                    $new_forward['source_ip'] = $insert['ipv4'];
                    $new_forward['target_id'] = $prev['id'];
                    $new_forward['target_ip'] = $prev['ipv4'];

                    /*
                     * Inserting and palying new rule
                     */
                    forwards_insert($new_forward);

                    /*
                     * Deleting old rule
                     */
                    $forward['apply'] = $apply;

                    forwards_delete($forward);

                } else{
                    /*
                    * Creating forward rule for inserted server
                    */
                    $random_port = mt_rand(1025, 65535);

                    $new_forward['apply']       = $apply;
                    $new_forward['servers_id']  = $insert['id'];
                    $new_forward['source_id']   = $insert['id'];
                    $new_forward['source_ip']   = $insert['ipv4'];
                    $new_forward['source_port'] = $default_source_port;
                    $new_forward['target_id']   = $prev['id'];
                    $new_forward['target_ip']   = $prev['ipv4'];
                    $new_forward['target_port'] = $random_port;
                    $new_forward['protocol']    = $forward['protocol'];
                    $new_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

                    $exists = forwards_exists($new_forward);
                    /*
                     * If source port is already in use throw exception
                     */
                    if($exists){
                       throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port'=>$default_source_port, ':host'=>$insert['hostname'])), 'invalid');
                    }

                    /*
                     * Updating source port for forward rule on prev server
                     */
                    $forward['apply']       = $apply;
                    $forward['source_port'] = $random_port;
                    $exists = forwards_exists($forward);
                    if($exists){
                       throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port'=>$prev['source_port'], ':host'=>$prev['hostname'])), 'invalid');
                    }

                    /*
                     * Inserting and aplying rules, first on inserted server then in prev server
                     */
                    log_console('Applying forward rule on inserted server', 'white');
                    forwards_insert($new_forward);

                    log_console('Applying forward rule on prev server for new source port', 'white');
                    forwards_insert($forward);

                    /*
                     * Deleting rule from data base
                     */
                    log_console('Removing old forward rule on prev server', 'white');
                    forwards_delete($forward);

                }

                /*
                 * Updating data base chaing
                 */
                log_console('Updating databse with new proxy chain', 'white');
                sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$prev['id'],':proxy_id' => $insert['id']));
            }

        }else{
            /*
             * if previous server does not have forwards, we set random ports for specified protocols
             */
            log_console('No forwards for previous server');
            foreach($protocols as $protocol){
                log_console('Applying forward for protocol: '.$protocol);
                $default_source_port = proxies_get_default_port($protocol);

                $forward['apply']       = $apply;
                $forward['servers_id']  = $insert['id'];
                $forward['source_id']   = $insert['id'];
                $forward['source_ip']   = $insert['ipv4'];
                $forward['source_port'] = $default_source_port;
                $forward['target_id']   = $prev['id'];
                $forward['target_ip']   = $prev['ipv4'];
                $forward['target_port'] = mt_rand(1025, 65535);
                $forward['protocol']    = $protocol;
                $forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

                if($protocol == 'ssh'){
                    /*
                     * :REVIEW: We keep the port configure on servers table
                     */
                    $forward['target_port'] = $prev['port'];
                    $forward['apply']       = false;
                    forwards_only_accept_traffic($forward);

                }else{
                    $exists = forwards_exists($forward);

                    /*
                     * If source port is already in use throw exception
                     */
                    if($exists){
                        throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port'=>$forward['source_port'], ':host'=>$insert['hostname'], ':protocol'=>$protocol)), 'invalid');
                    }

                }
                forwards_insert($forward);
            }

            /*
             * Updating proxy chain
             */
            log_console('Updating databse with new proxy chain', 'white');
            sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$prev['id'],':proxy_id' => $insert['id']));
        }

    }catch(Exception $e){
		throw new bException('proxies_insert_front(): Failed', $e);
	}
}



/*
 * Inserts a new server between other 2
 * Inserted server must apply rules from next server
 *
 * @param array $prev
 * @param array $next
 * @param array $insert
 * @return void
 */
function proxies_insert_middle($prev, $next, $insert, $apply){
    try{
        $next_forwards = forwards_list($next['id']);

        if(empty($next_forwards)){
            throw new bException(tr('proxies_insert_middle(): There are not configured rule forwards for next server, nothing to map from ":insert" server to ":next"', array(':insert'=>$insert['hostname'], ':next'=>$next['hostname'])), 'invalid');
        }

        foreach($next_forwards as $forward){

            if($forward['protocol'] == 'ssh'){
                log_console('Getting rules for ssh', 'white');

                $new_forward['apply']       = $apply;
                $new_forward['servers_id']  = $insert['id'];
                $new_forward['source_id']   = $insert['id'];
                $new_forward['source_ip']   = $insert['ipv4'];
                $new_forward['source_port'] = $insert['port'];
                $new_forward['target_id']   = $prev['id'];
                $new_forward['target_ip']   = $prev['ipv4'];
                $new_forward['target_port'] = $forward['target_port'];
                $new_forward['protocol']    = $forward['protocol'];
                $new_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

                /*
                 * Updating forward rule on next server to start redirecting traffic to inserted server
                 */
                $next_forward['apply']       = $apply;
                $next_forward['servers_id']  = $next['id'];
                $next_forward['source_id']   = $next['id'];
                $next_forward['source_ip']   = $next['ipv4'];
                $next_forward['source_port'] = $forward['source_port'];
                $next_forward['target_id']   = $insert['id'];
                $next_forward['target_ip']   = $insert['ipv4'];
                $next_forward['target_port'] = $insert['port'];
                $next_forward['protocol']    = $forward['protocol'];
                $next_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

            }else{
                log_console('Getting rules for protocol "'.$forward['protocol'].'"', 'white');
                $random_port = mt_rand(1025, 65535);

                $new_forward['apply']       = $apply;
                $new_forward['servers_id']  = $insert['id'];
                $new_forward['source_id']   = $insert['id'];
                $new_forward['source_ip']   = $insert['ipv4'];
                $new_forward['source_port'] = $random_port;
                $new_forward['target_id']   = $prev['id'];
                $new_forward['target_ip']   = $prev['ipv4'];
                $new_forward['target_port'] = $forward['target_port'];
                $new_forward['protocol']    = $forward['protocol'];
                $new_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

                /*
                 * Updating forward rule on next server to start redirecting traffic to inserted server
                 */
                $next_forward['apply']       = $apply;
                $next_forward['servers_id']  = $next['id'];
                $next_forward['source_id']   = $next['id'];
                $next_forward['source_ip']   = $next['ipv4'];
                $next_forward['source_port'] = $forward['source_port'];
                $next_forward['target_id']   = $insert['id'];
                $next_forward['target_ip']   = $insert['ipv4'];
                $next_forward['target_port'] = $random_port;
                $next_forward['protocol']    = $forward['protocol'];
                $next_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

            }

            /*
             * Apply forward rule on inserted server and start accepting traffic on prev server
             */
            log_console('Applying rule on inserted server', 'white');
            forwards_insert($new_forward);

            /*
             * Removing forward rule on next server
             */
            log_console('Removing rule on next server', 'white');
            forwards_delete($forward);

            /*
             * Appying new rule on next server to start redirecting traffic to inserted server
             */
            log_console('Applying new rule on next server to redirect traffic to inserted server', 'white');
            forwards_insert($next_forward);

        }

        /*
         * After applying and updating rules, we must update proxies chain
         */
        log_console('Updating proxies chain', 'white');
        sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$prev['id'],   ':proxy_id' => $insert['id']));
        sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id'=>$inserst['id'],':proxy_id' => $next['id']));

    }catch(Exception $e){
		throw new bException('proxies_insert_middle(): Failed', $e);
	}
}



/*
 * @param $root_hostname initial server on chain
 * @param $insert_hostname
 */
function proxies_insert($root_hostname, $insert_hostname, $target_hostname, $location, $protocols, $apply = true){
	try{
        $location  = proxies_validate_location($location);
		$root      = proxies_get_server($root_hostname, true);
		$insert    = proxies_get_server($insert_hostname, false);
        $protocols = array_force($protocols);
        $on_chain  = proxies_validate_on_chain($root['proxies'], $insert_hostname);

		$next = array();
		$prev = array();

        /*
         * If there are not proxies, server must go at the front, it can no be inserted
         * before main server
         */
        if(empty($root['proxies']) and $location == 'before'){
            throw new bException(tr('proxies_insert(): New host ":insert_hostname" can not be inserted before main server ":root_hostname"', array(':insert_hostname'=>$insert_hostname, ':root_hostname'=>$root_hostname)), 'invalid');
        }

        list($prev, $next) = proxies_get_prev_next_insert($root_hostname, $target_hostname, $root['proxies'], $location);


        if(!empty($prev) and empty($next)){
            /*
             * If there is not next server, inserted server goes at the front
             */
            proxies_insert_front($prev, $insert, $protocols, $apply);

        } else{
            /*
             * If there is and prev and next server, inserted server goest in the middle
             */
            proxies_insert_middle($prev, $next, $insert, $apply);
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

        switch($location){
            case 'before':
                if($root_hostname == $target_hostname){
                    throw new bException(tr('proxies_get_prev_next(): Server can not be inserted before main host', array(':location' => $location)), 'unkown');
                }

                $next    = proxies_get_server($target_hostname);
                $proxies = array_reverse($proxies);

                foreach($proxies as $position => $proxy){
                    if($proxy['hostname'] == $target_hostname){
                        if(isset($proxies[$position + 1 ])){
                            $prev = proxies_get_server($proxies[$position + 1 ]['id']);

                        }else{
                            $prev = proxies_get_server($root_hostname);
                        }

                        break;
                    }
                }

                break;

            case 'after':
                $prev = proxies_get_server($target_hostname);
                if(($root_hostname == $target_hostname) and !empty($proxies)){
                    $next = $proxies[0];

                }else{
                    foreach($proxies as $position => $proxy){
                        if($target_hostname == $proxy['hostname']){
                            if(isset($root['proxies'][$position + 1])){
                                $next = $root['proxies'][$position + 1];
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



/*
 * Validates type of location for inserting a new server
 * Valid entries: before, after
 *
 * @param string $location
 * @return string
 */
function proxies_validate_location($location){
    try{
		if(empty($location)){
            throw new bException(tr('proxies_validate_location(): Location not specified'), 'not-specified');
        }

        $location = strtolower($location);

        switch($location){
            case 'before':
                //FALLTHROUGH
            case 'after':
                break;

            default:
                throw new bException(tr('proxies_validate_location(): Unknown location ":location"', array(':location' => $location)), 'not-specified');
        }

        return $location;

	}catch(Exception $e){
		throw new bException('proxies_validate_location(): Failed', $e);
	}
}



/*
 * Returns default port for specified protocol, allow protocols: http, https, ssh
 * This is for incoming traffic(source port)
 *
 * @param string $protocol
 * @return integer
 */
function proxies_get_default_port($protocol){
    try{
        if(empty($protocol)){
            throw new bException(tr('proxies_validate_location(): Protocol not specified'), 'not-specified');
        }

        switch($protocol){
            case 'http':
                $port = 80;
                break;

            case 'https':
                $port = 443;
                break;

            case 'ssh':
                $port = 22;
                break;

            default:
                throw new bException(tr('proxies_get_default_port(): Unknown protocol ":location"', array(':location' => $protocol)), 'not-specified');
        }

        return $port;

    }catch(Exception $e){
		throw new bException('proxies_get_default_port(): Failed', $e);
	}
}
?>