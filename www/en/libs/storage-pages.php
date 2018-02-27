<?php
/*
 * Storage pages library
 *
 * This library manages storage pages, see storage library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Initialize the library
 * Auto executed by libs_load
 */
function storage_pages_library_init(){
    try{
        load_libs('storage');

    }catch(Exception $e){
        throw new bException('storage_pages_library_init(): Failed', $e);
    }
}



/*
 * Generate a new storage page
 */
function storage_pages_get($section, $page = null, $auto_create = false){
    try{
        $section = storage_ensure_section($section);

        if(empty($section['id'])){
            throw new bException(tr('storage_pages_get(): No sections id specified'), 'not-specified');
        }

        if(empty($page)){
            /*
             * Get a _new record for the current user
             */
            if(empty($_SESSION['user']['id'])){
                $where   = ' WHERE  `storage_pages`.`sections_id` = :sections_id
                             AND    `storage_documents`.`status`  = "_new"
                             AND    `storage_pages`.`createdby`   IS NULL LIMIT 1';

                $execute = array(':sections_id' => $section['id']);

            }else{
                $where   = ' WHERE  `storage_pages`.`sections_id` = :sections_id
                             AND    `storage_documents`.`status`  = "_new"
                             AND    `storage_pages`.`createdby`   = :createdby LIMIT 1';

                $execute = array(':sections_id' => $section['id'],
                                 ':createdby'   => $_SESSION['user']['id']);
            }

        }elseif(is_numeric($page)){
            /*
             * Assume this is pages id
             */
            $where   = ' WHERE  `storage_pages`.`sections_id` = :sections_id
                         AND    `storage_pages`.`id`          = :id
                         AND    `storage_documents`.`status`  IN ("published", "unpublished", "_new")';

            $execute = array(':sections_id' => $section['id'],
                             ':id'          => $page);

        }elseif(is_string($page)){
            /*
             * Assume this is pages seoname
             */
            $where   = ' WHERE  `storage_pages`.`sections_id` = :sections_id
                         AND    `storage_pages`.`seoname`     = :seoname
                         AND    `storage_documents`.`status`  IN ("published", "unpublished", "_new")';

            $execute = array(':sections_id' => $section['id'],
                             ':seoname'     => $page);

        }else{
            throw new bException(tr('storage_pages_get(): Invalid page specified, is datatype ":type", should be null, numeric id, or seoname string', array(':type' => gettype($page))), 'invalid');
        }

        $page = sql_get('SELECT   `storage_documents`.`id`      AS `documents_id`,
                                  `storage_documents`.`meta_id` AS `documents_meta_id`,
                                  `storage_documents`.`sections_id`,
                                  `storage_documents`.`masters_id`,
                                  `storage_documents`.`parents_id`,
                                  `storage_documents`.`rights_id`,
                                  `storage_documents`.`assigned_to_id`,
                                  `storage_documents`.`status`,
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

                                  `storage_pages`.`id`,
                                  `storage_pages`.`createdon`,
                                  `storage_pages`.`createdby`,
                                  `storage_pages`.`meta_id`,
                                  `storage_pages`.`language`,
                                  `storage_pages`.`name`,
                                  `storage_pages`.`seoname`,
                                  `storage_pages`.`description`,
                                  `storage_pages`.`body`

                         FROM      `storage_pages`

                         LEFT JOIN `storage_documents`
                         ON        `storage_documents`.`id` = `storage_pages`.`documents_id`

                         '.$where,

                         $execute);

        if(empty($page) and empty($page) and $auto_create){
            $page = storage_pages_add(array('status'       => '_new',
                                            'sections_id'  => $section['id'],
                                            'documents_id' => $page['documents_id'],
                                            'language'     => LANGUAGE));
        }

        return $page;

    }catch(Exception $e){
        throw new bException('storage_pages_get(): Failed', $e);
    }
}



/*
 * Generate a new storage page
 */
