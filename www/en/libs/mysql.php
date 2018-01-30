<?php
/*
 * MySQL library
 *
 * This library contains various functions to manage mysql databases and servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 * @copyright Ismael Haro <support@ingiga.com>
 *
 */



/*
 *
 */
function mysql_dump($params){
    try{
        array_ensure($params, 'host,user,pass,ssh_server');

        if($params['ssh_server']){
            /*
             * Execute this over SSH on a remote server
             */
            load_libs('ssh');

        }else{
            /*
             * Execute this directly on localhost
             */

        }

    }catch(Exception $e){
        throw new bException(tr('mysql_dump(): Failed'), $e);
    }
}



/*
 *
 */
function mysql_master_replication_setup($server){
    try{
        load_libs('ssh');

        /*
         * Validate params
         */
        array_ensure($server, 'server,root_db_user,root_db_password,database,replication_user,replication_db_password');
        $mysql_cnf_path = '/etc/mysql/mysql.conf.d/mysqld.cnf';

        /*
         * Check for mysqld.cnf file
         */
        $mysql_cnf = ssh_exec($server, 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

        /*
         * Mysql conf file does not exist
         */
        if(!$mysql_cnf[0]){
            throw new bException(tr('MySQL configuration file does not exist on remote server'), 'invalid');
        }

        /*
         * MySQL SETUP
         */
        ssh_exec($server, 'sed -i "s/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$server['id'].'/" '.$mysql_cnf_path);
        ssh_exec($server, 'sed -i "s/#log_bin/log_bin/" '.$mysql_cnf_path);
        ssh_exec($server, 'echo "binlog_do_db = '.$server['database'].'" '.$mysql_cnf_path);
        ssh_exec($server, 'sudo service mysql restart');

        /*
         * LOCK MySQL database
         */
        $ssh_mysql_pid = ssh_exec($server, 'mysql "-u'.$server['root_db_user'].'" "-p'.$server['root_db_password'].' '.$server['database'].'" <<-EOSQL &
                                            GRANT REPLICATION SLAVE ON *.* TO "'.$server['replication_user'].'@"localhost" IDENTIFIED BY "'.$server['replication_db_password'].'";
                                            FLUSH PRIVILEGES;
                                            USE '.$server['database'].'
                                            FLUSH TABLES WITH READ LOCK;
                                            EOSQL; sleep infinity', true);

        /*
         * Dump database
         */
        ssh_exec($server, 'rm /tmp/'.$server['database'].'.sql -f;');
        ssh_exec($server, 'mysqldump "-u'.$server['root_db_user'].'" "-p'.$server['root_db_password'].'" -K -R -n -e --dump-date --comments -B '.$server['database'].' > /tmp/ '.$server['database'].'.sql');

        /*
         * Import database
         */
        ssh_exec($server, 'rm /tmp/'.$server['database'].'.sql -f', false, true);

// :TODO: KEEP IMPLEMENTING
//        ssh_exec($server, 'scp -P '.($deploy_config['target_user'] ? $deploy_config['target_user'].'@' : '').$deploy_config['target_server'].':/tmp/'.$project.'_'.$source_config['db']['core']['db'].'.sql.gz /tmp/'.$project.'_'.$source_config['db']['core']['db'].'.sql.gz', $exitcode);

    }catch(Exception $e){
        throw new bException(tr('mysql_master_replication_setup(): Failed'), $e);
    }
}
?>