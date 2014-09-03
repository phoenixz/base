<?php
/*
 * PDO library
 *
 * This file contains various functions to access databases over PDO
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Execute specified query
 */
function sql_query($query, $execute = false, $handle_exceptions = true, $sql = 'sql') {
    try{
        sql_init($sql);

        if(is_string($query)){
            if(substr($query, 0, 1) == ' '){
                debug_sql($query, $execute);
                $query - substr($query, 1);
            }

            if(!$execute){
                /*
                 * Just execute plain SQL query string.
                 */
                return $GLOBALS[$sql]->query($query);
            }

            /*
             * Query was specified as a SQL query.
             */
            $p = $GLOBALS[$sql]->prepare($query);

        }else{
            /*
             * It's quietly assumed that a
             * PDOStatement object was specified
             */
            $p = $query;
        }

        try{
            $p->execute($execute);
            return $p;

        }catch(Exception $e){
            /*
             * Failure is probably that one of the the $execute array values is not scalar
             */
// :TODO: Move all of this to pdo_error()
            if(!is_array($execute)){
                throw new bException('sql_query(): Specified $execute is not an array!');
            }

            foreach($execute as $key => $value){
                if(!is_scalar($value) and !is_null($value)){
                    throw new bException('sql_query(): Specified key "'.str_log($key).'" in the execute array is NOT scalar!', 'invalid');
                }
            }

            throw $e;
        }

    }catch(Exception $e){
        if(!$handle_exceptions){
            throw new bException('sql_query(): Failed', $e);
        }

        try{
            load_libs('pdo_error');
            pdo_error($e, $query, $execute, $GLOBALS[$sql]);

        }catch(Exception $e){
            throw new bException('sql_query(): Query "'.str_log($query).'" Failed', $e);
        }
    }
}



/*
 * Prepare specified query
 */
function sql_prepare($query, $sql = 'sql'){
    try{
        return $GLOBALS[$sql]->prepare($query);

    }catch(Exception $e){
        throw new bException('sql_prepare(): Failed', $e);
    }
}



/*
 * Fetch and return data from specified resource
 */
function sql_fetch($r, $columns = false) {
    try{
        if(!is_object($r)){
            throw new bException('sql_fetch(): Specified resource is not a PDO object', 'invalid');
        }

        if(!$columns){
            /*
             * Return everything
             */
            return $r->fetch(PDO::FETCH_ASSOC);
        }

        $result = $r->fetch(PDO::FETCH_ASSOC);

        if($result === false){
            /*
             * There are no entries
             */
            return false;
        }

        /*
         * Validate that all specified columns were returned
         */
        foreach(array_force($columns) as $column){
            if(!array_key_exists($column, $result)){
                throw new bException('sql_fetch(): Specified column "'.str_log($column).'" does not exist in the specified result set', 'columnnotexist');
            }

            $retval[$column] = $result[$column];
        }

        /*
         * Return the one specified column value
         */
        if(count($retval) == 1){
            return array_pop($retval);
        }

        /*
         * Return all specified columns
         */
        return $retval;

    }catch(Exception $e){
        throw new bException('sql_fetch(): Failed', $e);
    }
}



/*
 * Execute query and return only the first row
 */
function sql_get($query, $column = null, $execute = null, $sql = 'sql') {
    try{
        if(is_array($column)){
            /*
             * Argument shift, no columns were specified.
             */
            $tmp     = $execute;
            $execute = $column;
            $column  = $tmp;
            unset($tmp);
        }

        return sql_fetch(sql_query($query, $execute, true, $sql), $column);

    }catch(Exception $e){
        if(strtolower(substr(trim($query), 0, 6)) != 'select'){
            throw new bException('sql_get(): Query "'.str_log($query).'" is not a select query and as such cannot return results', $e);
        }

        throw new bException('sql_get(): Failed', $e);
    }
}



/*
 * Execute query and return only the first row
 */
