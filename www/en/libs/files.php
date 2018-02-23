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
function files_add($file, $require_unique = false){
    try{
        array_params($file);
        array_default($file, 'type'       , null);
        array_default($file, 'status'     , null);
        array_default($file, 'original'   , null);
        array_default($file, 'description', null);

        $meta = file_mimetype($file['filename']);

        $file['meta1'] = str_until($meta, '/');
        $file['meta2'] = str_from($meta , '/');

        if(!$file['hash']){
            $file['hash'] = hash($_CONFIG['files']['hash'], file_get_contents($file['filename']));
        }

        if($require_unique){
            $exists = sql_get('SELECT `id` FROM `files` WHERE `hash` = :hash', array($file['hash']));

            if($exists){
                throw new bException(tr('files_add(): Specified file ":filename" already exists with id ":id"', array(':filename' => $file['filename'], ':id' => $exists)), 'exists');
            }
        }

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
                         ':description' => $file['descripition']));

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
