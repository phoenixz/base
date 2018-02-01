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
 * Execute a query on a remote SSH server.
 * NOTE: This does NOT support bound variables!
 */
function mysql_exec($server, $query){
    try{
        load_libs('servers');

        $query = addslashes($query);

        if(!is_array($server)){
            $server = servers_get($server);
        }

        mysql_create_password_file($server['db_username'], $server['db_password'], $server);
        $results = servers_exec($server, 'mysql -e \"'.str_ends($query, ';').'\"');
        mysql_delete_password_file($server);

        return $results;

    }catch(Exception $e){
        /*
         * Make sure the password file gets removed!
         */
        try{
            mysql_delete_password_file($server);

        }catch(Exception $e){

        }

        throw new bException(tr('mysql_dump(): Failed'), $e);
    }
}



/*
 *
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
 * Ensure
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
 * Make a dump of the specified database on the specified server and copy the file locally.
 */
function mysql_dump($params){
    try{
        array_params($params);
        arary_default($params, 'database', '');
        arary_default($params, 'file'    , $params['database'].'.sql.gz');

        load_libs('servers');

        if(!$params['database']){
            throw new bException(tr('mysql_dump(): No database specified'), 'not-specified');
        }

// :TOO: Implement optoins through $params
        $optoins = '-p -K -R -n -e --dump-date --comments -B';

        mysql_create_password_file($password, $user, $server);
        servers_exec($server, 'mysqldump '.$options.' '.$database.' | gzip > '.$file);
        mysql_delete_password_file($server);

    }catch(Exception $e){
        throw new bException(tr('mysql_dump(): Failed'), $e);
    }
}



/*
 * This function can setup a master
 * 1) MODIFY MASTER MYSQL CONFIG FILE
 * 2) CREATE REPLICATION USER ON MASTER MYSQL
 * 3) DUMP MYSQL DB
 * 4) ON OTHER SHELL GET MYSQL LOG_FILE AND LOG_POS
 */
