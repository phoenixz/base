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



/*
 * Update a server
 */
function servers_update($server){
    try{
        user_or_redirect();

        array_params($server);
        array_default($server, 'port', 22);

        if(empty($server['id'])){
            throw new bException('servers_update(): Domain has no id specified');
        }

        if(empty($server['ip'])){
            throw new bException('servers_insert(): Domain has no IP specified');
        }

        load_libs('validate');

        $v = new validate_form();

        if(empty($server['hostname'])){
            throw new bException('servers_update(): Domain has no hostname specified');
        }

        if(sql_get('SELECT `id` FROM `servers` WHERE `ip` :ip AND `port` = :port AND `id` != :id', array(':ip' => $server['ip'], ':port' => $server['port'], ':id' => $server['id']), 'id')){
            throw new bException('servers_update(): A server with that IPv4 or IPv6 already exists');
        }

        sql_query('UPDATE `servers`

                   SET    `modifiedon` = NOW(),
                          `modifiedby` = :users_id,
                          `ip`         = :ip,
                          `port`       = :port,
                          `hostname`   = :hostname

                   WHERE  `id`         = :id,

                   AND    `createdby`  = :createdby',

                   array(':modifiedby' => $_SESSION['user']['id'],
                         ':ip'         => $server['ip'],
                         ':port'       => $server['port'],
                         ':hostname'   => $server['hostname'],
                         ':id'         => $server['id'],
                         ':createdby'  => $_SESSION['users']['id']));

    }catch(Exception $e){
        throw new bException('servers_update(): Failed', $e);
    }
}

/*
 * Get server group data
 */
function servers_groups_list($params = null, $columns = '*', $require_filters = true){
    try{
        user_or_redirect();

        $admin = has_rights('admin');

        array_params($params);
        array_default($params, 'all'            , $admin);
        array_default($params, 'require_filters', $require_filters);

        if($params['all']){
            if(!$admin){
                throw new bException(tr('servers_groups_list(): "all" option can only be used by admin users'), 'noadmin');
            }

            $filters = sql_filters($params, 'id,ip,port,status,name');

        }else{
            /*
             *
             */
            $params['groups`.`createdby'] = $_SESSION['user']['id'];
            $filters                      = sql_filters($params, 'id,ip,port,status,name,servers_groups`.`createdby');
        }

        $filters['filters'] = sql_where(implode(' AND ', $filters['filters']), $params['require_filters']);

        return sql_list('SELECT    '.$columns.',
                                   `tk_groups`.`name` as group_name,
                                   `users`.`name`,
                                   `users`.`username`,
                                   `users`.`email`

                         FROM      `tk_groups`

                         LEFT JOIN `users`
                         ON        `users`.`id` = `tk_groups`.`createdby` '.

                         $filters['filters'].' ORDER BY `tk_groups`.`createdon` DESC'.(isset_get($params['limit']) ? ' LIMIT '.cfi($params['limit']) : ''),

                         $filters['execute']);

    }catch(Exception $e){
        throw new bException('servers_groups_list(): Failed', $e);
    }
}


/*
 * Get server data
 */
function servers_list($params = null, $columns = '*', $require_filters = true){
    try{
        user_or_redirect();

        array_params($params);
        array_default($params, 'all'            , has_rights('admin'));
        array_default($params, 'status'         , null);
        array_default($params, 'require_filters', $require_filters);

        if(has_rights('admin')){
            if($params['all']){
                $filters = sql_filters($params, 'id,ip,port,hostname', 'servers');

            }else{
                $filters = sql_filters($params, 'id,ip,port,hostname,status', 'servers');
            }

        }else{
            if($params['all']){
                throw new bException('servers_list(): "all" option can only be used by admin users');
            }

            $params['servers`.`createdby'] = $_SESSION['user']['id'];
            $filters = sql_filters($params, 'id,ip,port,status,hostname,createdby', 'servers');
        }

        $filters['filters'] = sql_where(implode(' AND ', $filters['filters']), $params['require_filters']);

        return sql_list('SELECT    '.$columns.',
                                   `users`.`name`,
                                   `users`.`username`,
                                   `users`.`email`

                         FROM      `servers`

                         LEFT JOIN `users`
                         ON        `users`.`id` = `servers`.`createdby` '.

                         $filters['filters'].' ORDER BY `servers`.`createdon` DESC'.(isset_get($params['limit']) ? ' LIMIT '.cfi($params['limit']) : ''),

                         $filters['execute']);

    }catch(Exception $e){
        throw new bException('servers_list(): Failed', $e);
    }
}
?>
