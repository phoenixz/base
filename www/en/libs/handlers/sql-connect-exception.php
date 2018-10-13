<?php
try{
    global $_CONFIG;

    if($e->getMessage() == 'could not find driver'){
        throw new bException('sql_connect(): Failed to connect with "'.str_log($connector['driver']).'" driver, it looks like its not available', 'driverfail');
    }

    log_console(tr('Encountered exception ":e" while connecting to database server, attempting to resolve', array(':e' => $e->getMessage())), 'yellow');

    /*
     * Check that all connector values have been set!
     */
    foreach(array('driver', 'host', 'user', 'pass') as $key){
        if(empty($connector[$key])){
            if($_CONFIG['production']){
                throw new bException('sql_connect(): The database configuration has key "'.str_log($key).'" missing, check your database configuration in '.ROOT.'/config/production.php');
            }

            throw new bException('sql_connect(): The database configuration has key "'.str_log($key).'" missing, check your database configuration in either '.ROOT.'/config/production.php and/or '.ROOT.'/config/'.ENVIRONMENT.'.php');
        }
    }

    switch($e->getCode()){
        case 2006:
            // FALLTHROUGH
        case 2002:
            /*
             * Connection refused
             */
            if(empty($connector['ssh_tunnel']['required'])){
                throw new bException(tr('sql_connect(): Connection refused for host ":hostname::port"', array(':hostname' => $connector['host'], ':port' => $connector['port'])), $e);
            }

            /*
             * This connection requires an SSH tunnel. Check if the tunnel process still exists
             */
            load_libs('cli,servers');

            if(!cli_pidgrep($tunnel['pid'])){
                $server     = servers_get($connector['ssh_tunnel']['hostname']);
                $registered = ssh_is_registered($server['hostname'], $server['port']);

                if($registered === false){
                    throw new bException(tr('sql_connect(): Connection refused for host ":hostname::port" because the tunnel process was canceled due to missing server fingerprints in the ROOT/data/ssh/known_hosts file and `ssh_fingerprints` table. Please register the server first', array(':hostname' => $connector['host'], ':port' => $connector['port'])), $e);
                }

                if($registered === true){
                    throw new bException(tr('sql_connect(): Connection refused for host ":hostname::port" because the tunnel process either started too late or already died. The server has its SSH fingerprints registered in the ROOT/data/ssh/known_hosts file.', array(':hostname' => $connector['host'], ':port' => $connector['port'])), $e);
                }

                /*
                 * The server was not registerd in the ROOT/data/ssh/known_hosts file, but was registered in the ssh_fingerprints table, and automatically updated. Retry to connect
                 */
                return sql_connect($connector, $use_database);
            }

//:TODO: SSH to the server and check if the msyql process is up!
            throw new bException(tr('sql_connect(): Connection refused for SSH tunnel requiring host ":hostname::port". The tunnel process is available, maybe the MySQL on the target server is down?', array(':hostname' => $connector['host'], ':port' => $connector['port'])), $e);

        case 1049:
            /*
             * Database not found!
             */
            $core->register['no-db'] = true;

            if(!((PLATFORM_CLI) and (SCRIPT == 'init'))){
                throw $e;
            }

            log_console(tr('Database base server conntection failed because database ":db" does not exist. Attempting to connect without using a database to correct issue', array(':db' => $connector['db'])), 'yellow');

            /*
             * We're running the init script, so go ahead and create the DB already!
             */
            $db  = $connector['db'];
            unset($connector['db']);
            $pdo = sql_connect($connector);

            log_console(tr('Successfully connected to database server. Attempting to create database ":db"', array(':db' => $db)), 'yellow');

            $pdo->query('CREATE DATABASE `'.$db.'`');

            log_console(tr('Reconnecting to database server with database ":db"', array(':db' => $db)), 'yellow');

            $connector['db'] = $db;
            $pdo = sql_connect($connector);
            break;

        case 2006:
            if(empty($connector['ssh_tunnel']['required'])){
                /*
                 * No SSH tunnel was required for this connector
                 */
                throw $e;
            }

            load_libs('servers,linux');
            $server  = servers_get($connector['ssh_tunnel']['hostname']);
            $allowed = linux_get_ssh_tcp_forwarding($server);

            if($allowed){
                /*
                 * SSH tunnel is required for this connector, but tcp fowarding is allowed
                 */
                throw $e;
            }

            if(!$server['allow_sshd_modification']){
                throw new bException(tr('sql_connect(): Connector ":connector" requires SSH tunnel to server, but that server does not allow TCP fowarding, nor does it allow auto modification of its SSH server configuration', array(':connector' => $connector)), 'configuration');
            }

            log_console(tr('sql_connect(): Connector ":connector" requires SSH tunnel to server ":server", but that server does not allow TCP fowarding. Server allows SSH server configuration modification, attempting to resolve issue', array(':server' => $connector['ssh_tunnel']['hostname'])), 'yellow');

            /*
             * Now enable TCP forwarding on the server, and retry connection
             */
            linux_set_ssh_tcp_forwarding($server, true);
            log_console(tr('sql_connect(): Enabled TCP fowarding for server ":server", trying to reconnect to MySQL database', array(':server' => $connector['ssh_tunnel']['hostname'])), 'yellow');

            if($connector['ssh_tunnel']['pid']){
                log_console(tr('sql_connect(): Closing previously opened SSH tunnel to server ":server"', array(':server' => $connector['ssh_tunnel']['hostname'])), 'yellow');
                ssh_close_tunnel($connector['ssh_tunnel']['pid']);
            }

            $pdo = sql_connect($connector);
            break;

        default:
            try{
                load_libs('sql-error');
                return sql_error($e, '', $connector, isset_get($pdo));

            }catch(Exception $e){
                throw new bException('sql_connect(): Failed', $e);
            }

            throw new bException('sql_connect(): Failed to create PDO SQL object', $e);
    }

}catch(Exception $e){
    throw $e;
}
?>
