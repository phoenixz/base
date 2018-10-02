<?php
/*
 * MySQL library
 *
 * This library contains various functions to manage mysql databases and servers
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
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @return void
 */
function mysqlr_library_init(){
    global $_CONFIG;

    try{
        load_config('mysqlr');

    }catch(Exception $e){
        throw new bException('mysqlr_library_init(): Failed', $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_update_server_replication_status($params, $status){
    try{
        /*
         * Update server replication_status
         */
        array_params($params);
        array_default($params, 'servers_id' , '');

        if(empty($params['servers_id'])){
            throw new bException(tr('mysqlr_update_replication_status(): No servers_id specified'), 'not-specified');
        }

        if(empty($status)){
            throw new bException(tr('mysqlr_update_replication_status(): No status specified'), 'not-specified');
        }

        /*
         * Update server replication_lock
         */
        switch($status){
            case 'preparing':
                sql_query('UPDATE `servers` SET `replication_lock` = :replication_lock WHERE `id` = :id', array(':replication_lock' => 1, ':id' => $params['servers_id']));
                break;

            case 'error':
                // FALLTHROUGH
            case 'disabled_lock':
                // FALLTHROUGH
            case 'enabled':
                sql_query('UPDATE `servers` SET `replication_lock`   = :replication_lock   WHERE `id` = :id', array(':replication_lock'   => 0      , ':id' => $params['servers_id']));
                sql_query('UPDATE `servers` SET `replication_status` = :replication_status WHERE `id` = :id', array(':replication_status' => $status, ':id' => $params['servers_id']));
                break;

            case 'disabled':
                /*
                 * No action
                 */
                sql_query('UPDATE `servers` SET `replication_lock`   = :replication_lock   WHERE `id` = :id', array(':replication_lock'   => 0      , ':id' => $params['servers_id']));
                sql_query('UPDATE `servers` SET `replication_status` = :replication_status WHERE `id` = :id', array(':replication_status' => $status, ':id' => $params['servers_id']));
                break;

            default:
                throw new bException(tr('Unknown status ":status"', array(':status' => $status)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException(tr('mysqlr_update_server_replication_status(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_update_replication_status($params, $status){
    try{
        /*
         * Update server and database replication_status
         */
        array_params($params);
        array_default($params, 'databases_id', '');
        array_default($params, 'servers_id' , '');

        if(empty($params['databases_id'])){
            throw new bException(tr('mysqlr_update_replication_status(): No database specified'), 'not-specified');
        }

        if(empty($params['servers_id'])){
            throw new bException(tr('mysqlr_update_replication_status(): No servers_id specified'), 'not-specified');
        }

        if(empty($status)){
            throw new bException(tr('mysqlr_update_replication_status(): No status specified'), 'not-specified');
        }

        /*
         * Update server replication_lock
         */
        switch($status){
            case 'disabling':
                // FALLTHROUGH
            case 'resuming':
                // FALLTHROUGH
            case 'pausing':
                // FALLTHROUGH
            case 'preparing':
                sql_query('UPDATE `servers` SET `replication_lock` = :replication_lock WHERE `id` = :id', array(':replication_lock' => 1, ':id' => $params['servers_id']));
                break;

            case 'paused':
                // FALLTHROUGH
            case 'disabled':
                // FALLTHROUGH
            case 'error':
                // FALLTHROUGH
            case 'enabled':
                sql_query('UPDATE `servers` SET `replication_lock` = :replication_lock WHERE `id` = :id', array(':replication_lock' => 0, ':id' => $params['servers_id']));
                break;

            default:
                throw new bException(tr('Unknown status ":status"', array(':status' => $status)));
        }

        /*
         * Update database
         */
        sql_query('UPDATE `databases` SET `replication_status` = :replication_status WHERE `id` = :id', array(':replication_status' => $status, ':id' => $params['databases_id']));

    }catch(Exception $e){
        throw new bException(tr('mysqlr_update_replication_status(): Failed'), $e);
    }
}



/*
 * This function can setup a master
 * 1) MODIFY MASTER MYSQL CONFIG FILE
 * 2) CREATE REPLICATION USER ON MASTER MYSQL
 * 3) DUMP MYSQL DB
 * 4) ON OTHER SHELL GET MYSQL LOG_FILE AND LOG_POS
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_master_replication_setup($params){
    global $_CONFIG;

    try{
        load_libs('mysql');

        /*
         * Validate params
         */
        array_ensure($params, 'hostname,database');

        /*
         * Check Slave hostname
         */
        $slave = $_CONFIG['mysqlr']['hostname'];

        if(empty($slave)){
            throw new bException(tr('mysqlr_master_replication_setup(): MySQL configuration for replicator hostname is not set'), 'not-specified');
        }

        /*
         * Get database
         */
        $database = mysql_get_database($params['database']);
        $database = array_merge($database, $params);
        mysqlr_update_replication_status($database, 'preparing');

        /*
         * Get MySQL configuration path
         */
        load_libs('ssh,servers');
        $mysql_cnf_path = mysqlr_check_configuration_path($database['hostname']);

        /*
         * MySQL SETUP
         */
        log_console(tr('Making master setup for MySQL configuration file'), 'DOT');
        servers_exec($database['hostname'], 'sudo sed -i \"s/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$database['id'].'/\" '.$mysql_cnf_path);
        servers_exec($database['hostname'], 'sudo sed -i \"s/#log_bin/log_bin/\" '.$mysql_cnf_path);

        /*
         * The next line just have to be added one time!
         * Check if it exists, if not append
         */
        servers_exec($database['hostname'], 'grep -q -F \'binlog_do_db = '.$database['database_name'].'\' '.$mysql_cnf_path.' || sudo sed -i \"/max_binlog_size[[:space:]]*=[[:space:]]*100M/a binlog_do_db = '.$database['database_name'].'\" '.$mysql_cnf_path);

        log_console(tr('Restarting remote MySQL service'), 'DOT');
        servers_exec($database['hostname'], 'sudo service mysql restart');

        /*
         * LOCK MySQL database
         * sleep infinity and run in background
         * kill ssh pid after dumping db
         */
        log_console(tr('Making grant replication on remote server and locking tables'), 'DOT');
// :FIX: There is an issue with mysql exec not executing as root
        //$ssh_mysql_pid = mysql_exec($database['hostname'], 'GRANT REPLICATION SLAVE ON *.* TO "'.$database['replication_db_user'].'"@"localhost" IDENTIFIED BY "'.$database['replication_db_password'].'"; FLUSH PRIVILEGES; USE '.$database['database'].'; FLUSH TABLES WITH READ LOCK; DO SLEEP(1000000);', true);
        $ssh_mysql_pid = servers_exec($database['hostname'], 'mysql \"-u'.$database['root_db_user'].'\" \"-p'.$database['root_db_password'].'\" -e \"GRANT REPLICATION SLAVE ON *.* TO \''.$database['replication_db_user'].'\'@\'localhost\' IDENTIFIED BY \''.$database['replication_db_password'].'\'; FLUSH PRIVILEGES; USE '.$database['database_name'].'; FLUSH TABLES WITH READ LOCK; DO SLEEP(1000000); \"', true);

        /*
         * Dump database
         */
        log_console(tr('Making dump of remote database'), 'DOT');
        servers_exec($database['hostname'], 'sudo rm /tmp/'.$database['database_name'].'.sql.gz -f;');
        servers_exec($database['hostname'], 'sudo mysqldump \"-u'.$database['root_db_user'].'\" \"-p'.$database['root_db_password'].'\" -K -R -n -e --dump-date --comments -B '.$database['database_name'].' | gzip | sudo tee /tmp/'.$database['database_name'].'.sql.gz');

        /*
         * KILL LOCAL SSH process
         * to drop the hanged connection
         */
        log_console(tr('Dump finished, killing background process mysql shell session'), 'DOT');
        shell_exec('kill -9 '.$ssh_mysql_pid[0]);

        log_console(tr('Restarting remote MySQL service'), 'DOT');
        servers_exec($database['hostname'], 'sudo service mysql restart');

        /*
         * Delete posible LOCAL backup
         * SCP dump from master server to local
         */
        log_console(tr('Copying remote dump to SLAVE'), 'DOT');
        safe_exec('rm /tmp/'.$database['database_name'].'.sql.gz -f');
        mysqlr_scp_database($database, '/tmp/'.$database['database_name'].'.sql.gz', '/tmp/', true);

        /*
         * Copy from local to slave server
         */
        servers_exec($slave, 'rm /tmp/'.$database['database_name'].'.sql.gz -f');
        mysqlr_scp_database(array('hostname' => $slave), '/tmp/'.$database['database_name'].'.sql.gz', '/tmp/');
        safe_exec('rm /tmp/'.$database['database_name'].'.sql.gz -f');

        /*
         * Get the log_file and log_pos
         */
        $master_status        = mysql_exec($database['hostname'], 'SHOW MASTER STATUS');
        $master_status        = explode(',', preg_replace('/\s+/', ',', $master_status[1]));
        $database['log_file'] = $master_status[0];
        $database['log_pos']  = $master_status[1];

        return $database;

    }catch(Exception $e){
        mysqlr_update_server_replication_status($database, 'disabled_lock');
        mysqlr_update_replication_status($database, 'disabled');
        throw new bException(tr('mysqlr_master_replication_setup(): Failed'), $e);
    }
}



/*
 * This function can setup a slave
 * 1) MODIFY SLAVE MYSQL CONFIG FILE
 * 2) CREATE A SSH TUNNELING USER ON A SPECIFIC PORT
 * 3) IMPORT MYSQL MASTER DB on SLAVE
 * 4) SETUP SLAVE REPLICATION ON A SPECIFIC PORT AND CHANNEL
 * 5) CHECK FOR SLAVE STATUS
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_slave_replication_setup($params){
    global $_CONFIG;

    try{
        load_libs('mysql');

        /*
         * Check Slave hostname
         */
        $slave = $_CONFIG['mysqlr']['hostname'];

        if(empty($slave)){
            throw new bException(tr('mysqlr_slave_replication_setup(): MySQL configuration for replicator hostname is not set'), 'not-specified');
        }

        /*
         * Get database and prepare info
         */
        $database       = mysql_get_database($params['database']);
        $database       = array_merge($database, $params);
        $database['id'] = mt_rand() - 1;

        /*
         * Get MySQL configuration path
         */
        load_libs('ssh,servers');
        $mysql_cnf_path = mysqlr_check_configuration_path($slave);

        /*
         * MySQL SETUP
         */
        log_console(tr('Making slave setup for MySQL configuration file'), 'DOT');
        servers_exec($slave, 'sudo sed -i \'s/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$database['id'].'/\' '.$mysql_cnf_path);
        servers_exec($slave, 'sudo sed -i \'s/#log_bin/log_bin/\' '.$mysql_cnf_path);

        /*
         * The next lines just have to be added one time!
         * Check if they already exist... if not append them
         */
        servers_exec($slave, 'grep -q -F \'relay-log = /var/log/mysql/mysql-relay-bin.log\' '.$mysql_cnf_path.' || echo "relay-log = /var/log/mysql/mysql-relay-bin.log" | sudo tee -a '.$mysql_cnf_path);
        servers_exec($slave, 'grep -q -F \'master-info-repository = table\' '.$mysql_cnf_path.' || echo "master-info-repository = table" | sudo tee -a '.$mysql_cnf_path);
        servers_exec($slave, 'grep -q -F \'relay-log-info-repository = table\' '.$mysql_cnf_path.' || echo "relay-log-info-repository = table" | sudo tee -a '.$mysql_cnf_path);
        servers_exec($slave, 'grep -q -F \'binlog_do_db = '.$database['database_name'].'\' '.$mysql_cnf_path.' || echo "binlog_do_db = '.$database['database_name'].'" | sudo tee -a '.$mysql_cnf_path);

        /*
         * Close PDO connection before restarting MySQL
         */
        log_console(tr('Restarting Slave MySQL service'), 'DOT');
        servers_exec($slave, 'sudo service mysql restart');
        sleep(2);

        /*
         * Import LOCAL db
         */
        mysql_exec($slave, 'DROP   DATABASE IF EXISTS '.$database['database_name']);
        mysql_exec($slave, 'CREATE DATABASE '.$database['database_name']);
        servers_exec($slave, 'sudo rm /tmp/'.$database['database_name'].'.sql -f');
        servers_exec($slave, 'gzip -d /tmp/'.$database['database_name'].'.sql.gz');
        servers_exec($slave, 'sudo mysql "-u'.$database['root_db_user'].'" "-p'.$database['root_db_password'].'" -B '.$database['database_name'].' < /tmp/'.$database['database_name'].'.sql');
        servers_exec($slave, 'sudo rm /tmp/'.$database['database_name'].'.sql -f');

        /*
         * Check if this server was already replicating
         */
        if($database['servers_replication_status'] == 'enabled'){
            mysqlr_update_replication_status($database, 'enabled');
            return 0;
        }

        /*
         * This server master was not replicating
         * Enable SSH tunnel
         * Enable SLAVE for this server
         *
         * Create SSH tunneling user
         */
        log_console(tr('Creating ssh tunneling user on local server'), 'DOT');
        mysqlr_slave_ssh_tunnel($database, $slave);

        /*
         * Setup global configurations to support multiple channels
         */
        mysql_exec($slave, 'SET GLOBAL master_info_repository = \"TABLE\"');
        mysql_exec($slave, 'SET GLOBAL relay_log_info_repository = \"TABLE\"');

        /*
         * Setup slave replication
         */
        $slave_setup  = 'STOP SLAVE; ';
        $slave_setup .= 'CHANGE MASTER TO MASTER_HOST=\"127.0.0.1\", ';
        $slave_setup .= 'MASTER_USER=\"'.$database['replication_db_user'].'\", ';
        $slave_setup .= 'MASTER_PASSWORD=\"'.$database['replication_db_password'].'\", ';
        $slave_setup .= 'MASTER_PORT='.$database['ssh_port'].', ';
        $slave_setup .= 'MASTER_LOG_FILE=\"'.$database['log_file'].'\", ';
        $slave_setup .= 'MASTER_LOG_POS='.$database['log_pos'].' ';
        $slave_setup .= 'FOR CHANNEL \"'.$database['hostname'].'\"; ';
        $slave_setup .= 'START SLAVE FOR CHANNEL \"'.$database['hostname'].'\";';
        mysql_exec($slave, $slave_setup);

        /*
         * Final step check for SLAVE status
         */
        mysqlr_update_replication_status($database, 'enabled');
        mysqlr_update_server_replication_status($database, 'enabled');
        log_console(tr('MySQL replication setup finished!'), 'white');

    }catch(Exception $e){
        mysqlr_update_server_replication_status($database, 'disabled_lock');
        mysqlr_update_replication_status($database, 'disabled');
        throw new bException(tr('mysqlr_slave_replication_setup(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_pause_replication($db, $restart_mysql = true){
    global $_CONFIG;

    try{
        load_libs('mysql');

        /*
         * Check Slave hostname
         */
        $slave = $_CONFIG['mysqlr']['hostname'];

        if(empty($slave)){
            throw new bException(tr('mysqlr_pause_replication(): MySQL Configuration for replicator hostname is not set'), 'not-specified');
        }

        /*
         * Check if this server exist
         */
        $database = mysql_get_database($db);

        if(empty($database)){
            throw new bException(tr('mysqlr_pause_replication(): The specified database :database does not exist', array(':database' => $database)), 'not-exist');
        }

        mysqlr_update_replication_status($database, 'pausing');

        /*
         * Get MySQL configuration path
         */
        load_libs('ssh,servers');
        $mysql_cnf_path = mysqlr_check_configuration_path($slave);

        /*
         * Enable replicate ignore
         */
        servers_exec($slave, 'grep -q -F \'replicate-ignore-db='.$database['database_name'].'\' '.$mysql_cnf_path.' || echo "replicate-ignore-db='.$database['database_name'].'" | sudo tee -a '.$mysql_cnf_path);

        /*
         * Close PDO connection before restarting MySQL
         */
        if($restart_mysql){
            log_console(tr('Restarting Slave MySQL service'), 'DOT');
            servers_exec($slave, 'sudo service mysql restart');   
        }

        mysqlr_update_replication_status($database, 'paused');
        log_console(tr('Paused replication for database :database', array(':database' => $database['database_name'])), 'DOT');

        return 0;

    }catch(Exception $e){
        throw new bException(tr('mysqlr_pause_replication(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_resume_replication($db, $restart_mysql = true){
    global $_CONFIG;

    try{
        load_libs('mysql');

        /*
         * Check Slave hostname
         */
        $slave = $_CONFIG['mysqlr']['hostname'];

        if(empty($slave)){
            throw new bException(tr('mysqlr_resume_replication(): MySQL Configuration for replicator hostname is not set'), 'not-specified');
        }

        /*
         * Check if this server exist
         */
        $database = mysql_get_database($db);

        if(empty($database)){
            throw new bException(tr('mysqlr_resume_replication(): The specified database :database does not exist', array(':database' => $database)), 'not-exist');
        }

        mysqlr_update_replication_status($database, 'resuming');

        load_libs('ssh,servers');
        $mysql_cnf_path = mysqlr_check_configuration_path($slave);

        /*
         * Comment the database for replication
         */
        servers_exec($slave, 'sudo sed -i "s/replicate-ignore-db='.$database['database_name'].'//" '.$mysql_cnf_path);

        /*
         * Close PDO connection before restarting MySQL
         */
        if($restart_mysql){
            log_console(tr('Restarting Slave MySQL service'), 'DOT');
            servers_exec($slave, 'sudo service mysql restart');   
        }
        
        mysqlr_update_replication_status($database, 'enabled');
        log_console(tr('Resumed replication for database :database', array(':database' => $database['database_name'])), 'DOT');

        return 0;

    }catch(Exception $e){
        throw new bException(tr('mysqlr_resume_replication(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_check_configuration_path($server_target){
    try{
        load_libs('ssh,servers');
        $mysql_cnf_path = '/etc/mysql/mysql.conf.d/mysqld.cnf';

        /*
         * Check for mysqld.cnf file
         */
        log_console(tr('Checking existance of mysql configuration file'), 'DOT');
        $mysql_cnf = servers_exec($server_target, 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

        /*
         * Mysql conf file does not exist
         */
        if(!$mysql_cnf[0]){
            /*
             * Try with other possible configuration file
             */
            $mysql_cnf_path = '/etc/mysql/my.cnf';
            $mysql_cnf      = servers_exec($server_target, 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

            if(!$mysql_cnf[0]){
                throw new bException(tr('mysqlr_check_configuration_path(): MySQL configuration file :file does not exist on server :server', array(':file' => $mysql_cnf_path, ':server' => $server_target)), 'not-exist');
            }
        }

        return $mysql_cnf_path;

    }catch(Exception $e){
        throw new bException(tr('mysqlr_check_configuration_path(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_slave_ssh_tunnel($server, $slave){
    global $_CONFIG;

    try{
        array_params($server);
        array_default($server, 'server'       , '');
        array_default($server, 'hostname'     , '');
        array_default($server, 'ssh_key'      , '');
        array_default($server, 'port'         , 22);
        array_default($server, 'arguments'    , '-T');
        array_default($server, 'hostkey_check', true);

        /*
         * If server was specified by just name, then lookup the server data in
         * the database
         */
        if($server['hostname']){
            $dbserver = sql_get('SELECT    `ssh_accounts`.`username`,
                                           `ssh_accounts`.`ssh_key`,
                                           `servers`.`id`,
                                           `servers`.`hostname`,
                                           `servers`.`port`

                                 FROM      `servers`

                                 LEFT JOIN `ssh_accounts`
                                 ON        `ssh_accounts`.`id` = `servers`.`ssh_accounts_id`

                                 WHERE     `servers`.`hostname` = :hostname', array(':hostname' => $server['hostname']));

            if(!$dbserver){
                throw new bException(tr('ssh_mysql_slave_tunnel(): Specified server ":server" does not exist', array(':server' => $server['server'])), 'not-exist');
            }

            $server = sql_merge($server, $dbserver);
        }

        if(!$server['hostkey_check']){
            $server['arguments'] .= ' -o StrictHostKeyChecking=no -o UserKnownHostsFile='.ROOT.'data/ssh/known_hosts ';
        }

        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        load_libs('file');
        file_ensure_path(ROOT.'data/ssh/keys');
        chmod(ROOT.'data/ssh', 0770);

        /*
         * Safely create SSH key file
         */
        $keyname = str_random(8);
        $keyfile = ROOT.'data/ssh/keys/'.$keyname;
        touch($keyfile);
        chmod($keyfile, 0600);
        file_put_contents($keyfile, $server['ssh_key'], FILE_APPEND);
        chmod($keyfile, 0400);

        /*
         * Copy key file
         * and execute autossh
         */
        safe_exec('scp '.$server['arguments'].' -P '.$_CONFIG['mysqlr']['port'].' -i '.$keyfile.' '.$keyfile.' '.$server['username'].'@'.$slave.':/data/ssh/keys/');
        servers_exec($slave, 'autossh -p '.$server['port'].' -i /data/ssh/keys/'.$keyname.' -L '.$server['ssh_port'].':localhost:3306 '.$server['username'].'@'.$server['hostname'].' -f -N');

        /*
         * Delete local file key
         */
        chmod($keyfile, 0600);
        file_delete($keyfile);

    }catch(Exception $e){
        notify(tr('mysqlr_slave_ssh_tunnel(): exception'), $e, 'developers');

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
            notify(tr('mysqlr_slave_ssh_tunnel(): cannot delete key'), $e, 'developers');
        }

        throw new bException(tr('mysqlr_slave_ssh_tunnel(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_full_backup(){
    global $_CONFIG;
    
    try{
        /*
         * Get all servers replicating
         */
        $slave   = $_CONFIG['mysqlr']['hostname'];
        $servers = sql_query('SELECT `id`,
                                     `hostname`,
                                     `seohostname`
                              
                              FROM   `servers`
                              
                              WHERE  `replication_status` = "enabled"');

        if(!$servers->rowCount()){
            /*
             * There are no servers in replication status
             */
            return false;
        }
        
        /*
         * Make a directory on the replication server
         */
        load_libs('ssh,servers');
        $backup_path = '/data/backups/databases';
        servers_exec($slave, 'sudo mkdir -p '.$backup_path);

        /*
         * For each server get the databases replicating
         */
        while($server = sql_fetch($servers)){
            $databases = sql_list('SELECT `id`,
                                           `name`
                                    
                                   FROM   `databases`
                                    
                                   WHERE  `replication_status` = "enabled"
                                   AND    `servers_id`         = :servers_id',
                                    
                                   array(':servers_id' => $server['id']));
 
            if(!count($databases)){
                /*
                 * There are no databases replicating at this time
                 * Skip to next server
                 */
                continue;
            }
            
            log_console(tr('Making backups of server :server', array(':server' => $server['hostname'])), 'DOT');
            
            /*
             * Disable replication of each database
             */
            foreach($databases as $id => $name){
                log_console(tr('Disabling replication of database :database', array(':database' => $name)), 'DOT');
                mysqlr_pause_replication($id, false);
            }
            
            /*
             * Restart mysql service on slave to disable replication on selected databases
             */
            servers_exec($slave, 'sudo service mysql restart');
            
            /*
             * Create a directory for the current server inside the backup directory
             */
            $server_backup_path = $backup_path.'/'.$server['hostname'];
            servers_exec($slave, 'sudo mkdir '.$server_backup_path);
            
            foreach($databases as $id => $name){
                $db                 = mysql_get_database($id);
                $db['root_db_user'] = 'root';
                
                log_console(tr('Making backup of database :database', array(':database' => $db['database_name'])), 'DOT');
                
                /*
                 * Make a dump and save it on the backups server backup directory
                 * And resume replication on this database
                 */
                mysql_dump(array('server'   => $slave,
                                 'database' => $db['database_name'],
                                 'gzip'     => '',
                                 'redirect' => ' | sudo tee',
                                 'file'     => $server_backup_path.'/'.$db['database_name'].'.sql'));
// :DELETE: the below code is deprecated since we are using mysql_dump function                
                //servers_exec($slave, 'sudo mysqldump \"-u'.$db['root_db_user'].'\" \"-p'.$db['root_db_password'].'\" -K -R -n -e --dump-date --comments -B '.$db['database_name'].' | gzip | sudo tee '.$server_backup_path.'/'.$db['database_name'].'.sql.gz');
                mysqlr_resume_replication($id, false);
            }
            
            /*
             * Restart mysql service on slave to enable replication again on selected databases
             */
            servers_exec($slave, 'sudo service mysql restart');
        }
        
        /*
         * rsync to backup server
         */
        servers_exec($slave, 'rsync -avze \"ssh -p '.$_CONFIG['mysqlr']['backup']['port'].'\" '.$backup_path.'/*'.' '.$_CONFIG['mysqlr']['backup']['hostname'].':'.$_CONFIG['mysqlr']['backup']['path']);
        
        /*
         * delete replicate backup for today
         */
        servers_exec($slave, 'sudo rm -rf '.$backup_path);
        
        log_console(tr('mysqlr_full_backup(): Finished backups'), 'DOT');
        
    }catch(Exception $e){
        throw new bException(tr('mysqlr_full_backup(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_scp_database($server, $source, $destnation, $from_server = false){
    try{
        array_params($server);
        array_default($server, 'server'       , '');
        array_default($server, 'hostname'     , '');
        array_default($server, 'ssh_key'      , '');
        array_default($server, 'port'         , 22);
        array_default($server, 'hostkey_check', false);
        array_default($server, 'arguments'    , '');

        /*
         * If server was specified by just name, then lookup the server data in
         * the database
         */
        if($server['hostname']){
            $dbserver = sql_get('SELECT    `ssh_accounts`.`username`,
                                           `ssh_accounts`.`ssh_key`,
                                           `servers`.`id`,
                                           `servers`.`hostname`,
                                           `servers`.`port`

                                 FROM      `servers`

                                 LEFT JOIN `ssh_accounts`
                                 ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                 WHERE     `servers`.`hostname` = :hostname', array(':hostname' => $server['hostname']));

            if(!$dbserver){
                throw new bException(tr('mysqlr_scp_database(): Specified server ":server" does not exist', array(':server' => $server['server'])), 'not-exist');
            }

            $server = sql_merge($server, $dbserver);
        }

        if(!$server['hostkey_check']){
            $server['arguments'] .= ' -o StrictHostKeyChecking=no -o UserKnownHostsFile='.ROOT.'data/ssh/known_hosts ';
        }

        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        load_libs('file');
        file_ensure_path(ROOT.'data/ssh/keys');
        chmod(ROOT.'data/ssh', 0770);

        /*
         * Safely create SSH key file
         */
        $keyfile = ROOT.'data/ssh/keys/'.str_random(8);

        touch($keyfile);
        chmod($keyfile, 0600);
        file_put_contents($keyfile, $server['ssh_key'], FILE_APPEND);
        chmod($keyfile, 0400);

        if($from_server){
            $command = $server['username'].'@'.$server['hostname'].':'.$source.' '.$destnation;

        }else{
            $command = $source.' '.$server['username'].'@'.$server['hostname'].':'.$destnation;
        }

        /*
         * Execute command
         */
        $result = safe_exec('scp '.$server['arguments'].' -P '.$server['port'].' -i '.$keyfile.' '.$command.'');
        chmod($keyfile, 0600);
        file_delete($keyfile);

        return $result;

    }catch(Exception $e){
        notify(tr('mysqlr_scp_database() exception'), $e, 'developers');

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
            notify(tr('mysqlr_scp_database() cannot delete key'), $e, 'developers');
        }

        throw new bException(tr('mysqlr_scp_database(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_add_log($params){
    try{
        /*
         * Validate
         */
        array_params($params);
        array_default($params, 'databases_id', '');
        array_default($params, 'type'        , '');
        array_default($params, 'message'     , '');

        if(empty($params['databases_id'])){
            throw new bException(tr('No database specified'), 'not-specified');
        }

        if(empty($params['type'])){
            throw new bException(tr('No type specified'), 'not-specified');
        }
        
        /*
         * Validate log type
         */
        switch($params['type']){
            case 'mysql_issue':
                // FALLTHROUGH
            case 'ssh_tunnel':
                // FALLTHROUGH
            case 'table_issue':
                // FALLTHROUGH
            case 'misconfiguration':
                // FALLTHROUGH
            case 'other':
                /*
                 * Do nothing
                 */
                break;
                
            default:
                throw new bException(tr('Specified type is not valid'), 'not-valid');
        }
        
        if(empty($params['message'])){
            throw new bException(tr('No message specified'), 'not-specified');
        }
        
        /*
         * Get database
         * This function will throw an error is this database does not exist
         */
        $database = mysql_get_database($params['databases_id']);
        
        /*
         * Update database
         */
        sql_query('INSERT INTO `replicator_logs` (`type`, `projects_id`, `servers_id`, `databases_id`, `message`)
                   VALUES                        (:type , :projects_id , :servers_id , :databases_id , :message )',
                   
                   array(':type'         => $params['type'],
                         ':projects_id'  => $database['projects_id'],
                         ':servers_id'   => $database['servers_id'],
                         ':databases_id' => $database['databases_id'],
                         ':message'      => $params['message']));

    }catch(Exception $e){
        throw new bException(tr('mysqlr_add_log(): Failed'), $e);
    }
}


/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_get_logs($database){
    try{
        load_libs('mysql');
        
        /*
         * Validate data
         */
        if(empty($database)){
            throw new bException(tr('No database specified'), 'not-specified');
        }
        
        /*
         * Get database
         * This function will throw an error is this database does not exist
         */
        $database = mysql_get_database($database);
        
        /*
         * Get logs
         */
        $replicator_logs = sql_list('SELECT    `replicator_logs`.`id`,
                                               `replicator_logs`.`status`,
                                               `replicator_logs`.`type`,
                                               `replicator_logs`.`projects_id`,
                                               `replicator_logs`.`servers_id`,
                                               `replicator_logs`.`databases_id`,
                                               `replicator_logs`.`message`,
                                            
                                               `servers`.`hostname`,
                                               `servers`.`seohostname`,
                                               
                                               `projects`.`name`
                                               
                                     FROM      `replicator_logs`
                                            
                                     LEFT JOIN `projects`
                                     ON        `replicator_logs`.`projects_id`  = `projects`.`id`
                                     
                                     LEFT JOIN `servers`
                                     ON        `replicator_logs`.`servers_id`   = `servers`.`id`
                                     
                                     WHERE     `replicator_logs`.`databases_id` = :databases_id
                                     AND       `replicator_logs`.`status`       IS NULL',
                                     
                                     array(':databases_id' => $database['id']));

        return $replicator_logs;

    }catch(Exception $e){
        throw new bException(tr('mysqlr_add_log(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_monitor_database($database){
    try{
        /*
         * Validate data
         */
        if(empty($database)){
            throw new bException(tr('No database specified'), 'not-specified');
        }
        
        /*
         * Get database
         * This function will throw an error is this database does not exist
         */
        $database = mysql_get_database($database);
        
        /*
         * Check if MySQL configuration still has this database
         */
        log_console(tr('Checking database :database from server :server', array(':database' => $database['database'], ':server' => $database['hostname'])), 'white');
        $mysql_cnf_path = mysqlr_check_configuration_path($database['database']);
        $result         = servers_exec($database['hostname'], 'grep -q -F \'binlog_do_db = '.$database['database'].'\' '.$mysql_cnf_path.' && echo "1" || echo "0"');

        if(!$result[0]){
            /*
             * Database is not in binlog then it is disabled
             */
            mysqlr_add_log(array('databases_id' => $database['id'],
                                 'type'         => 'misconfiguration',
                                 'message'      => 'The mysql configuration file does not contain this database'));
            mysqlr_update_database_replication_status($database, 'disabled');
            return 1;
        }

        /*
         * Check channel for the server database
         */
        $result = sql_get('SHOW SLAVE STATUS FOR CHANNEL :channel', array(':channel' => $database['hostname']));

        if(empty($result)){
            /*
             * No slave channel for this server
             */
            mysqlr_add_log(array('databases_id' => $database['id'],
                                 'type'         => 'mysql_issue',
                                 'message'      => 'The mysql channel for this database does not exist, check the configuration for this slave'));
            mysqlr_update_database_replication_status($database, 'disabled');
            return 1;
        }

        if(strtolower($result['Slave_IO_Running']) != 'yes' and strtolower($result['Slave_IO_Running']) != 'yes'){
            mysqlr_add_log(array('databases_id' => $database['id'],
                                 'type'         => 'mysql_issue',
                                 'message'      => tr('There is an error with the Slave, restarting ssh tunnel, Last_IO_Errno ":Last_IO_Errno", Last_IO_Error ":Last_IO_Error"', array(':Last_IO_Errno' => $result['Last_IO_Errno'], 'Last_IO_Error' => $result['Last_IO_Error']))));
            ssh_mysql_slave_tunnel($database);
            mysqlr_update_database_replication_status($database, 'error');
            return 1;
        }

        mysqlr_update_replication_status($database, 'enabled');

    }catch(Exception $e){
        throw new bException(tr('mysqlr_monitor_database(): Failed'), $e);
    }
}



/*
 *
 */
function mysqlr_log_type_human($type){
    try{
        $retval = '';
        switch($type){
            case 'mysql_issue':
                $retval = 'MySQL Issue';
                break;
                
            case 'ssh_tunnel':
                $retval = 'SSH Tunnel Issue';
                break;
            
            case 'table_issue':
                $retval = 'Database Table Issue';
                break;
            
            case 'misconfiguration':
                $retval = 'Misconfiguration';
                break;
            
            case 'other':
                $retval = 'Other';
                break;
                
            default:
                throw new bException(tr('Specified type is not valid'), 'not-valid');
        }
        
        return $retval;
        
    }catch(Exception $e){
        throw new bException(tr('mysqlr_log_html_tag_type(): Failed'), $e);
    }
}
?>