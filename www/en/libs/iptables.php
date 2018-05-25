<?php
/*
 * Route library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Capmega <copyright@capmega.com>
 */



 /*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function iptables_library_init(){
    try{
        load_libs('servers');

    }catch(Exception $e){
        throw new bException('iptables_library_init(): Failed', $e);
    }
}



/*
 * Execute iptables with the specified parameters
 * @param string $hostname The unique name or id of the host where to execute the iptables command
 * @param string $parameters
 * @return mixed The output of servers_exec() for the specified hostname with the specified parameters
 */
function iptables_exec($hostname, $parameters){
    try{
        return servers_exec($hostname, 'sudo iptables '.$parameters);

    }catch(Exception $e){
        throw new bException('iptables_exec(): Failed', $e);
    }
}



/*
 * ..............
 *
 * @param
 */
function iptables_add($hostname, $chain_type, $protocol, $destination_ip, $destination_port, $origin_port = null){
    try{
        $chain_type = iptables_validate_chain_type($chain_type);

        switch($chain_type){
            case 'prerouting':
                iptables_add_prerouting($hostname, $protocol, $origin_port, $destination_port, $destination_ip);
                break;

            case 'postrouting':
                iptables_add_postrouting($hostname, $protocol, $destination_port, $destination_ip);
                break;

            default:
                throw new bException(tr('iptables_add(): Unknown chain type ":chaintype" specified', array(':chaintype' => $chain_type)), 'unknown');

        }

    }catch(Exception $e){
        throw new bException('iptables_add(): Failed', $e);
    }
}



/*
 * ..............
 *
 * @example usage iptables -t nat -A PREROUTING -p tcp --dport 80 -j DNAT --to-destination 255.255.255.255:401
 * Sets a new iptables rulte for port forwarding
 * @param string $hostname
 * @param string $protocol
 * @param integer $origin_port
 * @param integer $destination_port
 * @param string $destination_ip
 */
function iptables_add_prerouting($hostname, $protocol, $origin_port, $destination_port, $destination_ip){
    try{
        $protocol         = iptables_validate_protocol($protocol);
        $origin_port      = iptables_validate_port($origin_port);
        $destination_port = iptables_validate_port($destination_port);
        $destination_ip   = iptables_validate_ip($destination_ip);

        iptables_exec($hostname, '-t nat -A PREROUTING -p tcp --dport '.$origin_port.' -j DNAT --to-destination '.$destination_ip.':'.$destination_port);

    }catch(Exception $e){
        throw new bException('iptables_add_prerouting(): Failed', $e);
    }
}



/*
 * ...............
 *
 * @example usage iptables -t nat -A POSTROUTING -p tcp -d 255.210.102.105 --dport 40001 -j SNAT --to-source 255.255.255.255
 * @param string $hostname
 * @param string $protocol
 * @param integer $port
 * @param string $destination_ip
 */
function iptables_add_postrouting($hostname, $protocol, $port, $destination_ip){
    try{
        $protocol       = iptables_validate_protocol($protocol);
        $port           = iptables_validate_port($port);
        $destination_ip = iptables_validate_ip($destination_ip);
        $public_ip      = servers_get_ip($hostname);

        iptables_exec($hostname, '-t nat -A POSTROUTING -p tcp -d '.$destination_ip.' --dport '.$port.' -j SNAT --to-source '.$public_ip);

    }catch(Exception $e){
        throw new bException('iptables_add_postrouting(): Failed', $e);
    }
}



/*
 * @param string $hostname
 */
function iptables_flush_all($hostname){
    try{
        iptables_exec($hostname, '-F');

    }catch(Exception $e){
        throw new bException('iptables_flush_all(): Failed', $e);
    }
}



/*
 * ................
 *
 * @example usage iptables -t nat -F
 * @param string $hostname
 * @see iptables_clean_nat_chain()
 */
function iptables_clean_chain_nat($hostname){
    try{
        iptables_exec($hostname, '-t nat -F');

    }catch(Exception $e){
        throw new bException('iptables_clean_chain_nat(): Failed', $e);
    }
}



/*
 * .............
 *
 * @param string $hostname
 * @see iptables_delete_all()
 */
function iptables_delete_all($hostname){
    try{
        iptables_exec($hostname, '-X');

    }catch(Exception $e){
        throw new bException('iptables_delete_all(): Failed', $e);
    }
}



/*
 * ............
 *
 * @param string $ip
 * @return string $ip
 * @see iptables_validate_port()
 * @see iptables_validate_protocol()
 * @see iptables_validate_chain_type()
 */
 function iptables_validate_ip($ip){
    try{
        if(filter_var($ip, FILTER_VALIDATE_IP) === false){
            throw new bException(tr('iptables_validate_ip(): Specified ip ":ip" is not valid', array(':ip' => $ip)), 'invalid');
        }

        return $ip;

    }catch(Exception $e){
        throw new bException('iptables_validate_ip(): Failed', $e);
    }
}



/*
 * .......
 *
 * @param string $protocol
 * @return string $protocol
 * @see iptables_validate_protocol()
 */
function iptables_validate_protocol($protocol){
    try{
        if(empty($protocol)){
            throw new bException(tr('iptables_validate_protocol(): No protocol specified'), 'not-specified');
        }

        $protocol = strtolower($protocol);

        switch($protocol){
            case 'tcp':
                // FALLTHROUGH
            case 'udp':
                // FALLTHROUGH
            case 'icmp':
                // FALLTHROUGH
                break;

            default:
                throw new bException(tr('iptables_validate_protocol(): Unknown protocol ":protocol" specified', array(':protocol' => $protocol)), 'unknown');
        }

        return $protocol;

    }catch(Exception $e){
        throw new bException('iptables_validate_protocol(): Failed', $e);
    }
}



/*
 * .......
 *
 * @param integer $port
 * @return integer $port
 * @see iptables_validate_protocol()
 * @see iptables_validate_chain_type()
 */
function iptables_validate_port($port){
    try{
        if(empty($port)){
            throw new bException(tr('iptables_validate_port(): No port specified'), 'not-specified');
        }

        if(!is_natural($port) or ($port > 65535)){
            throw new bException(tr('iptables_validate_port(): Invalid port ":port" specified', array(':port' => $port)), 'invalid');
        }

        return $port;

    }catch(Exception $e){
        throw new bException('iptables_validate_port(): Failed', $e);
    }
}



/*
 * ...........
 *
 * @param string $chain_type, available type = prerouting, postrouting
 * @return string $chain_type
 * @see iptables_validate_chain_port()
 * @see iptables_validate_chain_protocol()
 */
function iptables_validate_chain_type($chain_type){
    try{
        if(empty($chain_type)){
            throw new bException(tr('iptables_validate_chain_type(): No chain type specified'), 'not-specified');
        }

        $chain_type = strtolower($chain_type);

        switch($chain_type){
            case 'prerouting':
                // FALLTHROUGH
            case 'postrouting':
                //valid chaing types
                break;

            default:
                throw new bException(tr('iptables_validate_chain_type(): Unknown chain type ":chaintype" specified', array(':chaintype' => $chain_type)), 'unknown');
        }

        return $chain_type;

    }catch(Exception $e){
        throw new bException('iptables_validate_chain_type(): Failed', $e);
    }
}
?>