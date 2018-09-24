<?php
/*
 * SSH library library
 *
 * This library contains functions to manage SSH accounts
 *
 * Copyright (c) 2018 Capmega
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
        $v->isNotEmpty ($ssh['name']    , tr('No account name specified'));
        $v->hasMinChars($ssh['name'],  2, tr('Please ensure the account name has at least 2 characters'));
        $v->hasMaxChars($ssh['name'], 32, tr('Please ensure the account name has less than 32 characters'));

        $v->isNotEmpty ($ssh['username']    , tr('No user name specified'));
        $v->hasMinChars($ssh['username'],  2, tr('Please ensure the user name has at least 2 characters'));
        $v->hasMaxChars($ssh['username'], 32, tr('Please ensure the user name has less than 32 characters'));

        $v->isNotEmpty ($ssh['ssh_key'], tr('No SSH key specified to the account'));

        $v->isNotEmpty ($ssh['description']   , tr('No description specified'));
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
 * Returns ssh account data
 */
function ssh_get_account($account){
    try{
        if(!$account){
            throw new bException(tr('ssh_get_account(): No accounts id specified'), 'not-specified');
        }

        if(!is_numeric($account)){
            throw new bException(tr('ssh_get_account(): Specified accounts id ":id" is not numeric', array(':id' => $account)), 'invalid');
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

                           WHERE     `ssh_accounts`.`id`   = :id',

                           array(':id' => $account));

        return $retval;

    }catch(Exception $e){
        throw new bException('ssh_get_account(): Failed', $e);
    }
}



/*
 *
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
//showdie($e);
        throw new bException('ssh_start_control_master(): Failed', $e);
    }
}



/*
 *
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
//showdie($e);
        throw new bException('ssh_get_control_master(): Failed', $e);
    }
}



/*
 *
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
//showdie($e);
        throw new bException('ssh_stop_control_master(): Failed', $e);
    }
}



/*
 *
 */
