<?php
/*
 * New storage library
 *
 * This is the new storage library, progrenitor to the old blog library
 *
 * Tables:
 *
 * storage : This used to be the blogs table
 * storage_categories  hiarchical structure under which
 * storage_documents : These are the main documents
 * storage_pages : These are the pages from the documents containing the actual texts. All pages should have the same content, just in a different language
 * storage_comments : These are the comments made on storage_texts
 * storage_keywords : Blog documents can have multiple keywords, stored here
 * storage_key_values : Blog texts can have multiple key_value pairs. Stored here, per text
 * storage_key_values_definitions : The definitions of the available key_value pairs, per storage
 * storage_files :
 * storage_file_types :
 * storage_files : The files linked to each document. If file_types_id is NULL, then the file can be of any type. This is why this table will have its independant type, mime1, and mime2 columns
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Get specified document from storage
 */
function storage_get($storage, $seoname){
    try{
        if(is_numeric($storage)){
            $storage_id = $storage;

        }else{
            if(!is_scalar($storage)){
                throw new bException(tr('storage_get(): Specified storage ":storage" is invalid', array(':storage' => $storage)), 'invalid');
            }

            $storage_id = sql_get('SELECT `id` FROM `storage` WHERE `seoname` = :seoname AND `status` = NULL', true, array(':seoname' => $storage));

            if(!$storage_id){
                throw new bException(tr('storage_get(): Specified storage ":storage" does not exist', array(':storage' => $storage)), 'not-exist');
            }
        }

        if($seoname === null){
            /*
             * Generate a new document
             */
            sql_query('INSERT INTO `storage_documents` (`meta_id`)
                       VALUES                          ('.meta_action().')');

            $documents_id = sql_insert_id();

            sql_query('INSERT INTO `storage_documents` (`meta_id`)
                       VALUES                          ('.meta_action().')');

        }else{
            $document = sql_get('SELECT    `storage_documents`.`id`,
                                           `storage_documents`.`meta_id`,
                                           `storage_documents`.`storage_id`,
                                           `storage_documents`.`masters_id`,
                                           `storage_documents`.`parents_id`,
                                           `storage_documents`.`assigned_to_id`,
                                           `storage_documents`.`featured_until`,
                                           `storage_documents`.`category1`,
                                           `storage_documents`.`category2`,
                                           `storage_documents`.`category3`,
                                           `storage_documents`.`upvotes`,
                                           `storage_documents`.`downvotes`,
                                           `storage_documents`.`priority`,
                                           `storage_documents`.`level`,
                                           `storage_documents`.`views`,
                                           `storage_documents`.`rating`,
                                           `storage_documents`.`comments`,
                                           `storage_documents`.`status`,

                                           `storage_pages`.`name`,
                                           `storage_pages`.`seoname`,
                                           `storage_pages`.`description`,
                                           `storage_pages`.`body`

                                 FROM      `storage_pages`

                                 JOIN      `storage_documents`
                                 ON        `storage_documents`.`id` = `storage_pages`.`documents_id`

                                 WHERE     `storage_pages`.`seoname` = :seoname
                                 AND       `storage_pages`.`status`  = NULL');
        }

        return $document;

    }catch(Exception $e){
        throw new bException('storage_get(): Failed', $e);
    }
}
?>
