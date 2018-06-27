<?php
/*
 * Categories library
 *
 * This is a generic categories management library
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
 * @package categories
 *
 * @return void
 */
function categories_library_init(){
    try{

    }catch(Exception $e){
        throw new bException('categories_library_init(): Failed', $e);
    }
}



/*
 * Validate the specified category
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param array $category The category to validate
 * @return array The validated and cleaned $category array
 */
function categories_validate($category){
    try{
        load_libs('validate,seo');

        $v = new validate_form($category, 'name,parent,description');

        /*
         * Validate parents_id
         */
        if(empty($category['parent'])){
            $category['parents_id'] = null;

        }else{
            $category['parents_id'] = sql_get('SELECT `id` FROM `categories` WHERE `seoname` = :seoname', true, array(':seoname' => $category['parent']));

            if(!$category['parents_id']){
                $v->setError(tr('The specified parent category does not exist'));
            }

            if($category['parents_id'] == isset_get($category['id'])){
                $v->setError(tr('The specified parent category is the category itself.'));
            }
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($category['name']    , tr('Please specify a category name'));
        $v->hasMinChars($category['name'],  2, tr('Please ensure the category name has at least 2 characters'));
        $v->hasMaxChars($category['name'], 64, tr('Please ensure the category name has less than 64 characters'));

        if(is_numeric(substr($category['name'], 0, 1))){
            $v->setError(tr('Please ensure that the category name does not start with a number'));
        }

        $v->hasMaxChars($category['name'], 64, tr('Please ensure the category name has less than 32 characters'));

        $category['name'] = str_clean($category['name']);

        /*
         * Does the category already exist within the specified parents_id?
         */
        $exists = sql_get(' SELECT `id` FROM `categories` WHERE `parents_id` '.sql_is(isset_get($category['parents_id'])).' :parents_id AND `name` = :name AND `id` '.sql_is(isset_get($category['id']), true).' :id', true, array(':name' => $category['name'], ':id' => isset_get($category['id']), ':parents_id' => isset_get($category['parents_id'])));

        if($exists){
            if($category['parent']){
                $v->setError(tr('The category name ":category" already exists in the parent category ":parent"', array(':parent' => $category['parent'], ':category' => $category['name'])));

            }else{
                $v->setError(tr('The category name ":category" already exists', array(':category' => $category['name'])));
            }
        }

        /*
         * Validate description
         */
        if(empty($category['description'])){
            $category['description'] = null;

        }else{
            $v->hasMinChars($category['description'],   16, tr('Please ensure the category description has at least 16 characters'));
            $v->hasMaxChars($category['description'], 2047, tr('Please ensure the category description has less than 2047 characters'));

            $category['description'] = str_clean($category['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $category['seoname'] = seo_unique($category['name'], 'categories', isset_get($category['id']));

      return $category;

    }catch(Exception $e){
        throw new bException('categories_validate(): Failed', $e);
    }
}



/*
 * Return HTML for a categories select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available categories
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
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
function categories_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'      , 'seocategory');
        array_default($params, 'class'     , 'form-control');
        array_default($params, 'selected'  , null);
        array_default($params, 'parent'    , null);
        array_default($params, 'parents_id', null);
        array_default($params, 'status'    , null);
        array_default($params, 'remove'    , null);
        array_default($params, 'empty'     , tr('No categories available'));
        array_default($params, 'none'      , tr('Select a category'));
        array_default($params, 'tabindex'  , 0);
        array_default($params, 'extra'     , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'   , '`name`');

        if($params['parent']){
            $params['parents_id'] = sql_get('SELECT `id` FROM `categories` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $params['parent']));

            if(!$params['parents_id']){
                /*
                 * The category apparently does not exist, auto create it
                 */
                $parent = sql_get('SELECT `id`, `status` FROM `categories` WHERE `seoname` = :seoname', array(':seoname' => $params['parent']));

                if($parent){
                    /*
                     * The category exists, but has been deleted, we cannot continue!
                     */
                    throw new bException(tr('categories_select(): The reqested parent ":parent" does exist, but is deleted', array(':parent' => $params['parent'])), 'deleted');
                }

                load_libs('seo');
                sql_query('INSERT INTO `categories` (`meta_id`, `name`, `seoname`)
                           VALUES                   (:meta_id , :name , :seoname )',

                           array(':meta_id' => meta_action(),
                                 ':name'    => $params['parent'],
                                 ':seoname' => seo_unique($params['parent'], 'categories')));

                $params['parents_id'] = sql_insert_id();
            }
        }

        $execute = array();

        if($params['remove']){
            if(count(array_force($params['remove'])) == 1){
                /*
                 * Filter out only one entry
                 */
                $where[] = ' `id` = :id ';
                $execute[':id'] = $params['remove'];

            }else{
                /*
                 * Filter out multiple entries
                 */
                $in      = sql_in(array_force($params['remove']));
                $where[] = ' `id` NOT IN ('.implode(', ', array_keys($in)).') ';
                $execute = array_merge($execute, $in);
            }
        }

        if($params['parents_id']){
            $where[] = ' `parents_id` = :parents_id ';
            $execute[':parents_id'] = $params['parents_id'];
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

        $query              = 'SELECT `seoname`, `name` FROM `categories` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('categories_select(): Failed', $e);
    }
}



/*
 * Return data for the specified category
 *
 * This function returns information for the specified category. The category can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param mixed $category The requested category. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The category data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified category does not exist, NULL will be returned.
 */
function categories_get($category, $column = null, $status = null){
    try{
        if(is_numeric($category)){
            $where[] = ' `categories`.`id` = :id ';
            $execute[':id'] = $category;

        }else{
            $where[] = ' `categories`.`seoname` = :seoname ';
            $execute[':seoname'] = $category;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `categories`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `categories` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `categories`.`id`,
                                         `categories`.`createdon`,
                                         `categories`.`createdby`,
                                         `categories`.`meta_id`,
                                         `categories`.`status`,
                                         `categories`.`parents_id`,
                                         `categories`.`name`,
                                         `categories`.`seoname`,
                                         `categories`.`description`,

                                         `parents`.`seoname` AS `parent`

                               FROM      `categories`

                               LEFT JOIN `categories` AS `parents`
                               ON        `parents`.`id` = `categories`.`parents_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('categories_get(): Failed', $e);
    }
}
?>