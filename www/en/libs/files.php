<?php
/*
 * Files library
 *
 * This is the generic files storage and management library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Auto executed by libs_load
 */
function files_library_init(){
    try{
        load_libs('file');
        load_config('files');

    }catch(Exception $e){
        throw new bException('files_library_init(): Failed', $e);
    }
}



/*
 * Store a file
 */
function files_add($file, $base_path = ROOT.'data/files/', $require_unique = false){
    global $_CONFIG;

    try{
        if(is_string($file)){
            $file = array('filename' => $file,
                          'original' => basename($file));

        }elseif(isset($file['name']) and isset($file['tmp_name'])){
            /*
             * This is a PHP uploaded file array. Correct file names
             */
            $file['filename'] = $file['tmp_name'];
            $file['original'] = $file['name'];
        }

        array_ensure($file, 'filename,type,status,original,meta1,meta2,description');

        /*
         * Ensure that the files base path exists
         */
        file_ensure_path($base_path);

        $extension = str_rfrom($file['filename'], '.');
        $base_path = slash($base_path);
        $target    = file_assign_target($base_path, $extension);

        if(isset($file['name']) and isset($file['tmp_name'])){
            /*
             * Move uploaded file to its final position
             */
            move_uploaded_file($file['filename'], $base_path.$target);

        }else{
            /*
             * Move the normal file to the base path position
             */
            rename($file['filename'], $base_path.$target);
        }

        /*
         * Get file mimetype data
         */
        $meta = file_mimetype($base_path.$target);

        $file['meta1'] = str_until($meta, '/');
        $file['meta2'] = str_from($meta , '/');
        $file['hash']  = hash($_CONFIG['files']['hash'], file_get_contents($base_path.$target));

        /*
         * File must be unique?
         */
        if($require_unique){
            $exists = sql_get('SELECT `id` FROM `files` WHERE `hash` = :hash', array($file['hash']));

            if($exists){
                throw new bException(tr('files_add(): Specified file ":filename" already exists with id ":id"', array(':filename' => $base_path.$target, ':id' => $exists)), 'exists');
            }
        }

        /*
         * Store and return file data
         */
        sql_query('INSERT INTO `files` (`meta_id`, `status`, `filename`, `original`, `hash`, `type`, `meta1`, `meta2`, `description`)
                   VALUES              (:meta_id , :status , :filename , :original , :hash , :type , :meta1 , :meta2 , :description )',

                   array(':meta_id'     => meta_action(),
                         ':status'      => $file['status'],
                         ':filename'    => $target,
                         ':original'    => $file['original'],
                         ':hash'        => $file['hash'],
                         ':type'        => $file['type'],
                         ':meta1'       => $file['meta1'],
                         ':meta2'       => $file['meta2'],
                         ':description' => $file['description']));

        $file['id']       = sql_insert_id();
        $file['filename'] = $target;

        return $file;

    }catch(Exception $e){
        throw new bException('files_add(): Failed', $e);
    }
}



/*
 *
 */
function files_get($file){
    try{
        if(is_numeric($file)){
            $where = ' WHERE `id` = :id AND `status` IS NULL ';
            $execute[':id'] = $file;

        }else{
            $where = ' WHERE `filename` = :filename AND `status` IS NULL  ';
            $execute[':filename'] = $file;
        }

        $files = sql_get('SELECT    `id`,
                                    `meta_id`,
                                    `status`,
                                    `filename`,
                                    `hash`,
                                    `type`,
                                    `meta1`,
                                    `meta2`,
                                    `description`

                          FROM      `files`

                          '.$where,

                          $execute);

        return $files;

    }catch(Exception $e){
        throw new bException('files_get(): Failed', $e);
    }
}



/*
 * Delete a file
 */
function files_delete($file, $base_path = ROOT.'data/files/'){
    try{
        $dbfile = files_get($file);

        if(!$dbfile){
            throw new bException(tr('files_delete(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exist');
        }

        sql_query('DELETE FROM `files` WHERE `id` = :id', array(':id' => $dbfile['id']));

        load_libs('file');
        file_delete(slash($base_path).$dbfile['filename']);

        return $dbfile;

    }catch(Exception $e){
        throw new bException('files_delete(): Failed', $e);
    }
}



/*
 * Retrieve history for specified file
 */
function files_get_history($file){
    try{
        $meta_id = sql_get('SELECT `meta_id` FROM `files` WHERE `name` = :name, `hash` = :hash', true, array(':name' => $file, ':hash' => $file));

        if(!$meta_id){
            throw new bException(rt('files_get_history(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exist');
        }

        return meta_history($meta_id);

    }catch(Exception $e){
        throw new bException('files_get(): Failed', $e);
    }
}
?>
