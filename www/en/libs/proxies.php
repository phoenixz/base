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
 * Inserts first server on the proxies chain, ports must be specified alongside protocols
 *
 * @param array $prev
 * @param array insert
 * @param string $protocols, example http:4563,https:6464, etc.
 * @param boolean
 */
function proxies_insert_create($prev, $insert, $protocols, $apply){
    try{
        if(empty($protocols)){
            throw new bException(tr('proxies_insert_create(): No protocols specified', array(':insert_hostname' => $insert['hostname'])), 'not-specified');
        }

        $protocol_port = array();

        foreach($protocols as $protocol){
            $data_protocol = explode(":", $protocol);

            if(!isset($data_protocol[1]) or !is_natural($data_protocol[1]) or ($data_protocol[1] > 65535)){
                throw new bException(tr('proxies_insert_create(): Invalid port ":port" specified for protocol ":protocol"', array(':port' => $data_protocol[1], ':protocol' => $data_protocol[0])), 'invalid');
            }

            $protocol = proxies_validate_protocol($data_protocol[0]);

            $protocol_port[$protocol] = $data_protocol[1];
        }

        foreach($protocol_port as $protocol => $port){
            $default_port = proxies_get_default_port($protocol);

            $new_forward['apply']       = $apply;
            $new_forward['servers_id']  = $insert['id'];
            $new_forward['source_id']   = $insert['id'];
            $new_forward['source_ip']   = $insert['ipv4'];
            $new_forward['source_port'] = $default_port;
            $new_forward['target_id']   = $prev['id'];
            $new_forward['target_ip']   = $prev['ipv4'];
            $new_forward['target_port'] = $port;
            $new_forward['protocol']    = $protocol;
            $new_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

            /*
             * Insert new rule and apply
             */
            log_console(tr('Inserting first server on the proxies chain'));
            forwards_insert($new_forward);

        }

        /*
         * Allowing ssh on inserted server from prev server
         */
        $new_forward['apply']       = $apply;
        $new_forward['servers_id']  = $prev['id'];
        $new_forward['source_id']   = $prev['id'];
        $new_forward['source_ip']   = $prev['ipv4'];
        $new_forward['source_port'] = $prev['port'];
        $new_forward['target_id']   = $insert['id'];
        $new_forward['target_ip']   = $insert['ipv4'];
        $new_forward['target_port'] = $insert['port'];
        $new_forward['protocol']    = 'ssh';
        $new_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

        log_console(tr('Applying rule on inserted server to allow ssh connections from prev server'));
        forwards_insert($new_forward);

        /*
         * Creating relation on database
         */
        proxies_create_relation($prev['id'], $insert['id']);
        //sql_query('UPDATE `servers` SET `ssh_proxies_id` = :proxy_id WHERE `id` = :id', array(':id' => $prev['id'],':proxy_id' => $insert['id']));

    }catch(Exception $e){
        throw new bException('proxies_insert_create(): Failed', $e);
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
            throw new bException(tr('proxies_insert_front(): No protocols specified', array(':insert_hostname' => $insert['hostname'])), 'not-specified');
        }

        $prev_forwards = forwards_list($prev['id']);

        if($prev_forwards){
            log_console(tr('Inserting at the front with forwards for previous server'));

            foreach($prev_forwards as $forward){
                $default_source_port = proxies_get_default_port($forward['protocol']);

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
                   throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port' => $default_source_port, ':host' => $insert['hostname'])), 'invalid');
                }

                /*
                 * Updating source port for forward rule on prev server
                 */
                $prev_forward                = $forward;
                $prev_forward['apply']       = $apply;
                $prev_forward['source_port'] = $random_port;

                $exists = forwards_exists($prev_forward);

                if($exists){
                   throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port' => $prev['source_port'], ':host' => $prev['hostname'])), 'invalid');
                }

                /*
                 * Inserting and aplying rules, first on inserted server then in prev server
                 */
                log_console(tr('Applying forward rule on inserted server'));
                forwards_insert($new_forward);

                log_console(tr('Applying forward rule on prev server for new source port'));
                forwards_insert($prev_forward);


                /*
                 * Deleting rule from data base
                 */
                log_console(tr('Removing old forward rule on prev server'));
                /*
                 * Do not apply, we continue listening on the same port
                 */
                $forward['apply'] = $apply;
                forwards_delete($forward);


            }

        }else{
            /*
             * if previous server does not have forwards, we set random ports for specified protocols
             */
            log_console(tr('No forwards for previous server'));

            foreach($protocols as $protocol){
                log_console(tr('Applying forward for protocol: ":protocol"', array(':protocol' => $protocol)));
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

                $exists = forwards_exists($forward);

                /*
                 * If source port is already in use throw exception
                 */
                if($exists){
                    throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port' => $forward['source_port'], ':host' => $insert['hostname'], ':protocol' => $protocol)), 'invalid');
                }
                forwards_insert($forward);
            }
        }

        /*
         * For ssh we only accept traffic from
         */
        $new_forward['apply']       = $apply;
        $new_forward['servers_id']  = $prev['id'];
        $new_forward['source_id']   = $prev['id'];
        $new_forward['source_ip']   = $prev['ipv4'];
        $new_forward['source_port'] = $prev['port'];
        $new_forward['target_id']   = $insert['id'];
        $new_forward['target_ip']   = $insert['ipv4'];
        $new_forward['target_port'] = $insert['port'];
        $new_forward['protocol']    = 'ssh';
        $new_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

        /*
         * Inserting and palying new rule
         */
        log_console(tr('Allowing ssh connection from prev server to inserted server'));
        forwards_insert($new_forward);

        /*
         * Updating proxy chain
         */
        log_console(tr('Updating databse with new proxy chain'));
        proxies_create_relation($prev['id'], $insert['id']);

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
            throw new bException(tr('proxies_insert_middle(): There are not configured rule forwards for next server, nothing to map from ":insert" server to ":next"', array(':insert' => $insert['hostname'], ':next' => $next['hostname'])), 'invalid');
        }

        foreach($next_forwards as $forward){

            log_console(tr('Getting rules for protocol ":protocol"', array(':protocol' => $forward['protocol'])));
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



            /*
             * Apply forward rule on inserted server and start accepting traffic on prev server
             */
            log_console(tr('Applying rule on inserted server'));
            forwards_insert($new_forward);

            /*
             * Removing forward rule on next server
             */
            log_console(tr('Removing rule on next server'));
            forwards_delete($forward);

            /*
             * Appying new rule on next server to start redirecting traffic to inserted server
             */
            log_console(tr('Applying new rule on next server to redirect traffic to inserted server'));
            forwards_insert($next_forward);

        }

        /*
         * Applying ssh rules to accept only connection from previous servers
         */
        log_console(tr('Getting rules for SSH on next server'));

        $new_forward['apply']       = $apply;
        $new_forward['servers_id']  = $insert['id'];
        $new_forward['source_id']   = $insert['id'];
        $new_forward['source_ip']   = $insert['ipv4'];
        $new_forward['source_port'] = $insert['port'];
        $new_forward['target_id']   = $next['id'];
        $new_forward['target_ip']   = $next['ipv4'];
        $new_forward['target_port'] = $next['port'];
        $new_forward['protocol']    = 'ssh';
        $new_forward['description'] = 'Rule added by proxies library on '.date('Y-m-d H:i:s');

        forwards_insert($new_forward);

        log_console(tr('Updating ssh rule on prev server'));
        $ssh_forward_prev = forwards_get_by_protocol($prev['id'], 'ssh');

        $ssh_forward_prev['apply']       = $apply;
        $ssh_forward_prev['target_id']   = $insert['id'];
        $ssh_forward_prev['target_ip']   = $insert['ipv4'];
        $ssh_forward_prev['target_port'] = $insert['port'];
        $ssh_forward_prev['description'] = 'Updating ssh on '.date('Y-m-d H:i:s');

        forwards_update($ssh_forward_prev);

        /*
         * After applying and updating rules, we must update proxies chain
         */
        log_console(tr('Updating proxies chain'));
        proxies_update_relation($prev['id'], $next['id'], $insert['id']);

        /*
         * Creating new relation
         */
         proxies_create_relation($insert['id'], $next['id']);

    }catch(Exception $e){
		throw new bException('proxies_insert_middle(): Failed', $e);
	}
}



