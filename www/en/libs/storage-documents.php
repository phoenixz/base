<?php
/*
 * Storage documents library
 *
 * This library manages storage documents, see storage library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
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
                                    `cutomers_id`,
                                    `providers_id`,
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

        sql_query('INSERT INTO `storage_documents` (`id`, `createdby`, `meta_id`, `status`, `sections_id`, `masters_id`, `parents_id`, `rights_id`, `assigned_to_id`, `customers_id`, `providers_id`, `featured_until`, `category1`, `category2`, `category3`, `upvotes`, `downvotes`, `priority`, `level`, `views`, `rating`, `comments`)
                   VALUES                          (:id , :createdby , :meta_id , :status , :sections_id , :masters_id , :parents_id , :rights_id , :assigned_to_id , :customers_id , :providers_id , :featured_until , :category1 , :category2 , :category3 , :upvotes , :downvotes , :priority , :level , :views , :rating , :comments )',

                   array(':id'             => $document['id'],
                         ':meta_id'        => meta_action(),
                         ':createdby'      => $_SESSION['user']['id'],
                         ':status'         => $document['status'],
                         ':sections_id'    => $document['sections_id'],
                         ':masters_id'     => $document['masters_id'],
                         ':parents_id'     => $document['parents_id'],
                         ':rights_id'      => $document['rights_id'],
                         ':assigned_to_id' => $document['assigned_to_id'],
                         ':customers_id'   => $document['customers_id'],
                         ':providers_id'   => $document['providers_id'],
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
                          `customers_id`   = :customers_id,
                          `providers_id`   = :providers_id,
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
                         ':customers_id'   => $document['customers_id'],
                         ':providers_id'   => $document['providers_id'],
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

        array_ensure($params, 'errors', array());
        array_default($params['errors'], 'valid_page_id'       , tr('Please specify a valid created by id number'));
        array_default($params['errors'], 'valid_meta_id'       , tr('Please specify a valid meta id number'));
        array_default($params['errors'], 'valid_sections_id'   , tr('Please specify a valid sections id number'));
        array_default($params['errors'], 'valid_status'        , tr('Please specify a valid status'));
        array_default($params['errors'], 'valid_masters_id'    , tr('Please specify a valid masters id  number'));
        array_default($params['errors'], 'valid_parents_id'    , tr('Please specify a valid parents id  number'));
        array_default($params['errors'], 'valid_rights_id'     , tr('Please specify a valid rights id number'));
        array_default($params['errors'], 'valid_assigned_to_id', tr('Please specify a valid assigned to number'));
        array_default($params['errors'], 'valid_featured_until', tr('Please specify a valid featured until date'));
        array_default($params['errors'], 'valid_category1'     , tr('Please specify a valid category 1 number'));
        array_default($params['errors'], 'valid_category2'     , tr('Please specify a valid category 2 number'));
        array_default($params['errors'], 'valid_category3'     , tr('Please specify a valid category 3 number'));
        array_default($params['errors'], 'valid_upvotes'       , tr('Please specify a valid upvotes number'));
        array_default($params['errors'], 'valid_downvotes'     , tr('Please specify a valid downvotes number'));
        array_default($params['errors'], 'valid_priority'      , tr('Please specify a valid priority'));
        array_default($params['errors'], 'valid_level'         , tr('Please specify a valid level'));
        array_default($params['errors'], 'valid_views'         , tr('Please specify a valid views number'));
        array_default($params['errors'], 'valid_rating'        , tr('Please specify a valid ratings number'));
        array_default($params['errors'], 'valid_comments'      , tr('Please specify a valid comments number'));

        $v = new validate_form($document, 'id,meta_id,status,sections_id,masters_id,parents_id,rights_id,assigned_to_id,featured_until,category1,category2,category3,upvotes,downvotes,priority,level,views,rating,comments');

        $v->isNatural($document['id'], 1, tr('Please specify a valid documents id'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['meta_id'], 1, $params['errors']['valid_meta_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($page['sections_id'], 1, $params['errors']['valid_sections_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isStatus($document['status'], $params['errors']['valid_status']);
        $v->isNatural($document['masters_id'], 1, $params['errors']['valid_masters_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['parents_id'], 1, $params['errors']['valid_parents_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['rights_id'], 1, $params['errors']['valid_rights_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['assigned_to_id'], 1, $params['errors']['valid_assigned_to_id'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isDateTime($document['featured_until'], $params['errors']['valid_featured_until'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['category1'], 1, $params['errors']['valid_category1'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['category2'], 1, $params['errors']['valid_category2'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['category3'], 1, $params['errors']['valid_category3'], VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($document['upvotes'], 1, $params['errors']['valid_upvotes'], VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['downvotes'], 1, $params['errors']['valid_downvotes'], VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['priority'], 1, $params['errors']['valid_priority'], VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['level'], 1, $params['errors']['valid_level'], VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['views'], 1, $params['errors']['valid_views'], VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['rating'], 1, $params['errors']['valid_rating'], VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($document['comments'], 1, $params['errors']['valid_comments'], VALIDATE_ALLOW_EMPTY_INTEGER);

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
