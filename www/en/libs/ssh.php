<?php
/*
 * SSH library library
 *
 * This library contains functions to manage SSH accounts
 *
 * @copyright (c) 2018 Capmega
 * @author Sven Olaf Oostenbrink
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @return void
 */
function ssh_library_init(){
    try{
        load_config('ssh');

    }catch(Exception $e){
        throw new bException('ssh_library_init(): Failed', $e);
    }
}



/*
 * Executes the specified commands on the specified hostname. Supports passing through multiple SSH proxies
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $server
 * @params string hostname
 * @params string port (1 - 65535) [null]
 * @params string ssh_key alias for identity_file
 * @params string identity_file
 * @params string commands
 * @params string background
 * @params array proxies [null]
 * @param string $commands
 * @param boolean $background
 * @param string $function
 * @param string $ok_exitcodes
 * @return array The results of the executed SSH commands in an array, each entry containing one line of the output
 */
function ssh_exec($server, $commands = null, $background = false, $function = null, $ok_exitcodes = 0){
    global $core, $_CONFIG;

    try{
        if($function === null){
            $function = $_CONFIG['ssh']['function'];
        }

        /*
         * Ensure valid server variable
         * If $server is a string, the load server information from servers
         * table
         */
        if(!is_array($server)){
            if(!is_scalar($server)){
                throw new bException(tr('ssh_exec(): Invalid $server specified. $server must be either a hostname, or server array'), 'invalid');
            }

            load_libs('servers');
            return servers_exec($server, $commands, $background, $function, $ok_exitcodes);
        }

        array_default($server, 'hostname'     , null);
        array_default($server, 'identity_file', null);
        array_default($server, 'commands'     , $commands);
        array_default($server, 'background'   , $background);
        array_default($server, 'proxies'      , null);

        /*
         * If $server[hostname] is available without identity file, then load
         * server data from server table
         */
        if(empty($server['identity_file'])){
            load_libs('servers');
            return servers_exec($server, $commands, $background, $function, $ok_exitcodes);
        }

        /*
         * If no hostname is specified, then don't execute this command on a
         * remote server, just use safe_exec and execute it locally
         */
        if(!$server['hostname']){
            return safe_exec($server['commands'].($server['background'] ? ' &' : ''), $ok_exitcodes, true, $function);
        }

        /*
         * Build the SSH command
         * Execute the command
         */
        $command = ssh_build_command($server);
        $results = safe_exec($command, $ok_exitcodes, true, $function);

        /*
         * Remove SSH warning
         */
        if(!$server['background']){
            if(preg_match('/Warning: Permanently added \'\[.+?\]:\d{1,5}\' \(\w+\) to the list of known hosts\./', isset_get($results[0]))){
                /*
                 * Remove known host warning from results
                 */
                array_shift($results);
            }
        }

        if(!empty($server['tunnel'])){
            if(empty($server['tunnel']['persist'])){
                /*
                 * This SSH tunnel must be closed automatically once the script finishes
                 */
                log_file(tr('Created SSH tunnel ":source_port::target_hostname::target_port" to hostname ":hostname"', array(':hostname' => $server['hostname'], ':source_port' => $server['tunnel']['source_port'], ':target_hostname' => $server['tunnel']['target_hostname'], ':target_port' => $server['tunnel']['target_port'])));
                $core->register('shutdown_ssh_close_tunnel', $results);

            }else{
                log_file(tr('Created PERSISTENT SSH tunnel ":source_port::target_hostname::target_port" to hostname ":hostname"', array(':hostname' => $server['hostname'], ':source_port' => $server['tunnel']['source_port'], ':target_hostname' => $server['tunnel']['target_hostname'], ':target_port' => $server['tunnel']['target_port'])));
            }
        }

        return $results;

    }catch(Exception $e){
        switch($e->getCode()){
            case 'not-exist':
                break;

            default:
                $data = $e->getData();

                if($data){
                    foreach($data as $line){
                        /*
                         * SSH key authentication failed
                         */
                        if($line === 'Host key verification failed.'){
                            $e->setCode('host-verification-failed');
                        }
                    }
                }
        }

        /*
         * Check if SSH can connect to the specified server / port
         */
        load_libs('inet');
        inet_test_host_port($server['hostname'], $server['port'], true);

        /*
         * Remove "Permanently added host blah" error, even in this exception
         */
        notify($e);
        throw new bException('ssh_exec(): Failed', $e);
    }
}



