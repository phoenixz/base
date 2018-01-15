<?php
/*
 * Backups library
 *
 * This library contains functions to make backups of databases,
 * this projects, other projects, or just random directory paths
 * and send these backups to a remote server using rsync
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
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
            $params['database'] = '-B '.$_CONFIG['db']['core']['db'];
            log_console('Starting backup of core database "'.str_log($database).'"', 'white');

            backup_mysql($params);
            log_console('Completed backup of core database "'.str_log($database).'"', 'green');

            return $params['target'];

        }elseif($params['database'] === true){
            /*
             * Backup all databases
             * First get a list of all available databases, then back them up one by one
             */
            log_console('Starting backup of all databases', 'white');

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
            log_console('Starting backup of multiple databases', 'white');
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
            log_console('Starting backup of database "'.str_log($params['database']).'"', 'white');

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

            log_console('Completed backup of database "'.str_log($params['database']).'"', 'green');
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
?>