function ssh_exec($server, $commands = null, $background = false, $function = 'exec'){
    try{
        array_default($server, 'hostname'     , '');
        array_default($server, 'ssh_key'      , '');
        array_default($server, 'port'         , 22);
        array_default($server, 'timeout'      , 10);
        array_default($server, 'hostkey_check', true);
        array_default($server, 'arguments'    , '-T');
        array_default($server, 'commands'     , $commands);
        array_default($server, 'background'   , $background);
        array_default($server, 'proxies'      , null);

        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        load_libs('file');
        file_ensure_path(ROOT.'data/ssh/keys');
        chmod(ROOT.'data/ssh', 0770);

        /*
         * Validate commands
         */
        if($server['commands'] === null){
            throw new bException(tr('ssh_exec(): No commands specified'), 'not-specified');
        }

        if(!empty($server['hostname'])){
            if(empty($server['username'])){
                throw new bException(tr('ssh_exec(): No username specified'), 'not-specified');
            }

            if(empty($server['ssh_key'])){
                throw new bException(tr('ssh_exec(): No ssh key specified'), 'not-specified');
            }
        }

        if($server['timeout']){
            /*
             * Add timeout to the SSH command
             */
            $server['timeout'] = ' -o ConnectTimeout='.$server['timeout'].' ';

        }else{
            $server['timeout'] = '';
        }

        /*
         * If no hostname is specified, then don't execute this command on a
         * remote server, just use safe_exec and execute it locally
         */
        if(!$server['hostname']){
            $result = shell_exec($server['commands'].($server['background'] ? ' &' : ''));
            return $result;
        }

        /*
        * Path for known_hosts file
        */
        $user_known_hosts_file = ROOT.'data/ssh/known_hosts';

        /*
        * Cleaning file so we do not duplicate entries
        */
        safe_exec('> '.$user_known_hosts_file);

        if(!$server['hostkey_check']){
            $server['arguments'] .= ' -o CheckHostIP=no -o StrictHostKeyChecking=no -o UserKnownHostsFile='.$user_known_hosts_file;
        }

        /*
         * Safely create SSH key file
         */
        $keyfile = ROOT.'data/ssh/keys/'.str_random(8);

        touch($keyfile);
        chmod($keyfile, 0600);
        file_put_contents($keyfile, $server['ssh_key'], FILE_APPEND);
        chmod($keyfile, 0400);

        if($server['proxies']){
// :TODO: Right now its assumed that every proxy uses the same SSH user and key file, though in practice, they MIGHT have different ones. Add support for each proxy server having its own user and keyfile
            /*
             * ssh command line ProxyCommand example: -o ProxyCommand="ssh -p  -o ProxyCommand=\"ssh -p  40220 s1.s.ingiga.com nc s2.s.ingiga.com 40220\"  40220 s2.s.ingiga.com nc s3.s.ingiga.com 40220"
             * To connect to this server, one must pass through a number of SSH proxies
             */
            $escapes        = 0;
            $proxy_template = ' -o ProxyCommand="ssh '.$server['timeout'].$server['arguments'].' -i '.$keyfile.' -p :proxy_port :proxy_template '.$server['username'].'@:proxy_host nc :target_host :target_port" ';
            $proxies_string = ':proxy_template';
            $target_server  = $server['hostname'];
            $target_port    = $server['port'];

            foreach($server['proxies'] as $id => $proxy){
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
                $proxy_string   = str_replace(':proxy_port'    , $proxy['port']    , $proxy_string);
                $proxy_string   = str_replace(':proxy_host'    , $proxy['hostname'], $proxy_string);
                $proxy_string   = str_replace(':target_host'   , $target_server    , $proxy_string);
                $proxy_string   = str_replace(':target_port'   , $target_port      , $proxy_string);
                $proxies_string = str_replace(':proxy_template', $proxy_string     , $proxies_string);

                $target_server  = $proxy['hostname'];
                $target_port    = $proxy['port'];

                ssh_add_known_host($proxy['hostname'], $proxy['port'], $user_known_hosts_file);
            }

            /*
             * No more proxies, remove the template placeholder
             */
            $proxies_string = str_replace(':proxy_template', '', $proxies_string);

        }else{
            $proxies_string = '';
        }

        /*
        * Also add the target server
        */
        ssh_add_known_host($server['hostname'], $server['port'], $user_known_hosts_file);

        /*
         * Execute command on remote server
         */
        $command = 'ssh '.$server['timeout'].$server['arguments'].' -p '.$server['port'].' '.$proxies_string.' -i '.$keyfile.' '.$server['username'].'@'.$server['hostname'].' "'.$server['commands'].'"'.($server['background'] ? ' &' : '');

        /*
         * Execute the command
         */
//showdie($command);

        $results = safe_exec($command, null, true, $function);

        if($server['background']){
            /*
             * Delete key file in background process
             */
            safe_exec('{ sleep 5; sudo chmod 0600 '.$keyfile.' ; sudo rm -rf '.$keyfile.' ; } &');

        }else{
            chmod($keyfile, 0600);
            file_delete($keyfile);
        }

        if(preg_match('/Warning: Permanently added \'\[.+?\]:\d{1,5}\' \(\w+\) to the list of known hosts\./', isset_get($results[0]))){
            /*
             * Remove known host warning from results
             */
            array_shift($results);
        }

        return $results;

    }catch(Exception $e){
        /*
         * Remove "Permanently added host blah" error, even in this exception
         */
        $data = $e->getData();

        if(!empty($data[0])){
            if(preg_match('/Warning: Permanently added \'\[.+?\]:\d{1,5}\' \(\w+\) to the list of known hosts\./', isset_get($data[0]))){
                /*
                 * Remove known host warning from results
                 */
                array_shift($data);
            }
        }

        unset($data);

        notify(tr('ssh_exec() exception'), $e, 'developers');

        /*
         * Try deleting the keyfile anyway!
         */
        try{
            if(!empty($keyfile)){
                chmod($keyfile, 0600);
                file_delete($keyfile);
            }

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify(tr('ssh_exec() cannot delete key'), $e, 'developers');
        }

        throw new bException('ssh_exec(): Failed', $e);
    }
}



