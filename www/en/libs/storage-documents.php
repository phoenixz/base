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
 * Generate a new storage document
 */
function storage_documents_get($document = null){
    try{
        if(empty($document)){
            /*
             * Get a _new record for the current user
             */
            if(empty($_SESSION['user']['id'])){
                $where   = ' WHERE  `status` = "_new"
                             AND    `createdby` IS NULL LIMIT 1';
                $execute = null;

            }else{
                $where   = ' WHERE  `status`    = "_new"
                             AND    `createdby` = :createdby LIMIT 1';
                $execute = array(':createdby' => $_SESSION['user']['id']);
            }

        }elseif(is_numeric($document)){
            $where   = ' WHERE  `id` = :id
                         AND    `status` IS NULL';
            $execute = array(':id' => $document);

        }else{
            $where   = ' WHERE  `seoname` = :seoname
                         AND    `status` IS NULL';
            $execute = array(':seoname' => $document);
        }

        $document = sql_get('SELECT `id`,
                                    `meta_id`,
                                    `name`,
                                    `seoname`,
                                    `url_template`,
                                    `restrict_file_types`,
                                    `slogan`,
                                    `description`

                             FROM   `storage_documents`'.$where,

                             $execute);

        if(empty($document) and empty($document)){
            return storage_documents_add(array('status' => '_new'));
        }

        return $document;

    }catch(Exception $e){
        throw new bException('storage_documents_get(): Failed', $e);
    }
}



/*
 * Generate a new storage document
 */
function storage_documents_add($document){
    try{
        $document = storage_documents_validate($document);

        sql_query('INSERT INTO `storage_documents` (`createdby`, `meta_id`, `status`, `name`, `seoname`, `restrict_file_types`, `slogan`, `description`)
                   VALUES                         (:createdby , :meta_id , :status , :name , :seoname , :restrict_file_types , :slogan , :description )',

                   array(':createdby'           => $_SESSION['user']['id'],
                         ':meta_id'             => meta_action(),
                         ':status'              => $document['status'],
                         ':name'                => $document['name'],
                         ':seoname'             => $document['seoname'],
                         ':restrict_file_types' => $document['restrict_file_types'],
                         ':slogan'              => $document['slogan'],
                         ':description'         => $document['description']));

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

                   SET    `status`              = NULL,
                          `name`                = :name,
                          `seoname`             = :seoname,
                          `url_template`        = :url_template,
                          `restrict_file_types` = :restrict_file_types,
                          `slogan`              = :slogan,
                          `description`         = :description

                   WHERE  `id`                  = :id'.($new ? ' AND `status` = "_new"' : ''),

                   array(':id'                  => $document['id'],
                         ':name'                => $document['name'],
                         ':seoname'             => $document['seoname'],
                         ':restrict_file_types' => $document['restrict_file_types'],
                         ':url_template'        => $document['url_template'],
                         ':slogan'              => $document['slogan'],
                         ':description'         => $document['description']));

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
        load_libs('validate,seo');

        $v = new validate_form($document, 'id,name,seoname,restrict_file_types,slogan,description');
        $v->isAlphaNumeric($document, tr(''));
        $v->isValid();

        $document['seoname'] = seo_unique($document['name'], 'storage_documents', $document['id']);

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

    }catch(Exception $e){
        throw new bException('storage_document_has_access(): Failed', $e);
    }
}
?>
