<?php
/*
 * Files library
 *
 * This is the generic files storage and management library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
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
        array_params($file, 'filename,type,status,original,meta1,meta2,description');

        /*
         * Ensure that the files base path exists
         */
        file_ensure_path($base_path);

        $base_path = slash($base_path);
        $target    = file_assign_target($base_path, );

        $file['basename'] = substr(unique_code(), 0, 24);

        if(isset($file['name']) and isset($file['tmp_name'])){
            /*
             * This is a PHP uploaded file array
             */
            $file['filename'] = $file['tmp_name'];
            $file['original'] = $file['name'];

            /*
             * Move the uploaded file to its final position
             */
            move_uploaded_file($file['filename'], $base_path.$file['basename']);
            $file['filename'] = $base_path.$file['basename'];

        }else{
            /*
             * This is a normal file already existing in the filesystem
             */
            if(substr($file, 0, strlen($base_path)) != $base_path){
                /*
                 * Move the file to the base path position
                 */
                rename($file['filename'], $base_path.$file['basename']);
                $file['filename'] = $base_path.$file['basename'];
            }
        }

        /*
         * Get file mimetype data
         */
        $meta = file_mimetype($file['filename']);

        $file['meta1'] = str_until($meta, '/');
        $file['meta2'] = str_from($meta , '/');
        $file['hash']  = hash($_CONFIG['files']['hash'], file_get_contents($file['filename']));

        /*
         * File must be unique?
         */
        if($require_unique){
            $exists = sql_get('SELECT `id` FROM `files` WHERE `hash` = :hash', array($file['hash']));

            if($exists){
                throw new bException(tr('files_add(): Specified file ":filename" already exists with id ":id"', array(':filename' => $file['filename'], ':id' => $exists)), 'exists');
            }
        }

        /*
         * Store and return file data
         */
        sql_query('INSERT INTO `files` (`meta_id`, `status`, `filename`, `original`, `hash`, `type`, `meta1`, `meta2`, `description`)
                   VALUES              (:meta_id , :status , :filename , :original , :hash , :type , :meta1 , :meta2 , :description )',

                   array(':meta_id'     => meta_action(),
                         ':status'      => $file['status'],
                         ':filename'    => $file['filename'],
                         ':original'    => $file['original'],
                         ':hash'        => $file['hash'],
                         ':type'        => $file['type'],
                         ':meta1'       => $file['meta1'],
                         ':meta2'       => $file['meta2'],
                         ':description' => $file['description']));

        $file['id'] = sql_insert_id();
        return $file;

    }catch(Exception $e){
        throw new bException('files_add(): Failed', $e);
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
