<?php
/*
 * Servers library
 *
 * This library contains functions to manage registered servers
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
 * @package servers
 *
 * @return void
 */
function servers_library_init(){
    try{
        load_libs('ssh');
        load_config('servers');

    }catch(Exception $e){
        throw new bException('servers_library_init(): Failed', $e);
    }
}



/*
 * Validate the specified server. In case $structure_only is specified, only the array keys will be ensured available. If $password_strength is specified true, the specified passwords will be tested for strength as well.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param array $server
 * @param boolean $structure_only
 * @param boolean $password_strength
 * @return array
 */
function servers_validate($server, $structure_only = false, $password_strength = false){
    global $_CONFIG;

    try{
        load_libs('validate,file,seo,customers,providers');

        $v = new validate_form($server, 'id,ipv4,ipv6,port,hostname,hostnames,seoprovider,seocustomer,ssh_account,description,ssh_proxy,database_accounts_id,bill_duedate,cost,interval,allow_sshd_modification,register');

        if($structure_only){
            return $server;
        }

        /*
         * Check password
         */
        if($password_strength){
            $v->isPassword($server['db_password'], tr('Please specifiy a strong password'), '');
        }

        if($server['database_accounts_id']){
            $exists = sql_get('SELECT `id` FROM `database_accounts` WHERE `id` = :id', true, array(':id' => $server['database_accounts_id']), 'core');

            if(!$exists){
                $v->setError(tr('The specified database account does not exist'));
            }

        }else{
            $server['database_accounts_id'] = null;
        }

        /*
         * Hostname
         */
        $v->isNotEmpty($server['hostname'], tr('Please specifiy a hostname'));
        $v->isDomain($server['hostname'], tr('The hostname ":hostname" is invalid', array(':hostname' => $server['hostname'])));

        if(!empty($server['url']) and !FORCE){
            $v->setError(tr('Both hostname ":hostname" and URL ":url" specified, please specify one or the other', array(':hostname' => $server['hostname'], ':url' => $server['url'])));

        }elseif(!preg_match('/[a-z0-9][a-z0-9-.]+/', $server['hostname'])){
            $v->setError(tr('Invalid server specified, be sure it contains only a-z, 0-9, . and -'));
        }

        /*
         * Description
         */
        if(empty($server['description'])){
            $server['description'] = '';

        }else{
            $v->hasMinChars($server['description'],   16, tr('Please specifiy a minimum of 16 characters for the description'));
            $v->hasMaxChars($server['description'], 2047, tr('Please specifiy a maximum of 2047 characters for the description'));

            $server['description'] = cfm($server['description']);
        }

        /*
         * IPv4 check
         */
        if($server['ipv4']){
            /*
             * IP was specified manually
             */
            $v->isFilter($server['ipv4'], FILTER_VALIDATE_IP, tr('Please specify a valid IP address'));

        }else{
            /*
             * IP not specified, try to lookup
             */
            $server['ipv4'] = gethostbynamel($server['hostname']);

            if(!$server['ipv4']){
                $server['ipv4'] = null;

            }else{
                if(count($server['ipv4']) == 1){
                    $server['ipv4'] = array_shift($server['ipv4']);

                }else{
                    $v->isFilter($server['ipv4'], FILTER_VALIDATE_IP, tr('Failed to auto lookup IPv4, please specify a valid IP address'));
                }
            }
        }

        /*
         * Port check
         */
        if(empty($server['port'])){
            $server['port'] = ssh_get_port();
            log_console(tr('servers_validate(): No SSH port specified, using port ":port" as default', array(':port' => $server['port'])), 'yellow');
        }

        if(!is_numeric($server['port']) or ($server['port'] < 1) or ($server['port'] > 65535)){
            $v->setError(tr('Specified port ":port" is not valid', array(':port' => $server['port'])));
        }

        $server['allow_sshd_modification'] = (boolean) $server['allow_sshd_modification'];

        if($server['hostnames']){
            $server['hostnames'] = array_force($server['hostnames'], "\n");

            foreach($server['hostnames'] as &$hostname){
                $hostname = trim($hostname);
                $v->isDomain($hostname, tr('The hostname ":hostname" is invalid', array(':hostname' => $hostname)));
            }

            $v->isValid();

            /*
             * Ensure that the specified hostnames do not yet exist
             */
            foreach($server['hostnames'] as &$hostname){
                $exists = sql_get('SELECT `id` FROM `servers_hostnames` WHERE `servers_id` != :servers_id AND `hostname` = :hostname', true, array(':servers_id' => $server['id'], ':hostname' => $hostname), 'core');

                if($exists){
                    $v->setError(tr('Specified hostname ":hostname" already exists', array(':hostname' => $server['hostname'])));
                }
            }

            $server['hostnames'][] = $server['hostname'];
            $server['hostnames']   = array_unique($server['hostnames']);
        }

        $v->isValid();

        /*
         * Validate provider, customer, and ssh account
         */
        if($server['seoprovider']){
            $server['providers_id'] = sql_get('SELECT `id` FROM `providers` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $server['seoprovider']), 'core');

            if(!$server['providers_id']){
                $v->setError(tr('Specified provider ":provider" does not exist', array(':provider' => $server['seoprovider'])));
            }

        }else{
            $server['providers_id'] = null;
            //$v->setError(tr('Please specify a provider'));
        }

        if($server['seocustomer']){
            $server['customers_id'] = sql_get('SELECT `id` FROM `customers` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $server['seocustomer']), 'core');

            if(!$server['customers_id']){
                $v->setError(tr('Specified customer ":customer" does not exist', array(':customer' => $server['seocustomer'])));
            }

        }else{
            $server['customers_id'] = null;
        }

        if($server['ssh_account']){
            $server['ssh_accounts_id'] = sql_get('SELECT `id` FROM `ssh_accounts` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $server['ssh_account']), 'core');

            if(!$server['ssh_accounts_id']){
                $v->setError(tr('Specified SSH account ":account" does not exist', array(':account' => $server['ssh_account'])));
            }

        }else{
            $server['ssh_accounts_id'] = null;
        }

        /*
         * Already exists?
         */
        $exists = sql_get('SELECT `id` FROM `servers` WHERE `hostname` = :hostname AND `ssh_accounts_id` '.sql_is($server['ssh_accounts_id']).' :ssh_accounts_id AND `id` != :id LIMIT 1', true, array(':hostname' => $server['hostname'], ':ssh_accounts_id' => $server['ssh_accounts_id'], ':id' => isset_get($server['id'], 0)), 'core');

        if($exists){
            $v->setError(tr('A server with hostname ":hostname" and SSH account ":ssh_account" already exists', array(':hostname' => $server['hostname'], ':ssh_account' => $server['ssh_account'])));
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
 * Inserts a new server in the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param params $server The server data to be inserted into the database
 * @return params The validated server data, including server[id]
 */
function servers_insert($server){
    try{
        $server = servers_validate($server);

        sql_query('INSERT INTO `servers` (`createdby`, `meta_id`, `status`, `hostname`, `seohostname`, `port`, `database_accounts_id`, `bill_duedate`, `cost`, `interval`, `providers_id`, `customers_id`, `ssh_accounts_id`, `allow_sshd_modification`, `description`, `ipv4`)
                   VALUES                (:createdby , :meta_id , :status , :hostname , :seohostname , :port , :database_accounts_id , :bill_duedate , :cost , :interval , :providers_id , :customers_id , :ssh_accounts_id , :allow_sshd_modification , :description , :ipv4)',

                   array(':status'                  => ($server['ssh_accounts_id'] ? 'testing' : null),
                         ':createdby'               => isset_get($_SESSION['user']['id']),
                         ':meta_id'                 => meta_action(),
                         ':hostname'                => $server['hostname'],
                         ':seohostname'             => $server['seohostname'],
                         ':port'                    => $server['port'],
                         ':database_accounts_id'    => $server['database_accounts_id'],
                         ':cost'                    => $server['cost'],
                         ':interval'                => $server['interval'],
                         ':bill_duedate'            => $server['bill_duedate'],
                         ':providers_id'            => $server['providers_id'],
                         ':customers_id'            => $server['customers_id'],
                         ':ssh_accounts_id'         => $server['ssh_accounts_id'],
                         ':allow_sshd_modification' => $server['allow_sshd_modification'],
                         ':description'             => $server['description'],
                         ':ipv4'                    => $server['ipv4']));

        $server['id'] = sql_insert_id();

        if($server['register']){
            ssh_add_known_host($server['hostname'], $server['port']);
        }

        return $server;

    }catch(Exception $e){
        throw new bException('servers_insert(): Failed', $e);
    }
}



/*
 * Updates the specified server in the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param params $server The server data to be updated into the database
 * @return params The validated server data
 */
function servers_update($server){
    try{
        $server = servers_validate($server);
        meta_action($server['meta_id'], 'update');

        sql_query('UPDATE `servers`

                   SET    `status`                  = :status,
                          `hostname`                = :hostname,
                          `seohostname`             = :seohostname,
                          `port`                    = :port,
                          `database_accounts_id`    = :database_accounts_id,
                          `cost`                    = :cost,
                          `interval`                = :interval,
                          `bill_duedate`            = :bill_duedate,
                          `providers_id`            = :providers_id,
                          `customers_id`            = :customers_id,
                          `ssh_accounts_id`         = :ssh_accounts_id,
                          `allow_sshd_modification` = :allow_sshd_modification,
                          `description`             = :description,
                          `ipv4`                    = :ipv4

                   WHERE  `id`                      = :id',

                   array(':id'                      => $server['id'],
                         ':status'                  => ($server['ssh_accounts_id'] ? 'testing' : null),
                         ':hostname'                => $server['hostname'],
                         ':seohostname'             => $server['seohostname'],
                         ':port'                    => $server['port'],
                         ':database_accounts_id'    => $server['database_accounts_id'],
                         ':cost'                    => $server['cost'],
                         ':interval'                => $server['interval'],
                         ':bill_duedate'            => $server['bill_duedate'],
                         ':providers_id'            => $server['providers_id'],
                         ':customers_id'            => $server['customers_id'],
                         ':ssh_accounts_id'         => $server['ssh_accounts_id'],
                         ':allow_sshd_modification' => $server['allow_sshd_modification'],
                         ':description'             => $server['description'],
                         ':ipv4'                    => $server['ipv4']));

        return $server;

    }catch(Exception $e){
        throw new bException('servers_update(): Failed', $e);
    }
}



/*
 * Returns an array with all hostnames that are like the specified hostname
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param string $hostname The hostname section that is being searched for
 * @return array The list of hostnames that was found
 */
function servers_like($hostname){
    try{
        $server = sql_get('SELECT `hostname`

                           FROM   `servers_hostnames`

                           WHERE  `hostname`    LIKE :hostname
                           OR     `seohostname` LIKE :seohostname',

                           true, array(':hostname'    => '%'.$hostname.'%',
                                       ':seohostname' => '%'.$hostname.'%'));

        if(!$server){
            throw new bException(tr('servers_like(): Specified server ":server" does not exist', array(':server' => $hostname)), 'not-exist');
        }

        return $server;

    }catch(Exception $e){
        throw new bException('servers_like(): Failed', $e);
    }
}



/*
 * Return HTML for a servers select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available servers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param array $params The parameters required
 * @paramkey $params name
 * @paramkey $params class
 * @paramkey $params extra
 * @paramkey $params tabindex
 * @paramkey $params empty
 * @paramkey $params none
 * @paramkey $params selected
 * @paramkey $params parents_id
 * @paramkey $params status
 * @paramkey $params orderby
 * @paramkey $params resource
 * @return string HTML for a servers select box within the specified parameters
 */
function servers_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'    , 'seoserver');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No servers available'));
        array_default($params, 'none'    , tr('Select a server'));
        array_default($params, 'tabindex', 0);
        array_default($params, 'extra'   , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby' , '`hostname`');

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status']).' :status ';
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seohostname`, CONCAT(`hostname`, " (", `ipv4`, ")") AS `name` FROM `servers` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('servers_select(): Failed', $e);
    }
}



/*
 * Update the hostnames list for the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param integer $servers_id
 * @param array $hostnames
 *
 * @return integer The amount of hostnames added
 */
function servers_update_hostnames($servers_id, $hostnames){
    try{
        sql_query('DELETE FROM `servers_hostnames` WHERE `servers_id` = :servers_id', array(':servers_id' => $servers_id), 'core');

        if(!$hostnames){
            return false;
        }

        $insert  = sql_prepare('INSERT INTO `servers_hostnames` (`meta_id`, `servers_id`, `hostname`, `seohostname`)
                                VALUES                          (:meta_id , :servers_id , :hostname , :seohostname )', 'core');

        foreach($hostnames as $hostname){
            $insert->execute(array(':meta_id'     => meta_action(),
                                   ':servers_id'  => $servers_id,
                                   ':hostname'    => $hostname,
                                   ':seohostname' => seo_unique($hostname, 'servers_hostnames', null, 'seohostname')));
        }

        return count($hostnames);

    }catch(Exception $e){
        throw new bException('servers_update_hostnames(): Failed', $e);
    }
}



/*
 * Execute the specified commands on the specified server using ssh_exec() and return the results.
 *
 * If server is specified as an array, servers_exec() will assume the server data is available and send it directly to ssh_exec(). If server is specified as a string or integer, servers_exec() will look up the server in the database by either servers_id or hostname, and if found, use that server data to send the commands to ssh_exec()
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @param mixed $commands
 * @param boolean $background
 * @param string $function
 * @return array The results of the executed SSH commands in an array, each entry containing one line of the output
 * @see ssh_exec()
 */
function servers_exec($server, $commands = null, $background = false, $function = null){
    try{
        array_params($server, 'hostname');
        array_default($server, 'hostkey_check', true);
        array_default($server, 'background'   , $background);
        array_default($server, 'commands'     , $commands);

        $server = servers_get($server);

        if(empty($server['identity_file'])){
            if(empty($server['ssh_key'])){
                throw new bException(tr('servers_exec(): The specified server ":server" has no identity file or SSH key available', array(':server' => $server['hostname'])), 'missing-data');
            }

            /*
             * Copy the ssh_key to a temporal identity_file
             */
            $identity_file           = servers_create_identity_file($server['ssh_key']);
            $server['identity_file'] = ROOT.'data/ssh/keys/'.$identity_file;
            servers_clear_key($server);
        }


        /*
         * Execute command on remote server
         */
        $results = ssh_exec($server, null, false, $function);
        return $results;

    }catch(Exception $e){
        /*
         * Try deleting the keyfile anyway!
         */
        try{
            servers_remove_identity_file(isset_get($identity_file));
            notify($e);

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify(tr('servers_exec() cannot delete key'), $e, 'developers');
        }

        throw new bException('servers_exec(): Failed', $e);
    }
}



/*
 * Add SSH fingerprint all hostnames / ports for the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @return array The database entry data for the requested hostname
 */
function servers_register_host($server){
    try{
        $server    = servers_get($server);
        $hostnames = servers_get_hostnames($server);

        foreach($hostnames as $hostname){
            $server  = servers_get($hostname);
            $entries = ssh_add_known_host($server['hostname'], $server['port']);

            if($entries){
                $retval = array_merge($entries, $entries);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('servers_register_host(): Failed', $e);
    }
}



/*
 * Remove the SSH fingerprint for all hostnames / ports for the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @return void
 */
function servers_unregister_host($server){
    try{
        $server    = servers_get($server);
        $hostnames = servers_get_hostnames($server);

        foreach($hostnames as $hostname){
            $server  = servers_get($hostname);
            $entries = ssh_add_known_host($server['hostname'], $server['port']);

            if($entries){
                $retval = array_merge($entries, $entries);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('servers_unregister_host(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $server
 * @param boolean $database
 * @param boolean $return_proxies
 * @param boolean $limited_columns
 * @return array The database entry data for the requested hostname
 */
function servers_get($server, $database = false, $return_proxies = true, $limited_columns = false){
    try{
        if($server === null){
            /*
             * This means local server, no network connection needed
             */
            return null;
        }

        if(is_array($server)){
            /*
             * Specified host is an array, so it should already contain all
             * information
             *
             * Assume that if identity_file data is available, that we have a
             * complete one
             */
            if(!empty($server['id'])){
                return $server;
            }

        }elseif(!is_scalar($server)){
            throw new bException(tr('servers_get(): The specified server ":server" is invalid', array(':server' => $server)), 'invalid');
        }

        if($limited_columns){
            $query = 'SELECT `servers`.`id`,
                             `servers`.`hostname`,
                             `servers`.`port`,
                             `servers`.`ipv4`,

                             `ssh_accounts`.`username`,
                             `ssh_accounts`.`ssh_key` ';

        }else{
            $query = 'SELECT `servers`.`id`,
                             `servers`.`createdon`,
                             `servers`.`meta_id`,
                             `servers`.`port`,
                             `servers`.`cost`,
                             `servers`.`status`,
                             `servers`.`interval`,
                             `servers`.`hostname`,
                             `servers`.`seohostname`,
                             `servers`.`bill_duedate`,
                             `servers`.`ssh_accounts_id`,
                             `servers`.`database_accounts_id`,
                             `servers`.`description`,
                             `servers`.`ipv4`,
                             `servers`.`ipv6`,
                             `servers`.`allow_sshd_modification`,

                             `ssh_accounts`.`username`,
                             `ssh_accounts`.`ssh_key`,

                             `createdby`.`name`  AS `createdby_name`,
                             `createdby`.`email` AS `createdby_email`,

                             `providers`.`name`       AS `provider`,
                             `customers`.`name`       AS `customer`,
                             `providers`.`seoname`    AS `seoprovider`,
                             `customers`.`seoname`    AS `seocustomer`,
                             `ssh_accounts`.`seoname` AS `ssh_account`';
        }

        $from  = ' FROM      `servers`

                   LEFT JOIN `users` AS `createdby`
                   ON        `servers`.`createdby`            = `createdby`.`id`

                   LEFT JOIN `providers`
                   ON        `providers`.`id`                 = `servers`.`providers_id`

                   LEFT JOIN `customers`
                   ON        `customers`.`id`                 = `servers`.`customers_id`

                   LEFT JOIN `ssh_accounts`
                   ON        `ssh_accounts`.`id`              = `servers`.`ssh_accounts_id`

                   LEFT JOIN `servers_hostnames`
                   ON        `servers_hostnames`.`servers_id` = `servers`.`id` ';

        if(is_numeric($server)){
            /*
             * Host specified by id
             */
            $where   = ' WHERE `servers`.`id` = :id';
            $execute = array(':id' => $server);

        }elseif(is_array($server)){
            /*
             * Server host specified by array containing hostname
             */
            if(empty($server['hostname'])){
                throw new bException(tr('servers_get(): Specified server array does not contain a hostname'), 'invalid');
            }

            if(is_numeric($server['hostname'])){
                /*
                 * Host specified by id
                 */
                $where   = ' WHERE `servers`.`id` = :id';
                $execute = array(':id' => $server['hostname']);

            }elseif(is_scalar($server['hostname'])){
                /*
                 * Host specified by hostname
                 */
                $where   = ' WHERE `servers_hostnames`.`hostname` = :hostnames
                             OR    `servers`.`hostname`           = :hostname';

                $execute = array(':hostnames' => $server['hostname'],
                                 ':hostname'  => $server['hostname']);

            }else{
                throw new bException(tr('servers_get(): Specified server array hostname should be a natural numeric id or a hostname, but is a ":type"', array(':type' => gettype($server['hostname']))), 'invalid');
            }

        }elseif(is_string($server)){
            /*
             * Hostname specified by name
             */
            $where   = ' WHERE `servers_hostnames`.`hostname`    = :hostnames
                         OR    `servers_hostnames`.`seohostname` = :seohostnames
                         OR    `servers`.`hostname`              = :hostname
                         OR    `servers`.`seohostname`           = :seohostname';

            $execute = array(':hostnames'    => $server,
                             ':seohostnames' => $server,
                             ':hostname'     => $server,
                             ':seohostname'  => $server);

        }else{
            throw new bException(tr('servers_get(): Invalid server or hostname specified. Should be either a natural nuber, hostname, or array containing hostname information'), 'invalid');
        }

        if($database){
            $query .= ' ,
                        `database_accounts`.`username`      AS `db_username`,
                        `database_accounts`.`password`      AS `db_password`,
                        `database_accounts`.`root_password` AS `db_root_password`';

            $from  .= ' LEFT JOIN `database_accounts`
                        ON        `database_accounts`.`id` = `servers`.`database_accounts_id` ';
        }

        $dbserver = sql_get($query.$from.$where.' GROUP BY `servers`.`id`', null, $execute, 'core');

        if(!$dbserver){
            throw new bException(tr('servers_get(): Specified server ":server" does not exist', array(':server' => $server)), 'not-exist');
        }

        $dbserver['hostnames'] = sql_list('SELECT `id`, `hostname` FROM `servers_hostnames` WHERE `servers_id` = :servers_id AND `status` IS NULL', array(':servers_id' => $dbserver['id']), false, 'core');

        if($return_proxies){
            $dbserver['proxies'] = array();

            $dbserver_proxy = servers_get_proxy($dbserver['id']);

            if($dbserver_proxy){
                $dbserver['proxies'][] = $dbserver_proxy;
                $proxy                 = $dbserver_proxy['proxies_id'];

                while($proxy){
                    $dbserver_proxy = servers_get_proxy($proxy);
                    $proxy          = false;

                    if(!empty($dbserver_proxy)){
                        $dbserver['proxies'][] = $dbserver_proxy;
                        $proxy                 = $dbserver_proxy['proxies_id'];
                    }
                }

                $dbserver['proxies'] = array_filter($dbserver['proxies']);
            }

            if(is_array($server)){
                $dbserver = array_merge($server, $dbserver);
            }
        }

        return $dbserver;

    }catch(Exception $e){
        if($e->getCode() == 'multiple'){
            throw new bException(tr('servers_get(): Specified hostname ":hostname" matched multiple results, please specify a more exact hostname', array(':hostname' => (is_array($server) ? isset_get($server['hostname']) : $server))), 'multiple');
        }

        throw new bException('servers_get(): Failed', $e);
    }
}



/*
 * Test SSH connection with the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 * @exception bException/failed-connect when server connection test fails
 *
 * @param mixed $server The server to be tested. Specified either by only a hostname string, or a server array
 * @return void If the server test was executed succesfully, nothing happens
 */
function servers_test($hostname){
    try{
        sql_query('UPDATE `servers` SET `status` = "testing" WHERE `hostname` = :hostname', array(':hostname' => $hostname), 'core');

        $result = servers_exec($hostname, 'echo 1');
        $result = array_pop($result);

        if($result != '1'){
            throw new bException(tr('servers_test(): Failed to SSH connect to ":server"', array(':server' => $user.'@'.$hostname.':'.$port)), 'failed-connect');
        }

        sql_query('UPDATE `servers` SET `status` = NULL WHERE `hostname` = :hostname', array(':hostname' => $hostname), 'core');

    }catch(Exception $e){
        throw new bException('servers_test(): Failed', $e);
    }
}



/*
 * Returns an SSH key for the specified username, if available
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param string $username The SSH username for which an SSH key must be returned
 * @return string The SSH key for the specified username
 */
function servers_get_key($username){
    try{
        return sql_get('SELECT `ssh_key` FROM `ssh_accounts` WHERE `username` = :username', 'ssh_key', null, array(':username' => $username), 'core');

    }catch(Exception $e){
        throw new bException('servers_get_key(): Failed', $e);
    }
}



/*
 * Securely clear the private key from a servers array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $server Server array containing the private key that will be deleted securely
 * return boolean true if key was cleared, false if the specified $server array did not contain "ss_key"
 */
function servers_clear_key(&$server){
    try{
        if(empty($server['ssh_key'])){
            return false;
        }

        if(function_exists('sodium_memzero')){
            sodium_memzero($server['ssh_key']);
            unset($server['ssh_key']);

        }else{
            $server['ssh_key'] = random_bytes(2048);
            unset($server['ssh_key']);
        }

        return true;

    }catch(Exception $e){
        throw new bException('servers_clear_key(): Failed', $e);
    }
}



/*
 * Create a safe SSH keyfile containing the specified SSH key
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param string $ssh_key The SSH key that must be placed in a keyfile
 * return string $identity_file The created keyfile
 */
function servers_create_identity_file($ssh_key){
    global $core;

    try{
        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        load_libs('file');
        file_ensure_path(ROOT.'data/ssh/keys', 0700);
        chmod(ROOT.'data/ssh', 0700);

        /*
         * Safely create SSH key file
         */
        $identity_file = ROOT.'data/ssh/keys/'.str_random(8);

        touch($identity_file);
        chmod($identity_file, 0600);
        file_put_contents($identity_file, $ssh_key, FILE_APPEND);
        chmod($identity_file, 0400);

        $core->register('shutdown_servers_remove_identity_file', array($identity_file));

        return substr($identity_file, -8, 8);

    }catch(Exception $e){
        throw new bException('servers_create_identity_file(): Failed', $e);
    }
}



/*
 * Delete the specified SSH key
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param string $identity_file The SSH key file that must be deleted
 * return boolean True if the specified keyfile was deleted, false if no keyfile was specified
 */
function servers_remove_identity_file($identity_file, $background = false){
    try{
        if(!$identity_file){
            return false;
        }

        $identity_file = ROOT.'data/ssh/keys/'.$identity_file;

        if(file_exists($identity_file)){
            if($background){
                safe_exec('{ sleep 5; sudo chmod 0660 '.$identity_file.' ; sudo rm -rf '.$identity_file.' ; } &');

            }else{
                chmod($identity_file, 0600);
                file_delete($identity_file);
            }
        }

        return true;

    }catch(Exception $e){
        throw new bException('servers_remove_identity_file(): Failed', $e);
    }
}



/*
 * Detect the operating system on the specified host
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
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



/*
 * Returns the public IP for the specified hostname
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param string $hostname
 * @return string $ip The IP for the specified hostname
 */
function servers_get_public_ip($hostname){
    try{
        $ip = servers_exec($hostname, 'dig +short myip.opendns.com @resolver1.opendns.com');

        if(is_array($ip)){
            $ip = $ip[0];
        }

        return $ip;

    }catch(Exception $e){
        throw new bException('servers_get_public_ip(): Failed', $e);
    }
}



/*
 * Returns the proxy (if available) linked to the specified $servers_id. If the specified $servers_id has multiple linked proxy servers, a single random one will be chosen and returned
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param numeric $servers_id, id of the required server
 * @return array
 */
function servers_get_proxy($servers_id){
    try{
        $server = sql_get('SELECT    `servers`.`id`,
                                     `servers`.`hostname`,
                                     `servers`.`port`,
                                     `servers`.`ipv4`,
                                     `servers_ssh_proxies`.`proxies_id`

                           FROM      `servers_ssh_proxies`

                           LEFT JOIN `servers`
                           ON        `servers`.`id`                     = `servers_ssh_proxies`.`proxies_id`

                           WHERE     `servers_ssh_proxies`.`servers_id` = :servers_id

                           ORDER BY  RAND()

                           LIMIT     1',

                           array(':servers_id' => $servers_id), null, 'core');

        return $server;

    }catch(Exception $e){
        throw new bException('servers_get_proxy(): Failed', $e);
    }
}



/*
 * Returns all the proxy servers (if available) linked to the specified $servers_id
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param numeric $servers_id, id of the required server
 * @return array
 */
function servers_list_proxies($servers_id){
    try{
        $servers = sql_list('SELECT    `servers`.`id`,
                                       `servers`.`hostname`,
                                       `servers`.`port`,
                                       `servers`.`ipv4`,
                                       `servers_ssh_proxies`.`proxies_id`

                             FROM      `servers_ssh_proxies`

                             LEFT JOIN `servers`
                             ON        `servers`.`id`                     = `servers_ssh_proxies`.`proxies_id`

                             WHERE     `servers_ssh_proxies`.`servers_id` = :servers_id',

                             array(':servers_id' => $servers_id), false, 'core');

        return $servers;

    }catch(Exception $e){
        throw new bException('servers_list_proxies(): Failed', $e);
    }
}



/*
 * Add the specified proxy $proxies_id to the proxy chain for the specified $servers_id
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param integer $servers_id
 * @param integer $proxies_id
 * @return integer servers_ssh_proxies insert_id
 */
function servers_add_ssh_proxy($servers_id, $proxies_id){
    try{
        if(empty($servers_id)){
            throw new bException(tr('proxies_create_relation(): No servers id specified'), 'not-specified');
        }

        if(empty($proxies_id)){
            throw new bException(tr('proxies_create_relation(): No proxies id specified'), 'not-specified');
        }

        sql_query('INSERT INTO `servers_ssh_proxies` (`servers_id`, `proxies_id`)
                   VALUES                            (:servers_id , :proxies_id )',

                   array(':servers_id' => $servers_id,
                         ':proxies_id' => $proxies_id), 'core');

        return sql_insert_id('core');

    }catch(Exception $e){
		throw new bException('servers_add_ssh_proxy(): Failed', $e);
	}
}



/*
 * Updates relation in database base for specified server, in case relation does not exists, a new record is created
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param integer $servers_id
 * @param integer $old_proxies_id
 * @param integer $new_proxies_id
 * @return void
 */
function servers_update_ssh_proxy($servers_id, $old_proxies_id, $new_proxies_id){
    try{
        if(empty($servers_id)){
            throw new bException(tr('servers_update_ssh_proxy(): No servers id specified'), 'not-specified');
        }

        if(empty($old_proxies_id)){
            throw new bException(tr('servers_update_ssh_proxy(): No old proxies id specified'), 'not-specified');
        }

        if(empty($new_proxies_id)){
            throw new bException(tr('servers_update_ssh_proxy(): No new proxies id specified'), 'not-specified');
        }

        $id = sql_get('SELECT `id`

                       FROM   `servers_ssh_proxies`

                       WHERE  `servers_id` = :servers_id
                       AND    `proxies_id` = :proxies_id',

                       array(':servers_id' => $servers_id,
                             ':proxies_id' => $old_proxies_id), true, 'core');

        if($id){
            sql_query('UPDATE `servers_ssh_proxies`

                       SET    `proxies_id` = :proxies_id

                       WHERE  `id`         = :id',

                       array(':id'         => $id,
                             ':proxies_id' => $new_proxies_id), 'core');

        }else{
            /*
             * Record does not exist, creating a new one
             */
            load_libs('servers');
            servers_add_ssh_proxy($servers_id, $new_proxies_id);
        }

    }catch(Exception $e){
		throw new bException('servers_update_ssh_proxy(): Failed', $e);
	}
}



/*
 * Deletes from data base relation between two servers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param integer $servers_id
 * @param integer $proxies_id
 */
function servers_delete_ssh_proxy($servers_id, $proxies_id){
    try{
        sql_query('DELETE FROM `servers_ssh_proxies`

                   WHERE       `servers_id` = :servers_id
                   AND         `proxies_id` = :proxies_id',

                   array(':servers_id' => $servers_id,
                         ':proxies_id' => $proxies_id), 'core');

    }catch(Exception $e){
		throw new bException('servers_delete_ssh_proxy(): Failed', $e);
	}
}
?>
