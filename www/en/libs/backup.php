<?php
/*
 * Backups library
 *
 * This library contains functions to make backups of databases,
 * this projects, other projects, or just random directory paths
 * and send these backups to a remote server using rsync
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Make a backup of specified database
 *
 * Use mysqldump options:
 * -p for password
 * -B for database, or -A for all databases
 * --create-options for
 * --complete-insert for
 * --comments for
 * --dump-date for  (Requires --comments!)
 * -e or --extended-insert for
 * -n or --no-create-db to not add CREATE DATABASE statements. This may help to copy database to another name
 * -K or --disable-keys to add DISABLE KEYS / ENABLE KEYS around inserts to speed up table inserts
 * -R or --routines to include stored procedures
 */
function backup_mysql($params){
    global $_CONFIG;

    try{
        load_libs('file');
        load_config('backup');

        /*
         * See config file for more documentation on the various $_CONFIG['backups']['mysql'] options
         */
        array_params($params);
        array_default($params, 'table'          , '');
        array_default($params, 'database'       , '');
        array_default($params, 'date'           , null);
        array_default($params, 'target'         , $_CONFIG['backup']['target']);
        array_default($params, 'user'           , $_CONFIG['backup']['mysql']['username']);
        array_default($params, 'pass'           , $_CONFIG['backup']['mysql']['password']);
        array_default($params, 'compression'    , $_CONFIG['backup']['mysql']['compression']);
        array_default($params, 'create_options' , $_CONFIG['backup']['mysql']['create_options']);
        array_default($params, 'complete_insert', $_CONFIG['backup']['mysql']['complete_insert']);
        array_default($params, 'comments'       , $_CONFIG['backup']['mysql']['comments']);
        array_default($params, 'dump_date'      , $_CONFIG['backup']['mysql']['dump_date']);
        array_default($params, 'routines'       , $_CONFIG['backup']['mysql']['routines']);
        array_default($params, 'disable_keys'   , $_CONFIG['backup']['mysql']['routines']);
        array_default($params, 'extended_insert', $_CONFIG['backup']['mysql']['extended_insert']);
        array_default($params, 'no_create_db'   , $_CONFIG['backup']['mysql']['no_create_db']);

        $backup = sql_init('backup', $params);

        /*
         * Dump_date only works when comments are enabled
         */
        $params['dump_date'] = ($params['comments'] and $params['dump_date']);

        /*
         * create_options only works when no_create_db is false
         */
        $params['create_options'] = ($params['create_options'] and !$params['no_create_db']);

        /*
         * Set database
         */
        if(!$params['database']){
            /*
             * Backup the core database of this project
             */
            $params['database'] = '-B '.$_CONFIG['db']['db'];
            log_console('Starting backup of core database "'.str_log($database).'"', 'complete', 'white');

            backup_mysql($params);
            log_console('Completed backup of core database "'.str_log($database).'"', 'complete', 'green');

            return $params['target'];

        }elseif($params['database'] === true){
            /*
             * Backup all databases
             * First get a list of all available databases, then back them up one by one
             */
            log_console('Starting backup of all databases', 'complete', 'white');

            foreach(sql_list('SHOW DATABASES', null, null, 'backup') as $database){
                try{
                    //if($database == 'information_schema'){
                    //    continue;
                    //}

                    $params['database'] = $database;
                    backup_mysql($params);

                }catch(Exception $e){
                    /*
                     * One database failed to backup.
                     * Register the problem and continue
                     */
showdie($e);
// :TODO: Implement
                }
            }

            return $params['target'];

        }elseif(strpos($params['database'], ',') !== false){
            /*
             * Backup multiple databases
             */
            log_console('Starting backup of multiple databases', 'complete', 'white');
            $databases = explode(',', $params['database']);

            foreach($databases as $params['database']){
                backup_mysql($params);
            }

            return $params['target'];
        }

        /*
         * Set table
         */
        if(!$params['table']){
            log_console('Starting backup of database "'.str_log($params['database']).'"', 'start', 'white');

            /*
             * Backup all tabless separately
             * First get a list of all available tables, then back them up one by one
             */
            foreach(sql_list('SHOW TABLES FROM '.cfm($params['database']), null, null, 'backup') as $table){
                try{
                    $params['table'] = $table;
                    backup_mysql($params);
                    log_console('Backed up table "'.str_log($table).'"', 'backup');

                }catch(Exception $e){
                    /*
                     * One table failed to backup.
                     * Register the problem and continue
                     */
showdie($e);
// :TODO: Implement
                }
            }

            log_console('Completed backup of database "'.str_log($params['database']).'"', 'complete', 'green');
            return $params['target'];
        }

        /*
         * Set backup date
         * Make sure target exists
         */
        if($params['date'] === null){
            /*
             * No backup date specified yet. Specify now, and build target
             */
            $params['date']   = new DateTime();
        }

        $params['target'] = slash($params['target']).$params['date']->format('Ymd-His').'/mysql/'.$params['database'].'/';
        file_ensure_path($params['target']);

        $target    = $params['target'].$params['table'].'.sql';
        $command   = 'mysqldump -p"'.$params['pass'].'" -u '.$params['user'].' --single-transaction';

        if($params['create_options']){
            $command .=  ' --create-options';
        }

        if($params['complete_insert']){
            $command .= ' --complete-insert';
        }

        if($params['comments']){
            $command .= ' --comments';

        }else{
            $command .= ' --no-comments';
        }

        if($params['dump_date']){
            $command .= ' --dump-date';
        }

        if($params['routines']){
            $command .= ' --routines';
        }

        if($params['disable_keys']){
            $command .= ' --disable-keys';
        }

        if($params['extended_insert']){
            $command .= ' --extended-insert';
        }

        if($params['no_create_db']){
            $command .= ' --no-create-db';
        }

        /*
         * Backup what database?
         */
        $command .= ' '.$params['database'];

        /*
         * Backup what table?
         */
        $command .= ' '.$params['table'];

        switch($params['compression']){
            case '';
                /*
                 * No compression
                 */
                break;

            case 'gzip':
                $command .= ' | gzip ';
                $target  .= '.gz';
                break;

            default:
                throw new bException('backup_mysql(): Unknown compression type "'.str_log($params['compression']).'" specified', 'unknown');
        }

        $command .= ' > "'.$target.'"';

        safe_exec($command);

        return $params['target'];

    }catch(Exception $e){
        throw new bException('backup_mysql(): Failed', $e);
    }
}