function storage_pages_add($page, $section = null){
    try{
        load_libs('storage-documents');

        if(!$section){
            $section = storage_sections_get($page['sections_id']);
        }

        if($section['random_ids']){
            $page['id'] = sql_random_id('storage_pages');
        }

        if(empty($page['documents_id'])){
            /*
             * This page has no document
             * Generate a new document for this page
             */
            $document = storage_documents_add($page, $section);

        }else{
            /*
             * Get document information for this page
             */
            $document = storage_documents_get($page['documents_id']);
        }

        $page['documents_id'] = $document['id'];
        $page                 = sql_merge($page, $document);
        $page                 = storage_pages_validate($page);

        sql_query('INSERT INTO `storage_pages` (`id`, `createdby`, `meta_id`, `sections_id`, `documents_id`, `language`, `name`, `seoname`, `description`, `body`)
                   VALUES                      (:id , :createdby , :meta_id , :sections_id , :documents_id , :language , :name , :seoname , :description , :body )',

                   array(':id'           => $page['id'],
                         ':createdby'    => $_SESSION['user']['id'],
                         ':meta_id'      => meta_action(),
                         ':sections_id'  => $page['sections_id'],
                         ':documents_id' => $page['documents_id'],
                         ':language'     => $page['language'],
                         ':name'         => $page['name'],
                         ':seoname'      => $page['seoname'],
                         ':description'  => $page['description'],
                         ':body'         => $page['body']));

        $page['id'] = sql_insert_id();
        return $page;

    }catch(Exception $e){
        throw new bException('storage_pages_add(): Failed', $e);
    }
}



/*
 * Update the specified storage page
 */
function storage_pages_update($page, $new = false){
    try{
        load_libs('storage-documents');

        $page           = storage_pages_validate($page);
        $document       = $page;
        $document['id'] = $page['documents_id'];

        storage_documents_update($document, $new);
        meta_action($page['meta_id'], ($new ? 'create-update' : 'update'));

        sql_query('UPDATE `storage_pages`

                   SET    `language`    = :language,
                          `name`        = :name,
                          `seoname`     = :seoname,
                          `description` = :description,
                          `body`        = :body

                   WHERE  `id`          = :id',

                   array(':id'          => $page['id'],
                         ':language'    => $page['language'],
                         ':name'        => $page['name'],
                         ':seoname'     => $page['seoname'],
                         ':description' => $page['description'],
                         ':body'        => $page['body']));

        return $page;

    }catch(Exception $e){
        throw new bException('storage_pages_update(): Failed', $e);
    }
}



/*
 * Validate and return the specified storage page
 */
function storage_pages_validate($page){
    try{
        load_libs('validate,seo');

        $v = new validate_form($page, 'id,createdby,meta_id,sections_id,documents_id,language,name,seoname,description,body');
        $v->isNatural($page['id']          , 1, tr('Please specify a valid page id')              , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['createdby']   , 1, tr('Please specify a valid created by id')        , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['meta_id']     , 1, tr('Please specify a valid meta id')              , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['sections_id'] , 1, tr('Please specify a valid sections id')          , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['documents_id'], 1, tr('Please specify a valid documents id')         , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isAlpha($page['language']      , tr('Please specify a valid language')                , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['name']        , 1, tr('Please specify a valid rights id')            , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['description'] , 1, tr('Please specify a valid assigned to id')       , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isDateTime($page['body']       , tr('Please specify a valid featured until date time'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isValid();

        $page['seoname'] = seo_unique($page['seoname'], 'storage_pages', $page['id']);

        return $page;

    }catch(Exception $e){
        throw new bException('storage_pages_validate(): Failed', $e);
    }
}



/*
 *
 */
function storage_page_attach_file($pages_id, $file){
    try{
        load_libs('files');

        if(!is_array($file)){
            if(!is_numeric($file)){
            }

            /*
             * Assume this is a files_id from the files library
             */

            $file = files_get($file);
        }

    }catch(Exception $e){
        throw new bException('storage_page_attach_file(): Failed', $e);
    }
}



/*
 *
 */
function storage_page_has_access($pages_id, $users_id = null){
    try{
        if(empty($users_id)){
            $users_id = $_SESSION['user']['id'];
        }

    }catch(Exception $e){
        throw new bException('storage_page_has_access(): Failed', $e);
    }
}
?>
