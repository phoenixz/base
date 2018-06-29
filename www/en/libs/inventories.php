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
 * @param array $item The company to validate
 * @return array The validated and cleaned $item array
 */
function inventories_validate($item, $reload_only = false){
    try{
        load_libs('validate,seo');

        $v = new validate_form($item, 'name,seocategory,seoprovider,brand,model,code,description');

        /*
         * Validate category
         */
        if($item['seocategory']){
            load_libs('categories');
            $item['categories_id'] = categories_get($item['seocategory'], 'id');

            if(!$item['categories_id']){
                $v->setError(tr('Specified category does not exist'));
            }

        }else{
            $item['categories_id'] = null;
            $v->setError(tr('No category specified'));
        }

        /*
         * Validate provider
         */
        if($item['seoprovider']){
            load_libs('providers');
            $item['providers_id'] = providers_get($item['seoprovider'], 'id');

            if(!$item['providers_id']){
                $v->setError(tr('Specified provider does not exist'));
            }

        }else{
            $item['providers_id'] = null;
        }

        if($reload_only){
            return $item;
        }

        /*
         * Validate brand
         */
        $v->isNotEmpty ($item['brand']    , tr('Please specify a company brand'));
        $v->hasMinChars($item['brand'],  2, tr('Please ensure the company brand has at least 2 characters'));
        $v->hasMaxChars($item['brand'], 64, tr('Please ensure the company brand has less than 64 characters'));

        if(is_numeric(substr($item['brand'], 0, 1))){
            $v->setError(tr('Please ensure that the company brand does not start with a number'));
        }

        $v->hasMaxChars($item['brand'], 64, tr('Please ensure the company brand has less than 64 characters'));

        $item['brand'] = str_clean($item['brand']);

        /*
         * Validate model
         */
        $v->isNotEmpty ($item['model']    , tr('Please specify a company model'));
        $v->hasMinChars($item['model'],  2, tr('Please ensure the company model has at least 2 characters'));
        $v->hasMaxChars($item['model'], 64, tr('Please ensure the company model has less than 64 characters'));

        if(is_numeric(substr($item['model'], 0, 1))){
            $v->setError(tr('Please ensure that the company model does not start with a number'));
        }

        $v->hasMaxChars($item['model'], 64, tr('Please ensure the company model has less than 64 characters'));

        $item['model'] = str_clean($item['model']);

        /*
         * Validate code
         */
        $v->isNotEmpty ($item['code']    , tr('Please specify a company code'));
        $v->hasMinChars($item['code'],  2, tr('Please ensure the company code has at least 2 characters'));
        $v->hasMaxChars($item['code'], 64, tr('Please ensure the company code has less than 64 characters'));

        if(is_numeric(substr($item['code'], 0, 1))){
            $v->setError(tr('Please ensure that the company code does not start with a number'));
        }

        $v->hasMaxChars($item['code'], 64, tr('Please ensure the company code has less than 64 characters'));

        $item['code'] = str_clean($item['code']);

        /*
         * Validate description
         */
        if(empty($item['description'])){
            $item['description'] = null;

        }else{
            $v->hasMinChars($item['description'],   16, tr('Please ensure the company description has at least 16 characters'));
            $v->hasMaxChars($item['description'], 2047, tr('Please ensure the company description has less than 2047 characters'));

            $item['description'] = str_clean($item['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $item['seoname'] = seo_unique($item['name'], 'inventories_items', isset_get($item['id']));

        return $item;

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
 * @param mixed $item The required company. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The company data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified company does not exist, NULL will be returned.
 */
function inventories_get($item, $column = null, $status = null){
    try{
        if(is_numeric($item)){
            $where[] = ' `companies`.`id` = :id ';
            $execute[':id'] = $item;

        }else{
            $where[] = ' `companies`.`seoname` = :seoname ';
            $execute[':seoname'] = $item;
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
function inventories_validate_item($item, $reload_only = false){
    try{
        load_libs('validate,seo');
        $v = new validate_form($item, 'name,seocategory,seoprovider,brand,model,code,description');

        /*
         * Validate category
         */
        if($item['seocategory']){
            load_libs('categories');
            $item['categories_id'] = categories_get($item['seocategory'], 'id');

            if(!$item['categories_id']){
                $v->setError(tr('Specified category does not exist'));
            }

        }else{
            $item['categories_id'] = null;
            $v->setError(tr('No category specified'));
        }

        /*
         * Validate provider
         */
        if($item['seoprovider']){
            load_libs('providers');
            $item['providers_id'] = providers_get($item['seoprovider'], 'id');

            if(!$item['providers_id']){
                $v->setError(tr('Specified provider does not exist'));
            }

        }else{
            $item['providers_id'] = null;
        }

        if($reload_only){
            return $item;
        }

        /*
         * Validate brand
         */
        $v->isNotEmpty ($item['brand']    , tr('Please specify a company brand'));
        $v->hasMinChars($item['brand'],  2, tr('Please ensure the company brand has at least 2 characters'));
        $v->hasMaxChars($item['brand'], 64, tr('Please ensure the company brand has less than 64 characters'));

        if(is_numeric(substr($item['brand'], 0, 1))){
            $v->setError(tr('Please ensure that the company brand does not start with a number'));
        }

        $v->hasMaxChars($item['brand'], 64, tr('Please ensure the company brand has less than 64 characters'));

        $item['brand']    = str_clean($item['brand']);
        $item['seobrand'] = seo_string($item['brand']);

        /*
         * Validate model
         */
        $v->isNotEmpty ($item['model']    , tr('Please specify a company model'));
        $v->hasMinChars($item['model'],  2, tr('Please ensure the company model has at least 2 characters'));
        $v->hasMaxChars($item['model'], 64, tr('Please ensure the company model has less than 64 characters'));

        if(is_numeric(substr($item['model'], 0, 1))){
            $v->setError(tr('Please ensure that the company model does not start with a number'));
        }

        $v->hasMaxChars($item['model'], 64, tr('Please ensure the company model has less than 64 characters'));

        $item['model']    = str_clean($item['model']);
        $item['seomodel'] = seo_string($item['model']);

        /*
         * Validate code
         */
        $v->isNotEmpty ($item['code']    , tr('Please specify a company code'));
        $v->hasMinChars($item['code'],  2, tr('Please ensure the company code has at least 2 characters'));
        $v->hasMaxChars($item['code'], 64, tr('Please ensure the company code has less than 64 characters'));

        if(is_numeric(substr($item['code'], 0, 1))){
            $v->setError(tr('Please ensure that the company code does not start with a number'));
        }

        $v->hasMaxChars($item['code'], 64, tr('Please ensure the company code has less than 64 characters'));

        $item['code'] = str_clean($item['code']);

        /*
         * Validate description
         */
        if(empty($item['description'])){
            $item['description'] = null;

        }else{
            $v->hasMinChars($item['description'],   16, tr('Please ensure the company description has at least 16 characters'));
            $v->hasMaxChars($item['description'], 2047, tr('Please ensure the company description has less than 2047 characters'));

            $item['description'] = str_clean($item['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $item['seoname'] = seo_unique($item['name'], 'inventories_items', isset_get($item['id']));

        return $item;

    }catch(Exception $e){
        throw new bException('inventories_validate_item(): Failed', $e);
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
function inventories_get_item($brand, $model, $column = null, $status = null){
    try{
        /*
         * Filter by specified brand and model
         */
        if(!$brand){
            throw new bException(tr('inventories_get_item(): No brand specified'), 'not-specified');
        }

        if(!$model){
            throw new bException(tr('inventories_get_item(): No modelspecified'), 'not-specified');
        }

        $where[] = ' `inventories_items`.`brand` = :brand ';
        $where[] = ' `inventories_items`.`model` = :model ';

        $execute[':brand'] = $brand;
        $execute[':model'] = $model;

        /*
         * Filter by specified status
         */
        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `inventories_items`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `inventories_items`.`'.$column.'`

                               FROM   `inventories_items` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `inventories_items`.`id`,
                                         `inventories_items`.`createdon`,
                                         `inventories_items`.`createdby`,
                                         `inventories_items`.`meta_id`,
                                         `inventories_items`.`status`,
                                         `inventories_items`.`categories_id`,
                                         `inventories_items`.`providers_id`,
                                         `inventories_items`.`brand`,
                                         `inventories_items`.`seobrand`,
                                         `inventories_items`.`model`,
                                         `inventories_items`.`seomodel`,
                                         `inventories_items`.`code`,
                                         `inventories_items`.`description`,

                                         `providers`.`name`     AS `provider`,
                                         `providers`.`seoname`  AS `seoprovider`,

                                         `categories`.`name`    AS `category`,
                                         `categories`.`seoname` AS `seocategory`

                               FROM      `inventories_items`

                               LEFT JOIN `categories`
                               ON        `categories`.`id` = `inventories_items`.`categories_id`

                               LEFT JOIN `providers`
                               ON        `providers`.`id` = `inventories_items`.`providers_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('inventories_get_item(): Failed', $e);
    }
}
?>