function mysql_master_replication_setup($server){
    try{
        /*
         * Validate params
         */
        array_ensure($server, 'server,root_db_user,root_db_password,database,replication_db_password');

// :TODO: Store in DB, get unique from UNIQUE indexed column! perhaps the `databases`.`id` column?
        $server['id']   = mt_rand() - 1;
        $mysql_cnf_path = '/etc/mysql/mysql.conf.d/mysqld.cnf';

        load_libs('ssh,servers');

        /*
         * Check for mysqld.cnf file
         */
        log_console(tr('Checking existance of mysql configuration file on remote server'));
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
        log_console(tr('Making master setup for MySQL configuration file'));
        servers_exec($server['hostname'], 'sudo sed -i \"s/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$server['id'].'/\" '.$mysql_cnf_path);
        servers_exec($server['hostname'], 'sudo sed -i \"s/#log_bin/log_bin/\" '.$mysql_cnf_path);

        /*
         * The next line just have to be added one time!
         * Check if it exists, if not append
         */
        servers_exec($server['hostname'], 'grep -q -F \'binlog_do_db = '.$server['database'].'\' '.$mysql_cnf_path.' || sudo sed -i \"/max_binlog_size[[:space:]]*=[[:space:]]*100M/a binlog_do_db = '.$server['database'].'\" '.$mysql_cnf_path);

        log_console(tr('Restarting remote MySQL service'));
        servers_exec($server['hostname'], 'sudo service mysql restart');

        /*
         * LOCK MySQL database
         * sleep infinity and run in background
         * kill ssh pid after dumping db
         */
        log_console(tr('Making grant replication on remote server and locking tables'));
        $ssh_mysql_pid = servers_exec($server['hostname'], 'mysql \"-u'.$server['root_db_user'].'\" \"-p'.$server['root_db_password'].'\" -e \"GRANT REPLICATION SLAVE ON *.* TO \''.$server['replication_db_user'].'\'@\'localhost\' IDENTIFIED BY \''.$server['replication_db_password'].'\'; FLUSH PRIVILEGES; USE \''.$server['database'].'\'; FLUSH TABLES WITH READ LOCK; DO SLEEP(1000000); \"', null, true, false);

        /*
         * Dump database
         */
        log_console(tr('Making dump of remote database'));
        servers_exec($server['hostname'], 'sudo rm /tmp/'.$server['database'].'.sql.gz -f;');
        servers_exec($server['hostname'], 'sudo mysqldump \"-u'.$server['root_db_user'].'\" \"-p'.$server['root_db_password'].'\" -K -R -n -e --dump-date --comments -B '.$server['database'].' | gzip | sudo tee /tmp/'.$server['database'].'.sql.gz');

        /*
         * KILL LOCAL SSH process
         */
        log_console(tr('Dump finished, killing background process mysql shell session'));
        servers_exec($server['hostname'], 'kill -9 '.$ssh_mysql_pid[0], null, false, true);

        log_console(tr('Restarting remote MySQL service'));
        servers_exec($server['hostname'], 'sudo service mysql restart');

        /*
         * Delete posible LOCAL backup
         * SCP dump from server to local
         */
        log_console(tr('Copying remote dump to local'));
        servers_exec($server['hostname'], 'rm /tmp/'.$server['database'].'.sql.gz -f', null, false, true);
        ssh_cp($server, '/tmp/'.$server['database'].'.sql.gz', '/tmp/', true);

        /*
         * Get the log_file and log_pos
         */
        $master_status      = servers_exec($server['hostname'], 'mysql \"-u'.$server['root_db_user'].'\" \"-p'.$server['root_db_password'].'\" -ANe \"SHOW MASTER STATUS;\"');
        $master_status      = explode(',', preg_replace('/\s+/', ',', $master_status[0]));
        $server['log_file'] = $master_status[0];
        $server['log_pos']  = $master_status[1];

        return $server;

    }catch(Exception $e){
        throw new bException(tr('mysql_master_replication_setup(): Failed'), $e);
    }
}



/*
 * This function can setup a slave
 * 1) MODIFY SLAVE MYSQL CONFIG FILE
 * 2) CREATE A SSH TUNNELING USER ON A SPECIFIC PORT
 * 3) IMPORT MYSQL MASTER DB on SLAVE
 * 4) SETUP SLAVE REPLICATION ON A SPECIFIC PORT AND CHANNEL
 * 5) CHECK FOR SLAVE STATUS
 */
function mysql_slave_replication_setup($server){
    try{
        /*
         * Validate params
         */
        array_ensure($server, 'server,root_db_user,root_db_password,database,replication_db_password');

// :TODO: Store in DB, get unique from UNIQUE indexed column! perhaps the `databases`.`id` column?
        $server['id']   = mt_rand() - 1;
        $mysql_cnf_path = '/etc/mysql/mysql.conf.d/mysqld.cnf';

        load_libs('ssh,servers');

        /*
         * Check for mysqld.cnf file
         */
        log_console(tr('Checking existance of mysql configuration file on local server'));
        $mysql_cnf = servers_exec($server['hostname'], 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"', null, false, true);

        /*
         * Mysql conf file does not exist
         */
        if(!$mysql_cnf[0]){
            /*
             * Try with other possible configuration file
             */
            $mysql_cnf_path = '/etc/mysql/my.cnf';
            $mysql_cnf      = servers_exec($server['hostname'], 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"', null, false, true);

            if(!$mysql_cnf[0]){
                throw new bException(tr('mysql_master_replication_setup(): MySQL configuration file :file does not exist on local server', array(':file' => $mysql_cnf_path)), 'not-exist');
            }
        }

        /*
         * MySQL SETUP
         */
        log_console(tr('Making slave setup for MySQL configuration file'));
        servers_exec($server['hostname'], 'sudo sed -i "s/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$server['id'].'/" '.$mysql_cnf_path, null, false, true);
        servers_exec($server['hostname'], 'sudo sed -i "s/#log_bin/log_bin/" '.$mysql_cnf_path, null, false, true);

        /*
         * The next lines just have to be added one time!
         * Check if they already exist... if not append them
         */
        servers_exec($server['hostname'], 'grep -q -F \'relay-log = /var/log/mysql/mysql-relay-bin.log\' '.$mysql_cnf_path.' || echo "relay-log = /var/log/mysql/mysql-relay-bin.log" | sudo tee -a '.$mysql_cnf_path, null, false, true);
        servers_exec($server['hostname'], 'grep -q -F \'master-info-repository = table\' '.$mysql_cnf_path.' || echo "master-info-repository = table" | sudo tee -a '.$mysql_cnf_path, null, false, true);
        servers_exec($server['hostname'], 'grep -q -F \'relay-log-info-repository = table\' '.$mysql_cnf_path.' || echo "relay-log-info-repository = table" | sudo tee -a '.$mysql_cnf_path, null, false, true);
        servers_exec($server['hostname'], 'grep -q -F \'binlog_do_db = '.$server['database'].'\' '.$mysql_cnf_path.' || echo "binlog_do_db = '.$server['database'].'" | sudo tee -a '.$mysql_cnf_path, null, false, true);

        /*
         * Create SSH tunneling user
         */
        log_console(tr('Creating ssh tunneling user on local server'));
        ssh_mysql_slave_tunnel($server);


        /*
         * Close PDO connection before restarting MySQL
         */
        sql_close();
        log_console(tr('Restarting local MySQL service'));
        servers_exec($server['hostname'], 'sudo service mysql restart', null, false, true);

        log_console(tr('WAITING'), 'white');
        sleep(2);
        log_console(tr('CONTINUE!'), 'white');

        /*
         * Import LOCAL db
         */
        sql_query('DROP   DATABASE IF EXISTS `'.$server['database'].'`');
        sql_query('CREATE DATABASE `'.$server['database'].'`');
        servers_exec($server['hostname'], 'sudo rm /tmp/'.$server['database'].'.sql -f', null, false, true);
        servers_exec($server['hostname'], 'gzip -d /tmp/'.$server['database'].'.sql.gz', null, false, true);
        servers_exec($server['hostname'], 'mysql "-u'.$server['root_db_user'].'" "-p'.$server['root_db_password'].'" -B '.$server['database'].' < /tmp/'.$server['database'].'.sql', null, false, true);
        servers_exec($server['hostname'], 'sudo rm /tmp/'.$server['database'].'.sql -f', null, false, true);

        /*
         * Setup global configurations to support multiple channels
         */
        servers_exec($server['hostname'], 'mysql "-u'.$server['root_db_user'].'" "-p'.$server['root_db_password'].'" -e "SET GLOBAL master_info_repository = \'TABLE\';"', null, false, true);
        servers_exec($server['hostname'], 'mysql "-u'.$server['root_db_user'].'" "-p'.$server['root_db_password'].'" -e "SET GLOBAL relay_log_info_repository = \'TABLE\';"', null, false, true);

        /*
         * Setup slave replication
         */
        $slave_setup  = 'STOP SLAVE; ';
        //$slave_setup .= 'CHANGE MASTER TO MASTER_HOST=\''.$server['hostname'].'\', ';
        $slave_setup .= 'CHANGE MASTER TO MASTER_HOST=\'127.0.0.1\', ';
        $slave_setup .= 'MASTER_USER=\''.$server['replication_db_user'].'\', ';
        $slave_setup .= 'MASTER_PASSWORD=\''.$server['replication_db_password'].'\', ';
        $slave_setup .= 'MASTER_PORT='.$server['slave_ssh_port'].', ';
        $slave_setup .= 'MASTER_LOG_FILE=\''.$server['log_file'].'\', ';
        $slave_setup .= 'MASTER_LOG_POS='.$server['log_pos'].' ';
        $slave_setup .= 'FOR CHANNEL \''.$server['hostname'].'_'.$server['database'].'\'; ';
        $slave_setup .= 'START SLAVE FOR CHANNEL \''.$server['hostname'].'_'.$server['database'].'\';';
        servers_exec($server['hostname'], 'mysql "-u'.$server['root_db_user'].'" "-p'.$server['root_db_password'].'" -e "'.$slave_setup.'"', null, false, true);

        /*
         * Final step check for SLAVE status
         */
        $slave_status = servers_exec($server['hostname'], 'mysql "-u'.$server['root_db_user'].'" "-p'.$server['root_db_password'].'" -ANe "SHOW SLAVE STATUS;"', null, false, true);
showdie($slave_status);
    }catch(Exception $e){
        throw new bException(tr('mysql_slave_replication_setup(): Failed'), $e);
    }
}
?>