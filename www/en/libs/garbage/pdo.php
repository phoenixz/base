<?php
/*
 * PDO library
 *
 * This library contains various PDO functions
 *
 * Written and Copyright by Sven Oostenbrink
 */

/*
 * Execute $pdo query
 */
function pdo_query($query,$data_array) {
    global $pdo;
    try{
        $r = $pdo->prepare($query);

        $r->execute($data_array);

        if(!$r->rowCount()){
            return false;
        }

        return $r;

    }catch(Exception $e){
        throw new bException('pdo_query(): Failed', $e);
    }
}

/*
 * Return one row
 */
function pdo_fetch_assoc($r) {
    global $pdo;

    try{
    
        return $r->fetch(PDO::FETCH_ASSOC);

    }catch(Exception $e){
        throw new bException('pdo_fetch_assoc(): Failed', $e);
    }
}

/*
 * Returns if specified index exists
 */
function pdo_index_exists($table, $index, $query = ''){
    global $pdo;

    try{
        $r = $pdo->prepare('SHOW INDEX FROM `'.cfm($table).'` WHERE `Column_name` = :index');

        $r->execute(array(':index' => $index));

        if(!$r->rowCount()){
            return false;
        }

        $r = $r->fetch(PDO::FETCH_ASSOC);

        if($query){
            $pdo->query($query);
        }

        return $r['Key_name'];

    }catch(Exception $e){
        throw new bException('pdo_index_exists(): Failed', $e);
    }
}



/*
 * Returns if specified column exists
 */
function pdo_column_exists($table, $column, $query = ''){
    global $pdo;

    try{
        $r = $pdo->prepare('SHOW COLUMNS FROM `'.cfm($table).'` WHERE `Field` = :column');

        $r->execute(array(':column' => $column));

        if(!$r->rowCount()){
            return false;
        }

        $r = $r->fetch(PDO::FETCH_ASSOC);

        if($query){
            $pdo->query($query);
        }

        return $r['Type'];

    }catch(Exception $e){
        throw new bException('pdo_column_exists(): Failed', $e);
    }
}



/*
 * Returns if specified foreign key exists
 */
function pdo_foreignkey_exists($table, $foreign_key, $query = '', $database = ''){
    global $pdo, $_CONFIG;

    try{
        if(!$database){
            $database = $_CONFIG['db']['db'];
        }

        $r = $pdo->prepare($q='SELECT * FROM `information_schema`.`TABLE_CONSTRAINTS` WHERE `CONSTRAINT_TYPE` = "FOREIGN KEY" AND CONSTRAINT_SCHEMA = :database AND TABLE_SCHEMA = :table AND CONSTRAINT_NAME = :foreign_key');

        $r->execute(array(':database'    => $database,
                          ':table'       => $table,
                          ':foreign_key' => $foreign_key));

        if(!$r->rowCount()){
            return false;
        }

        $r = $r->fetch(PDO::FETCH_ASSOC);

        if($query){
            $pdo->query($query);
        }

        return $foreign_key;

    }catch(Exception $e){
        throw new bException('pdo_foreignkey_exists(): Failed', $e);
    }
}
?>
