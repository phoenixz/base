<?php
/*
 * SSH library library
 *
 * This library contains functions to manage SSH accounts
 *
 * Written and Copyright by Sven Oostenbrink
 */



/*
 * SSH account validation
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
                                 'arguments' => '-nNf -o ControlMaster=yes -o ControlPath='.$socket), ' 2>&1 >'.ROOT.'/data/log/ssh_master');

        return $socket;

    }catch(Exception $e){
showdie($e);
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
showdie($e);
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
showdie($e);
        throw new bException('ssh_stop_control_master(): Failed', $e);
    }
}



/*
 *
 */
function ssh_exec($server, $commands = null){

    try{

        array_params($server);
        array_default($server, 'hostname'     , '');
        array_default($server, 'ssh_key'      , '');
        array_default($server, 'port'         , 22);
        array_default($server, 'hostkey_check', false);
        array_default($server, 'arguments'    , '-T');

        if(!$server['hostkey_check']){
            $server['arguments'] .= ' -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null ';
        }

        /*
         * Ensure that ssh_keys directory exists and that its safe
         */
        load_libs('file');
        file_ensure_path(ROOT.'data/ssh_keys');
        chmod(ROOT.'data/ssh_keys', 0770);

        /*
         * Safely create SSH key file
         */
        $keyfile = ROOT.'data/ssh_keys/'.str_random(8);

        touch($keyfile);
        chmod($keyfile, 0600);
        file_put_contents($keyfile, $server['ssh_key'], FILE_APPEND);
        chmod($keyfile, 0400);

        /*
         * Execute command on remote server
         */
//show('ssh '.$server['arguments'].' -p '.$server['port'].' -i '.$keyfile.' '.$server['username'].'@'.$server['hostname'].' '.$commands);
        $result = safe_exec('ssh '.$server['arguments'].' -p '.$server['port'].' -i '.$keyfile.' '.$server['username'].'@'.$server['hostname'].' '.$commands);

        chmod($keyfile, 0600);
        file_delete($keyfile);

        return $result;

    }catch(Exception $e){
showdie($e);
        notify(tr('ssh_exec() exception'), $e, 'developers');

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
            notify(tr('ssh_exec() cannot delete key'), $e, 'developers');
        }

        throw new bException('ssh_exec(): Failed', $e);
    }
}



/*
 *
 */
function ssh_get_key($username){
    try{
        return sql_get('SELECT `ssh_key` FROM `ssh_accounts` WHERE `username` = :username', 'ssh_key', array(':username' => $username));

    }catch(Exception $e){
        throw new bException('ssh_get_key(): Failed', $e);
    }
}
?>