/*
 * Make a backup of specified path
 */
function backup_path($params){
    global $_CONFIG;

    try{
        load_libs('file');
        load_config('backup');

        /*
         * See config file for more documentation on the various $_CONFIG['backups']['mysql'] options
         */
        array_params($params);
        array_default($params, 'path'       , null);
        array_default($params, 'date'       , null);
        array_default($params, 'target'     , $_CONFIG['backup']['target']);
        array_default($params, 'sudo'       , $_CONFIG['backup']['path']['sudo']);
        array_default($params, 'compression', $_CONFIG['backup']['path']['compression']);

        if(strpos($params['path'], ',') !== false){
            log_console('Starting multi path backup', 'start', 'white');
            /*
             * Multiple backup paths were specified
             */
            $path = explode(',', $params['path']);

            foreach($path as $params['path']){
                $result = backup_path($params);
            }

            return $params['target'];
        }

        if(substr($params['path'], -1, 1) === '*'){
            $path = substr($params['path'], 0, -1);
            log_console('Starting multi path backup from "'.str_log($path).'"', 'start', 'white');

            /*
             * A path wildcard was specified
             */

            foreach(scandir($path) as $params['path']){
                if(($params['path'] == '.') or ($params['path'] == '..')){
                    continue;
                }

                $params['path'] = $path.$params['path'];

                $result = backup_path($params);
            }

            return $params['target'];
        }

        log_console('Starting backup of path "'.str_log($params['path']).'"', 'start', 'white');

        /*
         * Make sure source path exists and is a directory
         */
        if(!file_exists($params['path'])){
            throw new bException('backup_path(): The specified path "'.str_log($params['path']).'" does not exist', 'notexists');
        }

        if(!is_dir($params['path'])){
            log_console('backup_path(): The specified path "'.str_log($params['path']).'" is not a directory', 'notdirectory', 'yellow');
        }

        /*
         * Set backup date
         * Make sure target path exists
         */
        if($params['date'] === null){
            /*
             * No backup date specified yet. Specify now, and build target
             */
            $params['date']   = new DateTime();
        }

        $params['target'] = slash($params['target']).$params['date']->format('Ymd-His').'/path/';
        file_ensure_path($params['target']);

        /*
         * Need sudo?
         */
        if($params['sudo']){
            $command   = 'sudo ';

        }else{
            $command   = '';
        }

        $command   .= 'tar -c';
        $extension  = 'tar';

        switch($params['compression']){
            case 'bzip2';
                /*
                 * No compression
                 */
                $command   .= 'j';
                $extension  = 'tgz';
                break;

            case 'gzip':
                $command   .= 'z';
                $extension  = 'tgz';
                break;

            default:
                throw new bException('backup_path(): Unknown compression type "'.str_log($params['compression']).'" specified', 'unknown');
        }

        $target   = $params['target'].basename($params['path']).'.'.$extension;
        $command .= 'f '.$target.' '.$params['path'];

        safe_exec($command);

        log_console('Completed backup of path "'.str_log($params['path']).'" to "'.str_log($target).'"', 'complete', 'green');
        return $target;

    }catch(Exception $e){
        throw new bException('backup_path(): Failed', $e);
    }
}



