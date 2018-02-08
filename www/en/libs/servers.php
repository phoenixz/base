<?php
/*
 * Custom servers library
 *
 * This library contains functions to manage toolkit servers
 *
 * Written and Copyright by Sven Oostenbrink
 */

servers_init();



/*
 * Initialize the library
 */
function servers_init(){
    try{
        load_config('servers');

    }catch(Exception $e){
        throw new bException('servers_init(): Failed', $e);
    }
}



/*
 *
 */
function servers_validate($server, $password_strength = true){
    global $_CONFIG;

    try{
        load_libs('validate,file,seo');

        $v = new validate_form($server, 'port,hostname,provider,customer,ssh_account,description,database_accounts_id');
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
            log_console(tr('servers_validate(): No SSH port specified, using port ":port" as default', array(':port' => $server['port'])), 'defaultport', 'yellow');
        }

        if(!is_numeric($server['port']) or ($server['port'] < 1) or ($server['port'] > 65535)){
            $v->setError(tr('servers_validate(): Specified port ":port" is not valid', array(':port' => $server['port'])));
        }

        /*
         * Validate provider, customer, and ssh account
         */
        if($server['provider']){
            $server['providers_id'] = sql_get('SELECT `id` FROM `providers` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['provider']), 'id');

            if(!$server['providers_id']){
                $v->setError(tr('servers_validate(): Specified provider ":provider" does not exist', array(':provider' => $server['provider'])));
            }

        }else{
            $v->setError(tr('servers_validate(): Please specify a provider'));
        }

        if($server['customer']){
            $server['customers_id'] = sql_get('SELECT `id` FROM `customers` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['customer']), 'id');

            if(!$server['customers_id']){
                $v->setError(tr('servers_validate(): Specified customer ":customer" does not exist', array(':customer' => $server['customer'])));
            }

        }else{
            $v->setError(tr('servers_validate(): Please specify a customer'));
        }

        if($server['ssh_account']){
            $server['ssh_accounts_id'] = sql_get('SELECT `id` FROM `ssh_accounts` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['ssh_account']), 'id');

            if(!$server['ssh_accounts_id']){
                $v->setError(tr('servers_validate(): Specified SSH account ":account" does not exist', array(':account' => $server['ssh_account'])));
            }

        }else{
            $server['ssh_accounts_id'] = null;
        }

        /*
         * Already exists?
         */
        if($server['id']){
            if(sql_get('SELECT `id` FROM `servers` WHERE `hostname` = :hostname AND `ssh_accounts_id` = :ssh_accounts_id AND `id` != :id', array(':hostname' => $server['hostname'], ':ssh_accounts_id' => $server['ssh_account'], ':id' => $server['id']), 'id')){
                $v->setError(tr('servers_validate(): A server with hostname ":hostname" and user ":user" already exists', array(':hostname' => $server['hostname'], ':ssh_accounts_id' => $server['ssh_account'])));
            }

        }else{
            if(sql_get('SELECT `id` FROM `servers` WHERE `hostname` = :hostname AND `ssh_accounts_id` = :ssh_accounts_id', array(':hostname' => $server['hostname'], ':ssh_accounts_id' => $server['ssh_account']), 'id')){
                $v->setError(tr('servers_validate(): A server with hostname ":hostname" and ssh_accounts_id ":ssh_accounts_id" already exists', array(':hostname' => $server['hostname'], ':ssh_accounts_id' => $server['ssh_account'])));
            }
        }

        $server['seohostname']  = seo_unique($server['hostname'], 'servers', $server['id'], 'seohostname');
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
function servers_exec($host, $commands, $options = null, $background = false, $local = false){
    global $_CONFIG;

    try{
        array_params($options);
        array_default($options, 'hostkey_check', false);
        array_default($options, 'arguments'    , '-T');
        array_default($options, 'background'   , $background);
        array_default($options, 'local'        , $local);
        array_default($options, 'commands'     , $commands);

        if(is_array($host)){
            $server = $host;

        }else{
            $server = servers_get($host);
        }

        if(!$server){
            throw new bException(tr('servers_exec(): Specified hostname ":hostname" does not exist', array(':hostname' => $host)), 'not-exists');
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
        $result  = ssh_exec($options);

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
function servers_get($host, $database = false){
    try{
        $query =  'SELECT    `servers`.`hostname`,
                             `servers`.`port`,
                             `servers`.`ssh_accounts_id`,

                             `ssh_accounts`.`username`,
                             `ssh_accounts`.`ssh_key`';

        $from  = ' FROM      `servers`

                   LEFT JOIN `ssh_accounts`
                   ON        `servers`.`ssh_accounts_id` = `ssh_accounts`.`id`';


        if(is_numeric($host)){
            $where   = ' WHERE `servers`.`id`       = :id';
            $execute = array(':id'       => $host);

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

        return $server;

    }catch(Exception $e){
        throw new bException('servers_get(): Failed', $e);
    }
}
?>
