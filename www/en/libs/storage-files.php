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
function storage_files_add($page, $file, $types_id, $priority = null){
    try{
        load_libs('files');
        $file = files_add($file);

        sql_query('INSERT INTO `storage_files` (`sections_id`, `documents_id`, `pages_id`, `types_id`, `files_id`, `priority`)
                   VALUES                      (:sections_id , :documents_id , :pages_id , :types_id , :files_id , :priority )',

                   array(':sections_id'  => $page['sections_id'],
                         ':documents_id' => $page['documents_id'],
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
function storage_files_query($documents_id, $pages_id = null){
    try{
        if($pages_id){
            /*
             * Get files linked to this page only
             */
            $where   = ' WHERE `storage_files`.`documents_id` = :documents_id AND `storage_files`.`documents_id` = :documents_id';

            $execute = array(':documents_id' => $documents_id);

        }else{
            /*
             * Get files linked all pages for this document
             */
            $where   = ' WHERE    (`storage_files`.`documents_id` = :documents_id AND `storage_files`.`pages_id` IS NULL)
                         OR       (`storage_files`.`documents_id` = :documents_id AND `storage_files`.`pages_id` = :pages_id)';

            $execute = array(':documents_id' => $documents_id,
                             ':pages_id'     => $pages_id);
        }

        $files = sql_query('SELECT    `files`.`id`,
                                      `files`.`filename`,
                                      `files`.`type`,
                                      `files`.`description`,

                                      `storage_files`.`priority`

                            FROM      `storage_files`

                            LEFT JOIN `files`
                            ON        `files`.`id` = `storage_files`.`files_id`

                            '.$where.'

                            ORDER BY  `priority` DESC',

                            $execute);

        return $files;

    }catch(Exception $e){
        throw new bException('storage_files_query(): Failed', $e);
    }
}



/*
 *
 */
function storage_file_url($file, $type){
    try{
        return domain('/files/'.$file['filename']);

    }catch(Exception $e){
        throw new bException('storage_file_url(): Failed', $e);
    }
}
?>
