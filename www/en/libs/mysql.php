<?php
/*
 * MySQL library
 *
 * This library contains various functions to manage mysql databases and servers through the command line mysql or mysqldump commands
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @copyright Ismael Haro <support@capmega.com>
 *
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @return void
 */
function mysql_library_init(){
    try{

    }catch(Exception $e){
        throw new bException('mysql_library_init(): Failed', $e);
    }
}



/*
 * Execute a query on a remote SSH server.
 * NOTE: This does NOT support bound variables!
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysql
 *
 * @param mixed $server
 * @param string $query
 * @param boolean $query
 * @param boolean $simple_quotes
 *
 * @return
 */
function mysql_exec($server, $query, $root = false, $simple_quotes = false){
    try{
        load_libs('servers');

        $query  = addslashes($query);
        $server = servers_get($server, true);

        if(empty($server['database_accounts_id'])){
            throw new bException(tr('mysql_exec(): Cannot execute query on server ":server", it does not have a database account specified', array(':server' => $server['domain'])), 'not-specified');
        }

        /*
         * Are we going to execute as root?
         */
        if($root){
            mysql_create_password_file('root', $server['db_root_password'], $server);

        }else{
            mysql_create_password_file($server['db_username'], $server['db_password'], $server);
        }

        if($simple_quotes){
            $results = servers_exec($server, 'mysql -e \''.str_ends($query, ';').'\'');

        }else{
            $results = servers_exec($server, 'mysql -e \"'.str_ends($query, ';').'\"');
        }

        mysql_delete_password_file($server);
        return $results;

    }catch(Exception $e){
        /*
         * Make sure the password file gets removed!
         */
        try{
            mysql_delete_password_file($server);

        }catch(Exception $e){
            $e->addMessages($e->getMessages());
        }

        throw new bException(tr('mysql_exec(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysql
 *
 * @param string $user
 * @param string $password
 * @param mixed $server
 *
 * @return
 */
function mysql_create_password_file($user, $password, $server = null){
    try{
        load_libs('servers');
        servers_exec($server, "rm ~/.my.cnf -f; touch ~/.my.cnf; chmod 0600 ~/.my.cnf; echo '[client]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysql]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldump]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldiff]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n' >> ~/.my.cnf");

    }catch(Exception $e){
        throw new bException(tr('mysql_create_password_file(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysql
 *
 * @param mixed $server
 *
 * @return
 */
function mysql_delete_password_file($server = null){
    try{
        load_libs('servers');
        servers_exec($server, 'rm ~/.my.cnf -f');

    }catch(Exception $e){
        throw new bException(tr('mysql_delete_password_file(): Failed'), $e);
    }
}



/*
 * Make a dump of the specified database on the specified server and write the
 * file on the same server on the specified location
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysql
 *
 * @param array $params
 * @return
 */
function mysql_dump($params){
    try{
        array_params($params);
        array_default($params, 'server'  , '');
        array_default($params, 'database', '');
        array_default($params, 'path'    , '');
        array_default($params, 'gzip'    , 'yes');
        array_default($params, 'redirect', 'yes');
        array_default($params, 'filename', $params['database'].'.sql'.($params['gzip'] == 'yes' ? '.gz' : ''));

        load_libs('servers');

        if(!$params['database']){
            throw new bException(tr('mysql_dump(): No database specified'), 'not-specified');
        }

        $server = servers_get($params['server'], true);

// :TOO: Implement optoins through $params
        $options  = ' -K -R -n -e --dump-date --comments -B ';

        mysql_create_password_file('root', $server['db_root_password'], $server);
        servers_exec($params['server'], 'mysqldump '.$options.' '.$params['database'].($params['gzip'] == 'yes' ? ' | gzip' : $params['gzip']).' '.($params['redirect'] == 'yes' ? ' > ' : $params['redirect']).' '.$params['file']);
        mysql_delete_password_file($server);

    }catch(Exception $e){
        throw new bException(tr('mysql_dump(): Failed'), $e);
    }
}



/*
 * ..................
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysql
 *
 * @param array $params
 * @return
 */
function mysql_get_database($db_name){
    try{
        $database = sql_get('SELECT    `databases`.`id`,
                                       `databases`.`id` AS `databases_id`,
                                       `databases`.`servers_id`,
                                       `databases`.`projects_id`,
                                       `databases`.`status`,
                                       `databases`.`replication_status`,
                                       `databases`.`name` AS `database_name`,
                                       `databases`.`error`,

                                       `servers`.`id` AS `servers_id`,
                                       `servers`.`domain`,
                                       `servers`.`ssh_port`,
                                       `servers`.`replication_status` AS `servers_replication_status`,
                                       `servers`.`replication_lock`   AS `server_replication_lock`,
                                       `servers`.`tasks_id`           AS `server_tasks_id`,

                                       `database_accounts`.`username`      AS `replication_db_user`,
                                       `database_accounts`.`password`      AS `replication_db_password`,
                                       `database_accounts`.`root_password` AS `root_db_password`

                             FROM      `databases`

                             LEFT JOIN `servers`
                             ON        `servers`.`id`           = `databases`.`servers_id`

                             LEFT JOIN `database_accounts`
                             ON        `database_accounts`.`id` = `servers`.`database_accounts_id`

                             WHERE     `databases`.`id`         = :name
                             OR        `databases`.`name`       = :name',

                             array(':name' => $db_name));

        if(!$database){
            throw new bException(tr('mysql_get_database(): Specified database ":database" does not exist', array(':database' => $db_name)), 'not-exist');
        }

        return $database;

    }catch(Exception $e){
        throw new bException(tr('mysql_get_database(): Failed'), $e);
    }
}



/*
 * Reset the password for the specified user on the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysql
 *
 * @param mixed $server
 * @param mixed $username
 * @param mixed $password
 * @return
 */
function mysql_reset_password($server, $username, $password){
    try{

    }catch(Exception $e){
        throw new bException(tr('mysql_get_database(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysql
 *
 * @param mixed $server
 * @return
 */
function mysql_register_databases($server){
    try{
        $results = mysql_exec($server, 'SHOW DATABASES');
        $count   = 0;

        foreach($databases as $database){
            switch($database){
                case '':
                    // FALLTHROUGH
                case 'Database':
                    // FALLTHROUGH
                case 'mysql':
                    // FALLTHROUGH
                case 'performance_schema':
                    // FALLTHROUGH
                case 'information_schema':
                    $skip = true;
                    break;

                default:
                    $skip = false;
            }

            if($skip) continue;

            $exists = sql_get('SELECT `id` FROM `databases` WHERE `servers_id` = :servers_id AND `name` = :name', true, array(':servers_id' => $server['id'], ':name' => $database));
            if($exists) continue;

            $database['servers_id'] = $server['id'];

            mysql_insert_database($database);

            $count++;
        }

        return $count;

    }catch(Exception $e){
        throw new bException(tr('mysql_register_databases(): Failed'), $e);
    }
}



/*
 * Validate the specified database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param array $database
 * @param boolean $structure_only
 * @param boolean $password_strength
 * @return array
 */
function mysql_validate_database($database, $structure_only = false){
    global $_CONFIG;

    try{
        load_libs('validate,seo,projects,servers');

        $v = new validate_form($database, 'createdby,status,servers_id,projects_id,replication_status,name,description,error');

        if($structure_only){
            return $database;
        }

        /*
         * Name
         */
        $v->isNotEmpty($database['name'], tr('Please specifiy a name'));
        $v->isAlphaNumeric($database[' name'], tr('The name ":name" is invalid', array(':name' => $database['name'])));
        $v->hasMaxChars($database[' name'], 16, tr('Please specify a name of less than 16 characters'));

        /*
         * Description
         */
        if(empty($database['description'])){
            $database['description'] = '';

        }else{
            $v->hasMinChars($database['description'],   16, tr('Please specifiy a minimum of 16 characters for the description'));
            $v->hasMaxChars($database['description'], 2047, tr('Please specifiy a maximum of 2047 characters for the description'));

            $database['description'] = cfm($database['description']);
        }

        /*
         * status
         * replication_status
         */
        $v->isStatus($database['status']            , tr('Please specifiy a valid status'));
        $v->isStatus($database['replication_status'], tr('Please specifiy a valid replication status'));

        /*
         * Error data
         */
        if(empty($database['error'])){
            $database['error'] = '';

        }else{
            $v->hasMaxChars($database['error'], 2047, tr('Please specifiy a maximum of 2047 characters for the error'));

            $database['error'] = cfm($database['error']);
        }

        $v->isValid();

        /*
         * Validate server and project
         */
        if($database['server']){
            $database['servers_id'] = servers_get($database['server']);

            if(!$database['servers_id']){
                $v->setError(tr('Specified server ":server" does not exist', array(':server' => $database['server'])));
            }

        }else{
            $database['servers_id'] = null;
            //$v->setError(tr('Please specify a server'));
        }

        if($database['project']){
            $database['projects_id'] = projects_get($database['project']);

            if(!$database['projects_id']){
                $v->setError(tr('Specified project ":project" does not exist', array(':project' => $database['project'])));
            }

        }else{
            $database['projects_id'] = null;
        }

        /*
         * Already exists?
         */
        $exists = mysql_exists_database($database['domain'], isset_get($database['id'], 0));

        if($exists){
            $v->setError(tr('A database with name ":domain" already exists', array(':name' => $database['name'])));
        }

        $database['seoname'] = seo_unique($database['domain'], 'databases', isset_get($database['id']));

        $v->isValid();

        return $database;

    }catch(Exception $e){
        throw new bException('mysql_validate_database(): Failed', $e);
    }
}



/*
 * Returns true if the specified database name exists, optionally ignoring the specified id
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param params $database The server data to be inserted into the database
 * @return params The validated server data, including server[id]
 */
function mysql_exists_database($database_name, $id = null){
    try{
        $exists = sql_get('SELECT `id`

                           FROM   `servers`

                           WHERE  `domain` = :domain
                           AND    `id`    != :id

                           LIMIT 1',

                           array(':name' => $database_name,
                                 ':id'   => not_empty($id, 0)), true, 'core');

        return $exists;

    }catch(Exception $e){
        throw new bException('mysql_exists_database(): Failed', $e);
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
 * @param params $database The server data to be inserted into the database
 * @return params The validated server data, including server[id]
 */
function mysql_insert_database($database){
    try{
        $database = mysql_validate_database($database);

        sql_query('INSERT INTO `databases` (`createdby`, `meta_id`, `status`, `servers_id`, `projects_id`, `replication_status`, `name`, `description`, `error`)
                   VALUES                  (:createdby , :meta_id , :status , :servers_id , :projects_id , :replication_status , :name , :description , :error )',

                   array(':createdby'          => isset_get($_SESSION['user']['id']),
                         ':meta_id'            => meta_action(),
                         ':status'             => $database['status'],
                         ':servers_id'         => $database['servers_id'],
                         ':projects_id'        => $database['projects_id'],
                         ':replication_status' => $database['replication_status'],
                         ':name'               => $database['name'],
                         ':description'        => $database['description'],
                         ':error'              => $database['error']));

        $database['id'] = sql_insert_id();

        return $database;

    }catch(Exception $e){
        throw new bException('mysql_insert_database(): Failed', $e);
    }
}



/*
 * Updates the specified database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package servers
 *
 * @param params $database The database data to be updated into the database
 * @return params The validated database data
 */
function mysql_update_database($database){
    try{
        $database = mysql_validate_database($database);
        meta_action($database['meta_id'], 'update');

        sql_query('UPDATE `databases`

                   SET    `servers_id`         = :servers_id,
                          `projects_id`        = :projects_id,
                          `replication_status` = :replication_status,
                          `name`               = :name,
                          `description`        = :description,
                          `error`              = :error

                   WHERE  `id`                 = :id',

                   array(':id'                 => $database['id'],
                         ':servers_id'         => $database['servers_id'],
                         ':projects_id'        => $database['projects_id'],
                         ':replication_status' => $database['replication_status'],
                         ':name'               => $database['name'],
                         ':description'        => $database['description'],
                         ':error'              => $database['error']));

        return $database;

    }catch(Exception $e){
        throw new bException('mysql_update_database(): Failed', $e);
    }
}
?>