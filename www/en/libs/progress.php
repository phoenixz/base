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
                        $v->isURL($step['url'], tr('Please a valid URL'));

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
function progress_get_steps($processes_id){
    try{
        $steps = sql_list('SELECT    `progress_steps`.`id`,
                                     `progress_steps`.`name`,
                                     `progress_steps`.`seoname`,
                                     `progress_steps`.`url`,
                                     `progress_steps`.`description`,

                                     `users`.`name`     AS `user_name`,
                                     `users`.`email`    AS `user_email`,
                                     `users`.`username` AS `user_username`

                           FROM      `progress_steps`

                           LEFT JOIN `users`
                           ON        `users`.`id` = `progress_steps`.`createdby`

                           WHERE     `progress_steps`.`processes_id` = :processes_id
                           AND       `progress_steps`.`status` IS NULL

                           ORDER BY
                               CASE WHEN `progress_steps`.`parents_id` IS NULL THEN CAST(`progress_steps`.`id` AS CHAR)
                               ELSE CONCAT(CAST(`progress_steps`.`parents_id` AS CHAR), "-", CAST(`progress_steps`.`id` AS CHAR)) END',

                           array(':processes_id' => $processes_id));

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
function progress_process_select($params){
    try{

    }catch(Exception $e){
        throw new bException('progress_process_select(): Failed', $e);
    }
}
?>
