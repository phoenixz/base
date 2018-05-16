<?php
/*
 * CSF library
 *
 * This is a front-end library to the Config Server Firewall
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * On the command line we can only run this as root user
 */
if(PLATFORM_CLI){
    cli_root_only();
}

define('CSF_ALLOW_FILE',  '/etc/csf/csf.allow');
define('CSF_DENY_FILE',   '/etc/csf/csf.deny');
define('CSF_CONF_FILE',   '/etc/csf/csf.conf');

load_libs('servers,ssh');

/*
 * Return the absolute location of the CSF executable binary
 */
function csf_get_exec(){
    try{
        return trim(shell_exec('which csf 2> /dev/null'));

    }catch(Exception $e){
        throw new bException('csf_get_exec(): Failed', $e);
    }
}



/*
 * Install Config Server Firewall
 */
function csf_install(){
    try{
        if($csf = csf_get_exec()){
            throw new bException('csf_install(): CSF has already been installed and is available from "'.str_log($csf).'"', 'executablenotfound');
        }
        //old url not working: http://configserver.com/free/csf.tgz
        copy('https://download.configserver.com/csf.tgz', TMP.'csf.tgz');
        safe_exec('cd '.TMP.'; tar -xf '.TMP.'csf.tgz; cd '.TMP.'csf/; ./install.sh');

        if(!$csf = csf_get_exec()){
            throw new bException('csf_install(): The CSF executable could not be found after installation', 'executablenotfound');
        }

        /*
         * Cleanup
         */
        safe_exec('rm '.TMP.'csf/ -rf');

        return $csf;

    }catch(Exception $e){
        throw new bException('csf_install(): Failed', $e);
    }
}