/*
 * Returns SSH connection string for the specified SSH connection parameters. Supports multiple SSH proxy servers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $server The server parameters required to build the SSH connection string
 * @params numeric port [1 - 65535] The port number for the remote host to connect to
 * @params string log [filename]
 * @params boolean no_command
 * @params boolean background
 * @params boolean remote_connect
 * @params string tunnel [1 - 65535]>[1 - 65535]
 * @params string identity_file [filename]
 * @params array options
 * @return string The connection string
 */
function ssh_build_command(&$server = null, $ssh_command = 'ssh'){
    global $_CONFIG;

    try{
        /*
         * Validate minimum requirements
         */
        if(empty($server['hostname'])){
            throw new bException(tr('ssh_build_command(): No hostname specified'), 'not-specified');
        }

        if(empty($server['username'])){
            throw new bException(tr('ssh_build_command(): No username specified'), 'not-specified');
        }

        if(empty($server['identity_file'])){
            throw new bException(tr('ssh_build_command(): No identity_file specified'), 'not-specified');
        }

        /*
         * Get default SSH arguments and create basic SSH command with options
         */
        $server  = array_merge_null($_CONFIG['ssh']['arguments'], $server);
        $command = $ssh_command.ssh_build_options(isset_get($server['options']));

        /*
         * "tunnel" option requires (and automatically assumes) no_command, background, and remote_connect
         */
        if(!empty($server['tunnel'])){
//            $server['commands']        = 'true';
//            $server['goto_background'] = true;
            $server['background']      = true;
            $server['no_command']      = true;
            $server['remote_connect']  = true;
        }

        foreach($server as $parameter => &$value){
            switch($parameter){
                case 'options':
                    /*
                     * Options are processed in ssh_get_otions();
                     */
                    break;

                case 'port':
                    if($value){
                        if(!is_numeric($value) or ($value < 1) or ($value > 65535)){
                            if($value !== ':proxy_port'){
                                throw new bException(tr('ssh_build_command(): Specified port natural numeric value between 1 - 65535, but ":value" was specified', array(':value' => $value)), 'invalid');
                            }
                        }

                        switch($ssh_command){
                            case 'ssh':
                                // FALLTHROUGH
                            case 'autossh':
                                // FALLTHROUGH
                            case 'ssh-copy-id':
                                $command .= ' -p "'.$value.'"';
                                break;

                            case 'scp':
                                $command .= ' -P "'.$value.'"';
                                break;

                            default:
                                throw new bException(tr('ssh_build_command(): Unknown ssh command ":command" specified', array(':command' => $ssh_command)), 'command');
                        }
                    }

                    break;

                case 'log':
                    if($value){
                        if(!is_string($value)){
                            throw new bException(tr('ssh_build_command(): Specified option "log" requires string value containing the path to the identity file, but contains ":value"', array(':value' => $value)), 'invalid');
                        }

                        if(!file_exists($value)){
                            throw new bException(tr('ssh_build_command(): Specified log file directory ":path" does not exist', array(':file' => dirname($value))), 'not-exist');
                        }

                        $command .= ' -E "'.$value.'"';
                    }

                    break;

                case 'goto_background':
                    /*
                     * NOTE: This is not the same as shell background! This will execute SSH with one PID and then have it switch to an independant process with another PID!
                     */
                    if($value){
                        $command .= ' -f';
                    }

                    break;

                case 'remote_connect':
                    if($value){
                        $command .= ' -g';
                    }

                    break;

                case 'master':
                    if($value){
                        $command .= ' -M';
                    }

                    break;

                case 'no_command':
                    if($value){
                        $command .= ' -N';
                    }

                    break;

                case 'tunnel':
                    array_ensure ($value, 'source_port,target_port');
                    array_default($value, 'target_hostname', 'localhost');
                    array_default($value, 'persist'        , false);

                    if(!$value['persist'] and !empty($server['proxies'])){
                        throw new bException(tr('ssh_build_command(): A non persistent SSH tunnel with proxies was requested, but since SSH proxies will cause another SSH process with unknown PID, we will not be able to close them automatically. Use "persisten" for this tunnel or tunnel without proxies'), 'warning/invalid');
                    }

                    if(!is_natural($value['source_port']) or ($value['source_port'] > 65535)){
                        if(!$value['source_port']){
                            throw new bException(tr('ssh_build_command(): No source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                        }

                        throw new bException(tr('ssh_build_command(): Invalid source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                    }

                    if(!is_natural($value['target_port']) or ($value['target_port'] > 65535)){
                        if(!$value['target_port']){
                            throw new bException(tr('ssh_build_command(): No source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                        }

                        throw new bException(tr('ssh_build_command(): Invalid target_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                    }

                    if(!is_scalar($value['target_hostname']) or (strlen($value['target_hostname']) < 1) or (strlen($value['target_hostname']) > 253)){
                        if(!$value['target_hostname']){
                            throw new bException(tr('ssh_build_command(): No target_hostname specified for parameter "tunnel". Value should be the target hosts FQDN, IP, localhost, or host defined in the /etc/hosts of the target machine'), 'invalid');
                        }

                        throw new bException(tr('ssh_build_command(): Invalid target_hostname specified for parameter "tunnel". Value should be scalar, and between 1 and 253 characters'), 'invalid');
                    }

                    $command .= ' -L '.$value['source_port'].':'.$value['target_hostname'].':'.$value['target_port'];
                    break;

                case 'identity_file':
                    if($value){
                        if(!is_string($value)){
                            throw new bException(tr('ssh_build_command(): Specified option "identity_file" requires string value containing the path to the identity file, but contains ":value"', array(':value' => $value)), 'invalid');
                        }

                        if(!file_exists($value)){
                            throw new bException(tr('ssh_build_command(): Specified identity file ":file" does not exist', array(':file' => $value)), 'not-exist');
                        }

                        $command .= ' -i "'.$value.'"';
                    }

                    break;

                case 'proxies':
// :TODO: Right now its assumed that every proxy uses the same SSH user and key file, though in practice, they MIGHT have different ones. Add support for each proxy server having its own user and keyfile
                    if(!$value){
                        break;
                    }

                    /*
                     * $value IS REFERENCED, DO NOT USE IT DIRECTLY HERE!
                     */
                    $proxies = $value;

                    /*
                     * ssh command line ProxyCommand example: -o ProxyCommand="ssh -p  -o ProxyCommand=\"ssh -p  40220 s1.s.ingiga.com nc s2.s.ingiga.com 40220\"  40220 s2.s.ingiga.com nc s3.s.ingiga.com 40220"
                     * To connect to this server, one must pass through a number of SSH proxies
                     */
                    if($proxies === ':proxy_template'){
                        /*
                         * We're building a proxy_template command, which itself as proxy template has just the string ":proxy_template"
                         */
                        $command .= ' :proxy_template';

                    }else{
                        $template             = $server;
                        $template['hostname'] = ':proxy_host';
                        $template['port']     = ':proxy_port';
                        $template['commands'] = 'nc :target_hostname :target_port';
                        $template['proxies']  = ':proxy_template';

//'ssh '.$server['timeout'].$server['arguments'].' -i '.$identity_file.' -p :proxy_port :proxy_template '.$server['username'].'@:proxy_host nc :target_hostname :target_port';

                        $escapes        = 0;
                        $proxy_template = ' -o ProxyCommand="'.addslashes(ssh_build_command($template)).'" ';
                        $proxies_string = ':proxy_template';
                        $target_server  = $server['hostname'];
                        $target_port    = $server['port'];

                        foreach($proxies as $id => $proxy){
                            $proxy_string = $proxy_template;

                            for($escape = 0; $escape < $escapes; $escape++){
                                $proxy_string = addcslashes($proxy_string, '"\\');
                            }

                            /*
                             * Next proxy string needs more escapes
                             */
                            $escapes++;

                            /*
                             * Fill in proxy values for this proxy
                             */
                            $proxy_string   = str_replace(':proxy_port'     , $proxy['port']    , $proxy_string);
                            $proxy_string   = str_replace(':proxy_host'     , $proxy['hostname'], $proxy_string);
                            $proxy_string   = str_replace(':target_hostname', $target_server    , $proxy_string);
                            $proxy_string   = str_replace(':target_port'    , $target_port      , $proxy_string);
                            $proxies_string = str_replace(':proxy_template' , $proxy_string     , $proxies_string);

                            $target_server  = $proxy['hostname'];
                            $target_port    = $proxy['port'];

                            ssh_add_known_host($proxy['hostname'], $proxy['port']);
                        }

                        /*
                         * No more proxies, remove the template placeholder
                         */
                        $command .= str_replace(':proxy_template', '', $proxies_string);
                    }

                    break;

                case 'force_terminal':
                    if($value){
                        $command .= ' -t';
                    }

                    break;

                case 'disable_terminal':
                    if($value){
                        if(!empty($server['force_terminal'])){
                            throw new bException(tr('ssh_build_command(): Both "force_terminal" and "disable_terminal" were specified. These options are mutually exclusive, please use only one or the other'), 'invalid');
                        }

                        $command .= ' -T';
                    }

                    break;

                default:
                    /*
                     * Ignore any known parameter as specified $server list may contain parameters for other functions than the SSH library functions
                     */
            }

        }

        /*
         * Add the target server
         */
        $command .= ' "'.$server['username'].'@'.$server['hostname'].'"';

        if(isset_get($server['commands'])){
            $command .= ' "'.$server['commands'].'"';
        }

        if(isset_get($server['background'])){
            $command .= ' &';
        }

        return $command;

    }catch(Exception $e){
        throw new bException('ssh_build_command(): Failed', $e);
    }
}



/*
 * Returns SSH options string for the specified SSH options array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $options The SSH options to be used to build the options string
 * @return array The validated parameter data
 */
function ssh_build_options($options = null){
    global $_CONFIG;

    try{
        /*
         * Get options from  default configuration and specified options
         */
        if($options){
            $string  = '';
            $options = array_merge($_CONFIG['ssh']['options'], $options);

        }else{
            $string  = '';
            $options = $_CONFIG['ssh']['options'];
        }

        /*
         * Easy short cut to disable strict host key checks
         */
        if(isset($options['check_hostkey'])){
            if(!$options['check_hostkey']){
                $options['check_host_ip']            = false;
                $options['strict_host_key_checking'] = false;
            }

            unset($options['check_hostkey']);
        }

        /*
         * The known_hosts file for this user defaults to ROOT/data/ss/known_hosts
         */
        if(empty($options['user_known_hosts_file'])){
            $string .= ' -o UserKnownHostsFile="'.ROOT.'data/ssh/known_hosts"';

        }else{
            if($value){
                if(!is_string($value)){
                    throw new bException(tr('ssh_get_conect_string(): Specified option "user_known_hosts_file" requires a string value, but ":value" was specified', array(':value' => $value)), 'invalid');
                }

                $string .= ' -o UserKnownHostsFile="'.$value.'"';
            }

            unset($options['user_known_hosts_file']);
        }

        /*
         * Validate and apply each option
         */
        foreach($options as $option => $value){
            switch($option){
                case 'connect_timeout':
                    if($value){
                        if(!is_numeric($value)){
                            throw new bException(tr('ssh_get_conect_string(): Specified option "connect_timeout" requires a numeric value, but ":value" was specified', array(':value' => $value)), 'invalid');
                        }

                        $string .= ' -o ConnectTimeout="'.$value.'"';
                    }

                    break;

                case 'check_host_ip':
                    if(!is_bool($value)){
                        throw new bException(tr('ssh_get_conect_string(): Specified option "check_host_ip" requires a boolean value, but ":value" was specified', array(':value' => $value)), 'invalid');
                    }

                    $string .= ' -o CheckHostIP="'.get_yes_no($value).'"';
                    break;

                case 'strict_host_key_checking':
                    if(!is_bool($value)){
                        throw new bException(tr('ssh_get_conect_string(): Specified option "strict_host_key_checking" requires a boolean value, but ":value" was specified', array(':value' => $value)), 'invalid');
                    }

                    $string .= ' -o StrictHostKeyChecking="'.get_yes_no($value).'"';
                    break;

                default:
                    throw new bException(tr('ssh_build_options(): Unknown option ":option" specified', array(':option' => $option)), 'unknown');
            }
        }

        return $string;

    }catch(Exception $e){
        throw new bException('ssh_build_options(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_start_control_master($server, $socket = null){
    global $_CONFIG;

    try{
        load_libs('file');
        file_ensure_path(TMP);

        if(!$socket){
            $socket = file_temp();
        }

        if(ssh_get_control_master($socket)){
            return $socket;
        }

        $result = ssh_exec(array('hostname'  => $server['domain'],
                                 'port'      => $_CONFIG['cdn']['port'],
                                 'username'  => $server['username'],
                                 'ssh_key'   => ssh_get_key($server['username']),
                                 'arguments' => '-nNf -o ControlMaster=yes -o ControlPath='.$socket), ' 2>&1 >'.ROOT.'data/log/ssh_master');

        return $socket;

    }catch(Exception $e){
        throw new bException('ssh_start_control_master(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_get_control_master($socket = null){
    global $_CONFIG;

    try{
        $result = safe_exec('ps $(pgrep --full '.$socket.') | grep "ssh -nNf" | grep --invert-match pgrep', '0,1');
        $result = array_pop($result);

        preg_match_all('/^\s*\d+/', $result, $matches);

        $pid = array_pop($matches);
        $pid = (integer) array_pop($pid);

        return $pid;

    }catch(Exception $e){
        throw new bException('ssh_get_control_master(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_stop_control_master($socket = null){
    global $_CONFIG;

    try{
        $pid = ssh_get_control_master($socket);

        if(!posix_kill($pid, 15)){
            return posix_kill($pid, 9);
        }

        return true;

    }catch(Exception $e){
        throw new bException('ssh_stop_control_master(): Failed', $e);
    }
}



/*
 * SSH account validation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $ssh
 * @return array the specified $ssh array validated and clean
 */
function ssh_validate_account($ssh){
    try{
        load_libs('validate');

        $v = new validate_form($ssh, 'name,username,ssh_key,description');
        $v->isNotEmpty ($ssh['name'], tr('No account name specified'));
        $v->hasMinChars($ssh['name'], 2, tr('Please ensure the account name has at least 2 characters'));
        $v->hasMaxChars($ssh['name'], 32, tr('Please ensure the account name has less than 32 characters'));

        $v->isNotEmpty ($ssh['username'], tr('No user name specified'));
        $v->hasMinChars($ssh['username'], 2, tr('Please ensure the user name has at least 2 characters'));
        $v->hasMaxChars($ssh['username'], 32, tr('Please ensure the user name has less than 32 characters'));

        $v->isNotEmpty ($ssh['ssh_key'], tr('No SSH key specified to the account'));

        $v->isNotEmpty ($ssh['description'], tr('No description specified'));
        $v->hasMinChars($ssh['description'], 2, tr('Please ensure the description has at least 2 characters'));

        if(is_numeric(substr($ssh['name'], 0, 1))){
            $v->setError(tr('Please ensure that the account name does not start with a number'));
        }

        $v->isValid();

        return $ssh;

    }catch(Exception $e){
        throw new bException(tr('ssh_validate_account(): Failed'), $e);
    }
}



/*
 * Returns SSH account data for the specified SSH accounts id
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param numeric $accounts_id The table ID for the account
 * @return array The account data for the specified $accounts_id
 */
function ssh_get_account($accounts_id){
    try{
        if(!$accounts_id){
            throw new bException(tr('ssh_get_account(): No accounts id specified'), 'not-specified');
        }

        if(!is_numeric($accounts_id)){
            throw new bException(tr('ssh_get_account(): Specified accounts id ":id" is not numeric', array(':id' => $accounts_id)), 'invalid');
        }

        $retval = sql_get('SELECT    `ssh_accounts`.`id`,
                                     `ssh_accounts`.`createdon`,
                                     `ssh_accounts`.`modifiedon`,
                                     `ssh_accounts`.`name`,
                                     `ssh_accounts`.`username`,
                                     `ssh_accounts`.`ssh_key`,
                                     `ssh_accounts`.`status`,
                                     `ssh_accounts`.`description`,

                                     `createdby`.`name`   AS `createdby_name`,
                                     `createdby`.`email`  AS `createdby_email`,
                                     `modifiedby`.`name`  AS `modifiedby_name`,
                                     `modifiedby`.`email` AS `modifiedby_email`

                           FROM      `ssh_accounts`

                           LEFT JOIN `users` AS `createdby`
                           ON        `ssh_accounts`.`createdby`  = `createdby`.`id`

                           LEFT JOIN `users` AS `modifiedby`
                           ON        `ssh_accounts`.`modifiedby` = `modifiedby`.`id`

                           WHERE     `ssh_accounts`.`id`         = :id',

                           array(':id' => $accounts_id));

        return $retval;

    }catch(Exception $e){
        throw new bException('ssh_get_account(): Failed', $e);
    }
}



/*
 *
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_add_known_host($hostname, $port){
    try{
        if(empty($hostname)){
            throw new bException(tr('ssh_add_known_host(): No hostname specified'), 'not-specified');
        }

        if(empty($port)){
            throw new bException(tr('ssh_add_known_host(): No port specified'), 'not-specified');
        }

        load_libs('file');
        file_ensure_path(ROOT.'data/ssh/keys/', 0750);
        file_ensure_file(ROOT.'data/ssh/known_hosts', 0640);

        $hashes = safe_exec('ssh-keyscan -p '.$port.' -H '.$hostname);
        $count  = 0;

        if(empty($hashes)){
            throw new bException(tr('ssh_add_known_host(): ssh-keyscan found no public keys for hostname ":hostname"', array(':hostname' => $hostname)), 'not-found');
        }

        foreach($hashes as $hash){
            if(substr($hash, 0, 1) != '#'){
                /*
                 * Check if the hash already exists
                 */
                $test   = str_until($hash, ' ');
                $exists = safe_exec('grep "'.$test.'" '.ROOT.'data/ssh/known_hosts', '0,1');

                if(!$exists){
                    log_console(tr('Adding hash ":hash" for hostname ":hostname" to known_hosts', array(':hash' => $hash, ':hostname' => $hostname)), 'VERBOSE');
                    file_put_contents(ROOT.'data/ssh/known_hosts', $hash."\n", FILE_APPEND);
                    $count++;

                }else{
                    log_console(tr('Skipping hash ":hash" for hostname ":hostname", it already exists in known_hosts', array(':hash' => $hash, ':hostname' => $hostname)), 'VERYVERBOSE');
                }
            }
        }

        sql_query('UPDATE `servers` SET `status` = NULL WHERE `hostname` = :hostname', array(':hostname' => $hostname));

        return $count;

    }catch(Exception $e){
        throw new bException('ssh_add_known_host(): Failed', $e);
    }
}



///*
// *
// *
// * @Sven Olaf Oostenbrink <sven@capmega.com>
// * @copyright Copyright (c) 2018 Capmega
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package ssh
// *
// * @param
// */
//function ssh_read_server_config($server){
//    try{
//        $retval = array();
//        $config = ssh_exec($server, 'cat /etc/ssh/sshd_config');
//
//        foreach($config as $line){
//            $key    = str_until($line, ' ');
//            $values = str_from($line, ' ');
//
//            $retval[$key] = $config;
//        }
//
//        return $retval;
//
//    }catch(Exception $e){
//        throw new bException('ssh_read_server_config(): Failed', $e);
//    }
//}
//
//
//
///*
// *
// *
// * @Sven Olaf Oostenbrink <sven@capmega.com>
// * @copyright Copyright (c) 2018 Capmega
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package ssh
// *
// * @param
// */
//function ssh_write_server_config($server, $config){
//    try{
//        foreach($config as $key => $value){
//            $data = $key.' '.$value."\n";
//        }
//
//        ssh_exec($server, 'sudo cat > /etc/ssh/sshd_config << EOF '.$data);
//
//    }catch(Exception $e){
//        throw new bException('ssh_write_server_config(): Failed', $e);
//    }
//}
//
//
//
///*
// * Validate sshd_config data
// *
// * @Sven Olaf Oostenbrink <sven@capmega.com>
// * @copyright Copyright (c) 2018 Capmega
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package ssh
// *
// * @param array $entries
// * @return array The validated data
// */
//function ssh_validate_server_config($entries){
//    try{
//// :TODO: Implement
//
//        return $entries;
//
//    }catch(Exception $e){
//        throw new bException('ssh_validate_server_config(): Failed', $e);
//    }
//}
//
//
//
///*
// *
// *
// * @Sven Olaf Oostenbrink <sven@capmega.com>
// * @copyright Copyright (c) 2018 Capmega
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package ssh
// *
// * @param string $hostname
// * @param array $config
// */
//function ssh_update_config($hostname, $config){
//    try{
//        $config        = ssh_validate_server_config($config);
//        $server_config = ssh_read_server_config($hostname);
//
//        foreach($config as $key => $values){
//// :TODO: Just WTF was this in the first place?
//            //$comments = '';
//            //
//            //if(isset($values['description'])){
//            //    $comments = '#'.$values['description']."\n";
//            //}
//            //
//            //$server_config[$key] = preg_replace('/'.$key.'\s+(\d+|\w+)|#'.$key.'\s+(\d+|\w+)/', $comments.$key." ".$values, $values);
//            $server_config[$key] = $values;
//        }
//
//        ssh_write_server_config($hostname, $server_config);
//
//        return $server_config;
//
//    }catch(Exception $e){
//        throw new bException('ssh_update_config(): Failed', $e);
//    }
//}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $source
 * @param array $destnation
 * @return
 */
function ssh_cp($source, $target, $options = null){
    try{
under_construction();
        /*
         * If server was specified by just name, then lookup the server data in
         * the database
         */
        if(is_string($source)){
            /*
             * This source is a server specified by string with the source path in there.
             */
// :TODO: This may fail with files containing :
            if(strstr(':', $source)){
                $path   = str_from ($source, ':');
                $source = str_until($source, ':');
                $server = sql_get('SELECT    `ssh_accounts`.`username`,
                                             `ssh_accounts`.`ssh_key`,
                                             `servers`.`id`,
                                             `servers`.`hostname`,
                                             `servers`.`port`

                                   FROM      `servers`

                                   LEFT JOIN `ssh_accounts`
                                   ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                   WHERE     `servers`.`hostname` = :hostname',

                                   array(':hostname' => $source));

                if(!$server){
                    throw new bException(tr('ssh_cp(): Specified server ":server" does not exist', array(':server' => $source)), 'not-exist');
                }

                $source         = $server;
                $target['path'] = $path;
            }

        }else{
            /*
             * This source is a server
             */
            array_ensure($source, 'server,hostname,ssh_key,port,check_hostkey,arguments,path');
        }

        if(is_string($target)){
// :TODO: This may fail with files containing :
            if(strstr(':', $target)){
                if(is_array($source)){
                    throw new bException(tr('ssh_cp(): Specified source ":source" and target ":target" are both servers. This function can only copy from local to server or server to local', array(':source' => $source, ':target' => $target, )), 'invalid');
                }

                $path   = str_from ($target, ':');
                $target = str_until($target, ':');
                $server = sql_get('SELECT    `ssh_accounts`.`username`,
                                               `ssh_accounts`.`ssh_key`,
                                               `servers`.`id`,
                                               `servers`.`hostname`,
                                               `servers`.`port`

                                     FROM      `servers`

                                     LEFT JOIN `ssh_accounts`
                                     ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                     WHERE     `servers`.`hostname` = :hostname',

                                     array(':hostname' => $target));

                if(!$server){
                    throw new bException(tr('ssh_cp(): Specified target server ":server" does not exist', array(':server' => $target)), 'not-exist');
                }

                $target         = $server;
                $target['path'] = $path;
            }

        }else{
            array_ensure($target, 'server,hostname,ssh_key,port,check_hostkey,arguments');
        }

        $server = array('options' => $options);
        ssh_build_command($server, 'scp');

        if($options){

        }

        if(!$server['check_hostkey']){
            $server['arguments'] .= ' -o StrictHostKeyChecking=no -o UserKnownHostsFile='.ROOT.'data/ssh/known_hosts ';
        }


        /*
         * ????
         */
        if($from_server){
            $command = $server['username'].'@'.$server['hostname'].':'.$source.' '.$destnation;

        }else{
            $command = $source.' '.$server['username'].'@'.$server['hostname'].':'.$destnation;
        }

        /*
         * Execute command
         */
        return safe_exec('scp '.$server['arguments'].' -P '.$server['port'].' -i '.$identity_file.' '.$command.'');

    }catch(Exception $e){
        notify($e);

        /*
         * Try deleting the keyfile anyway!
         */
        try{
            ssh_remove_identity_file(isset_get($identity_file));

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify($e);
        }

        throw new bException(tr('ssh_cp(): Failed'), $e);
    }
}



/*
 * Set up an SSH tunnel
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @exception Throws an exception if the ssh command does not return exit code 0
 *
 * @param array $params
 * @params string $hostname The hostname where SSH should connect to
 * @params string $local_port The port on the local server where SSH should listen to 1-65535
 * @params string $remote_port The port on the remote server where SSH should connect to 1-65535
 * @params array $options The required SSH options
 * @return void
 */
function ssh_tunnel($params){
    try{
        array_ensure ($params, 'hostname,source_port,target_port');
        array_default($params, 'tunnel', 'localhost');

        /*
         * Ensure port is available.
         */
        $in_use = safe_exec('netstat -lnt | grep 127.0.0.1 | grep '.$params['source_port'], '0,1');

        if($in_use){
            throw new bException(tr('ssh_tunnel(): Source port ":port" is already in use', array(':port' => $params['source_port'])), 'not-available');
        }

        $params['tunnel'] = array('source_port'     => $params['source_port'],
                                  'target_hostname' => $params['target_hostname'],
                                  'target_port'     => $params['target_port']);

        $retval = ssh_exec($params, null, false, 'exec');
        log_console(tr('Created SSH tunnel ":source_port::target_hostname::target_port', array(':source_port' => $params['source_port'], ':target_hostname' => $params['target_hostname'], ':target_port' => $params['target_port'])), 'VERYVERBOSE');

        return $retval;

    }catch(Exception $e){
        throw new bException('ssh_tunnel(): Failed', $e);
    }
}



/*
 * Close SSH tunnel with the specified PID
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param numeric $pid
 * @return void
 */
function ssh_close_tunnel($pid){
    try{
        load_libs('cli');
        cli_kill($pid);

    }catch(Exception $e){
        throw new bException('ssh_close_tunnel(): Failed', $e);
    }
}
?>