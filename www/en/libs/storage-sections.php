<?php
/*
 * Storage sections library
 *
 * This library manages storage sections, see storage library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Generate a new storage section
 */
function storage_sections_get($section = null){
    try{
        if(empty($section)){
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

        }elseif(is_numeric($section)){
            $where   = ' WHERE  `id` = :id
                         AND    `status` IS NULL';
            $execute = array(':id' => $section);

        }else{
            $where   = ' WHERE  `seoname` = :seoname
                         AND    `status` IS NULL';
            $execute = array(':seoname' => $section);
        }

        $section = sql_get('SELECT `id`,
                                   `meta_id`,
                                   `name`,
                                   `seoname`,
                                   `url_template`,
                                   `restrict_file_types`,
                                   `slogan`,
                                   `description`

                            FROM   `storage_sections`'.$where,

                            $execute);

        if(empty($section) and empty($section)){
            return storage_sections_add(array('status' => '_new'));
        }

        return $section;

    }catch(Exception $e){
        throw new bException('storage_sections_get(): Failed', $e);
    }
}



/*
 * Generate a new storage section
 */
function storage_sections_add($section){
    try{
        $section = storage_sections_validate($section);

        sql_query('INSERT INTO `storage_sections` (`createdby`, `meta_id`, `status`, `name`, `seoname`, `restrict_file_types`, `slogan`, `description`)
                   VALUES                         (:createdby , :meta_id , :status , :name , :seoname , :restrict_file_types , :slogan , :description )',

                   array(':createdby'           => $_SESSION['user']['id'],
                         ':meta_id'             => meta_action(),
                         ':status'              => $section['status'],
                         ':name'                => $section['name'],
                         ':seoname'             => $section['seoname'],
                         ':restrict_file_types' => $section['restrict_file_types'],
                         ':slogan'              => $section['slogan'],
                         ':description'         => $section['description']));

        return $section;

    }catch(Exception $e){
        throw new bException('storage_sections_add(): Failed', $e);
    }
}



/*
 * Update the specified storage section
 */
function storage_sections_update($section, $new = false){
    try{
        $section = storage_sections_validate($section);
        meta_action($section['meta_id'], ($new ? 'create-update' : 'update'));

        sql_query('UPDATE `storage_sections`

                   SET    `status`              = NULL,
                          `name`                = :name,
                          `seoname`             = :seoname,
                          `url_template`        = :url_template,
                          `restrict_file_types` = :restrict_file_types,
                          `slogan`              = :slogan,
                          `description`         = :description

                   WHERE  `id`                  = :id'.($new ? ' AND `status` = "_new"' : ''),

                   array(':id'                  => $section['id'],
                         ':name'                => $section['name'],
                         ':seoname'             => $section['seoname'],
                         ':restrict_file_types' => $section['restrict_file_types'],
                         ':url_template'        => $section['url_template'],
                         ':slogan'              => $section['slogan'],
                         ':description'         => $section['description']));

        return $section;

    }catch(Exception $e){
        throw new bException('storage_sections_update(): Failed', $e);
    }
}



/*
 * Validate and return the specified storage section
 */
function storage_sections_validate($section){
    try{
        load_libs('validate,seo');

        $v = new validate_form($section, 'id,name,seoname,restrict_file_types,slogan,description');
        $v->isAlphaNumeric($section, tr(''));
        $v->isValid();

        $section['seoname'] = seo_unique($section['name'], 'storage_sections', $section['id']);

        return $section;

    }catch(Exception $e){
        throw new bException('storage_sections_validate(): Failed', $e);
    }
}



/*
 *
 */
function storage_section_has_access($sections_id, $users_id = null){
    try{

    }catch(Exception $e){
        throw new bException('storage_section_has_access(): Failed', $e);
    }
}
?>
