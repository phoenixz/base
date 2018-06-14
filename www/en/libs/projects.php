<?php
/*
 * Projects library
 *
 * This library contains funtions to work with the user projects
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Validate all project data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @param
 * @return
 */
function projects_validate($project){
    try{
        load_libs('validate,seo');

        $v = new validate_form($project, 'customers_id,name,code');

        $v->isNotEmpty ($project['name']    , tr('No projects name specified'));
        $v->hasMinChars($project['name'],  2, tr('Please ensure the project\'s name has at least 2 characters'));
        $v->hasMaxChars($project['name'], 64, tr('Please ensure the project\'s name has less than 64 characters'));
        $v->isAlphaNumeric($project['name'] , tr('Please specify a valid project name'), VALIDATE_IGNORE_ALL);

        $v->isNotEmpty ($project['code']     , tr('No projects code specified'));
        $v->hasMinChars($project['code'],   2, tr('Please ensure the project\'s code has at least 2 characters'));
        $v->hasMaxChars($project['code'],  32, tr('Please ensure the project\'s code has less than 32 characters'));
        $v->isAlphaNumeric($project['code']  , tr('Please ensure the project\'s code contains no spaces'), VALIDATE_IGNORE_UNDERSCORE);


        if(is_numeric(substr($project['name'], 0, 1))){
            $v->setError(tr('Please ensure that the project\'s name does not start with a number'));
        }

        $project['code'] = strtoupper($project['code']);

        /*
         * Validate customer
         */
        if($project['customers_id']){
            $exists = sql_get('SELECT `id` FROM `customers` WHERE `id` = :id', array(':id' => $project['customers_id']));

            if(!$exists){
                $v->setError(tr('Specified customer does not exist'));
            }

        }else{
            $project['customers_id'] = null;
        }

        /*
         * Structural validation finished, if all is okay continue to check for existence
         */
        $v->isValid();

        /*
         * Does the project name already exist?
         */
        $exists = sql_get('SELECT `id` FROM `projects` WHERE `name` = :name AND `id` != :id', true, array(':name' => $project['name'], ':id' => isset_get($project['id'])));

        if($exists){
            $v->setError(tr('The name ":name" already exists with id ":id"', array(':name' => $project['name'], ':id' => $exists)));
        }

        /*
         * Does the project code already exist?
         */
        $exists = sql_get('SELECT `id` FROM `projects` WHERE `code` = :code AND `id` != :id', true, array(':code' => $project['code'], ':id' => isset_get($project['id'])));

        if($exists){
            $v->setError(tr('The project code ":code" already exists with id ":id"', array(':code' => $project['code'], ':id' => $exists)));
        }

        $v->isValid();

        $project['seoname'] = seo_unique($project['name'], 'projects', isset_get($project['id']));

        return $project;

    }catch(Exception $e){
        throw new bException(tr('projects_validate(): Failed'), $e);
    }
}



/*
 * Return HTML for a projects select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available projects
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package projects
 *
 * @param array $params The parameters required
 * @paramkey $params name
 * @paramkey $params class
 * @paramkey $params extra
 * @paramkey $params tabindex
 * @paramkey $params empty
 * @paramkey $params none
 * @paramkey $params selected
 * @paramkey $params parents_id
 * @paramkey $params status
 * @paramkey $params orderby
 * @paramkey $params resource
 * @return string HTML for a projects select box within the specified parameters
 */
function projects_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'      , 'projects_id');
        array_default($params, 'class'     , 'form-control');
        array_default($params, 'selected'  , null);
        array_default($params, 'status'    , null);
        array_default($params, 'empty'     , tr('No projects available'));
        array_default($params, 'none'      , tr('Select a customer'));
        array_default($params, 'tabindex'  , 0);
        array_default($params, 'extra'     , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'   , '`name`');

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status']).' :status ';
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seoname`, `name` FROM `projects` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('projects_select(): Failed', $e);
    }
}



/*
 * Return data for the specified project
 *
 * This function returns information for the specified project. The project can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @project Function reference
 * @package projects
 *
 * @param mixed $project The requested project. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The project data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified project does not exist, NULL will be returned.
 */
function projects_get($project, $column = null, $status = null){
    try{
        if(is_numeric($process)){
            $where[] = ' `id` = :id ';
            $execute[':id'] = $process;

        }else{
            $where[] = ' `seoname` = :seoname ';
            $execute[':seoname'] = $process;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' :status '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `projects` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT `id`,
                                      `createdon`,
                                      `createdby`,
                                      `meta_id`,
                                      `status`,
                                      `categories_id`,
                                      `processes_id`,
                                      `steps_id`,
                                      `documents_id`,
                                      `name`,
                                      `seoname`,
                                      `code`,
                                      `api_key`,
                                      `last_login`,
                                      `description`

                               FROM   `projects` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('projects_get(): Failed', $e);
    }
}
?>
