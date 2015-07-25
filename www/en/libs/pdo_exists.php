<?php
/*
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Returns if specified index exists
 *
 * If query is specified, the query will be executed only if the specified function exists
 * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
 */
function sql_index_exists($table, $index, $query = '', $connector = null){
    global $pdo;

    try{
        $retval = sql_get('SHOW INDEX FROM `'.cfm($table).'` WHERE `Key_name` = "'.cfm($index).'"', null, null, $connector);

        if(substr($query, 0, 1) == '!'){
            $not   = true;
            $query = substr($query, 1);

        }else{
            $not = false;
        }

        if(empty($retval) xor $not){
            return false;
        }

        if($query){
            sql_query($query, null, null, $connector);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('sql_index_exists(): Failed', $e);
    }
}



/*
 * Returns if specified column exists
 *
 * If query is specified, the query will be executed only if the specified function exists
 * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
 */
function sql_table_exists($table, $query = '', $connector = null){
    global $pdo;

    try{
        $retval = sql_get('SHOW TABLES LIKE "'.cfm($table).'"', null, null, $connector);

        if(substr($query, 0, 1) == '!'){
            $not   = true;
            $query = substr($query, 1);

        }else{
            $not = false;
        }

        if(empty($retval) xor $not){
            return false;
        }

        if($query){
            sql_query($query, null, null, $connector);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('sql_table_exists(): Failed', $e);
    }
}



/*
 * Returns if specified column exists
 *
 * If query is specified, the query will be executed only if the specified function exists
 * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
 */
function sql_column_exists($table, $column, $query = '', $connector = null){
    global $pdo;

    try{
        $retval = sql_get('SHOW COLUMNS FROM `'.cfm($table).'` WHERE `Field` = "'.cfm($column).'"', null, null, $connector);

        if(substr($query, 0, 1) == '!'){
            $not   = true;
            $query = substr($query, 1);

        }else{
            $not = false;
        }

        if(empty($retval) xor $not){
            return false;
        }

        if($query){
            sql_query($query, null, null, $connector);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('sql_column_exists(): Failed', $e);
    }
}



/*
 * Returns if specified foreign key exists
 *
 * If query is specified, the query will be executed only if the specified function exists
 * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
 */
function sql_foreignkey_exists($table, $foreign_key, $query = '', $connector = null){
    global $pdo, $_CONFIG;

    try{
        $connector = sql_connector_name($connector);

        if(!$database){
            $database = $_CONFIG['db'][$connector]['db'];
        }

        $retval = sql_get('SELECT *

                           FROM   `information_schema`.`TABLE_CONSTRAINTS`

                           WHERE  `CONSTRAINT_TYPE`   = "FOREIGN KEY"
                           AND    `CONSTRAINT_SCHEMA` = "'.cfm($database).'"
                           AND    `TABLE_NAME`        = "'.cfm($table).'"
                           AND    `CONSTRAINT_NAME`   = "'.cfm($foreign_key).'"', null, null, $connector);

        if(substr($query, 0, 1) == '!'){
            $not   = true;
            $query = substr($query, 1);

        }else{
            $not = false;
        }

        if(empty($retval) xor $not){
            return false;
        }

        if($query){
            sql_query($query, null, null, $connector);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('sql_foreignkey_exists(): Failed', $e);
    }
}



/*
 * Returns if specified function exists
 *
 * If query is specified, the query will be executed only if the specified function exists
 * If the query is prefixed with an exclamation mark ! then the query will only be executed if the function does NOT exist
 */
function sql_function_exists($name, $query = '', $database = '', $connector = ''){
    global $pdo, $_CONFIG;

    try{
        $connector = sql_connector_name($connector);

        if(!$database){
            $database = $_CONFIG['db'][$connector]['db'];
        }

        $retval = sql_get('SELECT `ROUTINE_NAME`

                           FROM   `INFORMATION_SCHEMA`.`ROUTINES`

                           WHERE  `ROUTINE_SCHEMA` = "'.cfm($database).'"
                           AND    `ROUTINE_TYPE`   = "FUNCTION"
                           AND    `ROUTINE_NAME`   = "'.cfm($name).'"', null, null, $connector);

        if(substr($query, 0, 1) == '!'){
            $not   = true;
            $query = substr($query, 1);

        }else{
            $not = false;
        }

        if(empty($retval) xor $not){
            return false;
        }

        if($query){
            sql_query($query);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('sql_function_exists(): Failed', $e);
    }
}
?>