/*
 * ...
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_get_key($username){
    try{
        return sql_get('SELECT `ssh_key` FROM `ssh_accounts` WHERE `username` = :username', 'ssh_key', array(':username' => $username));

    }catch(Exception $e){
        throw new bException('ssh_get_key(): Failed', $e);
    }
}



/*
 * ...........
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 * @param
 * @param
 * @param
 * @return
 */
function ssh_cp($server, $source, $destnation, $from_server = false){
    try{
        array_params($server);
        array_default($server, 'server'       , '');
        array_default($server, 'hostname'     , '');
        array_default($server, 'ssh_key'      , '');
        array_default($server, 'port'         , 22);
        array_default($server, 'hostkey_check', false);
        array_default($server, 'arguments'    , '');

        /*
         * If server was specified by just name, then lookup the server data in
         * the database
         */
        if($server['hostname']){
            $dbserver = sql_get('SELECT    `ssh_accounts`.`username`,
                                           `ssh_accounts`.`ssh_key`,
                                           `servers`.`id`,
                                           `servers`.`hostname`,
                                           `servers`.`port`

                                 FROM      `servers`

                                 LEFT JOIN `ssh_accounts`
                                 ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                 WHERE     `servers`.`hostname` = :hostname', array(':hostname' => $server['hostname']));

            if(!$dbserver){
                throw new bException(tr('ssh_cp(): Specified server ":server" does not exist', array(':server' => $server['server'])), 'not-exist');
            }

            $server = sql_merge($server, $dbserver);
        }

        if(!$server['hostkey_check']){
            $server['arguments'] .= ' -o StrictHostKeyChecking=no -o UserKnownHostsFile='.ROOT.'data/ssh/known_hosts ';
        }

        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        load_libs('file');
        file_ensure_path(ROOT.'data/ssh/keys');
        chmod(ROOT.'data/ssh', 0770);

        /*
         * Safely create SSH key file
         */
        $keyfile = ROOT.'data/ssh/keys/'.str_random(8);

        touch($keyfile);
        chmod($keyfile, 0600);
        file_put_contents($keyfile, $server['ssh_key'], FILE_APPEND);
        chmod($keyfile, 0400);

        if($from_server){
            $command = $server['username'].'@'.$server['hostname'].':'.$source.' '.$destnation;

        }else{
            $command = $source.' '.$server['username'].'@'.$server['hostname'].':'.$destnation;
        }

        /*
         * Execute command
         */
        $result = safe_exec('scp '.$server['arguments'].' -P '.$server['port'].' -i '.$keyfile.' '.$command.'');
        chmod($keyfile, 0600);
        file_delete($keyfile);

        return $result;

    }catch(Exception $e){
        notify(tr('ssh_cp() exception'), $e, 'developers');

                /*
         * Try deleting the keyfile anyway!
         */
        try{
            if(!empty($keyfile)){
                safe_exec(chmod($keyfile, 0600));
                file_delete($keyfile);
            }

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify(tr('ssh_cp() cannot delete key'), $e, 'developers');
        }

        throw new bException(tr('ssh_cp(): Failed'), $e);
    }
}



/*
 * ..................
 *
 * @author Marcos Prudencio <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_add_known_host($hostname, $port, $known_hosts_path){
    try{
        if(empty($hostname)){
            throw new bException(tr('ssh_add_known_host(): No hostname specified'), 'not-specified');
        }

        if(empty($port)){
            throw new bException(tr('ssh_add_known_host(): No port specified'), 'not-specified');
        }

        if(empty($known_hosts_path)){
            throw new bException(tr('ssh_add_known_host(): No known_hosts_path specified'), 'not-specified');
        }

        $public_keys = safe_exec('ssh-keyscan -p '.$port.' -H '.$hostname);

        if(empty($public_keys)){
            throw new bException(tr('ssh_add_known_host(): ssh-keyscan found no public keys for hostname ":hostname"', array(':hostname' => $hostname)), 'not-found');
        }

        foreach($public_keys as $public_key){
            if(substr($public_key, 0, 1) != '#'){
                file_put_contents($known_hosts_path, $public_key."\n", FILE_APPEND);
            }
        }

        return count($public_keys);

    }catch(Exception $e){
        throw new bException('ssh_add_known_host(): Failed', $e);
    }
}



/*
 * .........
 *
 * @author Marcos Prudencio <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_get_config($hostname){
    try{
        $config = servers_exec($hostname, 'cat /etc/ssh/sshd_config');

        return $config;

    }catch(Exception $e){
        throw new bException('ssh_get_config(): Failed', $e);
    }
}



/*
 * Do not inlude  at the beggining of comments the name of the field, otherwise it would be also replace
 *
 * @author Marcos Prudencio <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_update_config($hostname, $params){
    try{
        $params = ssh_validate_config($params);
        $config = ssh_get_config($hostname);

        foreach($params as $key => $values){
            $comments = '';

            if(isset($values['description'])){
                $comments = '#'.$values['description']."\n";
            }

            $config = preg_replace('/'.$key.'\s+(\d+|\w+)|#'.$key.'\s+(\d+|\w+)/', $comments.$key." ".$values['value'], $config);
        }

        servers_exec($hostname, 'cat > /etc/ssh/sshd_config << EOF '.$config);

        return $config;

    }catch(Exception $e){
        throw new bException('ssh_update_config(): Failed', $e);
    }
}



/*
 * Field description is optional, value is mandatory
 * For example: $params = array('Port'=>array('description'=>'Comentary', 'value'=>40220));
 *
 * @author Marcos Prudencio <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $params The parameters that need to be validated
 * @return array The validated parameter data
 */
function ssh_validate_config($params){
    try{
        if(empty($params)){
            throw new bException(tr('ssh_validate_config(): No params specified'), 'not-specified');
        }

        if(!is_array($params)){
            throw new bException(tr('ssh_validate_config(): Params is not an array. Accepted array example: array(\'Port\'=>array(\'description\'=>\'Comentary\', \'value\'=>40220))'), 'not-specified');
        }

        foreach($params as $key => $values){
            if(!isset($values['value'])){
                throw new bException(tr('ssh_validate_config(): No value specified for configuration key ":key"', array(':key' => $key)), 'not-specified');
            }
        }

        return $params;

    }catch(Exception $e){
        throw new bException('ssh_validate_config(): Failed', $e);
    }
}



/*
 * Returns SSH connection string for the specified SSH options array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $params The parameters that need to be validated
 * @return array The validated parameter data
 */
function ssh_get_conect_string($options = null){
    global $_CONFIG;

    try{
        /*
         * Get options from  default configuration and specified options
         */
        $options = array_merge($_CONFIG['ssh']['options'], $options);
        $string  = '';

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

                case 'strict_host_checking':
                    if(!is_bool($value)){
                        throw new bException(tr('ssh_get_conect_string(): Specified option "strict_host_checking" requires a boolean value, but ":value" was specified', array(':value' => $value)), 'invalid');
                    }

                    $string .= ' -o StrictHostChecking="'.get_yes_no($value).'"';
                    break;

                case 'user_known_hosts_file':
                    if($value){
                        if(!is_string($value)){
                            throw new bException(tr('ssh_get_conect_string(): Specified option "user_known_hosts_file" requires a string value, but ":value" was specified', array(':value' => $value)), 'invalid');
                        }

                        $string .= ' -o UserKnownHostsFile="'.$value.'"';
                    }

                    break;

                case 'port':
                    if($value){
                        if(!is_natural($value) or ($value > 65535)){
                            throw new bException(tr('ssh_get_conect_string(): Specified option "port" requires a natural number value 1 - 65535, but ":value" was specified', array(':value' => $value)), 'invalid');
                        }

                        $string .= ' -p '.$value;
                    }

                    break;

                default:
                    throw new bException(tr('ssh_get_conect_string(): Unknown option ":option" specified', array(':option' => $option)), 'unknown');
            }

        }

        return $string;

    }catch(Exception $e){
        throw new bException('ssh_get_conect_string(): Failed', $e);
    }
}
?>