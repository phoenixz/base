<?php
    global $_CONFIG;

    if($e->getMessage() == 'could not find driver'){
        throw new bException('sql_connect(): Failed to connect with "'.str_log($connector['driver']).'" driver, it looks like its not available', 'driverfail');
    }

    /*
     * Check that all connector values have been set!
     */
    foreach(array('driver', 'host', 'user', 'pass') as $key){
        if(empty($connector[$key])){
            if($_CONFIG['production']){
                throw new bException('sql_connect(): The database configuration has key "'.str_log($key).'" missing, check your database configuration in '.ROOT.'/config/production.php');
            }

            if(REQUIRE_SUBENVIRONMENTS){
                throw new bException('sql_connect(): The database configuration has key "'.str_log($key).'" missing, check your database configuration in either '.ROOT.'/config/production.php and/or '.ROOT.'/config/'.ENVIRONMENT.'.php and/or '.ROOT.'/config/'.ENVIRONMENT.'_'.SUBENVIRONMENT.'.php');
            }

            throw new bException('sql_connect(): The database configuration has key "'.str_log($key).'" missing, check your database configuration in either '.ROOT.'/config/production.php and/or '.ROOT.'/config/'.ENVIRONMENT.'.php');
        }
    }

    if($e->getCode() == 1049){
        /*
         * Database not found!
         */
        $GLOBALS['no-db'] = true;

        if(!((PLATFORM == 'shell') and (SCRIPT == 'init'))){
            throw $e;
        }

        log_console(tr('Database base server conntection failed because database "%db%" does not exist. Attempting to connect without using a database to correct issue', array('%db%' => $connector['db'])), '', 'yellow');

        /*
         * We're running the init script, so go ahead and create the DB already!
         */
        $db  = $connector['db'];
        unset($connector['db']);
        $pdo = sql_connect($connector);

        log_console(tr('Successfully connected to database server. Attempting to create database "%db%"', array('%db%' => $db)), '', 'yellow');

        $pdo->query('CREATE DATABASE `'.$db.'`');

        log_console(tr('Reconnecting to database server with database "%db%"', array('%db%' => $db)), '', 'yellow');

        $connector['db'] = $db;
        $pdo = sql_connect($connector);

    }else{
        try{
            load_libs('pdo_error');
            return pdo_error($e, $connector, null, isset_get($pdo));

        }catch(Exception $e){
            throw new bException('sql_connect(): Failed', $e);
        }

        throw new bException('sql_connect(): Failed to create PDO SQL object', $e);
    }
?>
