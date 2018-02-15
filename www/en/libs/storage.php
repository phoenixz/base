<?php
/*
 * New storage library
 *
 * This is the new storage library, progrenitor to the old blog library
 *
 * Tables:
 *
 * storage : This used to be the blogs table
 * libraries_categories  hiarchical structure under which
 * libraries_documents : These are the main documents
 * libraries_pages : These are the pages from the documents containing the actual texts. All pages should have the same content, just in a different language
 * libraries_comments : These are the comments made on libraries_texts
 * libraries_keywords : Blog documents can have multiple keywords, stored here
 * libraries_key_values : Blog texts can have multiple key_value pairs. Stored here, per text
 * libraries_key_values_definitions : The definitions of the available key_value pairs, per storage
 * libraries_files :
 * libraries_file_types :
 * libraries_files : The files linked to each document. If file_types_id is NULL, then the file can be of any type. This is why this table will have its independant type, mime1, and mime2 columns
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Get specified document from storage
 */
function libraries_get_document($storage, $seoname){
    try{
        if(is_numeric($storage)){
            $libraries_id = $storage;

        }else{
            if(!is_scalar($storage)){
                throw new bException(tr('libraries_get_document(): Specified storage ":storage" is invalid', array(':storage' => $storage)), 'invalid');
            }

            $libraries_id = sql_get('SELECT `id` FROM `storage` WHERE `seoname` = :seoname AND `status` = NULL', true, array(':seoname' => $storage));

            if(!$libraries_id){
                throw new bException(tr('libraries_get_document(): Specified storage ":storage" does not exist', array(':storage' => $storage)), 'not-exist');
            }
        }

        if($seoname === null){
            /*
             * Generate a new document
             */
            sql_query('INSERT INTO `libraries_documents` (`meta_id`)
                       VALUES                            ('.meta_action().')');

            $documents_id = sql_insert_id();

            sql_query('INSERT INTO `libraries_documents` (`meta_id`)
                       VALUES                            ('.meta_action().')');

        }else{
            $document = sql_get('SELECT    `libraries_documents`.`id`,
                                           `libraries_documents`.`meta_id`,
                                           `libraries_documents`.`libraries_id`,
                                           `libraries_documents`.`masters_id`,
                                           `libraries_documents`.`parents_id`,
                                           `libraries_documents`.`assigned_to_id`,
                                           `libraries_documents`.`featured_until`,
                                           `libraries_documents`.`category1`,
                                           `libraries_documents`.`category2`,
                                           `libraries_documents`.`category3`,
                                           `libraries_documents`.`upvotes`,
                                           `libraries_documents`.`downvotes`,
                                           `libraries_documents`.`priority`,
                                           `libraries_documents`.`level`,
                                           `libraries_documents`.`views`,
                                           `libraries_documents`.`rating`,
                                           `libraries_documents`.`comments`,
                                           `libraries_documents`.`status`,

                                           `libraries_pages`.`name`,
                                           `libraries_pages`.`seoname`,
                                           `libraries_pages`.`description`,
                                           `libraries_pages`.`body`

                                 FROM      `libraries_pages`

                                 JOIN      `libraries_documents`
                                 ON        `libraries_documents`.`id`  = `libraries_pages`.`documents_id`

                                 WHERE     `libraries_pages`.`seoname` = :seoname
                                 AND       `libraries_pages`.`status`  = NULL');
        }

        return $document;

    }catch(Exception $e){
        throw new bException('libraries_get_document(): Failed', $e);
    }
}
?>
