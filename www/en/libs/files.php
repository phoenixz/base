<?php
/*
 * Files library
 *
 * This is the generic files storage and management library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_libs('file');
load_config('files');



/*
 * Store a file
 */
function files_put($file){
    try{
        array_params($file);
        array_default($file, 'type'       , null);
        array_default($file, 'status'     , null);
        array_default($file, 'original'   , null);
        array_default($file, 'description', null);

        $meta = file_mimetype($file['filename']);

        $file['meta1']   = str_until($meta, '/');
        $file['meta2']   = str_from($meta, '/');
        $file['meta_id'] = meta_action();

        if($file['hash']){
            $file['hash'] = hash($_CONFIG['files']['hash'], file_get_contents($file['filename']));
        }

        sql_query('INSERT INTO `files` (`meta_id`, `status`, `filename`, `original`, `hash`, `type`, `meta1`, `meta2`, `description`)
                   VALUES              (:meta_id , :status , :filename , :original , :hash , :type , :meta1 , :meta2 , :description )',

                   array(':meta_id'     => $file['meta_id'],
                         ':status'      => $file['status'],
                         ':filename'    => $file['filename'],
                         ':original'    => $file['original'],
                         ':hash'        => $file['hash'],
                         ':type'        => $file['type'],
                         ':meta1'       => $file['meta1'],
                         ':meta2'       => $file['meta2'],
                         ':description' => $file['descripition']));

        return $file;

    }catch(Exception $e){
        throw new bException('files_put(): Failed', $e);
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
