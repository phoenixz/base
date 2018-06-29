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
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package progress
 *
 * @return void
 */
function projects_library_init(){
    try{
        load_config('projects');

    }catch(Exception $e){
        throw new bException('projects_library_init(): Failed', $e);
    }
}



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
function projects_validate($project, $reload_only = false){
    try{
        load_libs('validate,seo');

        $v = new validate_form($project, 'seocategory,seocustomer,name,code,seoprocess,step');

        /*
         * Validate category
         */
        if($project['seocategory']){
            load_libs('categories');
            $project['categories_id'] = categories_get($project['seocategory'], 'id');

            if(!$project['categories_id']){
                $v->setError(tr('Specified category does not exist'));
            }

        }else{
            $project['categories_id'] = null;

            if(!$reload_only){
                $v->setError(tr('No category specified'));
            }
        }

        /*
         * Validate customer
         */
        if($project['seocustomer']){
            load_libs('customers');
            $project['customers_id'] = customers_get($project['seocustomer'], 'id');

            if(!$project['customers_id']){
                $v->setError(tr('Specified customer does not exist'));
            }

        }else{
            $project['customers_id'] = null;

            if(!$reload_only){
                $v->setError(tr('No customer specified'));
            }
        }

        /*
         * Validate process
         */
        if($project['seoprocess']){
            $project['processes_id'] = progress_get_process($project['seoprocess'], 'id');

            if(!$project['processes_id']){
                $v->setError(tr('The specified process does not exist'));
            }

            if($project['seostep']){
                $project['steps_id'] = progress_get_step($project['processes_id'], $project['seostep'], 'id');

                if(!$project['steps_id']){
                    $v->setError(tr('The specified step does not exist for this process'));
                }

            }else{
                /*
                 * No step specified, so it should start with the first step
                 */
                $project['steps_id'] = progress_get_step($project['processes_id'], null, 'id');
            }

        }else{
            $project['processes_id'] = null;
            $project['steps_id']     = null;
        }

        $v->isValid();

        if($reload_only){
            return $project;
        }

        /*
         * Validate name
         */
        if(!$v->isNotEmpty ($project['name'], tr('No projects name specified'))){
            $v->hasMinChars($project['name'],  2, tr('Please ensure the project\'s name has at least 2 characters'));
            $v->hasMaxChars($project['name'], 64, tr('Please ensure the project\'s name has less than 64 characters'));
            $v->isAlphaNumeric($project['name'] , tr('Please specify a valid project name'), VALIDATE_IGNORE_ALL);
        }

        $project['name'] = str_clean($project['name']);

        if($project['code']){
            $v->hasMinChars($project['code'],  2, tr('Please ensure the project\'s code has at least 2 characters'));
            $v->hasMaxChars($project['code'], 32, tr('Please ensure the project\'s code has less than 32 characters'));
            $v->isAlphaNumeric($project['code'] , tr('Please ensure the project\'s code contains no spaces'), VALIDATE_IGNORE_UNDERSCORE);

        }else{
            $project['code'] = null;
        }

        $project['code'] = str_clean($project['code']);
        $project['code'] = strtoupper($project['code']);

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

        /*
         * All is good, yay!
         */
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
        array_default($params, 'name'        , 'seoproject');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'selected'    , null);
        array_default($params, 'status'      , null);
        array_default($params, 'seocustomer' , null);
        array_default($params, 'customers_id', null);
        array_default($params, 'empty'       , tr('No projects available'));
        array_default($params, 'none'        , tr('Select a project'));
        array_default($params, 'tabindex'    , 0);
        array_default($params, 'extra'       , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'     , '`name`');

        if($params['seocustomer']){
            load_libs('customers');
            $params['customers_id'] = customers_get($params['seocustomer'], 'id');

            if(!$params['customers_id']){
                throw new bException(tr('projects_select(): The reqested customer ":customer" is not available', array(':customer' => $params['seocustomer'])), 'not-available');
            }
        }

        if($params['customers_id'] !== false){
            $where[] = ' `customers_id` '.sql_is($params['customers_id']).' :customers_id ';
            $execute[':customers_id'] = $params['customers_id'];
        }

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
        if(is_numeric($project)){
            $where[] = ' `projects`.`id` = :id ';
            $execute[':id'] = $project;

        }else{
            $where[] = ' `projects`.`seoname` = :seoname ';
            $execute[':seoname'] = $project;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `projects`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `projects` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `projects`.`id`,
                                         `projects`.`createdon`,
                                         `projects`.`createdby`,
                                         `projects`.`meta_id`,
                                         `projects`.`status`,
                                         `projects`.`categories_id`,
                                         `projects`.`customers_id`,
                                         `projects`.`processes_id`,
                                         `projects`.`steps_id`,
                                         `projects`.`documents_id`,
                                         `projects`.`name`,
                                         `projects`.`seoname`,
                                         `projects`.`code`,
                                         `projects`.`api_key`,
                                         `projects`.`last_login`,
                                         `projects`.`description`,

                                         `categories`.`name`    AS `category`,
                                         `categories`.`seoname` AS `seocategory`,

                                         `customers`.`name`    AS `customer`,
                                         `customers`.`seoname` AS `seocustomer`

                               FROM      `projects`

                               LEFT JOIN `categories`
                               ON        `categories`.`id` = `projects`.`categories_id`

                               LEFT JOIN `customers`
                               ON        `customers`.`id` = `projects`.`customers_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('projects_get(): Failed', $e);
    }
}
?>
