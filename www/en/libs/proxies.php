<?php
/*
 * Proxies library
 *
 * License http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * Copyright Capmega <copyright@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function proxies_library_init(){
    try{
        load_libs('servers,forwardings');

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
            $data_protocol = explode(':', $protocol);

            if(!isset($data_protocol[1])){
                throw new bException(tr('proxies_insert_create(): Port not specified for protocol ":protocol"', array(':protocol' => $data_protocol[0])), 'invalid');
            }

            if(!is_natural($data_protocol[1]) or (isset($data_protocol[1]) and $data_protocol[1] > 65535)){
                throw new bException(tr('proxies_insert_create(): Invalid port ":port" specified for protocol ":protocol"', array(':port' => $data_protocol[1], ':protocol' => $data_protocol[0])), 'invalid');
            }

            $protocol = proxies_validate_protocol($data_protocol[0]);

            $protocol_port[$protocol] = $data_protocol[1];
        }

        /*
         * Insert new rule and apply
         */
        log_console(tr('Inserting first server on the proxies chain'));
        foreach($protocol_port as $protocol => $port){
            $default_port = proxies_get_default_port($protocol);

            $new_forwarding['apply']       = $apply;
            $new_forwarding['servers_id']  = $insert['id'];
            $new_forwarding['source_id']   = $insert['id'];
            $new_forwarding['source_ip']   = $insert['ipv4'];
            $new_forwarding['source_port'] = $default_port;
            $new_forwarding['target_id']   = $prev['id'];
            $new_forwarding['target_ip']   = $prev['ipv4'];
            $new_forwarding['target_port'] = $port;
            $new_forwarding['protocol']    = $protocol;
            $new_forwarding['description'] = 'Rule added by proxies library';

            log_console(tr('Applying rule on inserted server'));
            forwardings_insert($new_forwarding);

        }

        /*
         * Allowing ssh on inserted server from prev server
         */
        $new_forwarding['apply']       = $apply;
        $new_forwarding['servers_id']  = $prev['id'];
        $new_forwarding['source_id']   = $prev['id'];
        $new_forwarding['source_ip']   = $prev['ipv4'];
        $new_forwarding['source_port'] = $prev['port'];
        $new_forwarding['target_id']   = $insert['id'];
        $new_forwarding['target_ip']   = $insert['ipv4'];
        $new_forwarding['target_port'] = $insert['port'];
        $new_forwarding['protocol']    = 'ssh';
        $new_forwarding['description'] = 'Rule added by proxies library';

        log_console(tr('Applying rule on inserted server to allow ssh connections from prev server'));
        forwardings_apply_rule($new_forwarding);

        /*
         * Creating relation on database
         */
        load_libs('servers');
        servers_add_ssh_proxy($prev['id'], $insert['id']);

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

        $prev_forwardings = forwardings_list($prev['id']);

        if($prev_forwardings){
            log_console(tr('Inserting at the front with forwardings for previous server'));

            foreach($prev_forwardings as $forwarding){
                $default_source_port = proxies_get_default_port($forwarding['protocol']);

                /*
                * Creating forwarding rule for inserted server
                */
                $random_port = mt_rand(1025, 65535);

                $new_forwarding['apply']       = $apply;
                $new_forwarding['servers_id']  = $insert['id'];
                $new_forwarding['source_id']   = $insert['id'];
                $new_forwarding['source_ip']   = $insert['ipv4'];
                $new_forwarding['source_port'] = $default_source_port;
                $new_forwarding['target_id']   = $prev['id'];
                $new_forwarding['target_ip']   = $prev['ipv4'];
                $new_forwarding['target_port'] = $random_port;
                $new_forwarding['protocol']    = $forwarding['protocol'];
                $new_forwarding['description'] = 'Rule added by proxies library';

                $exists = forwardings_exists($new_forwarding);

                /*
                 * If source port is already in use throw exception
                 */
                if($exists){
                   throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port' => $default_source_port, ':host' => $insert['hostname'])), 'invalid');
                }

                /*
                 * Updating source port for forwarding rule on prev server
                 */
                $prev_forwarding                = $forwarding;
                $prev_forwarding['apply']       = $apply;
                $prev_forwarding['source_port'] = $random_port;

                $exists = forwardings_exists($prev_forwarding);

                if($exists){
                   throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port' => $prev['source_port'], ':host' => $prev['hostname'])), 'invalid');
                }

                /*
                 * Inserting and aplying rules, first on inserted server then in prev server
                 */
                log_console(tr('Applying forwarding rule on inserted server'));
                forwardings_insert($new_forwarding);

                log_console(tr('Applying forwarding rule on prev server for new source port'));
                forwardings_insert($prev_forwarding);

                /*
                 * Deleting rule from data base
                 */
                log_console(tr('Removing old forwarding rule on prev server'));
                /*
                 * Do not apply, we continue listening on the same port
                 */
                $forwarding['apply'] = $apply;
                forwardings_delete($forwarding);
            }

        }else{
            /*
             * if previous server does not have forwardings, we set random ports for specified protocols
             */
            log_console(tr('No forwardings for previous server'));

            foreach($protocols as $protocol){
                log_console(tr('Applying forwarding on inserted server with random port for target_port'));
                $default_source_port = proxies_get_default_port($protocol);

                $forwarding['apply']       = $apply;
                $forwarding['servers_id']  = $insert['id'];
                $forwarding['source_id']   = $insert['id'];
                $forwarding['source_ip']   = $insert['ipv4'];
                $forwarding['source_port'] = $default_source_port;
                $forwarding['target_id']   = $prev['id'];
                $forwarding['target_ip']   = $prev['ipv4'];
                $forwarding['target_port'] = mt_rand(1025, 65535);
                $forwarding['protocol']    = $protocol;
                $forwarding['description'] = 'Rule added by proxies library';

                $exists = forwardings_exists($forwarding);

                /*
                 * If source port is already in use throw exception
                 */
                if($exists){
                    throw new bException(tr('proxies_insert_front(): Port ":source_port" on host ":host" for protocol ":protocol" is already in use', array(':source_port' => $forwarding['source_port'], ':host' => $insert['hostname'], ':protocol' => $protocol)), 'invalid');
                }
                forwardings_insert($forwarding);
            }
        }

        /*
         * For ssh we only accept traffic from
         */
        $new_forwarding['apply']       = $apply;
        $new_forwarding['servers_id']  = $prev['id'];
        $new_forwarding['source_id']   = $prev['id'];
        $new_forwarding['source_ip']   = $prev['ipv4'];
        $new_forwarding['source_port'] = $prev['port'];
        $new_forwarding['target_id']   = $insert['id'];
        $new_forwarding['target_ip']   = $insert['ipv4'];
        $new_forwarding['target_port'] = $insert['port'];
        $new_forwarding['protocol']    = 'ssh';
        $new_forwarding['description'] = 'Rule added by proxies library';

        /*
         * Inserting and palying new rule
         */
        log_console(tr('Allowing ssh connection from prev server to inserted server'));
        forwardings_apply_rule($new_forwarding);

        /*
         * Updating proxy chain
         */
        log_console(tr('Updating databse with new proxy chain'));
        load_libs('servers');
        servers_add_ssh_proxy($prev['id'], $insert['id']);

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
        log_console(tr('Getting forwarding rules for next server'));
        $next_forwardings = forwardings_list($next['id']);

        if(empty($next_forwardings)){
            throw new bException(tr('proxies_insert_middle(): There are not configured rule forwardings for next server, nothing to map from ":insert" server to ":next"', array(':insert' => $insert['hostname'], ':next' => $next['hostname'])), 'invalid');
        }

        foreach($next_forwardings as $forwarding){
            $random_port = mt_rand(1025, 65535);

            $new_forwarding['apply']       = $apply;
            $new_forwarding['servers_id']  = $insert['id'];
            $new_forwarding['source_id']   = $insert['id'];
            $new_forwarding['source_ip']   = $insert['ipv4'];
            $new_forwarding['source_port'] = $random_port;
            $new_forwarding['target_id']   = $prev['id'];
            $new_forwarding['target_ip']   = $prev['ipv4'];
            $new_forwarding['target_port'] = $forwarding['target_port'];
            $new_forwarding['protocol']    = $forwarding['protocol'];
            $new_forwarding['description'] = 'Rule added by proxies library';

            /*
             * Updating forwarding rule on next server to start redirecting traffic to inserted server
             */
            $next_forwarding['apply']       = $apply;
            $next_forwarding['servers_id']  = $next['id'];
            $next_forwarding['source_id']   = $next['id'];
            $next_forwarding['source_ip']   = $next['ipv4'];
            $next_forwarding['source_port'] = $forwarding['source_port'];
            $next_forwarding['target_id']   = $insert['id'];
            $next_forwarding['target_ip']   = $insert['ipv4'];
            $next_forwarding['target_port'] = $random_port;
            $next_forwarding['protocol']    = $forwarding['protocol'];
            $next_forwarding['description'] = 'Rule added by proxies library';

            /*
             * Apply forwarding rule on inserted server and start accepting traffic on prev server
             */
            log_console(tr('Applying rule on inserted server'));
            forwardings_insert($new_forwarding);

            /*
             * Removing forwarding rule on next server
             */
            log_console(tr('Removing rule on next server'));
            forwardings_delete($forwarding);

            /*
             * Appying new rule on next server to start redirecting traffic to inserted server
             */
            log_console(tr('Applying new rule on next server to redirect traffic to inserted server'));
            forwardings_insert($next_forwarding);
        }

        /*
         * Applying ssh rules to accept only connection from previous servers
         */
        log_console(tr('Getting rules for SSH on next server'));

        $new_forwarding['apply']       = $apply;
        $new_forwarding['servers_id']  = $insert['id'];
        $new_forwarding['source_id']   = $insert['id'];
        $new_forwarding['source_ip']   = $insert['ipv4'];
        $new_forwarding['source_port'] = $insert['port'];
        $new_forwarding['target_id']   = $next['id'];
        $new_forwarding['target_ip']   = $next['ipv4'];
        $new_forwarding['target_port'] = $next['port'];
        $new_forwarding['protocol']    = 'ssh';
        $new_forwarding['description'] = 'Rule added by proxies library';

        forwardings_apply_rule($new_forwarding);
        log_console(tr('Updating ssh rule on prev server'));

        $ssh_forwarding_prev_old['apply']       = $apply;
        $ssh_forwarding_prev_old['servers_id']  = $prev['id'];
        $ssh_forwarding_prev_old['source_id']   = $prev['id'];
        $ssh_forwarding_prev_old['source_ip']   = $prev['ipv4'];
        $ssh_forwarding_prev_old['source_port'] = $prev['port'];
        $ssh_forwarding_prev_old['target_id']   = $next['id'];
        $ssh_forwarding_prev_old['target_ip']   = $next['ipv4'];
        $ssh_forwarding_prev_old['target_port'] = $next['port'];
        $ssh_forwarding_prev_old['protocol']    = 'ssh';
        $ssh_forwarding_prev_old['description'] = 'Rule added by proxies library';

        $ssh_forwarding_prev_new['apply']       = $apply;
        $ssh_forwarding_prev_new['servers_id']  = $prev['id'];
        $ssh_forwarding_prev_new['source_id']   = $prev['id'];
        $ssh_forwarding_prev_new['source_ip']   = $prev['ipv4'];
        $ssh_forwarding_prev_new['source_port'] = $prev['port'];
        $ssh_forwarding_prev_new['target_id']   = $next['id'];
        $ssh_forwarding_prev_new['target_ip']   = $next['ipv4'];
        $ssh_forwarding_prev_new['target_port'] = $next['port'];
        $ssh_forwarding_prev_new['protocol']    = 'ssh';
        $ssh_forwarding_prev_new['description'] = 'Rule added by proxies library';

        forwardings_update_apply($ssh_forwarding_prev_new, $ssh_forwarding_prev_old);

        /*
         * After applying and updating rules, we must update proxies chain
         */
        log_console(tr('Updating proxies chain'));
        servers_update_ssh_proxy($prev['id'], $next['id'], $insert['id']);

        /*
         * Creating new relation
         */
        load_libs('servers');
        servers_add_ssh_proxy($insert['id'], $next['id']);

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
        $next      = array();
        $prev      = array();

        if($on_chain){
            throw new bException(tr('proxies_insert(): Host ":insert_hostname" is already on the proxies chain', array(':insert_hostname' => $insert_hostname)), 'exists');
        }

        if($root['proxies'] and $protocols){
            throw new bException(tr('proxies_insert(): Protocols specified, but specified root server ":server" already has a proxy chain with its own protocols. Please do NOT specify protocols for this root server', array(':server' => $root_hostname)), 'invalid');
        }

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

        }else{
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
        $prev_forwardings = forwardings_list($prev['id']);

        if($prev_forwardings){
            /*
             * Updating source port
             */
            foreach($prev_forwardings as $forwarding){
                $new_forwarding = $forwarding;

                if($forwarding['protocol'] != 'ssh'){
                    $default_port = proxies_get_default_port($forwarding['protocol']);

                    $new_forwarding['apply']       = $apply;
                    $new_forwarding['source_port'] = $default_port;

                    /*
                     * Applying new rule with default ports
                     */
                    log_console(tr('Applying rule on prev server with default ports'));
                    forwardings_insert($new_forwarding);

                    /*
                     * Removing old rule :REVIEW: something is wrong, rule is  not been deleted from database
                     */
                    $forwarding['apply'] = true;
                    log_console(tr('Removing old rule'));
                    forwardings_delete($forwarding);
                }
            }
        }

        /*
         * Remove rules from removed server
         */
        log_console(tr('Removing all rules for removed server'));
        $remove_forwardings = forwardings_list($remove['id']);

        if($remove_forwardings){
            forwardings_delete_list($remove_forwardings, $apply);
        }

        /*
         * Updating proxies chaing on database
         */
        log_console(tr('Updating relation on database for proxy chain'));
        servers_delete_ssh_proxy($prev['id'], $remove['id']);

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
        $next_forwardings   = forwardings_list($next['id']);
        $remove_forwardings = forwardings_list($remove['id']);

        if(empty($next_forwardings)){
            throw new bException(tr('proxies_remove_middle(): There are not forwardings rules on next server'), 'invalid');
        }

        if(empty($remove_forwardings)){
            throw new bException(tr('proxies_remove_middle(): There are not forwardings rules on remove server'), 'invalid');
        }

        /*
         * Applying new rule on next server to start redirecting traffic to prev server
         */
        foreach($next_forwardings as $forwarding){
            $new_forwarding = $forwarding;

            foreach($remove_forwardings as $index => $remove_forwarding){
                if($remove_forwarding['protocol'] == $forwarding['protocol']){
                    $new_forwarding['apply']       = $apply;
                    $new_forwarding['target_id']   = $prev['id'];
                    $new_forwarding['target_ip']   = $prev['ipv4'];
                    $new_forwarding['target_port'] = $remove_forwarding['target_port'];
                    break;
                }
            }

            /*
             * Inserting and applying new rule on next server
             */
            log_console(tr('Appying new rule on next server'));
            forwardings_insert($new_forwarding);

        }

        /*
         * Delete forwardings rules on next server and removed server
         */
        log_console(tr('Removing rules on next server'));
        forwardings_delete_list($next_forwardings, $apply);

        /*
         * Delete forwarding fules from removed server
         */
        log_console(tr('Removing rules on removed server'));
        forwardings_delete_list($remove_forwardings, $apply);

        /*
         * Update proxies chain on database
         */
        servers_update_ssh_proxy($prev['id']  , $remove['id'], $next['id']);
        servers_delete_ssh_proxy($remove['id'], $next['id']);

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

        $root = proxies_get_server($root_host, true);

        if($remove_host === 'all'){
            /*
             * Flush all rules on iptables
             */
            foreach($root['proxies'] as $proxy){
                forwardings_destroy($proxy['id']);
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
 * Validate protocol in order to forwarding traffic, ssh is configured by default,
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

            default:
                throw new bException(tr('proxies_validate_protocol(): Unknown protocol "ssh", allow protocols: http, https, imap, smtp'), 'unknown');
        }

        return $protocol;

    }catch(Exception $e){
        throw new bException('proxies_validate_protocol(): Failed', $e);
    }
}
?>