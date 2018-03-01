<?php
/*
 * Storage documents library
 *
 * This library manages storage documents, see storage library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Initialize the library
 * Auto executed by libs_load
 */
function storage_documents_library_init(){
    try{
        load_libs('storage');

    }catch(Exception $e){
        throw new bException('storage_documents_library_init(): Failed', $e);
    }
}



/*
 * Generate a new storage document
 */
function storage_documents_get($section, $document = null, $auto_create = false){
    try{
        $section = storage_ensure_section($section);

        if(empty($document)){
            /*
             * Get a _new record for the current user
             */
            if(empty($_SESSION['user']['id'])){
                $where   = ' WHERE  `sections_id` = :sections_id
                             AND    `status`      = "_new"
                             AND    `createdby`   IS NULL LIMIT 1';

                $execute = array(':sections_id' => $section['id']);

            }else{
                $where   = ' WHERE  `sections_id` = :sections_id
                             AND    `status`      = "_new"
                             AND    `createdby`   = :createdby LIMIT 1';

                $execute = array(':sections_id' => $section['id'],
                                 ':createdby'   => $_SESSION['user']['id']);
            }

        }elseif(is_numeric($document)){
            /*
             * Assume this is pages id
             */
            $where   = ' WHERE  `sections_id` = :sections_id
                         AND    `id`          = :id
                         AND    `status`      IN ("published", "unpublished", "_new")';

            $execute = array(':sections_id' => $section['id'],
                             ':id'          => $document);

        }else{
            throw new bException(tr('storage_documents_get(): Invalid document specified, is datatype ":type", should be null, numeric, string, or array', array(':type' => gettype($document))), 'invalid');
        }

        $document = sql_get('SELECT `id`,
                                    `meta_id`,
                                    `createdby`,
                                    `sections_id`,
                                    `masters_id`,
                                    `parents_id`,
                                    `rights_id`,
                                    `assigned_to_id`,
                                    `status`,
                                    `featured_until`,
                                    `category1`,
                                    `category2`,
                                    `category3`,
                                    `upvotes`,
                                    `downvotes`,
                                    `priority`,
                                    `level`,
                                    `views`,
                                    `rating`,
                                    `comments`

                             FROM   `storage_documents`

                             '.$where,

                             $execute);

        if(empty($document) and empty($document) and $auto_create){
            $document = storage_documents_add(array('status' => '_new'));
        }

        return $document;

    }catch(Exception $e){
        throw new bException('storage_documents_get(): Failed', $e);
    }
}



/*
 * Generate a new storage document
 */
function storage_documents_add($document, $section = null){
    try{
        $document = storage_documents_validate($document);

        if(!$section){
            $section = storage_sections_get($document['sections_id'], false);
        }

        if($section['random_ids']){
            $document['id'] = sql_random_id('storage_documents');
        }

        sql_query('INSERT INTO `storage_documents` (`id`, `createdby`, `meta_id`, `status`, `sections_id`, `masters_id`, `parents_id`, `rights_id`, `assigned_to_id`, `featured_until`, `category1`, `category2`, `category3`, `upvotes`, `downvotes`, `priority`, `level`, `views`, `rating`, `comments`)
                   VALUES                          (:id , :createdby , :meta_id , :status , :sections_id , :masters_id , :parents_id , :rights_id , :assigned_to_id , :featured_until , :category1 , :category2 , :category3 , :upvotes , :downvotes , :priority , :level , :views , :rating , :comments )',

                   array(':id'             => $document['id'],
                         ':meta_id'        => meta_action(),
                         ':createdby'      => $_SESSION['user']['id'],
                         ':status'         => $document['status'],
                         ':sections_id'    => $document['sections_id'],
                         ':masters_id'     => $document['masters_id'],
                         ':parents_id'     => $document['parents_id'],
                         ':rights_id'      => $document['rights_id'],
                         ':assigned_to_id' => $document['assigned_to_id'],
                         ':featured_until' => $document['featured_until'],
                         ':category1'      => $document['category1'],
                         ':category2'      => $document['category2'],
                         ':category3'      => $document['category3'],
                         ':upvotes'        => $document['upvotes'],
                         ':downvotes'      => $document['downvotes'],
                         ':priority'       => $document['priority'],
                         ':level'          => $document['level'],
                         ':views'          => $document['views'],
                         ':rating'         => $document['rating'],
                         ':comments'       => $document['comments']));

        $document['id'] = sql_insert_id();
        return $document;

    }catch(Exception $e){
        throw new bException('storage_documents_add(): Failed', $e);
    }
}



