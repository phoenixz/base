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
        ensure_installed(array('name'      => 'empty',
                               'project'   => 'emptyear',
                               'callback'  => 'empty_install',
                               'checks'    => array(ROOT.'libs/external/empty/')));

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
 * ..............
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
        mysql_delete_password_file($server);
        servers_exec($server, "rm ~/.my.cnf -f; touch ~/.my.cnf; chmod 0600 ~/.my.cnf; echo '[client]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysql]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldump]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n[mysqldiff]\nuser=\\\"".$user."\\\"\npassword=\\\"".$password."\\\"\n\n' >> ~/.my.cnf");

    }catch(Exception $e){
        throw new bException(tr('mysql_create_password_file(): Failed'), $e);
    }
}



/*
 * ..............
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
        array_default($params, 'gzip'    , true);
        array_default($params, 'filename', $params['database'].'.sql'.($params['gzip'] ? '.gz' : ''));

        load_libs('servers');

        if(!$params['database']){
            throw new bException(tr('mysql_dump(): No database specified'), 'not-specified');
        }

        $server = servers_get($params['server'], true);

// :TOO: Implement optoins through $params
        $optoins  = ' -p -K -R -n -e --dump-date --comments -B ';

        mysql_create_password_file($server['db_root_password'], 'root', $server);
        servers_exec($params['server'], 'mysqldump '.$options.' '.$params['database'].($params['gzip'] ? ' | gzip' : '').' > '.$params['file']);
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
                                       `databases`.`status`,
                                       `databases`.`replication_status`,
                                       `databases`.`name` AS `database_name`,
                                       `databases`.`error`,

                                       `servers`.`id` AS `servers_id`,
                                       `servers`.`hostname`,
                                       `servers`.`ssh_port`,
                                       `servers`.`replication_status` AS `servers_replication_status`,

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
 * @return voic
 */
function mysql_reset_password($server, $username, $password){
    try{

    }catch(Exception $e){
        throw new bException(tr('mysql_get_database(): Failed'), $e);
    }
}
?>