/**
* Accepted protocols tcp, udp
* @param string|array $ports, if $ports is a string it must be separeted by commas example: 12,80,443
*/
function csf_set_ports($hostname, $protocol, $type, $ports, $local=false){

    if($csf = csf_get_exec()){
        if(empty($hostname)){
            throw new bException(tr('csf_set_ports(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'not-specified');
        }

        if(csf_is_valid_protocol($protocol)){
            $protocol = strtoupper($protocol);
        }

        if(empty($ports)){
            throw new bException(tr('csf_set_ports(): Unknown ports ":ports" specified', array(':ports' => $ports)), 'not-specified');
        }

        preg_match("/(\d+,|\d+)+\d+\z/", $ports, $matches);
        if(empty($matches)){
            throw new bException(tr('csf_set_ports(): Unknown ports ":ports" specified', array(':ports' => $ports)), 'not-specified');
        }

        if(empty($type) or !in_array($type, array('in', 'out'))){
            throw new bException(tr('csf_set_ports(): Unknown type ":type" specified, Types available in,out', array(':type' => $type)), 'not-specified');
        }

        if(is_array($ports)){
            $ports = implode(",", $ports);
        }

        $server  = servers_get('*'.$hostname);

        $protocol_type = $protocol.'_'.strtoupper($type);

        $command = 'sed -i -E \'s/^'.$protocol_type.' = \"([0-9]+,)*([0-9]*)\"/'.$protocol_type.' = "'.$ports.'"/g\' '.CSF_CONF_FILE;

        return ssh_exec($server, $command, $local);
    }else{
        throw new bException('csf_set_ports(): CSF is not installed', 'executablenotfound');
    }
}

function csf_allow_ip($hostname, $ip, $local=false){

    if($csf = csf_get_exec()){
        if(empty($hostname)){
            throw new bException(tr('csf_allow_ip(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'not-specified');
        }

        if(filter_var($ip, FILTER_VALIDATE_IP) === false){
            throw new bException(tr('csf_allow_ip(): Unknown ip ":ip", the ip is not valid', array(':ip' => $ip)), 'not-valid');
        }

        $server = servers_get('*'.$hostname);

        return ssh_exec($server, 'csf -dr '.$ip.'; csf -a '.$ip, $local);
    }else{
        throw new bException('csf_allow_ip(): CSF is not installed', 'executablenotfound');
    }
}

function csf_deny_ip($hostname, $ip, $local=false){

    if($csf = csf_get_exec()){
        if(empty($hostname)){
            throw new bException(tr('csf_deny_ip(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'not-specified');
        }

        if(filter_var($ip, FILTER_VALIDATE_IP) === false){
            throw new bException(tr('csf_deny_ip(): Unknown ip ":ip", the ip is not valid', array(':ip' => $ip)), 'not-valid');
        }

        $server = servers_get('*'.$hostname);

        return ssh_exec($server, 'csf -ar '.$ip.'; csf -d '.$ip, $local);
    }else{
        throw new bException('csf_deny_ip(): CSF is not installed', 'executablenotfound');
    }
}

function csf_start($hostname, $local=false){

    if($csf = csf_get_exec()){

        if(empty($hostname)){
            throw new bException(tr('csf_start(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'not-specified');
        }

        $server = servers_get('*'.$hostname);

        return ssh_exec($server, 'csf -s', $local);
    }else{
        throw new bException('csf_start(): CSF is not installed', 'executablenotfound');
    }
}

function csf_stop($hostname, $local=false){

    if($csf = csf_get_exec()){

        if(empty($hostname)){
            throw new bException(tr('csf_stop(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'not-specified');
        }

        $server = servers_get('*'.$hostname);

        return ssh_exec($server, 'csf -f', $local);
    }else{
        throw new bException('csf_stop(): CSF is not installed', 'executablenotfound');
    }
}

function csf_restart($hostname, $local=false){

    if($csf = csf_get_exec()){

        if(empty($hostname)){
            throw new bException(tr('csf_restart(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'not-specified');
        }

        $server = servers_get('*'.$hostname);

        return ssh_exec($server, 'csf -r', $local);
    }else{
        throw new bException('csf_restart(): CSF is not installed', 'executablenotfound');
    }
}

/**
* when adding a new rule we need to check if exist on deny rule and remove in
* order to create the new one
*/
function csf_allow_rule($hostname, $protocol, $rule_type, $port, $ip, $local=false){

    if($csf = csf_get_exec()){

        if(empty($hostname)){
            throw new bException(tr('csf_allow_rule(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'not-specified');
        }

        if(csf_is_valid_protocol($protocol)){
            $protocol = strtolower($protocol);
        }else{
            throw new bException(tr('csf_allow_rule(): Unknown protocol ":protocol" specified', array(':protocol' => $protocol)), 'not-specified');
        }

        if(csf_is_valid_type_of_rule($rule_type)){
            $rule_type = strtolower($rule_type);
        }else{
            throw new bException(tr('csf_allow_rule(): Unknown rule type ":rule_type" specified', array(':rule_type' => $rule_type)), 'not-specified');
        }

        if(empty($port)){
            throw new bException(tr('csf_allow_rule(): Unknown port ":port" specified', array(':port' => $port)), 'not-specified');
        }

        if(filter_var($ip, FILTER_VALIDATE_IP) === false){
            throw new bException(tr('csf_allow_rule(): Unknown ip ":ip", the ip is not valid', array(':ip' => $ip)), 'not-valid');
        }

        $server = servers_get('*'.$hostname);

        $rule    = $protocol.'|'.$rule_type.'|d='.$port.'|s='.$ip;
        $command = 'if ! grep "'.$rule.'" '.CSF_ALLOW_FILE.'; then echo "'.$rule.'" >> '.CSF_ALLOW_FILE.'; fi;';

        return ssh_exec($server, $command, $local);
    }else{
        throw new bException('csf_allow_rule(): CSF is not installed', 'executablenotfound');
    }
}

/**
* when adding a new rule we need to check if exist on allow rule and remove in
* order to create the new one
*/
function csf_deny_rule($hostname, $protocol, $rule_type, $port, $ip, $local=false){

   if($csf = csf_get_exec()){

        if(empty($hostname)){
            throw new bException(tr('csf_allow_rule(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'not-specified');
        }

        if(csf_is_valid_protocol($protocol)){
            $protocol = strtolower($protocol);
        }else{
            throw new bException(tr('csf_allow_rule(): Unknown protocol ":protocol" specified', array(':protocol' => $protocol)), 'not-specified');
        }

        if(csf_is_valid_type_of_rule($rule_type)){
            $rule_type = strtolower($rule_type);
        }else{
            throw new bException(tr('csf_allow_rule(): Unknown rule type ":rule_type" specified', array(':rule_type' => $rule_type)), 'not-specified');
        }

        if(empty($port)){
            throw new bException(tr('csf_allow_rule(): Unknown port ":port" specified', array(':port' => $port)), 'not-specified');
        }

        if(filter_var($ip, FILTER_VALIDATE_IP) === false){
            throw new bException(tr('csf_allow_rule(): Unknown ip ":ip", the ip is not valid', array(':ip' => $ip)), 'not-valid');
        }

        $server = servers_get('*'.$hostname);

        $rule    = $protocol.'|'.$rule_type.'|d='.$port.'|s='.$ip;
        $command = 'if ! grep "'.$rule.'" '.CSF_DENY_FILE.'; then echo "'.$rule.'" >> '.CSF_DENY_FILE.'; fi;';

        return ssh_exec($server, $command, $local);
    }else{
        throw new bException('csf_allow_rule(): CSF is not installed', 'executablenotfound');
    }
}

function csf_is_valid_protocol($protocol){

    if(empty($protocol)){
        throw new bException(tr('csf_is_valid_protocol(): Unknown protocol ":protocol" specified', array(':protocol' => $protocol)), 'not-specified');
    }
    return in_array(strtolower($protocol), array('tcp', 'udp'));
}

function csf_is_valid_type_of_rule($rule_type){

    if(empty($rule_type)){
        throw new bException(tr('csf_is_valid_type_of_rule(): Unknown rule type ":rule_type" specified', array(':rule_type' => $rule_type)), 'not-specified');
    }

    return in_array(strtolower($rule_type), array('in', 'out'));
}
?>