/*
 * Update the specified storage document
 */
function storage_documents_update($document, $new = false){
    try{
        $document = storage_documents_validate($document);
        meta_action($document['meta_id'], ($new ? 'create-update' : 'update'));

        sql_query('UPDATE `storage_documents`

                   SET    '.($new ? '`status` = "unpublished", ' : '').'
                          `sections_id`    = :sections_id,
                          `masters_id`     = :masters_id,
                          `parents_id`     = :parents_id,
                          `rights_id`      = :rights_id,
                          `assigned_to_id` = :assigned_to_id,
                          `featured_until` = :featured_until,
                          `category1`      = :category1,
                          `category2`      = :category2,
                          `category3`      = :category3,
                          `upvotes`        = :upvotes,
                          `downvotes`      = :downvotes,
                          `priority`       = :priority,
                          `level`          = :level,
                          `views`          = :views,
                          `rating`         = :rating,
                          `comments`       = :comments

                   WHERE  `id`             = :id'.($new ? ' AND `status` = "_new"' : ''),

                   array(':id'             => $document['id'],
                         ':sections_id'    => $document['sections_id'],
                         ':masters_id'     => $document['masters_id'],
                         ':parents_id'     => $document['parents_id'],
                         ':rights_id'      => $document['rights_id'],
                         ':assigned_to_id' => $document['assigned_to_id'],
                         ':featured_until' => $document['featured_until'],
                         ':category1'      => $document['category1'],
                         ':category2'      => $document['category2'],
                         ':category3'      => $document['category3'],
                         ':upvotes'        => $document['upvotes'],
                         ':downvotes'      => $document['downvotes'],
                         ':priority'       => $document['priority'],
                         ':level'          => $document['level'],
                         ':views'          => $document['views'],
                         ':rating'         => $document['rating'],
                         ':comments'       => $document['comments']));

        return $document;

    }catch(Exception $e){
        throw new bException('storage_documents_update(): Failed', $e);
    }
}



/*
 * Validate and return the specified storage document
 */
function storage_documents_validate($document){
    try{
        load_libs('validate');

        $v = new validate_form($document, 'id,meta_id,status,sections_id,masters_id,parents_id,rights_id,assigned_to_id,featured_until,category1,category2,category3,upvotes,downvotes,priority,level,views,rating,comments');
        $v->isNatural($document['id']             , 1, tr('Please specify a valid page id')                 , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['meta_id']        , 1, tr('Please specify a valid meta id')                 , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isStatus($document['status']             , tr('Please specify a valid status'));
        $v->isNatural($document['masters_id']     , 1, tr('Please specify a valid meta id')                 , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['parents_id']     , 1, tr('Please specify a valid parent id')               , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['rights_id']      , 1, tr('Please specify a valid rights id')               , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['assigned_to_id'] , 1, tr('Please specify a valid assigned to id')          , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isDateTime($document['featured_until']   , tr('Please specify a valid featured until date time'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['category1']      , 1, tr('Please specify a valid category 1')              , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['category2']      , 1, tr('Please specify a valid category 2')              , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['category3']      , 1, tr('Please specify a valid category 3')              , VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['upvotes']        , 1, tr('Please specify a valid amount of upvotes')       , VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['downvotes']      , 1, tr('Please specify a valid amount of upvotes')       , VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['priority']       , 1, tr('Please specify a valid priority')                , VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['level']          , 1, tr('Please specify a valid level')                   , VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['views']          , 1, tr('Please specify a valid level')                   , VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['rating']         , 1, tr('Please specify a valid rating')                  , VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['comments']       , 1, tr('Please specify a valid comments')                , VALIDATE_ALLOW_EMPTY_INTEGER);

        $v->isValid();

        return $document;

    }catch(Exception $e){
        throw new bException('storage_documents_validate(): Failed', $e);
    }
}



/*
 *
 */
function storage_document_has_access($documents_id, $users_id = null){
    try{
        if(empty($users_id)){
            $users_id = $_SESSION['user']['id'];
        }

    }catch(Exception $e){
        throw new bException('storage_document_has_access(): Failed', $e);
    }
}
?>