/*
 * Inserts a new server on the proxies chain for a specified root server
 *
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
            throw new bException(tr('proxies_insert(): New host ":insert_hostname" can not be inserted before main server ":root_hostname"', array(':insert_hostname' => $insert_hostname, ':root_hostname' => $root_hostname)), 'invalid');
        }

        list($prev, $next) = proxies_get_prev_next_insert($root_hostname, $target_hostname, $root['proxies'], $location);


        if(!empty($prev) and empty($next)){
            /*
             * If there is not next server, inserted server goes at the front
             */
            if(empty($root['proxies'])){

                proxies_insert_create($prev, $insert, $protocols, $apply);

            }else{

                proxies_insert_front($prev, $insert, $protocols, $apply);

            }

            /*
             * Drop everything execept previous rules REVIEW
             */
            log_console(tr('Adding iptables rule to drop all except previous rules on inserted server'));
            //forwards_deny_access($insert['id']);

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
 * Removes a proxy at the front of the proxies chain
 *
 * @param array $prev, previous server information
 * @param array $remove, remove server information
 * @param boolean $apply, whether to apply the rules or not
 * @return void
 */
function proxies_remove_front($prev, $remove, $apply){
    try{
        $prev_forwards = forwards_list($prev['id']);

        if($prev_forwards){
            /*
             * Updating source port
             */
            foreach($prev_forwards as $forward){
                $new_forward = $forward;

                if($forward['protocol'] != 'ssh'){

                    $default_port = proxies_get_default_port($forward['protocol']);

                    $new_forward['apply']       = $apply;
                    $new_forward['source_port'] = $default_port;

                    /*
                     * Applying new rule with default ports
                     */
                    forwards_insert($new_forward);

                    /*
                     * Removing old rule :REVIEW: something is wrong, rule is  not been deleted from database
                     */
                    $forward['apply'] = true;
                    forwards_delete($forward);
                }
            }
        }

        /*
         * Remove rules from removed server
         */
        $remove_forwards = forwards_list($remove['id']);

        if($remove_forwards){
            forwards_delete_list($remove_forwards, $apply);
        }

        /*
         * Updating proxies chaing on database
         */
        proxies_delete_relation($prev['id'], $remove['id']);

    }catch(Exception $e){
		throw new bException('proxies_remove_front(): Failed', $e);
	}
}



