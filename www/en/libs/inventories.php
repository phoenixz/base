<?php
/*
 * inventories library
 *
 * This library contains functions for the inventory system
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
 * @package inventories
 *
 * @return void
 */
function inventories_library_init(){
    try{
        load_config('inventories');

    }catch(Exception $e){
        throw new bException('inventories_library_init(): Failed', $e);
    }
}



/*
 * Validate the specified company
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $company The company to validate
 * @return array The validated and cleaned $company array
 */
function inventories_validate($company){
    try{
        load_libs('validate,seo');

        $v = new validate_form($company, 'name,seocategory,description');

        /*
         * Validate category
         */
        if($company['seocategory']){
            load_libs('categories');
            $company['categories_id'] = categories_get($company['seocategory'], 'id');

            if(!$company['categories_id']){
                $v->setError(tr('Specified category does not exist'));
            }

        }else{
            $company['categories_id'] = null;
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($company['name']    , tr('Please specify a company name'));
        $v->hasMinChars($company['name'],  2, tr('Please ensure the company name has at least 2 characters'));
        $v->hasMaxChars($company['name'], 64, tr('Please ensure the company name has less than 64 characters'));

        if(is_numeric(substr($company['name'], 0, 1))){
            $v->setError(tr('Please ensure that the company name does not start with a number'));
        }

        $v->hasMaxChars($company['name'], 64, tr('Please ensure the company name has less than 64 characters'));

        $company['name'] = str_clean($company['name']);

        /*
         * Does the company already exist within the specified categories_id?
         */
        $exists = sql_get('SELECT `id` FROM `companies` WHERE `categories_id` '.sql_is(isset_get($company['categories_id'])).' :categories_id AND `name` = :name AND `id` '.sql_is(isset_get($company['id']), true).' :id', true, array(':name' => $company['name'], ':id' => isset_get($company['id']), ':categories_id' => isset_get($company['categories_id'])));

        if($exists){
            if($company['category']){
                $v->setError(tr('The company name ":company" already exists in the category company ":category"', array(':category' => $company['category'], ':company' => $company['name'])));

            }else{
                $v->setError(tr('The company name ":company" already exists', array(':company' => $company['name'])));
            }
        }

        /*
         * Validate description
         */
        if(empty($company['description'])){
            $company['description'] = null;

        }else{
            $v->hasMinChars($company['description'],   16, tr('Please ensure the company description has at least 16 characters'));
            $v->hasMaxChars($company['description'], 2047, tr('Please ensure the company description has less than 2047 characters'));

            $company['description'] = str_clean($company['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $company['seoname'] = seo_unique($company['name'], 'companies', isset_get($company['id']));

      return $company;

    }catch(Exception $e){
        throw new bException('inventories_validate(): Failed', $e);
    }
}



/*
 * Return HTML for a companies select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available companies
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $params The parameters required
 * @paramkey $params name
 * @paramkey $params class
 * @paramkey $params extra
 * @paramkey $params tabindex
 * @paramkey $params empty
 * @paramkey $params none
 * @paramkey $params selected
 * @paramkey $params categories_id
 * @paramkey $params status
 * @paramkey $params orderby
 * @paramkey $params resource
 * @return string HTML for a companies select box within the specified parameters
 */
function inventories_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'         , 'seocompany');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'category'     , null);
        array_default($params, 'categories_id', null);
        array_default($params, 'status'       , null);
        array_default($params, 'remove'       , null);
        array_default($params, 'empty'        , tr('No companies available'));
        array_default($params, 'none'         , tr('Select a company'));
        array_default($params, 'tabindex'     , 0);
        array_default($params, 'extra'        , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'      , '`name`');

        if($params['category']){
            load_libs('categories');
            $params['categories_id'] = categories_get($params['category'], 'id');

            if(!$params['categories_id']){
                throw new bException(tr('inventories_select(): The reqested category ":category" does exist, but is deleted', array(':category' => $params['category'])), 'deleted');
            }
        }

        $execute = array();

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

        $query              = 'SELECT `seoname`, `name` FROM `companies` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('inventories_select(): Failed', $e);
    }
}



/*
 * Return data for the specified company
 *
 * This function returns information for the specified company. The company can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param mixed $company The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function inventories_get($company, $column = null, $status = null){
    try{
        if(is_numeric($company)){
            $where[] = ' `companies`.`id` = :id ';
            $execute[':id'] = $company;

        }else{
            $where[] = ' `companies`.`seoname` = :seoname ';
            $execute[':seoname'] = $company;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `companies`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `companies` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `companies`.`id`,
                                         `companies`.`createdon`,
                                         `companies`.`createdby`,
                                         `companies`.`meta_id`,
                                         `companies`.`status`,
                                         `companies`.`categories_id`,
                                         `companies`.`name`,
                                         `companies`.`seoname`,
                                         `companies`.`description`,

                                         `categories`.`seoname` AS `category`

                               FROM      `companies`

                               LEFT JOIN `categories`
                               ON        `categories`.`id` = `companies`.`categories_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('inventories_get(): Failed', $e);
    }
}



/*
 * Validate the specified branch
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $branch The branch to validate
 * @return array The validated and cleaned $branch array
 */
function inventories_validate_items($branch, $reload_only = false){
    try{
        load_libs('validate,seo');

        $v = new validate_form($branch, 'name,seocompany,description');

        /*
         * Validate company
         */
        if($branch['seocompany']){
            $branch['inventories_id'] = inventories_get($branch['seocompany'], 'id');

            if(!$branch['inventories_id']){
                $v->setError(tr('Specified company does not exist'));
            }

        }else{
            $branch['inventories_id'] = null;
            $v->setError(tr('No company specified'));
        }

        if($reload_only){
            return $branch;
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($branch['name']    , tr('Please specify a branch name'));
        $v->hasMinChars($branch['name'],  2, tr('Please ensure the branch name has at least 2 characters'));
        $v->hasMaxChars($branch['name'], 64, tr('Please ensure the branch name has less than 64 characters'));

        if(is_numeric(substr($branch['name'], 0, 1))){
            $v->setError(tr('Please ensure that the branch name does not start with a number'));
        }

        $v->hasMaxChars($branch['name'], 64, tr('Please ensure the branch name has less than 64 characters'));

        $branch['name'] = str_clean($branch['name']);

        /*
         * Does the branch already exist within the specified inventories_id?
         */
        $exists = sql_get('SELECT `id` FROM `branches` WHERE `inventories_id` '.sql_is(isset_get($branch['inventories_id'])).' :inventories_id AND `name` = :name AND `id` '.sql_is(isset_get($branch['id']), true).' :id', true, array(':name' => $branch['name'], ':id' => isset_get($branch['id']), ':inventories_id' => isset_get($branch['inventories_id'])));

        if($exists){
            if($branch['category']){
                $v->setError(tr('The branch name ":branch" already exists in the category branch ":category"', array(':category' => $branch['category'], ':branch' => $branch['name'])));

            }else{
                $v->setError(tr('The branch name ":branch" already exists', array(':branch' => $branch['name'])));
            }
        }

        /*
         * Validate description
         */
        if(empty($branch['description'])){
            $branch['description'] = null;

        }else{
            $v->hasMinChars($branch['description'],   16, tr('Please ensure the branch description has at least 16 characters'));
            $v->hasMaxChars($branch['description'], 2047, tr('Please ensure the branch description has less than 2047 characters'));

            $branch['description'] = str_clean($branch['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $branch['seoname'] = seo_unique($branch['name'], 'branches', isset_get($branch['id']));

      return $branch;

    }catch(Exception $e){
        throw new bException('inventories_validate_items(): Failed', $e);
    }
}



/*
 * Return HTML for a companies select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available companies
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $params The parameters required
 * @paramkey $params name
 * @paramkey $params class
 * @paramkey $params extra
 * @paramkey $params tabindex
 * @paramkey $params empty
 * @paramkey $params none
 * @paramkey $params selected
 * @paramkey $params categories_id
 * @paramkey $params status
 * @paramkey $params orderby
 * @paramkey $params resource
 * @return string HTML for a companies select box within the specified parameters
 */
function inventories_select_items($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'        , 'seobranch');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'seocompany'  , null);
        array_default($params, 'selected'    , null);
        array_default($params, 'category'    , null);
        array_default($params, 'inventories_id', null);
        array_default($params, 'status'      , null);
        array_default($params, 'remove'      , null);
        array_default($params, 'empty'       , tr('No branches available'));
        array_default($params, 'none'        , tr('Select a branch'));
        array_default($params, 'tabindex'    , 0);
        array_default($params, 'extra'       , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'     , '`name`');

        if($params['seocompany']){
            $params['inventories_id'] = inventories_get($params['seocompany'], 'id');

            if(!$params['inventories_id']){
                throw new bException(tr('inventories_select_items(): The specified company ":company" does not exist or is not available', array(':company' => $params['company'])), 'not-exist');
            }
        }

        $execute = array();

        /*
         * Only show branches per office
         */
        if($params['inventories_id']){
            $where[] = ' `inventories_id` = :inventories_id ';
            $execute[':inventories_id'] = $params['inventories_id'];

            if($params['status'] !== false){
                $where[] = ' `status` '.sql_is($params['status']).' :status ';
                $execute[':status'] = $params['status'];
            }

            if(empty($where)){
                $where = '';

            }else{
                $where = ' WHERE '.implode(' AND ', $where).' ';
            }

            $query              = 'SELECT `seoname`, `name` FROM `branches` '.$where.' ORDER BY `name`';
            $params['resource'] = sql_query($query, $execute);
        }

        $retval = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('inventories_select_items(): Failed', $e);
    }
}



/*
 * Return data for the specified company
 *
 * This function returns information for the specified company. The company can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param mixed $branch The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function inventories_get_items($company, $branch, $column = null, $status = null){
    try{
        /*
         * Filter by specified company
         */
        if(!is_numeric($company)){
            $inventories_id = inventories_get($company, 'id');

            if(!$inventories_id){
                throw new bException(tr('inventories_get_items(): Specified company ":company" does not exist', array(':company' => $company)), 'not-exist');
            }

        }else{
            $inventories_id = $company;
        }

        $where[] = ' `branches`.`inventories_id` = :inventories_id ';
        $execute[':inventories_id'] = $inventories_id;

        /*
         * Filter by specified branch
         */
        if(is_numeric($branch)){
            $where[] = ' `branches`.`id` = :id ';
            $execute[':id'] = $branch;

        }else{
            $where[] = ' `branches`.`seoname` = :seoname ';
            $execute[':seoname'] = $branch;
        }

        /*
         * Filter by specified status
         */
        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `branches`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `branches`.`'.$column.'`

                               FROM   `branches`

                               JOIN   `companies`
                               ON     `companies`.`id` = `branches`.`inventories_id`
                               AND    `companies`.`status` IS NULL '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT  `branches`.`id`,
                                       `branches`.`createdon`,
                                       `branches`.`createdby`,
                                       `branches`.`meta_id`,
                                       `branches`.`status`,
                                       `branches`.`inventories_id`,
                                       `branches`.`name`,
                                       `branches`.`seoname`,
                                       `branches`.`description`,

                                       `companies`.`name`    AS `company`,
                                       `companies`.`seoname` AS `seocompany`

                               FROM    `branches`

                               JOIN    `companies`
                               ON      `companies`.`id` = `branches`.`inventories_id`
                               AND     `companies`.`status` IS NULL '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('inventories_get_items(): Failed', $e);
    }
}



/*
 * Validate the specified inventory item
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param
 * @return
 */
function inventories_validate_item($item){
    try{
        return $item;

    }catch(Exception $e){
        throw new bException('inventories_validate_item(): Failed', $e);
    }
}
?>
t