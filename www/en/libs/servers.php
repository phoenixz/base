<?php
/*
 * Custom servers library
 *
 * This library contains functions to manage toolkit servers
 *
 * Written and Copyright by Sven Oostenbrink
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function servers_library_init(){
    try{
        load_config('servers');

    }catch(Exception $e){
        throw new bException('servers_library_init(): Failed', $e);
    }
}



/*
 *
 */
function servers_validate($server, $password_strength = true){
    global $_CONFIG;

    try{
        load_libs('validate,file,seo');

        $v = new validate_form($server, 'port,hostname,provider,customer,ssh_account,description,ssh_proxy,database_accounts_id');
        $v->isNotEmpty($server['ssh_account'], tr('Please specifiy an SSH account'));
        $v->isNotEmpty($server['hostname']   , tr('Please specifiy a hostnames'));
        $v->isNotEmpty($server['port']       , tr('Please specifiy a port'));
        $v->isNotEmpty($server['provider']   , tr('Please specifiy a provider'));
        $v->isNotEmpty($server['customer']   , tr('Please specifiy a customer'));

        if($password_strength){
            $v->isPassword($server['db_password'], tr('Please specifiy a strong password'), '');
        }

        if($server['database_accounts_id']){
            $exists = sql_get('SELECT `id` FROM `database_accounts` WHERE `id` = :id', true, array(':id' => $server['database_accounts_id']));

            if(!$exists){
                $v->setError(tr('The specified database account does not exist'));
            }

        }else{
            $server['database_accounts_id'] = null;
        }

        /*
         * Hostname
         */
        if(!empty($server['url']) and !FORCE){
            $v->setError(tr('Both hostname ":hostname" and URL ":url" specified, please specify one or the other', array(':hostname' => $server['hostname'], ':url' => $server['url'])));

        }elseif(!preg_match('/[a-z0-9][a-z0-9-.]+/', $server['hostname'])){
            $v->setError(tr('servers_validate(): Invalid server specified, be sure it contains only a-z, 0-9, . and -'));
        }

        /*
         * Port check
         */
        if(empty($server['port'])){
            $server['port'] = not_empty($_CONFIG['scanner']['ssh']['default_port'], 22);
            log_console(tr('servers_validate(): No SSH port specified, using port ":port" as default', array(':port' => $server['port'])), 'yellow');
        }

        if(!is_numeric($server['port']) or ($server['port'] < 1) or ($server['port'] > 65535)){
            $v->setError(tr('servers_validate(): Specified port ":port" is not valid', array(':port' => $server['port'])));
        }

        /*
         * Validate proxy, provider, customer, and ssh account
         */
        if($server['ssh_proxy']){
            $server['ssh_proxies_id'] = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $server['ssh_proxy']), true);

            if(!$server['ssh_proxies_id']){
                $v->setError(tr('servers_validate(): Specified proxy ":proxy" does not exist', array(':proxy' => $server['ssh_proxy'])));
            }

        }else{
            $server['ssh_proxies_id'] = null;
        }

        if($server['provider']){
            $server['providers_id'] = sql_get('SELECT `id` FROM `providers` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['provider']), true);

            if(!$server['providers_id']){
                $v->setError(tr('servers_validate(): Specified provider ":provider" does not exist', array(':provider' => $server['provider'])));
            }

        }else{
            $v->setError(tr('servers_validate(): Please specify a provider'));
        }

        if($server['customer']){
            $server['customers_id'] = sql_get('SELECT `id` FROM `customers` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['customer']), true);

            if(!$server['customers_id']){
                $v->setError(tr('servers_validate(): Specified customer ":customer" does not exist', array(':customer' => $server['customer'])));
            }

        }else{
            $v->setError(tr('servers_validate(): Please specify a customer'));
        }

        if($server['ssh_account']){
            $server['ssh_accounts_id'] = sql_get('SELECT `id` FROM `ssh_accounts` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['ssh_account']), true);

            if(!$server['ssh_accounts_id']){
                $v->setError(tr('servers_validate(): Specified SSH account ":account" does not exist', array(':account' => $server['ssh_account'])));
            }

        }else{
            $server['ssh_accounts_id'] = null;
        }

        /*
         * Already exists?
         */
        $exists = sql_get('SELECT `id` FROM `servers` WHERE `hostname` = :hostname AND `ssh_accounts_id` = :ssh_accounts_id AND `id` != :id', array(':hostname' => $server['hostname'], ':ssh_accounts_id' => $server['ssh_account'], ':id' => isset_get($server['id'])), true);

        if($exists){
            $v->setError(tr('servers_validate(): A server with hostname ":hostname" and user ":user" already exists', array(':hostname' => $server['hostname'], ':ssh_accounts_id' => $server['ssh_account'])));
        }

        $server['seohostname']  = seo_unique($server['hostname'], 'servers', isset_get($server['id']), 'seohostname');
        $server['bill_duedate'] = date_convert($server['bill_duedate'], 'mysql');

        $v->isValid();

        return $server;

    }catch(Exception $e){
        throw new bException('servers_validate(): Failed', $e);
    }
}



/*
 *
 */
function servers_test($hostname){
    try{
        sql_query('UPDATE `servers` SET `status` = "testing" WHERE `hostname` = :hostname', array(':hostname' => $hostname));
        $result = servers_exec($hostname, 'echo 1');
        $result = array_pop($result);

        if($result != '1'){
            throw new bException(tr('servers_test(): Failed to SSH connect to ":server"', array(':server' => $user.'@'.$hostname.':'.$port)), 'failedconnect');
        }

        sql_query('UPDATE `servers` SET `status` = NULL WHERE `hostname` = :hostname', array(':hostname' => $hostname));

    }catch(Exception $e){
        throw new bException('servers_test(): Failed', $e);
    }
}



/*
 *
 */
function servers_exec($host, $commands, $options = null, $background = false, $function = 'exec'){
    global $_CONFIG;

    try{
        array_params($options);
        array_default($options, 'hostkey_check', false);
        array_default($options, 'arguments'    , '-T');
        array_default($options, 'background'   , $background);
        array_default($options, 'commands'     , $commands);

        if(is_array($host)){
            $server = $host;

        }else{
            $server = servers_get($host);
        }

        if(!$server){
            $server = array();
        }

        if(!$options){
            $options = ' -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no'.($_CONFIG['servers']['ssh']['connect_timeout'] ? ' -o ConnectTimeout='.$_CONFIG['servers']['ssh']['connect_timeout'] : '');
        }

        /*
         * Ensure that ssk_keys directory exists and that its safe
         */
        load_libs('ssh');

        /*
         * Execute command on remote server
         */
        $options = array_merge($options, $server);
        $result  = ssh_exec($options, null, $background, $function);

        return $result;

    }catch(Exception $e){
        /*
         * Try deleting the keyfile anyway!
         */
        try{
            if(!empty($filepath)){
                chmod($filepath, 0600);
                file_delete($filepath);
            }

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify(tr('servers_exec() cannot delete key'), $e, 'developers');
        }

        notify(tr('servers_exec() exception'), $e, 'developers');
        throw new bException('servers_exec(): Failed', $e);
    }
}



/*
 *
 */
function servers_get($host, $database = false, $return_proxies = true, $limited_columns = false){
    try{
        if($limited_columns){
            $query =  'SELECT    `servers`.`hostname`,
                                 `servers`.`port`,
                                 `servers`.`ssh_proxies_id` ';

        }else{
            $query =  'SELECT    `servers`.`hostname`,
                                 `servers`.`port`,
                                 `servers`.`ssh_accounts_id`,
                                 `servers`.`ssh_proxies_id`,

                                 `ssh_accounts`.`username`,
                                 `ssh_accounts`.`ssh_key` ';
        }

        $from  = ' FROM      `servers`

                   LEFT JOIN `ssh_accounts`
                   ON        `servers`.`ssh_accounts_id` = `ssh_accounts`.`id`';


        if(is_numeric($host)){
            $where   = ' WHERE `servers`.`id`       = :id';
            $execute = array(':id'       => $host);

        }elseif(substr($host, 0, 1) === '*'){
            $host    = substr($host, 1);
            $where   = ' WHERE `servers`.`hostname` LIKE :hostname';
            $execute = array(':hostname' => '%'.$host.'%');

        }else{
            $where   = ' WHERE `servers`.`hostname` = :hostname';
            $execute = array(':hostname' => $host);
        }

        if($database){
            $query .= ' ,
                        `database_accounts`.`username`      AS `db_username`,
                        `database_accounts`.`password`      AS `db_password`,
                        `database_accounts`.`root_password` AS `db_root_password`';

            $from  .= ' LEFT JOIN `database_accounts`
                        ON        `database_accounts`.`id` = `servers`.`database_accounts_id` ';
        }

        $server = sql_get($query.$from.$where, $execute);

        if($server and $return_proxies){
            $server['proxies'] = array();

            $proxy = $server['ssh_proxies_id'];

            while($proxy){
                $server_proxy        = servers_get($proxy, $database, false, true);
                $server['proxies'][] = $server_proxy;
                $proxy               = $server_proxy['ssh_proxies_id'];
            }
        }

        return $server;

    }catch(Exception $e){
        if($e->getCode() == 'multiple'){
            throw new bException(tr('servers_get(): Specified hostname ":hostname" matched multiple results, please specify a more exact hostname', array(':hostname' => $host)), 'multiple');
        }

        throw new bException('servers_get(): Failed', $e);
    }
}



/*
 * Detect the operating system on the specified host
 * @param  string $hostname The name of the host where to detect the operating system
 * @return array            An array containing the operatings system type (linux, windows, macos, etc), group (ubuntu group, redhad group, debian group), name (ubuntu, mint, fedora, etc), and version (7.4, 16.04, etc)
 * @see servers_get_os()
 */
function servers_detect_os($hostname){
    try{
        /*
         * Getting complete operating system distribution
         */
        $output_version = servers_exec($hostname, 'cat /proc/version');

        if(empty($output_version)){
            throw new bException(tr('servers_detect_os(): No operating system found on /proc/version for hostname ":hostname"', array(':hostname' => $hostname)), 'unknown');
        }

        /*
         * Determine to which group belongs the operating system
         */
        preg_match('/(ubuntu |debian |red hat )/i', $output_version, $matches);

        if(empty($matches)){
            throw new bException(tr('servers_detect_os(): No group version found'), 'unknown');
        }

        $group = trim(strtolower($matches[0]));

        switch($group){
            case 'debian':
                $release = servers_exec($hostname, 'cat /etc/issue');
                break;

            case 'ubuntu':
                $release = servers_exec($hostname, 'cat /etc/issue');
                break;

            case 'red hat':
                $group   = 'redhat';
                $release = servers_exec($hostname, 'cat /etc/redhat-release');
                break;

            default:
                throw new bException(tr('servers_detect_os(): No os group valid :group', array(':group' => $matches[0])), 'invalid');
        }

        if(empty($release)){
            throw new bException(tr('servers_detect_os(): No data found on for os group ":group"', array(':group' => $matches[0])), 'not-exist');
        }

        $server_os['type']  = 'linux';
        $server_os['group'] = $group;

        /*
         * Getting operating systema name based on release file(/etc/issue or /etc/redhad-release)
         */
        preg_match('/((:?[kxl]|edu)?ubuntu|mint|debian|red hat enterprise|fedora|centos)/i', $release, $matches);

        if(!isset($matches[0])){
            throw new bException(tr('servers_detect_os(): No name found for os group ":group"', array(':group' => $matches[0])), 'not-exist');
        }

        $server_os['name'] = strtolower($matches[0]);

        /*
         * Getting complete version for the operating system
         */
        preg_match('/\d*\.?\d+/', $release, $version);

        if(!isset($version[0])){
            throw new bException(tr('servers_detect_os(): No version found for os ":os"', array(':os' => $server_os['name'])), 'not-exist');
        }

        $server_os['version'] = $version[0];

        return $server_os;

    }catch(Exception $e){
        throw new bException('servers_get_os(): Failed', $e);
    }
}
?>