/*
 * Removes a proxy from the middle of the chain
 *
 * @param array $prev
 * @param array $next
 * @param array $remove
 * @param boolean $apply
 * return void
 */
function proxies_remove_middle($prev, $next, $remove, $apply){
    try{
        $next_forwards   = forwards_list($next['id']);
        $remove_forwards = forwards_list($remove['id']);

        if(empty($next_forwards)){
            throw new bException(tr('proxies_remove_middle(): There are not forwards rules on next server'), 'invalid');
        }

        if(empty($remove_forwards)){
            throw new bException(tr('proxies_remove_middle(): There are not forwards rules on remove server'), 'invalid');
        }

        /*
         * Applying new rule on next server to start redirecting traffic to prev server
         */
        foreach($next_forwards as $forward){
            $new_forward = $forward;

            foreach($remove_forwards as $index => $remove_forward){
                if($remove_forward['protocol'] == $forward['protocol']){
                    $new_forward['apply']       = $apply;
                    $new_forward['target_id']   = $prev['id'];
                    $new_forward['target_ip']   = $prev['ipv4'];
                    $new_forward['target_port'] = $remove_forward['target_port'];

                    break;
                }
            }

            /*
             * Inserting and applying new rule on next server
             */
            log_console(tr('Appying new rule on next server'));
            forwards_insert($new_forward);

        }

        /*
         * Delete forwards rules on next server and removed server
         */
        log_console(tr('Removing rules on next server'));
        forwards_delete_list($next_forwards, $apply);

        /*
         * Delete forward fules from removed server
         */
        log_console(tr('Removing rules on removed server'));
        forwards_delete_list($remove_forwards, $apply);

        /*
         * Update proxies chain on database
         */
        proxies_update_relation($prev['id'], $remove['id'], $next['id']);
        proxies_delete_relation($remove['id'], $next['id']);

    }catch(Exception $e){
		throw new bException('proxies_remove_middle(): Failed', $e);
	}
}