function sql_list($query, $column = null, $execute = null, $sql = 'sql') {
    try{
        if(is_array($column)){
            /*
             * Argument shift, no columns were specified.
             */
            $tmp     = $execute;
            $execute = $column;
            $column  = $tmp;
            unset($tmp);
        }

        $r      = sql_query($query, $execute, true, $sql);
        $retval = array();

        while($row = sql_fetch($r, $column)){
            if(is_scalar($row)){
                $retval[] = $row;

            }else{
                switch(count($row)){
                    case 1:
                        $retval[] = array_shift($row);
                        break;

                    case 2:
                        $retval[array_shift($row)] = array_shift($row);
                        break;

                    default:
                        $retval[array_shift($row)] = $row;
                }
            }
        }

        return $retval;

    }catch(Exception $e){
        if(strtolower(substr(trim($query), 0, 6)) != 'select'){
            throw new bException('sql_list(): Query "'.str_log($query).'" is not a select query and as such cannot return results', $e);
        }

        throw new bException('sql_list(): Failed', $e);
    }
}



/*
 * Connect with the main database
 */
function sql_init($sql = 'sql', $db = null){
    global $_CONFIG;

    try{
        if(!empty($GLOBALS[$sql])) {
            /*
             * Already connected to DB
             */
            return false;
        }

        if(empty($db)){
            $db = $_CONFIG['db'];
        }

        $GLOBALS[$sql] = sql_connect($db);

        /*
         * This is only required for the system connection
         */
        if((PLATFORM == 'shell') and (SCRIPT == 'init') and FORCE and ($sql == 'sql')){
            /*
             * We're doing a forced init from shell. Forced init will
             * basically set database version to 0 BY DROPPING THE FUCKER SO BE CAREFUL!
             *
             * Forced init is NOT allowed on production (for obvious safety reasons, doh!)
             */
            if(ENVIRONMENT == 'production'){
                throw new bException('sql_init(): For safety reasons, init force is NOT allowed on production environment! Please drop the database using "./scripts/base/init drop" or in the mysql console with "DROP DATABASE \''.str_log($_CONFIG['db']['db']).'\'"and continue with a standard init', 'forcedenied');
            }

            if(!str_is_version(FORCE)){
                if(!is_bool(FORCE)){
                    throw new bException('sql_init(): Invalid "force" sub parameter "'.str_log(FORCE).'" specified. "force" can only be followed by a valid init version number', 'invalidforce');
                }

                /*
                 * Dump database, and recreate it
                 */
                $GLOBALS[$sql]->query('DROP   DATABASE IF EXISTS '.$_CONFIG['db']['db']);
                $GLOBALS[$sql]->query('CREATE DATABASE '.$_CONFIG['db']['db']);
                $GLOBALS[$sql]->query('USE             '.$_CONFIG['db']['db']);
            }
        }

        /*
         * Get database version
         *
         * In some VERY FEW cases, this check must be skipped. Skipping is done by setting the global variable
         * skipversioncheck to true. If you don't know why this should be done, then DONT USE IT!
         */
        try{
            if($sql == 'sql'){
                $r = $GLOBALS[$sql]->query('SELECT `project`, `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

                if(!$r->rowCount()){
                    log_console('sql_init(): No versions in versions table found, assumed empty database', 'warning/versions', 'yellow');

                    define('FRAMEWORKDBVERSION', 0);
                    define('PROJECTDBVERSION'  , 0);

                }else{
                    $versions = $r->fetch(PDO::FETCH_ASSOC);

                    define('FRAMEWORKDBVERSION', $versions['framework']);
                    define('PROJECTDBVERSION'  , $versions['project']);
                }
            }

        }catch(Exception $e){
            /*
             * Database version lookup failed. Usually, this would be due to the database being empty,
             * and versions table does not exist (yes, that makes a query fail). Just to be sure that
             * it did not fail due to other reasons, check why the lookup failed.
             */
            load_libs('init');
            init_process_version_fail($e);
        }

        /*
         * On console, show current versions
         */
        if((PLATFORM == 'shell') and empty($GLOBALS['quiet'])){
            log_console('sql_init(): Found framework code version "'.str_log(FRAMEWORKCODEVERSION).'" and framework database version "'.str_log(FRAMEWORKDBVERSION).'"');
            log_console('sql_init(): Found project code version "'.str_log(PROJECTCODEVERSION).'" and project database version "'.str_log(PROJECTDBVERSION).'"');
        }


        /*
         * Validate code and database version. If both FRAMEWORK and PROJECT versions of the CODE and DATABASE do not match,
         * then check exactly what is the version difference
         */
        if((FRAMEWORKCODEVERSION != FRAMEWORKDBVERSION) or (PROJECTCODEVERSION != PROJECTDBVERSION)){
            load_libs('init');
            init_process_version_diff();
        }

    }catch(Exception $e){
        /*
         *
         */
        if($e->code == 1049){
            if(!empty($retry)){
                static $retry = true;

                pdo_error_init($e, $_CONFIG['db']);
                return sql_init();
            }
        }

        $e = new bException('sql_init(): Failed', $e);

        try{
            load_libs('pdo_error');
            return pdo_error($e, $_CONFIG['db'], null, isset_get($GLOBALS[$sql]));

        }catch(Exception $e){
            throw new bException('sql_init(): Failed', $e);
        }
    }
}



/*
 * Connect to database and do a DB version check.
 * If the database was already connected, then just ignore and continue.
 * If the database version check fails, then exception
 */
function sql_connect($connector) {
    global $_CONFIG;

    try{
        array_params($connector, 'db');
        array_default($connector, 'driver' , $_CONFIG['db']['driver']);
        array_default($connector, 'host'   , $_CONFIG['db']['host']);
        array_default($connector, 'user'   , $_CONFIG['db']['user']);
        array_default($connector, 'pass'   , $_CONFIG['db']['pass']);
        array_default($connector, 'charset', $_CONFIG['db']['charset']);

        if(!class_exists('PDO')){
            /*
             * Wulp, PDO class not available, PDO driver is not loaded somehow
             */
            throw new bException('sql_connect(): Could not find the "PDO" class, does this PHP have PDO available?', 'dbdriver');
        }

        /*
         * Connect!
         */
        try{
            $pdo = new PDO($connector['driver'].':;host='.$connector['host'], $connector['user'], $connector['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        }catch(Exception $e){
            if($e->getMessage() == 'could not find driver'){
                throw new bException('sql_connect(): Failed to connect with "'.str_log($connector['driver']).'" driver, it looks like its not available', $e);
            }

            throw new bException('sql_connect(): Failed to create PDO SQL object', $e);
        }

        if(!empty($connector['buffered'])){
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        /*
         * Try to use the database. If it doesnt exist, then create it now
         */
        try{
            if(!empty($connector['db'])){
                $pdo->query('USE '.$connector['db']);
            }

        }catch(Exception $e){
            /*
             * IMPORTANT!
             * This pdo_error handler is RETURNED because
             * if running init and database is not found,
             * it will recursive retry and return the PDO SQL to sql_init()!
             *
             * Do NOT change this behaviour!
             */
            try{
                load_libs('pdo_error');
                return pdo_error($e, $connector, null, $pdo);

            }catch(Exception $e){
                throw new bException('sql_connect(): Failed', $e);
            }
        }

        /*
         * Ensure correct character set usage
         */
        $pdo->query('SET NAMES '.$connector['charset']);
        $pdo->query('SET CHARACTER SET '.$connector['charset']);

        if(!empty($connector['mode'])){
            $pdo->query('SET sql_mode="'.$connector['mode'].'";');
        }

        return $pdo;

    }catch(Exception $e){
        throw new bException('sql_connect(): Failed', $e);
    }
}



/*
 * Import data from specified file
 */
function sql_import($file) {
    try {
        if(!file_exists($file)){
            throw new bException('sql_import(): Specified file "'.str_log($file).'" does not exist', 'notexist');
        }

        $tel    = 0;
        $handle = @fopen($file, 'r');

        if(!$handle){
            throw new isException('sql_import(): Could not open file', 'notopen');
        }

        while (($buffer = fgets($handle)) !== false) {
            $buffer = trim($buffer);

            if(!empty($buffer)) {
                $GLOBALS['sql']->query(trim($buffer));

                $tel++;
// :TODO:SVEN:20130717: Right now it updates the display for each record. This may actually slow down import. Make display update only every 10 records or so
                echo 'Importing SQL data ('.$file.') : '.number_format($tel)."\n";
                //one line up!
                echo "\033[1A";
            }
        }

        echo "\nDone\n";

        if(!feof($handle)){
            throw new isException('sql_import(): Unexpected EOF', '');
        }

        fclose($handle);

    }catch(Exception $e){
        throw new bException('sql_import(): Failed to import file "'.$file.'"', $e);
    }
}



/*
 *
 */
function sql_columns($source, $columns){
    try{
        if(!is_array($source)){
            throw new bException('sql_columns(): Specified source is not an array');
        }

        $columns = array_force($columns);
        $retval  = array();

        foreach($source as $key => $value){
            if(in_array($key, $columns)){
                $retval[] = '`'.$key.'`';
            }
        }

        if(!count($retval)){
            throw new bException('sql_columns(): Specified source contains non of the specified columns "'.str_log(implode(',', $columns)).'"');
        }

        return implode(', ', $retval);

    }catch(Exception $e){
        throw new bException('sql_columns(): Failed', $e);
    }
}



/*
 *
 */
function sql_set($source, $columns, $filter = 'id'){
    try{
        if(!is_array($source)){
            throw new bException('sql_set(): Specified source is not an array', 'invalid');
        }

        $columns = array_force($columns);
        $filter  = array_force($filter);
        $retval  = array();

        foreach($source as $key => $value){
            /*
             * Add all in columns, but not in filter (usually to skip the id column)
             */
            if(in_array($key, $columns) and !in_array($key, $filter)){
                $retval[] = '`'.$key.'` = :'.$key;
            }
        }

        foreach($filter as $item){
            if(!isset($source[$item])){
                throw new bException('sql_set(): Specified filter item "'.str_log($item).'" was not found in source', 'notfound');
            }
        }

        if(!count($retval)){
            throw new bException('sql_set(): Specified source contains non of the specified columns "'.str_log(implode(',', $columns)).'"', 'empty');
        }

        return implode(', ', $retval);

    }catch(Exception $e){
        throw new bException('sql_set(): Failed', $e);
    }
}



/*
 *
 */
function sql_values($source, $columns, $prefix = ':'){
    try{
        if(!is_array($source)){
            throw new bException('sql_values(): Specified source is not an array');
        }

        $columns = array_force($columns);
        $retval  = array();

        foreach($source as $key => $value){
            if(in_array($key, $columns) or ($key == 'id')){
                $retval[$prefix.$key] = $value;
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('sql_values(): Failed', $e);
    }
}



/*
 *
 */
function sql_insert_id(){
    return $GLOBALS['sql']->lastInsertId();
}



/*
 *
 */
function sql_get_id_or_name($entry, $seo = true, $code = false){
    try{
        if(is_array($entry)){
            if(!empty($entry['id'])){
                $entry = $entry['id'];

            }elseif(!empty($entry['name'])){
                $entry = $entry['name'];

            }elseif(!empty($entry['seoname'])){
                $entry = $entry['seoname'];

            }elseif(!empty($entry['code'])){
                $entry = $entry['code'];

            }else{
                throw new bException('sql_get_id_or_name(): Invalid entry array specified', 'invalid');
            }
        }

        if(is_numeric($entry)){
            $retval['where']   = '`id` = :id';
            $retval['execute'] = array(':id'   => $entry);

        }elseif(is_string($entry)){
            if($seo){
                if($code){
                    $retval['where']   = '`name` = :name OR `seoname` = :seoname OR `code` = :code';
                    $retval['execute'] = array(':code'    => $entry,
                                               ':name'    => $entry,
                                               ':seoname' => $entry);

                }else{
                    $retval['where']   = '`name` = :name OR `seoname` = :seoname';
                    $retval['execute'] = array(':name'    => $entry,
                                               ':seoname' => $entry);
                }

            }else{
                if($code){
                    $retval['where']   = '`name` = :name OR `code` = :code';
                    $retval['execute'] = array(':code' => $entry,
                                               ':name' => $entry);

                }else{
                    $retval['where']   = '`name` = :name';
                    $retval['execute'] = array(':name' => $entry);
                }
            }

        }else{
            throw new bException('sql_get_id_or_name(): Invalid entry with type "'.gettype($entry).'" specified', 'invalid');
        }

        return $retval;

    }catch(bException $e){
        throw new bException('sql_get_id_or_name(): Failed (use either numeric id, name sting, or entry array with id or name)', $e);
    }
}



/*
 * Return a unique, non existing ID for the specified table.column
 */
function sql_unique_id($table, $column = 'id', $max = 10000000, $sql = 'sql'){
    try{
        $retries    =  0;
        $maxretries = 50;

        while(++$retries < $maxretries){
            $id = mt_rand(1, $max);

            if(!sql_get('SELECT `'.$column.'` FROM `'.$table.'` WHERE `'.$column.'` = :id', array(':id' => $id), null, $sql)){
                return $id;
            }
        }

        throw new bException('sql_unique_id(): Could not find a unique id in "'.$maxretries.'" retries', 'notfound');

    }catch(bException $e){
        throw new bException('sql_unique_id(): Failed', $e);
    }
}



/*
 *
 */
function sql_filters($params, $columns, $table = ''){
    try{
        $retval  = array();
        $filters = array_keep($params, $columns);

        foreach($filters as $key => $value){
            $retval['filters'][]         = ($table ? '`'.$table.'`.' : '').'`'.$key.'` = :'.$key;
            $retval['execute'][':'.$key] = $value;
        }

        return $retval;

    }catch(bException $e){
        throw new bException('sql_filters(): Failed', $e);
    }
}



/*
 * Return a sequential array that can be used in sql_in
 */
function sql_in($source, $column = ':value'){
    try{
        if(empty($source)){
            throw new bException('sql_in(): Specified source is empty', 'empty');
        }

        load_libs('array');
        return array_sequential_keys(array_force($source), $column);

    }catch(bException $e){
        throw new bException('sql_in(): Failed', $e);
    }
}



/*
 *
 */
function sql_where($query, $required){
    try{
        if(!$query){
            if($required){
                throw new bException('sql_where(): No filter query specified, but it is required', 'required');
            }

            return ' ';
        }

        return ' WHERE '.$query;

    }catch(bException $e){
        throw new bException('sql_filters(): Failed', $e);
    }
}



/*
 * Try to get single data entry from memcached. If not available, get it from
 * MySQL and store results in memcached for future use
 */
function sql_get_cached($key, $query, $column = false, $execute = false, $expiration_time = 86400, $sql = 'sql'){
    try{
        if(($value = mc_get($key)) === false){
            /*
             * Keyword data not found in cache, get it from MySQL with
             * specified query and store it in cache for next read
             */
            if(is_array($column)){
                /*
                 * Argument shift, no columns were specified.
                 */
                $tmp     = $execute;
                $execute = $column;
                $column  = $tmp;
                unset($tmp);
            }

            if(is_numeric($column)){
                /*
                 * Argument shift, no columns were specified.
                 */
                $tmp             = $expiration_time;
                $expiration_time = $execute;
                $execute         = $tmp;
                unset($tmp);
            }

            $value = sql_get($query, $column, $execute, $sql);

            mc_put($key, $value, $expiration_time);
        }

        return $value;

    }catch(bException $e){
        throw new bException('sql_get_cached(): Failed', $e);
    }
}



/*
 * Try to get data list from memcached. If not available, get it from
 * MySQL and store results in memcached for future use
 */
function sql_list_cached($key, $query, $column = false, $execute = false, $expiration_time = 86400, $sql = 'sql'){
    try{
        if(($list = mc_get($key)) === false){
            /*
             * Keyword data not found in cache, get it from MySQL with
             * specified query and store it in cache for next read
             */
            if(is_array($column)){
                /*
                 * Argument shift, no columns were specified.
                 */
                $tmp     = $execute;
                $execute = $column;
                $column  = $tmp;
                unset($tmp);
            }

            if(is_numeric($column)){
                /*
                 * Argument shift, no columns were specified.
                 */
                $tmp             = $expiration_time;
                $expiration_time = $execute;
                $execute         = $tmp;
                unset($tmp);
            }

            $list = sql_list($query, $column, $execute, $sql);

            mc_put($key, $list, $expiration_time);
        }

        return $list;

    }catch(bException $e){
        throw new bException('sql_list_cached(): Failed', $e);
    }
}



/*
 * COMPATIBILITY FUNCTIONS
 *
 * These functions below exist only for compatibility between pdo.php and mysqli.php
 *
 * Return affected rows
 */
function sql_affected_rows($r) {
    return $r->rowCount();
}

/*
 * Return number of rows in the specified resource
 */
function sql_num_rows(&$r) {
    return $r->rowCount();
}

/*
 * Fetch and return data from specified resource
 */
function sql_fetch_column($r, $column) {
    try{
        $row = sql_fetch($r);

        if(!isset($row[$column])){
            throw new bException('sql_fetch_column(): Specified column "'.str_log($column).'" does not exist', $e);
        }

        return $row[$column];

    }catch(Exception $e){
        throw new bException('sql_fetch_column(): Failed', $e);
    }
}
?>
