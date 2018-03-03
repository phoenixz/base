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
function storage_files_add($params){
    try{
        array_ensure($params);
        array_default($params, 'sections_id' , null);
        array_default($params, 'documents_id', null);
        array_default($params, 'originals_id', null);
        array_default($params, 'pages_id'    , null);
        array_default($params, 'types_id'    , null);
        array_default($params, 'file'        , null);
        array_default($params, 'priority'    , 0);
        array_default($params, 'convert'     , false);
        array_default($params, 'update_owner', false);

        load_libs('files');

        $params = storage_files_validate($params);
        $file   = $params['file'];

        if(!is_array($file)){
            $file = array('filename' => $file);
        }

        if($params['update_owner']){
            load_libs('file');
            file_chown($file['filename']);
        }

        if($params['convert']){
            load_libs('file,image');

            switch($params['convert']){
                case 'jpg':
                    // FALLTHROUGH
                case 'jpeg':
                    /*
                     * Convert to JPEG
                     */
                    image_convert($file['filename'], str_runtil($file['filename'], '.').'.jpg', array('method' => 'custom',
                                                                                                      'format' => 'jpg'));
                    file_delete($file['filename']);
                    $file['filename'] = str_runtil($file['filename'], '.').'.jpg';
                    break;

                default:
                    throw new bException(tr('storage_files_add(): Unknown convert value ":convert" specified', array(':convert' => $params['convert'])), 'unknown');
            }
        }

        $file = files_add($file);

        sql_query('INSERT INTO `storage_files` (`sections_id`, `documents_id`, `pages_id`, `types_id`, `files_id`, `originals_id`, `priority`)
                   VALUES                      (:sections_id , :documents_id , :pages_id , :types_id , :files_id , :originals_id , :priority )',

                   array(':sections_id'  => $params['sections_id'],
                         ':documents_id' => $params['documents_id'],
                         ':pages_id'     => $params['pages_id'],
                         ':types_id'     => $params['types_id'],
                         ':files_id'     => $file['id'],
                         ':originals_id' => $params['originals_id'],
                         ':priority'     => $params['priority']));

        $file['id'] = sql_insert_id();
        return $file;

    }catch(Exception $e){
        throw new bException('storage_files_add(): Failed', $e);
    }
}



/*
 *
 */
function storage_files_validate($params){
    try{
        load_libs('validate');
        $v = new validate_form($params, '');
// :TODO: Implement!
        return $params;

    }catch(Exception $e){
        throw new bException('storage_files_validate(): Failed', $e);
    }
}



/*
 *
 */
function storage_files_delete($params){
    try{
        array_ensure($params);
        array_default($params, 'sections_id' , null);
        array_default($params, 'documents_id', null);
        array_default($params, 'pages_id'    , null);
        array_default($params, 'file'        , null);
        array_default($params, 'base_path'   , ROOT.'data/files/');

        load_libs('files');

        $file = storage_files_get($params['file'], $params['documents_id'], $params['pages_id']);

        if(!$file){
            throw new bException(tr('storage_files_delete(): Specified file ":file" does not exist for S/D/P ":section/:document/:page"', array(':file' => $params['file'], ':section' => $params['sections_id'], ':document' => $params['documents_id'], ':page' => $params['pages_id'])), 'not-exist');
        }

        sql_query('DELETE FROM `storage_files` WHERE `id` = :id', array(':id' => $file['id']));

        load_libs('files');
        $file = files_delete($file['files_id'], $params['base_path']);

        return $file;

    }catch(Exception $e){
        throw new bException('storage_files_delete(): Failed', $e);
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

        $files = sql_query('SELECT    `files`.`id` AS `files_id`,
                                      `files`.`filename`,
                                      `files`.`type`,
                                      `files`.`description`,

                                      `storage_files`.`id`,
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
function storage_files_get($file, $documents_id, $pages_id = null){
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

        if(is_numeric($file)){
            $where .= ' AND `storage_files`.`id` = :id ';
            $execute[':id'] = $file;

        }else{
            $where .= ' AND `files`.`filename` = :filename ';
            $execute[':filename'] = $file;
        }

        $files = sql_get('SELECT    `files`.`id` AS `files_id`,
                                    `files`.`filename`,
                                    `files`.`type`,
                                    `files`.`description`,

                                    `storage_files`.`id`,
                                    `storage_files`.`priority`

                          FROM      `storage_files`

                          LEFT JOIN `files`
                          ON        `files`.`id` = `storage_files`.`files_id`

                          '.$where,

                          $execute);

        return $files;

    }catch(Exception $e){
        throw new bException('storage_files_get(): Failed', $e);
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
