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

        $v = new validate_form($server, 'port,user,hostname,provider,customer,description');

        $v->isNotEmpty($server['user']    , tr('Please specifiy a user'));
        $v->isNotEmpty($server['hostname'], tr('Please specifiy a hostnames'));
        $v->isNotEmpty($server['port']    , tr('Please specifiy a port'));
        $v->isNotEmpty($server['provider'], tr('Please specifiy a provider'));
        $v->isNotEmpty($server['customer'], tr('Please specifiy a customer'));

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
         * Username
         */
        if(empty($server['user'])){
            $v->setError(tr('Server has no SSH user specified'));

        }else{
            $users_id = sql_get('SELECT `id` FROM `users` WHERE `username` = :username OR `email` = :email', 'id', array(':username' => $server['user'], ':email' => $server['user']));

            if(!$users_id){
                $v->setError(tr('Specified user ":user" does not exist', array(':user' => $server['user'])));
            }
        }

        /*
         * Already exists?
         */
        if($server['id']){
            if(sql_get('SELECT `id` FROM `servers` WHERE `hostname` = :hostname AND `user` = :user AND `id` != :id', array(':hostname' => $server['hostname'], ':user' => $server['user'], ':id' => $server['id']), 'id')){
                $v->setError(tr('servers_validate(): A server with hostname ":hostname" and user ":user" already exists', array(':hostname' => $server['hostname'], ':user' => $server['user'])));
            }

        }else{
            if(sql_get('SELECT `id` FROM `servers` WHERE `hostname` = :hostname AND `user` = :user', array(':hostname' => $server['hostname'], ':user' => $server['user']), 'id')){
                $v->setError(tr('servers_validate(): A server with hostname ":hostname" and user ":user" already exists', array(':hostname' => $server['hostname'], ':user' => $server['user'])));
            }
        }

        $server['providers_id'] = sql_get('SELECT `id` FROM `providers` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['provider']), 'id');
        $server['customers_id'] = sql_get('SELECT `id` FROM `customers` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $server['customer']), 'id');

        $server['users_id'] = sql_get('SELECT `id` FROM `users` WHERE (`username` = :username OR `email` = :email) AND `status` IS NULL', array(':username' => $server['user'], ':email' => $server['user']), 'id');

        if(!$server['providers_id']){
            $v->setError(tr('servers_validate(): Specified provider ":provider" does not exist', array(':provider' => $server['provider'])));
        }

        if(!$server['customers_id']){
            $v->setError(tr('servers_validate(): Specified customer ":customer" does not exist', array(':customer' => $server['customer'])));
        }

        if(!$server['users_id']){
            $v->setError(tr('servers_validate(): Specified user ":user" does not exist', array(':user' => $server['user'])));
        }

        $server['seohostname'] = seo_unique($server['hostname'], 'servers', $server['id'], 'seohostname');

        $v->isValid();

        return $server;

    }catch(Exception $e){
        throw new bException('servers_validate(): Failed', $e);
    }
}



/*
 *
 */
function servers_test($hostname, $port, $user){
    try{
        $result = safe_exec('ssh -Tp '.$port.' '.$user.'@'.$hostname.' echo 1');

        if(array_shift($result) != '1'){
            throw new bException(tr('Failed to SSH connect to ":server"', array(':server' => $user.'@'.$hostname.':'.$port)), 'failedconnect');
        }

    }catch(Exception $e){
        throw new bException('servers_test(): Failed', $e);
    }
}

function servers_execute($hostname, $port, $user){
    try{
        $result = safe_exec('ssh -Tp '.$port.' '.$user.'@'.$hostname.' echo 1');

        if(array_shift($result) != '1'){
            throw new bException(tr('Failed to SSH connect to ":server"', array(':server' => $user.'@'.$hostname.':'.$port)), 'failedconnect');
        }

    }catch(Exception $e){
        throw new bException('servers_test(): Failed', $e);
    }
}



?>
