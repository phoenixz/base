<?php
/*
 * Custom servers library
 *
 * This library contains functions to manage toolkit servers
 *
 * Written and Copyright by Sven Oostenbrink
 */



/*
 *
 */
function servers_validate($server){
    global $_CONFIG;

    try{
        load_libs('validate,file,seo');

        $v = new validate_form($server, 'port,hostname,provider,customer,ssh_account,description');
        $v->isNotEmpty($server['ssh_account'], tr('Please specifiy an SSH account'));
        $v->isNotEmpty($server['hostname']   , tr('Please specifiy a hostnames'));
        $v->isNotEmpty($server['port']       , tr('Please specifiy a port'));
        $v->isNotEmpty($server['provider']   , tr('Please specifiy a provider'));
        $v->isNotEmpty($server['customer']   , tr('Please specifiy a customer'));

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
        $server['providers_id']    = sql_get('SELECT `id` FROM `providers`    WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['provider'])   , 'id');
        $server['customers_id']    = sql_get('SELECT `id` FROM `customers`    WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['customer'])   , 'id');
        $server['ssh_accounts_id'] = sql_get('SELECT `id` FROM `ssh_accounts` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['ssh_account']), 'id');

        if(!$server['providers_id']){
            $v->setError(tr('servers_validate(): Specified provider ":provider" does not exist', array(':provider' => $server['provider'])));
        }

        if(!$server['customers_id']){
            $v->setError(tr('servers_validate(): Specified customer ":customer" does not exist', array(':customer' => $server['customer'])));
        }

        if(!$server['ssh_accounts_id']){
            $v->setError(tr('servers_validate(): Specified SSH account ":account" does not exist', array(':account' => $server['ssh_account'])));
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
        $result = servers_exec($hostname, 'echo 1');
        $result = array_pop($result);

        if($result != '1'){
            throw new bException(tr('servers_test(): Failed to SSH connect to ":server"', array(':server' => $user.'@'.$hostname.':'.$port)), 'failedconnect');
        }

    }catch(Exception $e){
        throw new bException('servers_test(): Failed', $e);
    }
}



/*
 *
 */
function servers_exec($hostname, $commands, $options = null){
    try{
        $server = sql_get('SELECT    `servers`.`ssh_accounts_id`,
                                     `servers`.`port`,

                                     `ssh_accounts`.`username`,
                                     `ssh_accounts`.`ssh_key`

                           FROM      `servers`

                           LEFT JOIN `ssh_accounts`
                           ON        `servers`.`ssh_accounts_id` = `ssh_accounts`.`id`

                           WHERE     `servers`.`hostname` = :hostname', array(':hostname' => $hostname));

        if(!$server){
            throw new bException(tr('servers_exec(): Specified hostname ":hostname" does not exist', array(':hostname' => $hostname)), 'not-exists');
        }

        if(!$options){
            $options = '-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no';
        }

        /*
         * Ensure that ssk_keys directory exists and that its safe
         */
        load_libs('file');
        file_ensure_path(ROOT.'data/ssh_keys');
        chmod(ROOT.'data/ssh_keys', 0770);

        /*
         * Safely create SSH key file
         */
        $key_file = str_random(8);
        $filepath = ROOT.'data/ssh_keys/'.$key_file;

        touch($filepath);
        chmod($filepath, 0600);
        file_put_contents($filepath, $server['ssh_key'], FILE_APPEND);
        chmod($filepath, 0400);

        /*
         * Execute command on remote server
         */
        $result = safe_exec($command = 'ssh '.$options.' -Tp '.$server['port'].' -i '.$filepath.' '.$server['username'].'@'.$hostname.' '.$commands);
//show($command);

        chmod($filepath, 0600);
        file_delete($filepath);

        if(preg_match('/Warning: Permanently added \'\[.+?\]:\d{1,5}\' \(\w+\) to the list of known hosts\./', isset_get($result[0]))){
            /*
             * Remove known host warning from results
             */
            array_shift($result);
        }

        return $result;

    }catch(Exception $e){
        notify(tr('servers_exec() exception'), $e, 'developers');

        /*
         * Try deleting the keyfile anyway!
         */
        try{
            if(!empty($filepath)){
                safe_exec(chmod($filepath, 0600));
                file_delete($filepath);
            }

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify(tr('servers_exec() cannot delete key'), $e, 'developers');
        }

        throw new bException('servers_exec(): Failed', $e);
    }
}
?>
