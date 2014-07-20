<?php
/*
 * Backup library
 *
 * This library contains various backup related functions
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 * Backup the specified database to the specified path and or file
 */
function backup_databases($databases, $target_path){
    global $_CONFIG;

    try{
        if(!is_array($databases)){
            if(!is_string($databases)){
                throw new lsException('backup_databases(): Databases should be specified either by string or array');
            }

            $databases = explode(',', $databases);
        }

        if(file_exists($target_path)){
            if(!is_dir($target_path)){
                throw new lsException('backup_databases(): Specified target path "'.str_log($path).'" is not a directory');
            }

        }else{
            mkdir($target_path, $_CONFIG['fs']['mkdir_mode'], true);
        }

        /*
         * Start backups
         */
        foreach($databases as $database){
            if(!$database){
                throw new lsException('backup_databases(): Empty database specified', 'emptyspecified');
            }

            backup_database($databases, $target_path);
        }

    }catch(Exception $e){
        throw new lsException('backup_databases(): Failed', $e);
    }
}



/*
 * Backup the specified database to the specified path and or file
 */
function backup_database($database, $username, $password, $target_path, $target_file = '', $gzip = true){
    try{
        if(!is_string($databases)){
            throw new lsException('backup_databases(): Databases should be specified either by string or array');
        }

        if(file_exists($target_path)){
            if(!is_dir($target_path)){
                throw new lsException('backup_databases(): Specified target path "'.str_log($path).'" is not a directory');
            }

        }else{
            mkdir($target_path, $_CONFIG['fs']['mkdir_mode'], true);
        }

        $target_path = slash(realpath($target_path));

        if(!$target_file){
            /*
             * Create a target file name
             */
        }

        if(file_exists($target_path.$target_file)){
            throw new lsException('backup_databases(): Specified target file "'.str_log($path).'" already exists in target path "'.str_log($path).'"');
        }

        /*
         * Use MySQL dump to create the export
         */
        shell_exec('mysqldump -u '.$username.' -p '.$password.($gzip ? ' | gzip' : '').' > '.$target_path.$target_file);

    }catch(Exception $e){
        throw new lsException('backup_database(): Failed', $e);
    }
}
?>
