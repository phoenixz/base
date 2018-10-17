<?php
global $_CONFIG, $core;

if(!$e instanceof PDOException){
    switch($e->getCode()){
        case 'forcedenied':
            uncaught_exception($e, true);

        default:
            /*
             * This is likely not a PDO error, so it cannot be handled here
             */
            throw new bException('sql_error(): Not a PDO exception', $e);
    }
}

try{
    if(!is_object($sql)){
        if(empty($core->sql['core'])){
            throw new bException('sql_error(): The $sql is not an object, cannot get more info from there', $e);
        }

        $sql = $core->sql['core'];
    }

    if($query){
        if($execute){
            if(!is_array($execute)){
                throw new bException(tr('sql_error(): The specified $execute parameter is NOT an array, it is an ":type"', array(':type' => gettype($execute))), $e);
            }

            foreach($execute as $key => $value){
                if(!is_scalar($value) and !is_null($value)){
                    /*
                     * This is automatically a problem!
                     */
                    throw new bException(tr('sql_error(): POSSIBLE ERROR: The specified $execute array contains key ":key" with non scalar value ":value"', array(':key' => $key, ':value' => $value)), $e);
                }
            }
        }
    }

    /*
     * Get error data
     */
    $error = $sql->errorInfo();

    if(($error[0] == '00000') and !$error[1]){
        $error = $e->errorInfo;
    }

    switch($e->getCode()){
        case 'denied':
            // FALLTHROUGH
        case 'invalidforce':

            /*
             * Some database operation has failed
             */
            foreach($e->getMessages() as $message){
                log_console($message, 'red');
            }

            die(1);

        case 'HY093':
            /*
             * Invalid parameter number: number of bound variables does not match number of tokens
             *
             * Get tokens from query
             */
            preg_match_all('/:\w+/imus', $query, $matches);

            if(count($matches[0]) != count($execute)){
                throw new bException(tr('sql_error(): Query ":query" failed with error HY093, the number of query tokens does not match the number of bound variables. The query contains tokens ":tokens", where the bound variables are ":variables"', array(':query' => $query, ':tokens' => implode(',', $matches['0']), ':variables' => implode(',', array_keys($execute)))), $e);
            }

            throw new bException(tr('sql_error(): Query ":query" failed with error HY093, One or more query tokens does not match the bound variables keys. The query contains tokens ":tokens", where the bound variables are ":variables"', array(':query' => $query, ':tokens' => implode(',', $matches['0']), ':variables' => implode(',', array_keys($execute)))), $e);

        case '23000':
            /*
             * 23000 is used for many types of errors!
             */

// :TODO: Remove next 5 lines, 23000 cannot be treated as a generic error because too many different errors cause this one
//showdie($error)
//                /*
//                 * Integrity constraint violation: Duplicate entry
//                 */
//                throw new bException('sql_error(): Query "'.str_log($query, 4096).'" tries to insert or update a column row with a unique index to a value that already exists', $e);

        default:
            switch(isset_get($error[1])){
                case 1044:
                    /*
                     * Access to database denied
                     */
                    if(!is_array($query)){
                        if(empty($query['db'])){
                            throw new bException(tr('sql_error(): Query ":query" failed, access to database denied', array(':query' => $query)), $e);
                        }

                        throw new bException(tr('sql_error(): Cannot use database ":db", this user has no access to it', array(':db' => $query['db'])), $e);
                    }

                    throw new bException(tr('sql_error(): Cannot use database with query ":query", this user has no access to it', array(':query' => debug_sql($query, $execute, true))), $e);

                case 1049:
                    /*
                     * Specified database does not exist
                     */
                    static $retry;

                    if((SCRIPT == 'init')){
                        if($retry){
                            $e = new bException(tr('sql_error(): Cannot use database ":db", it does not exist and cannot be created automatically with the current user ":user"', array(':db' => isset_get($query['db']), ':user' => isset_get($query['user']))), $e);
                            $e->addMessages(tr('sql_error(): Possible reason can be that the configured user does not have the required GRANT to create database'));
                            $e->addMessages(tr('sql_error(): Possible reason can be that MySQL cannot create the database because the filesystem permissions of the mysql data files has been borked up (on linux, usually this is /var/lib/mysql, and this should have the user:group mysql:mysql)'));

                            throw $e;
                        }

                        /*
                         * We're doing an init, try to automatically create the database
                         */
                        $retry = true;
                        log_console('Database "'.$query['db'].'" does not exist, attempting to create it automatically', 'yellow');

                        $sql->query('CREATE DATABASE `'.$query['db'].'` DEFAULT CHARSET="'.$connector['charset'].'" COLLATE="'.$connector['collate'].'";');
                        return sql_connect($query);
                    }

                    throw new bException(tr('sql_error(): Cannot use database ":db", it does not exist', array(':db' => $query['db'])), $e);

                case 1052:
                    /*
                     * Integrity constraint violation
                     */
                    throw new bException(tr('sql_error(): Query ":query" contains an abiguous column', array(':query' => debug_sql($query, $execute, true))), $e);

                case 1054:
                    /*
                     * Column not found
                     */
                    throw new bException(tr('sql_error(): Query ":query" refers to a column that does not exist', array(':query' => debug_sql($query, $execute, true))), $e);

                case 1064:
                    /*
                     * Syntax error or access violation
                     */
                    throw new bException(tr('sql_error(): Query ":query" has a syntax error', array(':query' => debug_sql($query, $execute, true))), $e);

                case 1072:
                    /*
                     * Adding index error, index probably does not exist
                     */
                    throw new bException(tr('sql_error(): Query ":query" failed with error 1072 with the message ":message"', array(':query' => debug_sql($query, $execute, true), ':message' => isset_get($error[2]))), $e);

                case 1005:
                    //FALLTHROUGH
                case 1217:
                    //FALLTHROUGH
                case 1452:
                    /*
                     * Foreign key error, get the FK error data from mysql
                     */
                    try{
                        $fk = sql_fetch(sql_query('SHOW ENGINE INNODB STATUS', null, false), 'Status');
                        $fk = str_from ($fk, 'LATEST FOREIGN KEY ERROR');
                        $fk = str_from ($fk, '------------------------');
                        $fk = str_until($fk, '------------');
                        $fk = str_replace("\n", ' ', $fk);

                    }catch(Exception $e){
                        throw new bException(tr('sql_error(): Query ":query" failed with error 1005, but another error was encountered while trying to obtain FK error data', array(':query' => debug_sql($query, $execute, true))), $e);
                    }

                    throw new bException(tr('sql_error(): Query ":query" failed with error 1005 with the message ":message"', array(':query' => debug_sql($query, $execute, true), ':message' => $fk)), $e);

                case 1146:
                    /*
                     * Base table or view not found
                     */
                    throw new bException(tr('sql_error(): Query ":query" refers to a base table or view that does not exist', array(':query' => debug_sql($query, $execute, true))), $e);

                default:
                    if(!is_string($query)){
                        if(!is_object($query) or !($query instanceof PDOStatement)){
                            throw new bException('sql_error(): Specified query is neither a SQL string or a PDOStatement it seems to be a ":type"', array(':type' => gettype($query)), 'invalid');
                        }

                        $query = $query->queryString;
                    }

                    throw new bException(tr('sql_error(): Query ":query" failed', array(':query' => debug_sql(preg_replace('!\s+!', ' ', $query), $execute, true))), $e);

                    $body = "SQL STATE ERROR : \"".$error[0]."\"\n".
                            "DRIVER ERROR    : \"".$error[1]."\"\n".
                            "ERROR MESSAGE   : \"".$error[2]."\"\n".
                            "query           : \"".(PLATFORM_HTTP ? "<b>".str_log(debug_sql($query, $execute, true), 4096)."</b>" : str_log(debug_sql($query, $execute, true), 4096))."\"\n".
                            "date            : \"".date('d m y h:i:s')."\"\n";

                    if(isset($_SESSION)) {
                        $body .= "Session : ".print_r(isset_get($_SESSION), true)."\n";
                    }

                    $body .= "POST   : ".print_r($_POST  , true)."
                              GET    : ".print_r($_GET   , true)."
                              SERVER : ".print_r($_SERVER, true)."\n";

                    error_log('PHP SQL_ERROR: '.str_log($error[2]).' on '.str_log(debug_sql($query, $execute, true), 4096));

                    if(!$_CONFIG['production']){
                        throw new bException(nl2br($body), $e);
                    }

                    throw new bException(tr('sql_error(): An error has been detected, our staff has been notified about this problem.'), $e);
            }
    }

}catch(Exception $e){
    throw new bException('sql_error(): Failed', $e);
}
?>