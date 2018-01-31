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
        /*
         * Validate params
         */
        array_ensure($server, 'server,root_db_user,root_db_password,database,replication_user,replication_db_password');

// :TODO: Store in DB, get unique from UNIQUE indexed column! perhaps the `databases`.`id` column?
        $server['id']   = mt_rand() - 1;
        $mysql_cnf_path = '/etc/mysql/mysql.conf.d/mysqld.cnf';

        load_libs('ssh,servers');

        /*
         * Check for mysqld.cnf file
         */
        log_console(tr('Checking existance of mysql configuration file on remote server'), 'white');
        $mysql_cnf = servers_exec($server['hostname'], 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

        /*
         * Mysql conf file does not exist
         */
        if(!$mysql_cnf[0]){
            /*
             * Try with other possible configuration file
             */
            $mysql_cnf_path = '/etc/mysql/my.cnf';
            $mysql_cnf      = servers_exec($server['hostname'], 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

            if(!$mysql_cnf[0]){
                throw new bException(tr('mysql_master_replication_setup(): MySQL configuration file :file does not exist on remote server', array(':file' => $mysql_cnf_path)), 'not-exist');
            }
        }

        /*
         * MySQL SETUP
         */
        //log_console(tr('Making master setup for MySQL configuration file'));
        //servers_exec($server, 'sudo sed -i \"s/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$server['id'].'/\" '.$mysql_cnf_path);
        //servers_exec($server, 'sudo sed -i \"s/#log_bin/log_bin/\" '.$mysql_cnf_path);
        //servers_exec($server, 'echo \"binlog_do_db = '.$server['database'].'\" | sudo tee -a '.$mysql_cnf_path);
        //
        //log_console(tr('Restarting remote MySQL service'));
        //servers_exec($server, 'sudo service mysql restart');

        /*
         * LOCK MySQL database
         * sleep infinity and run in background
         * kill ssh pid after dumping db
         */
        log_console(tr('Making grant replication on remote server and locking tables'));
        $ssh_mysql_pid = servers_exec($server['hostname'], 'mysql \"-u'.$server['root_db_user'].'\" \"-p'.$server['root_db_password'].'\" -e \"GRANT REPLICATION SLAVE ON *.* TO \''.$server['replication_db_user'].'\'@\'localhost\' IDENTIFIED BY \''.$server['replication_db_password'].'\'; FLUSH PRIVILEGES; USE \''.$server['database'].'\'; FLUSH TABLES WITH READ LOCK; DO SLEEP(35); \"', null, true, false);

        /*
         * Dump database
         */
        servers_exec($server, 'rm /tmp/'.$server['database'].'.sql -f;');
        servers_exec($server, 'mysqldump \"-u'.$server['root_db_user'].'\" \"-p'.$server['root_db_password'].'\" -K -R -n -e --dump-date --comments -B '.$server['database'].' > /tmp/ '.$server['database'].'.sql');
showdie("aaaaa");
        /*
         * KILL LOCAL SSH process
         */
        servers_exec($server, 'kill -9'.$ssh_mysql_pid[0], null, false, true);

        /*
         * Delete posible LOCAL backup
         */
        servers_exec($server, 'rm /tmp/'.$server['database'].'.sql -f', null, false, true);

        /*
         * SCP dump from server to local
         */
        ssh_cp($server, '/tmp/'.$server['database'].'.sql', '/tmp/', true);

        /*
         * Return
         * database dump name
         * log file
         * log pos
         */
        $master_status      = servers_exec($server, 'mysql "-u'.$server['root_db_user'].'" "-p'.$server['root_db_password'].'" -ANe "SHOW MASTER STATUS;" | awk \'{print $1 " " $2}\'');
        $server['log_file'] = servers_exec($server, 'echo '.$master_status[0].' | cut -f1 -d \' \'', null, false, true);
        $server['log_pos']  = servers_exec($server, 'echo '.$master_status[0].' | cut -f2 -d \' \'', null, false, true);
        return $server;

    }catch(Exception $e){
        throw new bException(tr('mysql_master_replication_setup(): Failed'), $e);
    }
}



/*
 *
 */
function mysql_slave_replication_setup($server){
    try{
        /*
         * Import LOCAL db
         */
        sql_query('DROP   DATABASE IF EXISTS `'.$server['database'].'`');
        sql_query('CREATE DATABASE `'.$server['database'].'`');
        ssh_exec('cat /tmp/'.$server['database'].'.sql | mysql -u '.$server['root_db_user'].' -p"'.$server['root_db_password'].'" -B '.$server['database']);

    }catch(Exception $e){
        throw new bException(tr('mysql_slave_replication_setup(): Failed'), $e);
    }
}
?>