/*
 * Backup program steps
 */
function backup_sync($params){
    try{
        array_params($params);
        array_default($params, 'date'  , null);
        array_default($params, 'target', $_CONFIG['backup']['target']);

        /*
         * Set backup date
         */
        if($params['date'] === null){
            /*
             * No backup date specified yet. Specify now, and build target
             */
            $params['date']   = new DateTime();
            $params['target'] = slash($params['target']).$params['date']->format('Ymd-His').'/mysql/';
        }

        load_libs('rsync');
        rsync($params);

        return $params['target'];

    }catch(Exception $e){
        throw new bException('backup_sync(): Failed', $e);
    }
}



/*
 * Clean up old backup files
 */
function backups_cleanup($params){
    global $_CONFIG;

    try{
        load_libs('file');
        load_config('backup');

        array_params($params);
        array_default($params, 'date'  , null);
        array_default($params, 'target', $_CONFIG['backup']['target']);
        array_default($params, 'clear' , $_CONFIG['backup']['clear']);

        /*
         * Check the backup target path, it should contain only date directories (anything else will be ignored)
         */
        if(!file_exists($params['target'])){
            throw new bException('backups_cleanup(): Target path "'.$params['target'].'" does not exist', 'notexists');
        }

        if(!is_dir($params['target'])){
            throw new bException('backups_cleanup(): Target path "'.$params['target'].'" is not a directory', 'notexists');
        }

        /*
         * Get a clean backup files list
         */
        $now     = new DateTime();
        $backups = array();
        $last    = array();
        $files   = scandir($params['target']);

        rsort($files);

        foreach($files as $file){
            if(($file == '.') or ($file == '..') or !is_dir($params['target'].$file)){
                continue;
            }

            if(!preg_match('/\d{8}-\d{6}/', $file)){
                continue;
            }

            /*
             * This is a backup path
             */
            $backups[$file] = new DateTime(substr($file, 6, 2).'-'.substr($file, 4, 2).'-'.substr($file, 0, 4).' '.substr($file, 9, 2).':'.substr($file, 11, 2).':'.substr($file, 13, 2));
        }

        foreach($backups as $file => $backup){
            /*
             * Do NOT process backup files that have future dates
             */
            $abs_diff = $backup->diff($now);

            if($abs_diff->invert){
                log_console('backup_cleanup(): Not processing "'.str_log($params['target'].$file).'", its date is in the future', 'future', 'yellow');
                continue;
            }

            if(!$last){
                /*
                 * This is the last backup made, never delete this one.
                 */
                $last = $backup;
                continue;
            }

            $rel_diff = $backup->diff($last);

            if($abs_diff->days < 7){
                /*
                 * This backup is less than a week old
                 */
                $deleted = backups_cleanup_delete($rel_diff, $params['clear'][0], $params['target'].$file);

            }elseif($abs_diff->days < 14){
                /*
                 * This backup is less than two weeks old
                 */
                $deleted = backups_cleanup_delete($rel_diff, $params['clear'][1], $params['target'].$file);

            }elseif($abs_diff->days < 31){
                /*
                 * This backup is less than a month old
                 */
                $deleted = backups_cleanup_delete($rel_diff, $params['clear'][2], $params['target'].$file);

            }else{
                /*
                 * This backup is more than a month old
                 */
                $deleted = backups_cleanup_delete($rel_diff, $params['clear'][3], $params['target'].$file);
            }

            if(!$deleted){
                $last = $backup;
            }
        }

        return $params['target'];

    }catch(Exception $e){
        throw new bException('backups_cleanup(): Failed', $e);
    }
}



/*
 *
 */
function backups_cleanup_delete($rel_diff, $clear, $file){
    try{
        if($clear){
show($rel_diff->days.' | '.$clear);
            if($rel_diff->days < $clear){
                /*
                 * The distance in days is less then allowed, we have the previous backup file for this.
                 */
                log_console('backups_cleanup_delete(): Deleting backup "'.str_log($file).'" for cleanup', 'deleting', 'yellow');
                //file_delete_tree($file);
                return true;
            }
        }

        return false;

    }catch(Exception $e){
        throw new bException('backups_cleanup_delete(): Failed', $e);
    }
}
?>
