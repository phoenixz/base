<?php
    if($e->getMessage() == 'could not find driver'){
        throw new bException('sql_connect(): Failed to connect with "'.str_log($connector['driver']).'" driver, it looks like its not available', $e);
    }

    if($e->getCode() == 1049){
        /*
         * Database not found!
         */
        if(!((PLATFORM == 'shell') and (SCRIPT == 'init'))){
            throw $e;
        }

        /*
         * We're running the init script, so go ahead and create the DB already!
         */
        $db = $connector['db'];
        $connector['db'] = '';

        sql_connect($connector);
        load_libs('init');
        init_process_version_diff();

        $pdo = sql_connect($connector);
        $pdo->query('CREATE DATABASE '.$db);

        $connector['db'] = $db;

        unset($pdo);
        unset($db);

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