/*
 * Removes a server from the proxy chain
 *
 * @param string $root_hostname
 * @param string $remove_hostname
 * @return void
 */
function proxies_remove($root_host, $remove_host, $apply = true){
    try{
        if(strcasecmp($root_host, $remove_host) == 0){
            throw new bException(tr('proxies_remove(): You can not remove host ":remove_host", it is the main host on the proxies chain', array(':remove_host' => $remove_host)), 'invalid');
        }

        $root   = proxies_get_server($root_host, true);

        if($remove_host === 'all'){
            /*
             * Flush all rules on iptables
             */
            foreach($root['proxies'] as $proxy){
                forwards_destroy($proxy['id']);
            }
        }

        $remove = proxies_get_server($remove_host);

        if(empty($root['proxies'])){
            throw new bException(tr('proxies_remove(): Root host ":root_host" does not have proxies chain', array(':root_host' => $root_host)), 'not-exist');
        }

        $host_on_chain = proxies_validate_on_chain($root['proxies'], $remove_host);

        if(!$host_on_chain){
            throw new bException(tr('proxies_remove(): Host ":remove_host" is not on the proxies chain', array(':remove_host' => $remove_host)), 'not-exist');
        }

        /*
         * Getting prev and next server
         */
        list($prev, $next) = proxies_get_prev_next_remove($root, $remove, $root['proxies']);

        if(!empty($prev) and empty($next)){
            proxies_remove_front($prev, $remove, $apply);

        }else{
            proxies_remove_middle($prev, $next, $remove, $apply);

        }

    }catch(Exception $e){
		throw new bException('proxies_remove(): Failed', $e);
	}
}



/*
 * Returns prev and next server in order to remove a specified server
 *
 * @param array $root_server
 * @param array $remove_server
 * @return array
 */
function proxies_get_prev_next_remove($root_server, $remove_server, $proxies){
    try{

        $prev = array();
        $next = array();

        foreach($proxies as $position => $proxy){
            if($proxy['hostname'] == $remove_server['hostname']){

                /*
                 * Getting prev server
                 */
                if(isset($proxies[$position - 1])){
                    $prev = proxies_get_server($proxies[$position - 1]['id']);

                }else{
                    $prev = $root_server;
                }

                /*
                 * Getting next server
                 */
                if(isset($proxies[$position + 1])){
                    $next = proxies_get_server($proxies[$position + 1]['id']);

                }

                break;

            }
        }

        return array($prev, $next);

    }catch(Exception $e){
		throw new bException('proxies_get_prev_next_remove(): Failed', $e);
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
                    throw new bException(tr('proxies_get_prev_next_insert(): Server can not be inserted before main host', array(':location' => $location)), 'unkown');
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
                throw new bException(tr('proxies_get_prev_next_insert(): Unknown location ":location"', array(':location' => $location)), 'unkown');
        }

        return array($prev, $next);

    }catch(Exception $e){
		throw new bException('proxies_get_prev_next_insert(): Failed', $e);
	}
}



/*
 * Returns server information for a specified hostname
 *
 * @param string $hostname
 * @param boolean $return_proxies (false)
 * @return array
 */
function proxies_get_server($host, $return_proxies = false){
	try{
		$server = servers_get($host, false, $return_proxies);

		if(empty($server)){
			throw new bException(tr('proxies_get_server(): No server found for host ":host"', array(':host' => $host)), 'not-found');
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
        foreach($proxies as $proxy){
            if($proxy['hostname'] == $search_hostname){
                return true;
            }
        }

        return false;

	}catch(Exception $e){
		throw new bException('proxies_validate_on_chain(): Failed', $e);
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
                return $location;

            default:
                throw new bException(tr('proxies_validate_location(): Unknown location ":location"', array(':location' => $location)), 'unknown');
        }

	}catch(Exception $e){
		throw new bException('proxies_validate_location(): Failed', $e);
	}
}



/*
 * Returns default port for specified protocol, allow protocols: http, https, ssh
 * This is for incoming traffic(destination port)
 *
 * @param string $protocol
 * @return integer
 */
