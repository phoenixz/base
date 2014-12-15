<?php
/*
 * PDO error handler library
 *
 * This library tries to handle the various SQL errors and tries to at least give useful error messages
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */

// :TODO: Add SQLSTATE[HY000] [2002] No such file or directory


/*
 * Handle the PDO error
 */
function pdo_error($e, $query, $execute, $sql = null){
    if(!$e instanceof PDOException){
        switch($e->getCode()){
            case 'forcedenied':
                uncaught_exception($e, true);

            default:
                /*
                 * This is likely not a PDO error, so it cannot be handled here
                 */
                throw new bException('pdo_error(): Not a PDO exception', $e);
        }
    }

    try{
        if(!is_object($sql)){
            if(empty($GLOBALS['sql'])){
                throw new bException('pdo_error(): The $sql is not an object, cannot get more info from there', $e);
            }

            $sql = $GLOBALS['sql'];
        }

        if($execute){
            if(!is_array($execute)){
                throw new bException('pdo_error(): The specified $execute parameter is NOT an array, it is an "'.gettype($execute).'"', $e);
            }

            foreach($execute as $key => $value){
                if(!is_scalar($value) and !is_null($value)){
                    /*
                     * This is automatically a problem!
                     */
                    throw new bException('pdo_error(): POSSIBLE ERROR: The specified $execute array contains key "'.str_log($key).'" with non scalar value "'.str_log($value).'"', $e);
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
                    log_screen($message, '', 'red');
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
                    throw new bException('pdo_error(): Query "'.str_log($query).'" failed with error HY093, the number of query tokens does not match the number of bound variables. The query contains tokens "'.str_log(implode(',', $matches['0'])).'", where the bound variables are "'.str_log(implode(',', array_keys($execute))).'"', $e);
                }

                throw new bException('pdo_error(): Query "'.str_log($query).'" failed with error HY093, One or more query tokens does not match the bound variables keys. The query contains tokens "'.str_log(implode(',', $matches['0'])).'", where the bound variables are "'.str_log(implode(',', array_keys($execute))).'"', $e);

            case '23000':
                /*
                 * 23000 is used for many types of errors!
                 */

// :TODO: Remove next 5 lines, 23000 cannot be treated as a generic error because too many different errors cause this one
//showdie($error)
//                /*
//                 * Integrity constraint violation: Duplicate entry
//                 */
//                throw new bException('pdo_error(): Query "'.str_log($query).'" tries to insert or update a column row with a unique index to a value that already exists', $e);

            default:
                switch(isset_get($error[1])){
                    case 1044:
                        /*
                         * Access to database denied
                         */
                        if(!is_array($query)){
                            if(empty($query['db'])){
                                throw new bException('pdo_error(): "'.str_log($query).'" failed, access to databaes denied', $e);
                            }

                            throw new bException('pdo_error(): Cannot use database "'.str_log($query['db']).'", this user has no access to it', $e);
                        }

                        throw new bException('pdo_error(): Cannot use database with query "'.str_log($query).'", this user has no access to it', $e);

                    case 1049:
                        /*
                         * Specified database does not exist
                         */
                        static $retry;

                        if((SCRIPT == 'init')){
                            if($retry){
                                $e = new bException('pdo_error(): Cannot use database "'.isset_get($query['db']).'", it does not exist and cannot be created automatically with the current user "'.isset_get($query['user']).'"', $e);
                                $e->addMessage('pdo_error(): Possible reason can be that the configured user does not have the required GRANT to create database');
                                $e->addMessage('pdo_error(): Possible reason can be that MySQL cannot create the database because the filesystem permissions of the mysql data files has been borked up (on linux, usually this is /var/lib/mysql, and this should have the user:group mysql:mysql)');

                                throw $e;
                            }

                            /*
                             * We're doing an init, try to automatically create the database
                             */
                            $retry = true;
                            log_screen('Database "'.$query['db'].'" does not exist, attempting to create it automatically', 'warning/database', 'yellow');

                            $sql->query('CREATE DATABASE '.$query['db']);
                            return sql_connect($query);
                        }

                        throw new bException('pdo_error(): Cannot use database "'.str_log($query['db']).'", it does not exist', $e);

                    case 1052:
                        /*
                         * Integrity constraint violation
                         */
                        throw new bException('pdo_error(): Query "'.str_log($query).'" contains an abiguous column', $e);

                    case 1054:
                        /*
                         * Column not found
                         */
                        throw new bException('pdo_error(): Query "'.str_log($query).'" refers to a column that does not exist', $e);

                    case 1064:
                        /*
                         * Syntax error or access violation
                         */
                        throw new bException('pdo_error(): Query "'.str_log($query, 4096).'" has a syntax error', $e);

                    case 1072:
                        /*
                         * Adding index error, index probably does not exist
                         */
                        throw new bException('pdo_error(): Query "'.str_log($query).'" failed with error 1072 with the message "'.isset_get($error[2]).'"', $e);

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
                            throw new bException('pdo_error(): Query "'.str_log($query).'" failed with error 1005, but another error was encountered while trying to obtain FK error data', $e);
                        }

                        throw new bException('pdo_error(): Query "'.str_log($query).'" failed with error 1005 with the message "'.$fk.'"', $e);

                    case 1146:
                        /*
                         * Base table or view not found
                         */
                        throw new bException('pdo_error(): Query "'.str_log($query).'" refers to a base table or view that does not exist', $e);

                    default:
                        if(!is_string($query)){
                            if(!is_object($query) or !($query instanceof PDOStatement)){
                                throw new bException('pdo_error(): Specified query is neither a SQL string or a PDOStatement, it seems to be a "'.gettype($query).'"', 'invlaid');
                            }

                            $query = $query->queryString;
                        }

                        load_libs('debug');
                        throw new bException('pdo_error(): Query "'.str_log(debug_sql(preg_replace('!\s+!', ' ', $query), null, $execute, true)).'" failed', $e);

                        $body = "SQL STATE ERROR : \"".$error[0]."\"\n".
                                "DRIVER ERROR    : \"".$error[1]."\"\n".
                                "ERROR MESSAGE   : \"".$error[2]."\"\n".
                                "query           : \"".(PLATFORM == 'apache' ? "<b>".str_log($query)."</b>" : str_log($query))."\"\n".
                                "date            : \"".date('d m y h:i:s')."\"\n";

                        if(isset($_SESSION)) {
                            $body .= "Session : ".print_r($_SESSION,true)."\n";
                        }

                        $body .= "POST   : ".print_r($_POST,true)."
                                  GET    : ".print_r($_GET,true)."
                                  SERVER : ".print_r($_SERVER,true)."\n";

                        error_log('PHP SQL_ERROR: '.str_log($error[2]).' on '.str_log($query));

                        if (ENVIRONMENT != 'production') {
                            throw new bException(nl2br($body), $e);
                        }

                        throw new bException(tr('An error has been detected, our staff has been notified about this problem.'), $e);
                }
        }

    }catch(Exception $e){
        throw new bException('pdo_error(): Failed', $e);
    }
}



/*
 * PDO USE failed
 */
function pdo_error_init($e, $connector){
    global $_CONFIG;

    try{
        $GLOBALS['sql']->query('DROP DATABASE IF EXISTS '.$connector['db'].';');
        $GLOBALS['sql']->query('CREATE DATABASE '.$connector['db'].' DEFAULT CHARSET="'.$connector['charset'].'" COLLATE="'.$connector['collate'].'";');
        $GLOBALS['sql']->query('USE '.$connector['db']);
        return true;


    }catch(Exception $e){
        throw new bException('pdo_error_init(): Failed', $e);
    }

    throw $e;
}
?>
