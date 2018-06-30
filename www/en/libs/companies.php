<?php
/*
 * companies library
 *
 * This library contains functions for the companies management system
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
 * @package companies
 *
 * @return void
 */
function companies_library_init(){
    try{
        load_config('companies');

    }catch(Exception $e){
        throw new bException('companies_library_init(): Failed', $e);
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
function companies_validate($company){
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
            if($company['categories_id']){
                $v->setError(tr('The company name ":company" already exists in the category company ":category"', array(':category' => not_empty($company['seocategory'], $company['categories_id']), ':company' => $company['name'])));

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
        throw new bException('companies_validate(): Failed', $e);
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
function companies_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'         , 'seocompany');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'seocategory'  , null);
        array_default($params, 'categories_id', null);
        array_default($params, 'status'       , null);
        array_default($params, 'remove'       , null);
        array_default($params, 'autosubmit'   , true);
        array_default($params, 'empty'        , tr('No companies available'));
        array_default($params, 'none'         , tr('Select a company'));
        array_default($params, 'tabindex'     , 0);
        array_default($params, 'extra'        , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'      , '`name`');

        if($params['seocategory']){
            load_libs('categories');
            $params['categories_id'] = categories_get($params['seocategory'], 'id');

            if(!$params['categories_id']){
                throw new bException(tr('companies_select(): The reqested category ":category" does exist, but is deleted', array(':category' => $params['seocategory'])), 'deleted');
            }
        }

        $execute = array();

        if($params['categories_id'] !== false){
            $where[] = ' `categories_id` '.sql_is($params['categories_id']).' :categories_id ';
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
        throw new bException('companies_select(): Failed', $e);
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
function companies_get($company, $column = null, $status = null){
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

                                         `categories`.`name`    AS `category`,
                                         `categories`.`seoname` AS `seocategory`

                               FROM      `companies`

                               LEFT JOIN `categories`
                               ON        `categories`.`id` = `companies`.`categories_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('companies_get(): Failed', $e);
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
function companies_validate_branch($branch, $reload_only = false){
    try{
        load_libs('validate,seo');

        $v = new validate_form($branch, 'name,seocompany,description');

        /*
         * Validate company
         */
        if($branch['seocompany']){
            $branch['companies_id'] = companies_get($branch['seocompany'], 'id');

            if(!$branch['companies_id']){
                $v->setError(tr('Specified company does not exist'));
            }

        }else{
            $branch['companies_id'] = null;

            if(!$reload_only){
                $v->setError(tr('No company specified'));
            }
        }

        $v->isValid();

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
         * Does the branch already exist within the specified companies_id?
         */
        $exists = sql_get('SELECT `id` FROM `branches` WHERE `companies_id` '.sql_is(isset_get($branch['companies_id'])).' :companies_id AND `name` = :name AND `id` '.sql_is(isset_get($branch['id']), true).' :id', true, array(':name' => $branch['name'], ':id' => isset_get($branch['id']), ':companies_id' => isset_get($branch['companies_id'])));

        if($exists){
            $v->setError(tr('The branch name ":branch" already exists', array(':branch' => $branch['name'])));
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
        throw new bException('companies_validate_branch(): Failed', $e);
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
function companies_select_branch($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'        , 'seobranch');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'selected'    , null);
        array_default($params, 'seocompany'  , null);
        array_default($params, 'companies_id', null);
        array_default($params, 'status'      , null);
        array_default($params, 'remove'      , null);
        array_default($params, 'autosubmit'  , true);
        array_default($params, 'empty'       , tr('No branches available'));
        array_default($params, 'none'        , tr('Select a branch'));
        array_default($params, 'tabindex'    , 0);
        array_default($params, 'extra'       , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'     , '`name`');

        if($params['seocompany']){
            $params['companies_id'] = companies_get($params['seocompany'], 'id');

            if(!$params['companies_id']){
                throw new bException(tr('companies_select_branch(): The specified company ":company" does not exist or is not available', array(':company' => $params['company'])), 'not-exist');
            }
        }

        $execute = array();

        /*
         * Only show branches per company
         */
        if($params['companies_id']){
            $where[] = ' `companies_id` = :companies_id ';
            $execute[':companies_id'] = $params['companies_id'];

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
        throw new bException('companies_select_branch(): Failed', $e);
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
function companies_get_branch($company, $branch, $column = null, $status = null){
    try{
        /*
         * Filter by specified company
         */
        if(!is_numeric($company)){
            $companies_id = companies_get($company, 'id');

            if(!$companies_id){
                throw new bException(tr('companies_get_branch(): Specified company ":company" does not exist', array(':company' => $company)), 'not-exist');
            }

        }else{
            $companies_id = $company;
        }

        $where[] = ' `branches`.`companies_id` = :companies_id ';
        $execute[':companies_id'] = $companies_id;

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
                               ON     `companies`.`id` = `branches`.`companies_id`
                               AND    `companies`.`status` IS NULL '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `branches`.`id`,
                                         `branches`.`createdon`,
                                         `branches`.`createdby`,
                                         `branches`.`meta_id`,
                                         `branches`.`status`,
                                         `branches`.`companies_id`,
                                         `branches`.`name`,
                                         `branches`.`seoname`,
                                         `branches`.`description`,

                                         `categories`.`name`    AS `category`,
                                         `categories`.`seoname` AS `seocategory`,

                                         `companies`.`name`     AS `company`,
                                         `companies`.`seoname`  AS `seocompany`

                               FROM      `branches`

                               JOIN      `companies`
                               ON        `companies`.`id`    = `branches`.`companies_id`
                               AND       `companies`.`status` IS NULL

                               LEFT JOIN `categories`
                               ON        `categories`.`id`   = `companies`.`categories_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('companies_get_branch(): Failed', $e);
    }
}



/*
 * Validate the specified department
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $department The department to validate
 * @param $reload_only
 * @return array The validated and cleaned $department array
 */
function companies_validate_department($department, $reload_only = false){
    try{
        load_libs('validate,seo');

        $v = new validate_form($department, 'name,seocompany,seobranch,description');

        /*
         * Validate company
         */
        if($department['seocompany']){
            $department['companies_id'] = companies_get($department['seocompany'], 'id');

            if(!$department['companies_id']){
                $v->setError(tr('Specified company does not exist'));
            }

            if($department['seobranch']){
                $department['branches_id'] = companies_get_branch($department['seocompany'], $department['seobranch'], 'id');

                if(!$department['branches_id']){
                    $v->setError(tr('Specified branch does not exist'));
                }

            }else{
                $department['branches_id'] = null;

                if(!$reload_only){
                    $v->setError(tr('No branch specified'));
                }
            }

        }else{
            $department['companies_id'] = null;

            if(!$reload_only){
                $v->setError(tr('No company specified'));
            }
        }

        $v->isValid();

        if($reload_only){
            return $department;
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($department['name']    , tr('Please specify a department name'));
        $v->hasMinChars($department['name'],  2, tr('Please ensure the department name has at least 2 characters'));
        $v->hasMaxChars($department['name'], 64, tr('Please ensure the department name has less than 64 characters'));

        if(is_numeric(substr($department['name'], 0, 1))){
            $v->setError(tr('Please ensure that the department name does not start with a number'));
        }

        $v->hasMaxChars($department['name'], 64, tr('Please ensure the department name has less than 64 characters'));

        $department['name'] = str_clean($department['name']);

        /*
         * Does the department already exist within the specified companies_id?
         */
        $exists = sql_get('SELECT `id` FROM `departments` WHERE `companies_id` '.sql_is(isset_get($department['companies_id'])).' :companies_id AND `branches_id` '.sql_is(isset_get($department['branches_id'])).' :branches_id AND `name` = :name AND `id` '.sql_is(isset_get($department['id']), true).' :id', true, array(':name' => $department['name'], ':id' => isset_get($department['id']), ':companies_id' => isset_get($department['companies_id']), ':branches_id' => isset_get($department['branches_id'])));

        if($exists){
            $v->setError(tr('The department name ":department" already exists', array(':department' => $department['name'])));
        }

        /*
         * Validate description
         */
        if(empty($department['description'])){
            $department['description'] = null;

        }else{
            $v->hasMinChars($department['description'],   16, tr('Please ensure the department description has at least 16 characters'));
            $v->hasMaxChars($department['description'], 2047, tr('Please ensure the department description has less than 2047 characters'));

            $department['description'] = str_clean($department['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $department['seoname'] = seo_unique($department['name'], 'departments', isset_get($department['id']));

      return $department;

    }catch(Exception $e){
        throw new bException('companies_validate_department(): Failed', $e);
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
function companies_select_department($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'        , 'seodepartment');
        array_default($params, 'class'       , 'form-control');
        array_default($params, 'selected'    , null);
        array_default($params, 'seocompany'  , null);
        array_default($params, 'seobranch'   , null);
        array_default($params, 'companies_id', null);
        array_default($params, 'branches_id' , null);
        array_default($params, 'status'      , null);
        array_default($params, 'remove'      , null);
        array_default($params, 'autosubmit'  , true);
        array_default($params, 'empty'       , tr('No departments available'));
        array_default($params, 'none'        , tr('Select a department'));
        array_default($params, 'tabindex'    , 0);
        array_default($params, 'extra'       , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'     , '`name`');

        if($params['seocompany']){
            $params['companies_id'] = companies_get($params['seocompany'], 'id');

            if(!$params['companies_id']){
                throw new bException(tr('companies_select_department(): The reqested company ":company" does not exist or is not available', array(':company' => $params['seocompany'])), 'deleted');
            }
        }

        if($params['seobranch']){
            $params['branches_id'] = companies_get_branch($params['companies_id'], $params['seobranch'], 'id');

            if(!$params['branches_id']){
                throw new bException(tr('companies_select_department(): The reqested branch ":branch" does not exist or is not available', array(':branch' => $params['seobranch'])), 'deleted');
            }
        }

        $execute = array();

        if($params['companies_id']){
            $where[] = ' `companies_id` = :companies_id ';
            $execute[':companies_id'] = $params['companies_id'];
        }

        if($params['branches_id']){
            $where[] = ' `branches_id` = :branches_id ';
            $execute[':branches_id'] = $params['branches_id'];
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

        $query              = 'SELECT `seoname`, `name` FROM `departments` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('companies_select_department(): Failed', $e);
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
 * @param mixed $department The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function companies_get_department($company, $branch, $department, $column = null, $status = null){
    try{
        /*
         * Filter by specified company
         */
        if(!is_numeric($company)){
            $companies_id = companies_get($company, 'id');

            if(!$companies_id){
                throw new bException(tr('companies_get_department(): Specified company ":company" does not exist', array(':company' => $company)), 'not-exist');
            }

        }else{
            $companies_id = $company;
        }

        $where[] = ' `departments`.`companies_id` = :companies_id ';
        $execute[':companies_id'] = $companies_id;

        /*
         * Filter by specified branch
         */
        if(!is_numeric($branch)){
            $branches_id = companies_get_branch($companies_id, $branch, 'id');

            if(!$branches_id){
                throw new bException(tr('companies_get_department(): Specified branch ":branch" does not exist', array(':branch' => $branch)), 'not-exist');
            }

        }else{
            $branches_id = $branch;
        }

        $where[] = ' `departments`.`companies_id` = :companies_id ';
        $execute[':companies_id'] = $companies_id;

        /*
         * Filter by specified department
         */
        if(is_numeric($department)){
            $where[] = ' `departments`.`id` = :id ';
            $execute[':id'] = $department;

        }else{
            $where[] = ' `departments`.`seoname` = :seoname ';
            $execute[':seoname'] = $department;
        }

        /*
         * Filter by specified status
         */
        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `departments`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `departments`.`'.$column.'`

                               FROM   `departments`

                               JOIN   `companies`
                               ON     `companies`.`id` = `departments`.`companies_id`
                               AND    `companies`.`status` IS NULL

                               JOIN   `branches`
                               ON     `branches`.`id`  = `departments`.`branches_id`
                               AND    `branches`.`status`  IS NULL '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `departments`.`id`,
                                         `departments`.`createdon`,
                                         `departments`.`createdby`,
                                         `departments`.`meta_id`,
                                         `departments`.`status`,
                                         `departments`.`companies_id`,
                                         `departments`.`branches_id`,
                                         `departments`.`name`,
                                         `departments`.`seoname`,
                                         `departments`.`description`,

                                         `categories`.`name`    AS `category`,
                                         `categories`.`seoname` AS `seocategory`,

                                         `companies`.`name`     AS `company`,
                                         `companies`.`seoname`  AS `seocompany`,

                                         `branches`.`name`      AS `branch`,
                                         `branches`.`seoname`   AS `seobranch`

                               FROM      `departments`

                               LEFT JOIN `categories`
                               ON        `categories`.`id`   = `companies`.`categories_id`

                               JOIN      `companies`
                               ON        `companies`.`id`    = `departments`.`companies_id`
                               AND       `companies`.`status` IS NULL

                               JOIN      `branches`
                               ON        `branches`.`id`     = `departments`.`branches_id`
                               AND       `branches`.`status`  IS NULL '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('companies_get_department(): Failed', $e);
    }
}



/*
 * Validate the specified employee
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package companies
 *
 * @param array $employee The employee to validate
 * @param $reload_only
 * @return array The validated and cleaned $employee array
 */
function companies_validate_employee($employee, $reload_only = false){
    try{
        load_libs('validate,seo');

        $v = new validate_form($employee, 'name,users_id,seocompany,seobranch,seodepartment,description');

        /*
         * Validate user
         */
        if($employee['users_id']){
            $exists = sql_get('SELECT `id` FROM `users` WHERE `id` = :id AND `status` IS NULL', true, array(':id' => $employee['users_id']));

            if(!$exists){
                $v->setError(tr('Specified user does not exist'));
            }

        }else{
            $employee['users_id'] = null;
        }

        /*
         * Validate company
         */
        if($employee['seocompany']){
            $employee['companies_id'] = companies_get($employee['seocompany'], 'id');

            if(!$employee['companies_id']){
                $v->setError(tr('Specified company does not exist'));
            }

            if($employee['seobranch']){
                $employee['branches_id'] = companies_get_branch($employee['seocompany'], $employee['seobranch'], 'id');

                if(!$employee['branches_id']){
                    $v->setError(tr('Specified branch does not exist'));
                }

                if($employee['seodepartment']){
                    $employee['departments_id'] = companies_get_department($employee['seocompany'], $employee['seobranch'], $employee['seodepartment'], 'id');

                    if(!$employee['departments_id']){
                        $v->setError(tr('Specified department does not exist'));
                    }

                }else{
                    $employee['departments_id'] = null;

                    if(!$reload_only){
                        $v->setError(tr('No department specified'));
                    }
                }

            }else{
                $employee['branches_id'] = null;

                if(!$reload_only){
                    $v->setError(tr('No branch specified'));
                }
            }

        }else{
            $employee['companies_id'] = null;

            if(!$reload_only){
                $v->setError(tr('No company specified'));
            }
        }

        $v->isValid();

        if($reload_only){
            return $employee;
        }

        /*
         * Validate name
         */
        $v->isNotEmpty ($employee['name']    , tr('Please specify a employee name'));
        $v->hasMinChars($employee['name'],  2, tr('Please ensure the employee name has at least 2 characters'));
        $v->hasMaxChars($employee['name'], 64, tr('Please ensure the employee name has less than 64 characters'));

        if(is_numeric(substr($employee['name'], 0, 1))){
            $v->setError(tr('Please ensure that the employee name does not start with a number'));
        }

        $v->hasMaxChars($employee['name'], 64, tr('Please ensure the employee name has less than 64 characters'));

        $employee['name'] = str_clean($employee['name']);

        /*
         * Does the employee already exist within the specified companies_id?
         */
        $exists = sql_get('SELECT `id` FROM `employees` WHERE `companies_id` '.sql_is(isset_get($employee['companies_id'])).' :companies_id AND `branches_id` '.sql_is(isset_get($employee['branches_id'])).' :branches_id AND `departments_id` '.sql_is(isset_get($employee['departments_id'])).' :departments_id AND `name` = :name AND `id` '.sql_is(isset_get($employee['id']), true).' :id', true, array(':name' => $employee['name'], ':id' => isset_get($employee['id']), ':companies_id' => isset_get($employee['companies_id']), ':branches_id' => isset_get($employee['branches_id']), ':departments_id' => isset_get($employee['departments_id'])));

        if($exists){
            $v->setError(tr('The employee name ":employee" already exists', array(':employee' => $employee['name'])));
        }

        /*
         * Validate description
         */
        if(empty($employee['description'])){
            $employee['description'] = null;

        }else{
            $v->hasMinChars($employee['description'],   16, tr('Please ensure the employee description has at least 16 characters'));
            $v->hasMaxChars($employee['description'], 2047, tr('Please ensure the employee description has less than 2047 characters'));

            $employee['description'] = str_clean($employee['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $employee['seoname'] = seo_unique($employee['name'], 'employees', isset_get($employee['id']));

      return $employee;

    }catch(Exception $e){
        throw new bException('companies_validate_employee(): Failed', $e);
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
function companies_select_employee($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'          , 'seoemployee');
        array_default($params, 'class'         , 'form-control');
        array_default($params, 'selected'      , null);
        array_default($params, 'seocompany'    , null);
        array_default($params, 'seobranch'     , null);
        array_default($params, 'seodepartment' , null);
        array_default($params, 'companies_id'  , null);
        array_default($params, 'branches_id'   , null);
        array_default($params, 'departments_id', null);
        array_default($params, 'status'        , null);
        array_default($params, 'remove'        , null);
        array_default($params, 'empty'         , tr('No employees available'));
        array_default($params, 'none'          , tr('Select an employee'));
        array_default($params, 'tabindex'      , 0);
        array_default($params, 'extra'         , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'       , '`name`');

        if($params['seocompany']){
            $params['companies_id'] = companies_get($params['seocompany'], 'id');

            if(!$params['companies_id']){
                throw new bException(tr('companies_select_employee(): The reqested company ":company" does not exist or is not available', array(':company' => $params['seocompany'])), 'deleted');
            }
        }

        if($params['seobranch']){
            $params['branches_id'] = companies_get_branch($params['companies_id'], $params['seobranch'], 'id');

            if(!$params['branches_id']){
                throw new bException(tr('companies_select_employee(): The reqested branch ":branch" does not exist or is not available', array(':branch' => $params['seobranch'])), 'deleted');
            }
        }

        if($params['seodepartment']){
            $params['departments_id'] = companies_get($params['companies_id'], $params['branches_id'], $params['seodepartment'], 'id');

            if(!$params['departments_id']){
                throw new bException(tr('companies_select_employee(): The reqested department ":department" does not exist or is not available', array(':department' => $params['seodepartment'])), 'deleted');
            }
        }

        $execute = array();

        if($params['companies_id']){
            $where[] = ' `companies_id` = :companies_id ';
            $execute[':companies_id'] = $params['companies_id'];
        }

        if($params['branches_id']){
            $where[] = ' `branches_id` = :branches_id ';
            $execute[':branches_id'] = $params['branches_id'];
        }

        if($params['departments_id']){
            $where[] = ' `departments_id` = :departments_id ';
            $execute[':departments_id'] = $params['departments_id'];
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

        $query              = 'SELECT `seoname`, `name` FROM `employees` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('companies_select_employee(): Failed', $e);
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
 * @param mixed $employee The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function companies_get_employee($company, $branch, $department, $employee, $column = null, $status = null){
    try{
        /*
         * Filter by specified company
         */
        if(!is_numeric($company)){
            $companies_id = companies_get($company, 'id');

            if(!$companies_id){
                throw new bException(tr('companies_get_employee(): Specified company ":company" does not exist', array(':company' => $company)), 'not-exist');
            }

        }else{
            $companies_id = $company;
        }

        $where[] = ' `employees`.`companies_id` = :companies_id ';
        $execute[':companies_id'] = $companies_id;

        /*
         * Filter by specified branch
         */
        if(!is_numeric($branch)){
            $branches_id = companies_get_branch($companies_id, $branch, 'id');

            if(!$branches_id){
                throw new bException(tr('companies_get_employee(): Specified branch ":branch" does not exist', array(':branch' => $branch)), 'not-exist');
            }

        }else{
            $branches_id = $branch;
        }

        $where[] = ' `employees`.`companies_id` = :companies_id ';
        $execute[':companies_id'] = $companies_id;

        /*
         * Filter by specified department
         */
        if(!is_numeric($department)){
            $departments_id = companies_get_department($companies_id, $department, 'id');

            if(!$departments_id){
                throw new bException(tr('companies_get_employee(): Specified department ":department" does not exist', array(':department' => $department)), 'not-exist');
            }

        }else{
            $departments_id = $department;
        }

        $where[] = ' `employees`.`companies_id` = :companies_id ';
        $execute[':companies_id'] = $companies_id;

        /*
         * Filter by specified employee
         */
        if(is_numeric($employee)){
            $where[] = ' `employees`.`id` = :id ';
            $execute[':id'] = $employee;

        }else{
            $where[] = ' `employees`.`seoname` = :seoname ';
            $execute[':seoname'] = $employee;
        }

        /*
         * Filter by specified status
         */
        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `employees`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `employees`.`'.$column.'`

                               FROM   `employees`

                               JOIN   `companies`
                               ON     `companies`.`id`    = `employees`.`companies_id`
                               AND    `companies`.`status`    IS NULL

                               JOIN    `branches`
                               ON      `branches`.`id`    = `employees`.`branches_id`
                               AND     `branches`.`status`    IS NULL

                               JOIN    `departments`
                               ON      `departments`.`id` = `employees`.`departments_id`
                               AND     `departments`.`status` IS NULL '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `employees`.`id`,
                                         `employees`.`createdon`,
                                         `employees`.`createdby`,
                                         `employees`.`meta_id`,
                                         `employees`.`status`,
                                         `employees`.`companies_id`,
                                         `employees`.`branches_id`,
                                         `employees`.`name`,
                                         `employees`.`seoname`,
                                         `employees`.`description`,

                                         `categories`.`name`     AS `category`,
                                         `categories`.`seoname`  AS `seocategory`,

                                         `companies`.`name`      AS `company`,
                                         `companies`.`seoname`   AS `seocompany`,

                                         `branches`.`name`       AS `branch`,
                                         `branches`.`seoname`    AS `seobranch`,

                                         `departments`.`name`    AS `department`,
                                         `departments`.`seoname` AS `seodepartment`

                               FROM      `employees`

                               LEFT JOIN `categories`
                               ON        `categories`.`id` = `companies`.`categories_id`

                               JOIN      `companies`
                               ON        `companies`.`id`   = `employees`.`companies_id`
                               AND       `companies`.`status`   IS NULL

                               JOIN      `branches`
                               ON        `branches`.`id`    = `employees`.`branches_id`
                               AND       `branches`.`status`    IS NULL

                               JOIN      `departments`
                               ON        `departments`.`id` = `employees`.`departments_id`
                               AND       `departments`.`status` IS NULL '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('companies_get_employee(): Failed', $e);
    }
}
?>