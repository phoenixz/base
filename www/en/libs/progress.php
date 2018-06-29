<?php
/*
 * Progress library
 *
 * This library can keep track of progress in processes
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
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
function progress_library_init(){
    try{
        load_config('progress');

    }catch(Exception $e){
        throw new bException('progress_library_init(): Failed', $e);
    }
}



/*
 * Validate a process
 *
 * This function will validate all relevant fields in the specified $process array
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available categories
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package progress
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
 * @return string HTML for a categories select box within the specified parameters
 */
function progress_validate_process($process){
    try{
        load_libs('validate,seo');

        $v = new validate_form($process, 'name,steps,category,description');
        $v->isNotEmpty ($process['name']    , tr('No process name specified'));
        $v->hasMinChars($process['name'],  2, tr('Please ensure the process\'s name has at least 2 characters'));
        $v->hasMaxChars($process['name'], 64, tr('Please ensure the process\'s name has less than 64 characters'));
        $v->isAlphaNumeric($process['name'], tr('Please specify a valid process name'), VALIDATE_IGNORE_ALL);

        /*
         * Validate basic data
         */
        if($process['description']){
            $v->hasMinChars($process['description'],    8, tr('Please ensure the process\'s description has at least 8 characters'));
            $v->hasMaxChars($process['description'], 2047, tr('Please ensure the process\'s description has less than 2047 characters'));

        }else{
            $process['description'] = null;
        }

        /*
         * Validate category
         */
        if($process['category']){
            load_libs('categories');
            $process['categories_id'] = categories_get($process['category'], 'id');

            if(!$process['categories_id']){
                $v->setError(tr('Specified category does not exist'));
            }

        }else{
            $process['categories_id'] = null;
        }

        /*
         * Does the process already exist?
         */
        $exists = sql_get('SELECT `id` FROM `progress_processes` WHERE `name` = :name AND `id` != :id AND `categories_id` = :categories_id', array(':name' => $process['name'], ':categories_id' => $process['categories_id'], ':id' => isset_get($process['id'])));

        if($exists){
            $v->setError(tr('The process ":process" already exists with id ":id"', array(':process' => $process['name'], ':id' => $id)));
        }

        /*
         * Validate process steps
         */
        if(!empty($process['steps'])){
            foreach($process['steps'] as $id => &$step){
                if(!is_natural($id, 0)){
                    $v->setError(tr('Step ":id" is invalid', array(':id' => $id)));

                }else{
                    array_ensure($step, 'name,url,description');

                    if(!$step['name']){
                        /*
                         * Remove this step
                         */
                        unset($process['steps'][$id]);
                        continue;
                    }

                    /*
                     * Validate the rest of the step
                     */
                    $v->hasMinChars($step['name'],  2, tr('Please ensure the process step names have at least 2 characters'));
                    $v->hasMaxChars($step['name'], 64, tr('Please ensure the process step names have less than 64 characters'));
                    $v->isAlphaNumeric($process['name'], tr('Please specify valid process step names'), VALIDATE_IGNORE_ALL);

                    if($step['url']){
                        $v->hasMaxChars($step['url'], 255, tr('Please ensure the process step URLs have less than 255 characters'));

                        if(preg_match('/^[a-z-]+:\/\//', $step['url'])){
                            $v->isURL($step['url'], tr('Please a valid URL'));

                        }else{
                            $v->isAlphaNumeric($step['url'], tr('Please specify valid process step program'), VALIDATE_IGNORE_DASH);
                        }

                    }else{
                        $step['url'] = null;
                    }

                   if($step['description']){
                        $v->hasMinChars($step['description'],    8, tr('Please ensure the process step descriptions has at least 8 characters'));
                        $v->hasMaxChars($step['description'], 2047, tr('Please ensure the process step descriptions has less than 2047 characters'));

                    }else{
                        $step['description'] = null;
                    }

                    $v->isValid();

                    $step['seoname'] = seo_unique($step['name'], 'progress_steps', $id);
                }
            }

            unset($step);
        }

        $v->isValid();

        $process['seoname'] = seo_unique($process['name'], 'progress_processes', isset_get($process['id']));

        return $process;

    }catch(Exception $e){
        throw new bException(tr('progress_validate_process(): Failed'), $e);
    }
}



