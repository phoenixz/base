<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 *
 */
function storage_files_add($document, $page, $file, $priority, $types_id){
    try{
        load_libs('files');
        $file = files_add($file);
show($file);
        sql_query(' INSERT INTO (`sections_id`, `documents_id`, `pages_id`, `types_id`, `files_id`, `priority`)
                   VALUES      (:sections_id , :documents_id , :pages_id , :types_id , :files_id , :priority )',

                   array(':sections_id'  => $document['sections_id'],
                         ':documents_id' => $document['id'],
                         ':pages_id'     => $page['id'],
                         ':types_id'     => $types_id,
                         ':files_id'     => $file['id'],
                         ':priority'     => $priority));

        $file['id'] = sql_insert_id();
        return $file;

    }catch(Exception $e){
        throw new bException('storage_file_url(): Failed', $e);
    }
}



/*
 *
 */
function storage_file_url($file, $type){
    try{
        switch($type){

        }

        return $url;

    }catch(Exception $e){
        throw new bException('storage_file_url(): Failed', $e);
    }
}
?>
