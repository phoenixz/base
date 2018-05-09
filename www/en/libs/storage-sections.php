<?php
/*
 * Storage sections library
 *
 * This library manages storage sections, see storage library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Generate a new storage section
 */
function storage_sections_get($get_section = null, $auto_create = false){
    try{
        if(empty($get_section)){
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

        }elseif(is_numeric($get_section)){
            $where   = ' WHERE  `id` = :id
                         AND    `status` IS NULL';
            $execute = array(':id' => $get_section);

        }else{
            $where   = ' WHERE  `seoname` = :seoname
                         AND    `status`  IS NULL';
            $execute = array(':seoname' => $get_section);
        }

        $section = sql_get('SELECT `id`,
                                   `meta_id`,
                                   `status`,
                                   `name`,
                                   `seoname`,
                                   `url_template`,
                                   `random_ids`,
                                   `restrict_file_types`,
                                   `slogan`,
                                   `description`

                            FROM   `storage_sections`'.$where,

                            $execute);

        if(empty($section) and empty($get_section) and $auto_create){
            return storage_sections_add(array('status'              => '_new',
                                              'random_ids'          => true,
                                              'restrict_file_types' => true), true);
        }

        return $section;

    }catch(Exception $e){
        throw new bException('storage_sections_get(): Failed', $e);
    }
}



/*
 * Generate a new storage section
 */
function storage_sections_add($section, $new = false){
    try{
        $section = storage_sections_validate($section, $new);

        sql_query('INSERT INTO `storage_sections` (`id`, `createdby`, `meta_id`, `status`, `name`, `seoname`, `random_ids`, `restrict_file_types`, `slogan`, `description`)
                   VALUES                         (:id , :createdby , :meta_id , :status , :name , :seoname , :random_ids , :restrict_file_types , :slogan , :description )',

                   array(':id'                  => sql_random_id('storage_sections'),
                         ':createdby'           => $_SESSION['user']['id'],
                         ':meta_id'             => meta_action(),
                         ':status'              => $section['status'],
                         ':name'                => $section['name'],
                         ':seoname'             => $section['seoname'],
                         ':random_ids'          => $section['random_ids'],
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
                          `random_ids`          = :random_ids,
                          `restrict_file_types` = :restrict_file_types,
                          `slogan`              = :slogan,
                          `description`         = :description

                   WHERE  `id`                  = :id'.($new ? ' AND `status` = "_new"' : ''),

                   array(':id'                  => $section['id'],
                         ':name'                => $section['name'],
                         ':seoname'             => $section['seoname'],
                         ':random_ids'          => $section['random_ids'],
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
function storage_sections_validate($section, $new = false){
    try{
        load_libs('validate,seo');

        if($new){
            $section = array('id'                  => null,
                             'status'              => '_new',
                             'name'                => '',
                             'seoname'             => '',
                             'random_ids'          => true,
                             'restrict_file_types' => true,
                             'slogan'              => '',
                             'description'         => '');


        }else{
            $v = new validate_form($section, 'id,name,seoname,random_ids,restrict_file_types,slogan,description');
            if(!$v->isNotEmpty($section['name'], tr('Please specify a section name'))){
                $v->isAlphaNumeric($section['name'], tr('Please specify a valid alpha numeric section name (spaces, dashes and parentheses are allowed)'), VALIDATE_IGNORE_PARENTHESES|VALIDATE_IGNORE_SPACE|VALIDATE_IGNORE_DASH);
            }

            $v->isAlphaNumeric($section['slogan']                        , tr('Please specify a valid alpha numeric slogan (spaces, dashes and parentheses are allowed)'), VALIDATE_ALLOW_EMPTY_STRING|VALIDATE_IGNORE_PARENTHESES|VALIDATE_IGNORE_SPACE|VALIDATE_IGNORE_COMMA|VALIDATE_IGNORE_DOT|VALIDATE_IGNORE_DASH|VALIDATE_IGNORE_EXCLAMATIONMARK|VALIDATE_IGNORE_QUESTIONMARK|VALIDATE_IGNORE_PARENTHESES);
            $v->isRegex($section['url_template'], '/(:?[a-z0-9-_.%]\/)+/', tr('Please specify a valid URL template'), VALIDATE_ALLOW_EMPTY_STRING);
            $v->isAlphaNumeric($section['description']                   , tr('Please specify a valid alpha numeric section description (spaces, dashes, commas, dots, underscores, colons, parentheses, exclamation marks, question marks, and asterisks are allowed)'), VALIDATE_ALLOW_EMPTY_STRING|VALIDATE_IGNORE_PARENTHESES|VALIDATE_IGNORE_SPACE|VALIDATE_IGNORE_DASH|VALIDATE_IGNORE_COMMA|VALIDATE_IGNORE_DOT|VALIDATE_IGNORE_UNDERSCORE|VALIDATE_IGNORE_EXCLAMATIONMARK|VALIDATE_IGNORE_QUESTIONMARK|VALIDATE_IGNORE_ASTERISK);

            $section['random_ids']          = (boolean) $section['random_ids'];
            $section['restrict_file_types'] = (boolean) $section['restrict_file_types'];

            $v->isValid();

            $section['seoname'] = seo_unique($section['name'], 'storage_sections', $section['id']);
        }

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