/*
 * Return data for the specified process
 *
 * This function returns information for the specified process. The process can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package progress
 *
 * @param mixed $process The requested process. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The category data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified category does not exist, NULL will be returned.
 */
function progress_get_process($process, $column = null, $status = null){
    try{
        if(is_numeric($process)){
            $where[] = ' `progress_processes`.`id` = :id ';
            $execute[':id'] = $process;

        }else{
            $where[] = ' `progress_processes`.`seoname` = :seoname ';
            $execute[':seoname'] = $process;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `progress_processes`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $process = sql_get('SELECT `'.$column.'`

                                FROM   `progress_processes`'.$where, true, $execute);

        }else{
            $process = sql_get('SELECT    `progress_processes`.`id`,
                                          `progress_processes`.`createdon`,
                                          `progress_processes`.`createdby`,
                                          `progress_processes`.`meta_id`,
                                          `progress_processes`.`status`,
                                          `progress_processes`.`categories_id`,
                                          `progress_processes`.`name`,
                                          `progress_processes`.`seoname`,
                                          `progress_processes`.`description`,

                                          `categories`.`seoname` AS `category`

                                FROM      `progress_processes`

                                LEFT JOIN `categories`
                                ON        `categories`.`id` = `progress_processes`.`categories_id` '.$where, $execute);
        }


        return $process;

    }catch(Exception $e){
        throw new bException('progress_get_process(): Failed', $e);
    }
}



/*
 * Return data for the specified process step
 *
 * This function returns information for the specified process. The process can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column

 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package progress
 *
 * @param mixed $process The requested process. Can either be specified by id (natural number) or string (seoname)
 * @param mixed $step The requested process step. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The category data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified category does not exist, NULL will be returned.
 */
function progress_get_step($processes_id, $step, $column = null, $status = null){
    try{
        $where[] = ' `progress_steps`.`processes_id` = :processes_id ';
        $execute[':processes_id'] = $processes_id;

        if($step){
            if(is_numeric($step)){
                $where[] = ' `progress_steps`.`id` = :id ';
                $execute[':id'] = $step;

            }else{
                $where[] = ' `progress_steps`.`seoname` = :seoname ';
                $execute[':seoname'] = $step;
            }

        }else{
            $where[] = ' `progress_steps`.`parents_id` IS NULL';
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `progress_steps`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where);

        if($column){
            $process = sql_get('SELECT `'.$column.'`

                                FROM   `progress_steps`'.$where, true, $execute);

        }else{
            $process = sql_get('SELECT `id`,
                                       `createdon`,
                                       `createdby`,
                                       `meta_id`,
                                       `status`,
                                       `url`,
                                       `name`,
                                       `seoname`,
                                       `description`

                                FROM   `progress_steps` '.$where, $execute);
        }


        return $process;

    }catch(Exception $e){
        throw new bException('progress_get_step(): Failed', $e);
    }
}



/*
 * Return the available process steps for the specified $processes_id
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package progress
 *
 * @param integer $processes_id
 * @return array
 */
function progress_get_steps($processes_id, $columns = null, $status = null){
    try{
        if(!$processes_id){
            return null;
        }

        if(!is_numeric($processes_id)){
            throw new bException(tr('progress_get_steps(): Invalid processes_id ":id" specified', array(':id' => $processes_id)), 'invalid');
        }

        $execute[':processes_id'] = $processes_id;
        $where[]                  = ' `progress_steps`.`processes_id` = :processes_id ';

        if($status !== false){
            $execute[':status'] = $status;
            $where[]            = ' `progress_steps`.`status` '.sql_is($status).' :status ';
        }

        $where = ' WHERE '.implode(' AND ', $where);

        if(!$columns){
            $columns = '`progress_steps`.`id`,
                        `progress_steps`.`name`,
                        `progress_steps`.`seoname`,
                        `progress_steps`.`url`,
                        `progress_steps`.`description`,

                        `users`.`name`     AS `user_name`,
                        `users`.`email`    AS `user_email`,
                        `users`.`username` AS `user_username`';
        }

        $steps = sql_list('SELECT    '.$columns.'

                           FROM      `progress_steps`

                           LEFT JOIN `users`
                           ON        `users`.`id` = `progress_steps`.`createdby`

                           '.$where.'

                           ORDER BY
                               CASE WHEN `progress_steps`.`parents_id` IS NULL THEN CAST(`progress_steps`.`id` AS CHAR)
                               ELSE CONCAT(CAST(`progress_steps`.`parents_id` AS CHAR), "-", CAST(`progress_steps`.`id` AS CHAR)) END',

                           $execute);

        return $steps;

    }catch(Exception $e){
        throw new bException('progress_get_steps(): Failed', $e);
    }
}



/*
 * Update the steps for the specified processes_id
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @param integer $processes_id The ID of the process to which the specified steps are linked
 * @param array $steps
 * @return
 */
function progress_update_steps($processes_id, $steps){
    try{
        $insert = sql_prepare('INSERT INTO `progress_steps` (`createdby`, `meta_id`, `processes_id`, `parents_id`, `name`, `seoname`, `url`, `description`)
                               VALUES                       (:createdby , :meta_id , :processes_id , :parents_id , :name , :seoname , :url , :description )');

        sql_query('DELETE FROM `progress_steps`WHERE `processes_id` = :processes_id', array(':processes_id' => $processes_id));

        foreach($steps as $id => $step){
            $insert ->execute(array(':createdby'    => isset_get($_SESSION['user']['id']),
                                    ':meta_id'      => meta_action(),
                                    ':processes_id' => $processes_id,
                                    ':parents_id'   => isset_get($prev_id),
                                    ':name'         => $step['name'],
                                    ':seoname'      => $step['seoname'],
                                    ':url'          => $step['url'],
                                    ':description'  => $step['description']));

            $prev_id = sql_insert_id();
        }

        return count($steps);

    }catch(Exception $e){
        throw new bException('progress_update(): Failed', $e);
    }
}



/*
 * ...
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
function progress_next($processes_id){
    try{

    }catch(Exception $e){
        throw new bException('progress_next(): Failed', $e);
    }
}



/*
 * Return HTML for a progress_process select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available progress processes
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package progress_process
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
 * @return string HTML for a progress process select box within the specified parameters
 */
function progress_processes_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'         , 'process');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'categories_id', null);
        array_default($params, 'status'       , null);
        array_default($params, 'empty'        , tr('No processes available'));
        array_default($params, 'none'         , tr('Select a process'));
        array_default($params, 'tabindex'     , 0);
        array_default($params, 'extra'        , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'      , '`name`');

        if($params['categories_id']){
            $where[] = ' `categories_id` = :categories_id ';
            $execute[':categories_id'] = $params['categories_id'];
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

        $query              = 'SELECT `seoname`, `name` FROM `progress_processes` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('progress_processes_select(): Failed', $e);
    }
}



/*
 * Return HTML for a progress_step select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available progress processes
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package progress_step
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
 * @return string HTML for a progress process select box within the specified parameters
 */
function progress_steps_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'        , 'step');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'selected'    , null);
        array_default($params, 'processes_id', null);
        array_default($params, 'status'      , null);
        array_default($params, 'empty'       , tr('No steps available'));
        array_default($params, 'none'        , '');
        array_default($params, 'tabindex'    , 0);
        array_default($params, 'extra'       , 'tabindex="'.$params['tabindex'].'"');

        $params['resource'] = progress_get_steps($params['processes_id'], '`progress_steps`.`id`, `progress_steps`.`name`');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('progress_steps_select(): Failed', $e);
    }
}



/*
 * Execute the current step for this project
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @customer Function reference
 * @package progress_step
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
 * @return string HTML for a progress process select box within the specified parameters
 */
function progress_exec_step($project){
    try{
        $step_data = progress_get_step($project['processes_id'], $project['steps_id']);

        if(!$step_data){
            throw new bException(tr('progress_redirect_to_step(): Specified step ":step" for progress ":process" in project ":project" does not exist', array(':project' => $project['id'], ':step' => $project['steps_id'], ':process' => $project['processes_id'])), 'not-exist');
        }

        if(preg_match('/^[a-z-]+:\/\//', $step_data['url'])){
            redirect($step_data['url']);
        }

        $step_data['url'] = 'projects/'.$project['seoname'].'/'.$step_data['url'];
        page_show($step_data['url'], array('project' => $project));

    }catch(Exception $e){
        throw new bException('progress_exec_step(): Failed', $e);
    }
}
?>