function proxies_get_default_port($protocol){
    try{
        if(empty($protocol)){
            throw new bException(tr('proxies_get_default_port(): Protocol not specified'), 'not-specified');
        }

        switch($protocol){
            case 'http':
                return 80;

            case 'https':
                return 443;

            case 'ssh':
                return 22;

            default:
                throw new bException(tr('proxies_get_default_port(): Unknown protocol ":protocol"', array(':protocol' => $protocol)), 'unknown');
        }

    }catch(Exception $e){
		throw new bException('proxies_get_default_port(): Failed', $e);
	}
}



/*
 * Validate protocol in order to forward traffic, ssh is configured by default,
 * in order to listen only connection from previous server
 *
 * @param string procotol
 * @return string
 */
function proxies_validate_protocol($protocol){
    try{
        if(empty($protocol)){
            throw new bException(tr('proxies_validate_protocol(): No protocol specified'), 'not-specified');
        }

        $protocol = strtolower($protocol);

        switch($protocol){
            case 'http':
                //FALLTHROUGH
            case 'https':
                //FALLTHROUGH
            case 'imap':
                //FALLTHROUGH
            case 'smtp':
                //Valid protocols
                break;
        }

        return $protocol;

    }catch(Exception $e){
		throw new bException('proxies_validate_protocol(): Failed', $e);
	}
}



/*
 * Creates a new record on proxy_servers table to store relation between servers
 *
 * @param integer $servers_id
 * @param integer $proxies_id
 * @return integer, inserted id
 */
function proxies_create_relation($servers_id, $proxies_id){
    try{
        if(empty($servers_id)){
            throw new bException(tr('No servers id specified'), 'not-specified');
        }

        if(empty($proxies_id)){
            throw new bException(tr('No proxies id specified'), 'not-specified');
        }

        sql_query('INSERT INTO `proxy_servers` (`servers_id`, `proxies_id`)
                   VALUES (:servers_id, :proxies_id)',

                   array(':servers_id' => $servers_id, ':proxies_id' => $proxies_id));

        $proxy_servers_id = sql_insert_id();
    }catch(Exception $e){
		throw new bException('proxies_create_relation(): Failed', $e);
	}
}



/*
 * Updates relation in database base for specified server, in case relation does
 * not exists, a new record is created
 *
 * @param integer $servers_id
 * @param integer $old_proxies_id
 * @param integer $new_proxies_id
 * @return void
 */
function proxies_update_relation($servers_id, $old_proxies_id, $new_proxies_id){
    try{
        if(empty($servers_id)){
            throw new bException(tr('No servers id specified'), 'not-specified');
        }

        if(empty($old_proxies_id)){
            throw new bException(tr('No old proxies id specified'), 'not-specified');
        }

        if(empty($new_proxies_id)){
            throw new bException(tr('No new proxies id specified'), 'not-specified');
        }

        $record = sql_get('SELECT * FROM `proxy_servers`

                                    WHERE `servers_id` = :servers_id
                                    AND   `proxies_id` = :proxies_id',

                                    array(':servers_id'=> $servers_id,
                                          ':proxies_id'=> $old_proxies_id));

        if($record){

            sql_query('UPDATE `proxy_servers`

                       SET    `proxies_id` = :proxies_id

                       WHERE  `id` = :id',

                      array(':id'         => $record['id'],
                            ':proxies_id' => $new_proxies_id));

        }else{
            /*
             * Record does not exist, creating a new one
             */
            proxies_create_relation($servers_id, $new_proxies_id);

        }

    }catch(Exception $e){
		throw new bException('proxies_update_relation(): Failed', $e);
	}
}



/*
 * Deletes from data base relation between two servers
 * @param integer $servers_id
 * @param integer $proxies_id
 */
function proxies_delete_relation($servers_id, $proxies_id){
    try{
        sql_query('DELETE FROM `proxy_servers`

                   WHERE servers_id = :servers_id
                   AND proxies_id = :provies_id',

                   array('servers_id' => $servers_id,
                         'proxies_id' => $proxies_id));

    }catch(Exception $e){
		throw new bException('proxies_delete_relation(): Failed', $e);
	}
}
?>