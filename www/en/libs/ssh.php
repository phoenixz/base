<?php
/*
 * SSH library library
 *
 * This library contains functions to manage SSH accounts
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package ssh
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
 * Executes the specified commands on the specified domain. Supports passing through multiple SSH proxies
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $server
 * @params string domain
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
    static $retry = 0;

    try{
        if($retry > 1){
            throw new bException(tr('ssh_exec(): Found command ":command" retried ":retry" times, command failed', array(':command' => $commands, ':retry' => $retry)), 'failed');
        }

        if($function === null){
            $function = $_CONFIG['ssh']['function'];
        }

        /*
         * Ensure valid server variable
         * If $server is a string, the load server information from servers
         * table
         */
        if(!is_array($server) or (empty($server['identity_file']))){
            if(!is_array($server) and !is_scalar($server)){
                throw new bException(tr('ssh_exec(): Invalid $server specified. $server must be either a domain, or server array'), 'invalid');
            }

            $retry = 0;

            load_libs('servers');
            return servers_exec($server, $commands, $background, $function, $ok_exitcodes);
        }

        array_default($server, 'domain'       , null);
        array_default($server, 'identity_file', null);
        array_default($server, 'commands'     , $commands);
        array_default($server, 'background'   , $background);
        array_default($server, 'proxies'      , null);

        /*
         * If $server[domain] is available without identity file, then load
         * server data from server table
         */
        if(empty($server['identity_file'])){
            $retry = 0;

            load_libs('servers');
            return servers_exec($server, $commands, $background, $function, $ok_exitcodes);
        }

        /*
         * If no domain is specified, then don't execute this command on a
         * remote server, just use safe_exec and execute it locally
         */
        if(!$server['domain']){
            $retry = 0;
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
// :TODO: Add persist timeouts, so that these SSL tunnels can still be closed after X amount of time
                /*
                 * This SSH tunnel must be closed automatically once the script finishes
                 */
                log_console(tr('Created SSH tunnel ":source_port::target_domain::target_port" to domain ":domain"', array(':domain' => $server['domain'], ':source_port' => $server['tunnel']['source_port'], ':target_domain' => $server['tunnel']['target_domain'], ':target_port' => $server['tunnel']['target_port'])));
                $core->register('shutdown_ssh_close_tunnel', $results);

            }else{

                log_console(tr('Created PERSISTENT SSH tunnel ":source_port::target_domain::target_port" to domain ":domain"', array(':domain' => $server['domain'], ':source_port' => $server['tunnel']['source_port'], ':target_domain' => $server['tunnel']['target_domain'], ':target_port' => $server['tunnel']['target_port'])));
            }
        }

        $retry = 0;
        return $results;

    }catch(Exception $e){
        try{
            switch($e->getRealCode()){
                case 'not-exist':
                    // FALLTHROUGH
                case 'invalid':
                    break;

                default:
                    $data = $e->getData();

                    if($data){
                        foreach($data as $line){
                            /*
                             * SSH key authentication failed
                             */
                            if($line === 'Host key verification failed.'){
                                $e = new bException(tr('ssh_exec(): The host ":domain" SSH key fingerprint does not match any of the available finger prints in the ROOT/data/ssh/known_hosts file. This means somegbody is faking this server, or the server was reinstalled', array(':domain' => $server['domain'])), 'host-verification-failed');
                                $not_check_inet = true;

                                foreach($data as $line){
                                    /*
                                     * Did key authentication fail because of
                                     * missing fingerprint or because it didn't
                                     * match?
                                     */
                                    if(preg_match('/No [a-z0-9-]+ host key is known for/i', $line)){
                                        /*
                                         * It's only missing, so we only have to
                                         * add it
                                         */
                                        $e = new bException(tr('ssh_exec(): The host ":domain" has no SSH key fingerprint in the known_hosts file.', array(':domain' => $server['domain'])), 'warning/host-verification-missing');

                                        /*
                                         * Is the server fingerprint perhaps already
                                         * available in the `ssh_fingerprints`
                                         * table? If so, we can rebuild the
                                         * known_hosts file
                                         */
                                        $exists = sql_get('SELECT `id` FROM `ssh_fingerprints` WHERE `domain` = :domain AND `port` = :port LIMIT 1', true, array(':domain' => $server['domain'], ':port' => $server['port']));

                                        if($exists){
                                            /*
                                             * Hostname fingerprints are available
                                             * in ssh_fingerprints. Rebuild the
                                             * known_hosts file, and retry command
                                             */
                                            log_console(tr('The host ":domain" has no SSH key fingerprint in the ROOT/data/ssh/known_hosts file, but the keys were found in the ssh_fingerprints table. Rebuilding known_hosts file and retrying execution', array(':domain' => $server['domain'])), 'yellow');
                                            ssh_rebuild_known_hosts();
                                            return ssh_exec($server, $commands, $background, $function, $ok_exitcodes);
                                        }


                                        /*
                                         * Host fingerprints are not available, fail
                                         * with a warning
                                         */
                                        break;
                                    }
                                }

                                /*
                                 * Host fingerprint matching failed. The fingerprint
                                 * from the host did not match the registered
                                 * entries.
                                 */
                                break;

                            }else{
                                /*
                                 * Search for other known errors
                                 */
                                foreach($data as $line){
                                    if($line === 'sudo: no tty present and no askpass program specified'){
                                        throw new bException(tr('ssh_exec(): The SSH user ":user" does not have password-less sudo privileges on the host ":domain"', array(':domain' => $server['domain'], ':user' => $server['username'])), 'sudo');
                                    }
                                }
                            }
                        }
                    }

                    /*
                     * Check if SSH can connect to the specified server / port
                     */
                    if(empty($not_check_inet) and isset($server['port'])){
                        try{
                            load_libs('inet');
                            inet_test_host_port($server['domain'], $server['port']);

                        }catch(Exception $f){
                            throw new bException(tr('ssh_exec(): inet_test_host_port() failed with ":e"', array(':e' => $f->getMessage())), $e);
                        }
                    }
            }

        }catch(bException $f){
            $f = new bException(tr('ssh_exec(): Failed to auto resolve ssh_exec() exception ":e"', array(':e' => $e)), $f);
            notify($f);
            throw  $f;
        }

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
        if(empty($server['domain'])){
            throw new bException(tr('ssh_build_command(): No domain specified'), 'not-specified');
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
                    array_default($value, 'target_domain', 'localhost');
                    array_default($value, 'persist'      , false);

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

                    if(!is_scalar($value['target_domain']) or (strlen($value['target_domain']) < 1) or (strlen($value['target_domain']) > 253)){
                        if(!$value['target_domain']){
                            throw new bException(tr('ssh_build_command(): No target_domain specified for parameter "tunnel". Value should be the target hosts FQDN, IP, localhost, or host defined in the /etc/hosts of the target machine'), 'invalid');
                        }

                        throw new bException(tr('ssh_build_command(): Invalid target_domain specified for parameter "tunnel". Value should be scalar, and between 1 and 253 characters'), 'invalid');
                    }

                    $command .= ' -L '.$value['source_port'].':'.$value['target_domain'].':'.$value['target_port'];
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
                        $template['domain']   = ':proxy_host';
                        $template['port']     = ':proxy_port';
                        $template['commands'] = 'nc :target_domain :target_port';
                        $template['proxies']  = ':proxy_template';

//'ssh '.$server['timeout'].$server['arguments'].' -i '.$identity_file.' -p :proxy_port :proxy_template '.$server['username'].'@:proxy_host nc :target_domain :target_port';

                        $escapes        = 0;
                        $proxy_template = ' -o ProxyCommand="'.addslashes(ssh_build_command($template)).'" ';
                        $proxies_string = ':proxy_template';
                        $target_server  = $server['domain'];
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
                            $proxy_string   = str_replace(':proxy_port'    , $proxy['port']  , $proxy_string);
                            $proxy_string   = str_replace(':proxy_host'    , $proxy['domain'], $proxy_string);
                            $proxy_string   = str_replace(':target_domain' , $target_server  , $proxy_string);
                            $proxy_string   = str_replace(':target_port'   , $target_port    , $proxy_string);
                            $proxies_string = str_replace(':proxy_template', $proxy_string   , $proxies_string);

                            $target_server  = $proxy['domain'];
                            $target_port    = $proxy['port'];

                            ssh_add_known_host($proxy['domain'], $proxy['port']);
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
        $command .= ' "'.$server['username'].'@'.$server['domain'].'"';

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

        $result = ssh_exec(array('domain'    => $server['domain'],
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
 * @param natural $accounts_id The table ID for the account
 * @return array The account data for the specified $accounts_id
 */
function ssh_get_account($account){
    try{
        if(!$account){
            throw new bException(tr('ssh_get_account(): No accounts id specified'), 'not-specified');
        }

        if(!is_numeric($account)){
            if(!is_string($account)){
                throw new bException(tr('ssh_get_account(): Specified account ":account" should be either a numeric accounts id or an accounts name string', array(':account' => $account)), 'invalid');
            }

            $where   = ' WHERE `ssh_accounts`.`seoname`  = :seoname
                         OR    `ssh_accounts`.`username` = :username
                         OR    `ssh_accounts`.`name`     = :name';

            $execute = array(':name'     => $account,
                             ':seoname'  => $account,
                             ':username' => $account);

        }else{
            $where   = ' WHERE `ssh_accounts`.`id` = :id';
            $execute = array(':id' => $account);
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
                           ON        `ssh_accounts`.`modifiedby` = `modifiedby`.`id`'.$where,

                           $execute);

        return $retval;

    }catch(Exception $e){
        throw new bException('ssh_get_account(): Failed', $e);
    }
}



/*
 * Add the fingerprints for the specified domain:port to the `ssh_fingerprints` table and the ROOT/data/ssh/known_hosts file
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_rebuild_known_hosts()
 * @see ssh_remove_known_hosts()
 *
 * @param string $domain
 * @param natural $port
 */
function ssh_add_known_host($domain, $port){
    try{
        $port   = ssh_get_port($port);
        $retval = ssh_get_fingerprints($domain, $port);
        $count  = 0;

        if(empty($retval)){
            throw new bException(tr('ssh_add_known_host(): ssh-keyscan found no public keys for domain ":domain"', array(':domain' => $domain)), 'not-found');
        }

        /*
         * Is this a registered server?
         */
        try{
            $server = servers_get($domain, false, false, true);

        }catch(Exception $e){
            $server = array('id' => null);
        }

        /*
         * Auto register the fingerprints in the ssh_fingerprints table
         */
        $fingerprints = sql_list('SELECT `fingerprint`, `algorithm` FROM `ssh_fingerprints` WHERE `domain` = :domain AND `port` = :port', array(':domain' => $domain, ':port' => $port));

        if($fingerprints){
            /*
             * This host is already registered in the ssh_fingerprints table. We
             * should be able to find all its fingerprints
             */
            foreach($retval as $fingerprint){
                $exists = array_key_exists($fingerprint['fingerprint'], $fingerprints);

                if(!$exists){
                    throw new bException(tr('ssh_add_known_host(): The domain ":domain" gave fingerprint ":fingerprint", which does not match any of the already registered fingerprints', array(':domain' => $fingerprint['domain'], ':fingerprint' => $fingerprint['fingerprint'])), 'not-exist');
                }

                if($fingerprints[$fingerprint['fingerprint']] != $fingerprint['algorithm']){
                    throw new bException(tr('ssh_add_known_host(): The domain ":domain" gave fingerprint ":fingerprint", which does match an already registered fingerprints, but for the wrong algorithm ":algorithm"', array(':domain' => $fingerprint['domain'], ':fingerprint' => $fingerprint['fingerprint'], ':algorithm' => $fingerprint['algorithm'])), 'not-match');
                }
            }

        }else{
            /*
             * This host is not yet registered in the ssh_fingerprints table.
             * Regiser its fingerprints now.
             */
            $insert = sql_prepare('INSERT INTO `ssh_fingerprints` (`createdby`, `meta_id`, `servers_id`, `domain`, `seodomain`, `port`, `fingerprint`, `algorithm`)
                                   VALUES                         (:createdby , :meta_id , :servers_id , :domain , :seodomain , :port , :fingerprint , :algorithm )');


            foreach($retval as $fingerprint){
                $insert->execute(array(':createdby'   => isset_get($_SESSION['user']['id']),
                                       ':meta_id'     => meta_action(),
                                       'servers_id'   => $server['id'],
                                       ':domain'      => $fingerprint['domain'],
                                       ':seodomain'   => seo_unique($fingerprint['domain'], 'ssh_fingerprints', null, 'seodomain'),
                                       ':port'        => $fingerprint['port'],
                                       ':fingerprint' => $fingerprint['fingerprint'],
                                       ':algorithm'   => $fingerprint['algorithm']));
            }

            if($server['id']){
                log_console(tr('Added ":count" fingerprints for registered domain ":domain" with servers id ":id"', array(':count' => count($retval), ':domain' => $domain, ':id' => $server['id'])));

            }else{
                log_console(tr('Added ":count" fingerprints for unregistered domain ":domain"', array(':count' => count($retval), ':domain' => $domain)));
            }
        }

        /*
         * Now add them to the known_hosts file
         */
        foreach($retval as $fingerprint){
            if(ssh_append_fingerprint($fingerprint)){
                $count++;
            }
        }

        sql_query('UPDATE `servers` SET `status` = NULL WHERE `domain` = :domain', array(':domain' => $domain));
        return $count;

    }catch(Exception $e){
        throw new bException('ssh_add_known_host(): Failed', $e);
    }
}



/*
 * Remove the registered fingerprints for the specified domain:port from the `ssh_fingerprints` table and the ROOT/data/ssh/known_hosts file
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_rebuild_known_hosts()
 * @see ssh_add_known_hosts()
 *
 * @param string $domain
 * @param natural $port
 * @return natural The amount of fingerprints removed
 */
function ssh_remove_known_host($domain, $port){
    try{
        if(empty($domain)){
            throw new bException(tr('ssh_remove_known_host(): No domain specified'), 'not-specified');
        }

        $count = 0;
        $port  = ssh_get_port($port);

        sql_query('DELETE FROM `ssh_fingerprints` WHERE `domain` = :domain AND `port` = :port', array(':domain' => $domain, ':port' => $port));

        file_ensure_file(ROOT.'data/ssh/known_hosts', 0640, 0750);
        file_delete(ROOT.'data/ssh/known_hosts~update');

        $f1 = fopen(ROOT.'data/ssh/known_hosts'       , 'r');
        $f2 = fopen(ROOT.'data/ssh/known_hosts~update', 'w+');

        while($line = fgets($f1)){
            $found = preg_match('/^\['.$domain.'\]\:'.$port.'\s+/', $line);

            if(!$found){
                fputs($f2, $line);

            }else{
                $count++;
            }
        }

        fclose($f1);
        fclose($f2);

        file_delete(ROOT.'data/ssh/known_hosts');
        rename(ROOT.'data/ssh/known_hosts~update', ROOT.'data/ssh/known_hosts');

        return $count;

    }catch(Exception $e){
        /*
         * Close the files
         */
        if(isset($f1)){
            fclose($f1);
        }

        if(isset($f2)){
            fclose($f2);
        }

        throw new bException('ssh_remove_known_host(): Failed', $e);
    }
}



/*
 * Append the specified fingerprint data to the ROOT/data/ssh/known_hosts file
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_add_known_host()
 * @see ssh_rebuild_known_hosts()
 *
 * @param params $fingerprint
 * @params string domain
 * @params natural port
 * @params natural algorithm
 * @params natural fingerprint
 * @return boolean
 */
function ssh_append_fingerprint($fingerprint){
    try{
        file_ensure_file(ROOT.'data/ssh/known_hosts', 0640, 0750);

        $exists = safe_exec('grep "\['.$fingerprint['domain'].'\]:'.$fingerprint['port'].' '.$fingerprint['algorithm'].' '.$fingerprint['fingerprint'].'" '.ROOT.'data/ssh/known_hosts', '0,1');

        if($exists){
            log_console(tr('Skipping fingerprint ":fingerprint" for domain ":domain", it already exists in known_hosts', array(':fingerprint' => $fingerprint['fingerprint'], ':domain' => $fingerprint['domain'])), 'VERYVERBOSE');
            return false;
        }

        log_console(tr('Adding fingerprint ":fingerprint" for domain ":domain" to known_hosts', array(':fingerprint' => $fingerprint['fingerprint'], ':domain' => $fingerprint['domain'])), 'VERBOSE');
        file_put_contents(ROOT.'data/ssh/known_hosts', '['.$fingerprint['domain'].']:'.$fingerprint['port'].' '.$fingerprint['algorithm'].' '.$fingerprint['fingerprint']."\n", FILE_APPEND);
        return true;

    }catch(Exception $e){
        throw new bException('ssh_append_fingerprint(): Failed', $e);
    }
}



/*
 * Gets and registers the SSH fingerprints for the specified domain and port
 *
 * The obtained fingerprints are stored in the ssh_fingerprints table and any subsequent call will attempt to match them. If the match fails, an exception will be thrown
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @exception bException not-match Thrown if any of the found fingerprint does not match any of the fingerprints and algorithms registered in the ssh_fingerprints table
 * @see ssh_rebuild_known_hosts()
 *
 * @param string $domain
 * @param natural $port
 * @return array The found fingerprints for the specified domain and port
 */
function ssh_get_fingerprints($domain, $port){
    try{
        if(empty($domain)){
            throw new bException(tr('ssh_get_fingerprints(): No domain specified'), 'not-specified');
        }

        load_libs('servers,seo');

        $port    = ssh_get_port($port);
        $retval  = array();
        $results = safe_exec('ssh-keyscan -p '.$port.' '.$domain);

        foreach($results as $result){
            if(substr($result, 0, 1) === '#') continue;

            preg_match_all('/\[(.+?)\](?:\:(\d{1,5}))\s+([a-z0-9-]+)\s+([a-z0-9+\/]+)/i', $result, $matches);

            $entry = array('fingerprint' => $matches[4][0],
                           'domain'      => $matches[1][0],
                           'port'        => $matches[2][0],
                           'algorithm'   => $matches[3][0]);

            /*
             * Validate domain format
             */
            if(!filter_var($entry['domain'], FILTER_VALIDATE_DOMAIN)){
                if(!filter_var($entry['domain'], FILTER_VALIDATE_IP)){
                    throw new bException(tr('ssh_get_fingerprints(): ssh-keyscan returned invalid domain name ":domain"', array(':domain' => $entry['domain'])), '');
                }
            }

            $retval[] = $entry;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('ssh_get_fingerprints(): Failed', $e);
    }
}



/*
 * Rebuild the ROOT/data/ssh/known_hosts file, adding all host key fingerprints
 * stored in the ssh_fingerprints table
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see ssh_get_fingerprints()
 * @see ssh_rebuild_known_hosts()
 * @package ssh
 *
 * @return natural The amount of finger prints added to the known_hosts file
 */
function ssh_rebuild_known_hosts($clear = false){
    try{
        if($clear){
            /*
             * Clear the SSH known hosts file
             */
            log_console(tr('Deleting the known_hosts file "'.ROOT.'data/ssh/known_hosts"'), 'VERBOSE/yellow');
            file_delete(ROOT.'data/ssh/known_hosts');
        }

        $count        = 0;
        $fingerprints = sql_query('SELECT `id`, `domain`, `port`, `algorithm`, `fingerprint` FROM `ssh_fingerprints` WHERE `status` IS NULL');

        while($fingerprint = sql_fetch($fingerprints)){
            if(ssh_append_fingerprint($fingerprint)){
                $count++;
            }
        }

        return $count;

    }catch(Exception $e){
        throw new bException('ssh_rebuild_known_hosts(): Failed', $e);
    }
}



/*
 * Returns true if the specified domain:port is registered in the ROOT/data/ssh/known_hosts file
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see ssh_get_fingerprints()
 * @see ssh_rebuild_known_hosts()
 * @package ssh
 *
 * @params string $domain
 * @params natural $port
 * @params boolean $auto_register If set to true, if the domain is not specified in the ROOT/data/ssh/known_hosts file but is available in the ssh_fingerprints table, then the function will automatically add the fingerprints to the ROOT/data/ssh/known_hosts file
 * @return boolean True if the specified domain:port is registered in the ROOT/data/ssh/known_hosts file
 */
function ssh_is_registered($domain, $port, $auto_register = true){
    try{
        file_ensure_file(ROOT.'data/ssh/known_hosts', 0640, 0750);

        $port       = ssh_get_port($port);
        $db_count   = sql_get('SELECT COUNT(`id`) FROM `ssh_fingerprints` WHERE `domain` = :domain AND `port` = :port', true, array('domain' => $domain, ':port' => $port), 'core');
        $file_count = safe_exec('grep "['.$domain.']:'.$port.'" '.ROOT.'data/ssh/known_hosts | wc -l');
        $file_count = array_shift($file_count);

        if($file_count){
            /*
             * Fingerprints are avaialble in the known_hosts file
             */
            return true;
        }

        if(!$db_count or !$auto_register){
            /*
             * No fingerprints available at all, or we cannot auto register
             */
            return false;
        }

        /*
         * Fingerprints are in the ssh_fingerprints table, but not in the
         * known_hosts file, and we can auto register
         */
        return ssh_add_known_host($domain, $port);

    }catch(Exception $e){
        throw new bException('ssh_is_registered(): Failed', $e);
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
// * @param string $domain
// * @param array $config
// */
//function ssh_update_config($domain, $config){
//    try{
//        $config        = ssh_validate_server_config($config);
//        $server_config = ssh_read_server_config($domain);
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
//        ssh_write_server_config($domain, $server_config);
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
                                             `servers`.`domain`,
                                             `servers`.`port`

                                   FROM      `servers`

                                   LEFT JOIN `ssh_accounts`
                                   ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                   WHERE     `servers`.`domain` = :domain',

                                   array(':domain' => $source));

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
            array_ensure($source, 'server,domain,ssh_key,port,check_hostkey,arguments,path');
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
                                               `servers`.`domain`,
                                               `servers`.`port`

                                     FROM      `servers`

                                     LEFT JOIN `ssh_accounts`
                                     ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                     WHERE     `servers`.`domain` = :domain',

                                     array(':domain' => $target));

                if(!$server){
                    throw new bException(tr('ssh_cp(): Specified target server ":server" does not exist', array(':server' => $target)), 'not-exist');
                }

                $target         = $server;
                $target['path'] = $path;
            }

        }else{
            array_ensure($target, 'server,domain,ssh_key,port,check_hostkey,arguments');
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
            $command = $server['username'].'@'.$server['domain'].':'.$source.' '.$destnation;

        }else{
            $command = $source.' '.$server['username'].'@'.$server['domain'].':'.$destnation;
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
 * @see inet_port_availalbe();
 *
 * @param params $params
 * @params string $domain The domain where SSH should connect to
 * @params string $local_port The port on the local server where SSH should listen to 1-65535
 * @params string $remote_port The port on the remote server where SSH should connect to 1-65535
 * @params array $options The required SSH options
 * @param boolean $reuse If set to true, ssh_tunnel() will first check if a tunnel with the requested configuration already exists. If it does, no new tunnel will be created and the PID for the already existing tunnel will be returned instead
 * @return natural The process id of the created (or reused) SSH tunnel.
 */
function ssh_tunnel($params, $reuse = true){
    try{
        array_ensure ($params, 'domain,source_port,target_port,target_domain,persist');
        array_default($params, 'tunnel'     , 'localhost');
        array_default($params, 'test_tries' , 50);
        load_libs('inet');

        if(!$params['domain']){
            throw new bException(tr('ssh_tunnel(): No domain specified'), 'not-specified');
        }

        /*
         * Is a tunnel with the requested configuration already available? If
         * so, use that, don't make a new one!
         */
        if($reuse){
            $exists = ssh_tunnel_exists($params['domain'], $params['target_port'], $params['target_domain']);

            if($exists){
                if($params['source_port'] === $exists['source_port']){
                    log_console(tr('Found pre-existing SSH tunnel for requested configuration ":source_port::target_domain::target_port" with pid ":pid" on the requested source port, not creating a new one', array(':source_port' => $params['source_port'], ':target_domain' => $params['target_domain'], ':target_port' => $params['target_port'], ':pid' => $exists['pid'])), 'VERBOSE/warning');

                }else{
                    log_console(tr('Found pre-existing SSH tunnel for requested configuration ":source_port::target_domain::target_port" with pid ":pid" on different source port ":different_port", not creating a new one', array(':source_port' => $params['source_port'], ':target_domain' => $params['target_domain'], ':target_port' => $params['target_port'], ':pid' => $exists['pid'], ':different_port' => $exists['source_port'])), 'VERBOSE/warning');
                }
                return $exists;
            }
        }

        if($params['source_port']){
            /*
             * Ensure port is available.
             */
            if(!inet_port_available($params['source_port'], '127.0.0.1')){
                throw new bException(tr('ssh_tunnel(): Source port ":port" is already in use', array(':port' => $params['source_port'])), 'not-available');
            }

        }else{
            /*
             * Ensure Assign a random local port
             */
            $params['source_port'] = inet_get_available_port('127.0.0.1');
        }

        $params['tunnel'] = array('persist'       => $params['persist'],
                                  'source_port'   => $params['source_port'],
                                  'target_domain' => $params['target_domain'],
                                  'target_port'   => $params['target_port']);

        unset($params['persist']);
        unset($params['source_port']);
        unset($params['target_port']);
        unset($params['target_domain']);

        $retval = ssh_exec($params, null, false, 'exec');
        $retval = array_shift($retval);

        log_console(tr('Created SSH tunnel ":source_port::target_domain::target_port"', array(':source_port' => $params['tunnel']['source_port'], ':target_domain' => $params['tunnel']['target_domain'], ':target_port' => $params['tunnel']['target_port'])), 'VERYVERBOSE');

        /*
         * Check that the tunnel is being setup correctly in the background
         * This is a blocking section, we will NOT continue until the tunnel is
         * availabe
         */
        if(!$params['test_tries']){
            log_console(tr('Not confirming SSH tunnel existence due to "tries" set to 0. Waiting 1.000.000 useconds instead'), 'warning');
            usleep(1000000);

        }else{
            $tries = $params['test_tries'];

            while(--$tries > 0){
                usleep(10000);

                /*
                 * Is the tunnel responding?
                 */
                $exists = inet_test_host_port('127.0.0.1', $params['tunnel']['source_port']);

                if($exists){
                    log_console(tr('SSH tunnel confirmed working'), 'VERBOSE/green');
                    break;
                }

                /*
                 * Is the SSH tunnel process still there? If not, there is no
                 * reason to be testing
                 */
                if(!cli_pid($retval)){
                    throw new bException(tr('ssh_tunnel(): Failed to confirm SSH tunnel available, the SSH tunnel process ":pid" is gone', array(':pid' => $retval)), 'failed');
                }
            }

            if($tries <= 0){
                /*
                 * Tunnel either failed to build, or no target was listening
                 */
                throw new bException(tr('ssh_tunnel(): Failed to confirm SSH tunnel available after ":tries" tries', array(':tries' => $params['test_tries'])), 'failed');
            }
        }

        return array('pid'         => $retval,
                     'source_port' => $params['tunnel']['source_port']);

    }catch(Exception $e){
        throw new bException('ssh_tunnel(): Failed', $e);
    }
}



/*
 * Detect if an SSH tunnel with the specified parameters already exists or not
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param numeric $domain
 * @param numeric $target_port
 * @param numeric $target_domain
 * @return array Resulting array either is null, or an arry containing the pid (process id) and source_port of the found tunnel
 */
function ssh_tunnel_exists($domain, $target_port, $target_domain = null){
    global $core;

    try{
        load_libs('cli');

        if(!$target_domain){
            $target_domain = 'localhost';
        }

        $results   = array();
        $processes = cli_list_processes('ssh,-L');

        foreach($processes as $pid => $process){
            if(!preg_match_all('/(\d+)(\:.+?\:\d+)/', $process, $matches)){
                /*
                 * Failed to identify the tunnel configuration
                 */
                log_console(tr('Failed to identify SSH tunnel configuration for process ":process"', array(':process' => $process)), 'VERBOSE/yellow');
            }

            $process_domain        = str_rfrom($process, ' ');
            $process_domain        = str_from($process_domain, '@');
            $process_source_port   = isset_get($matches[1][0]);
            $process_configuration = isset_get($matches[2][0]);

            if($process_domain === $domain){
                /*
                 * Target server matches, check tunnel configuration
                 * In case of 127.0.0.1 or localhost, check for both alternatives
                 */
// :TODO: Check if maybe in the future we should check all possible domain names and IP's
                switch($target_domain){
                    case 'localhost':
                        $alt_domain = '127.0.0.1';
                        break;

                    case '127.0.0.1':
                        $alt_domain = 'localhost';
                        break;
                }

                if(($process_configuration === (':'.$target_domain.':'.$target_port)) or ($process_configuration === (':'.$alt_domain.':'.$target_port))){
                    $results[$pid] = $process_source_port;
                }
            }
        }

        switch(count($results)){
            case 0:
                /*
                 * No tunnel with the requeste configuration found
                 */
                return null;

            case 1:
                /*
                 * Yay! Found a tunnel! Return its PID
                 */
                return array('pid'         => key($results),
                             'source_port' => current($results));

            default:
                /*
                 * Apparently there are multiple SSH tunnels with this configuration. Pick a random one
                 */
                log_console(tr('Found multiple SSH tunnels to host ":domain" with configuration "::target_domain::target_port"', array(':domain' => $domain, ':target_port' => $target_port, ':target_domain' => $target_domain)), 'yellow');
                return array_random_value($pids);
        }

    }catch(Exception $e){
        throw new bException('ssh_tunnel_exists(): Failed', $e);
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
    global $core;

    try{
        /*
         * Ensure that the PID for this tunnel is no longer on the shutdown list
         */
        if(isset($core->register['shutdown_ssh_close_tunnel'])){
            if(is_array($core->register['shutdown_ssh_close_tunnel'])){
                foreach($core->register['shutdown_ssh_close_tunnel'] as $key => $registered_pid){
                    if($pid == $registered_pid){
                        unset($core->register['shutdown_ssh_close_tunnel'][$key]);
                    }
                }
            }
        }

        load_libs('cli');
        cli_kill($pid);

    }catch(Exception $e){
        throw new bException('ssh_close_tunnel(): Failed', $e);
    }
}



/*
 * Return either the specified port (if any) or the dedfault SSH port (if null or equivalent of empty was specified)
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param natural $port
 * @return natural If the specified port was not empty, it will be returned. If the specified port was empty, the default port configuration will be returned
 */
function ssh_get_port($port = null){
    global $_CONFIG;

    try{
        if($port){
            if(!is_natural($port) or ($port > 65535)){
              throw new bException(tr('ssh_get_port(): No port specified'), 'not-specified');
          }

          return $port;
        }

        if($_CONFIG['servers']['ssh']['default_port']){
            return $_CONFIG['servers']['ssh']['default_port'];
        }

        return 22;

    }catch(Exception $e){
        throw new bException('ssh_get_port(): Failed', $e);
    }
}
?>