<?php
/*
 * Providers library
 *
 * This is the providers library file, it contains providers functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyprovider Sven Oostenbrink <support@capmega.com>
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
 * @package providers
 *
 * @return void
 */
function providers_library_init(){
    try{
        load_config('providers');

    }catch(Exception $e){
        throw new bException('providers_library_init(): Failed', $e);
    }
}



/*
 * Validate a provider
 *
 * This function will validate all relevant fields in the specified $provider array
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
function providers_validate($provider){
    try{
        load_libs('validate,seo');

        $v = new validate_form($provider, 'seocategory,name,code,url,email,phones,description');
        $v->isNotEmpty ($provider['name']    , tr('No providers name specified'));
        $v->hasMinChars($provider['name'],  2, tr('Please ensure the provider\'s name has at least 2 characters'));
        $v->hasMaxChars($provider['name'], 64, tr('Please ensure the provider\'s name has less than 64 characters'));
        $v->isRegex    ($provider['name'], '/^[a-zA-Z- ]{2,32}$/', tr('Please ensure the provider\'s name contains only lower case letters, and dashes'));

        /*
         * Validate category
         */
        if($provider['seocategory']){
            load_libs('categories');

            $provider['categories_id'] = categories_get($provider['seocategory'], 'id');

            if(!$provider['categories_id']){
                $v->setError(tr('Specified category does not exist'));
            }

        }else{
            $provider['categories_id'] = null;
        }

        /*
         * Validate basic data
         */
        if($provider['description']){
            $v->hasMinChars($provider['description'],    8, tr('Please ensure the description has at least 8 characters'));
            $v->hasMaxChars($provider['description'], 2047, tr('Please ensure the description has less than 2047 characters'));

            $provider['description'] = str_clean($provider['description']);

        }else{
            $provider['description'] = null;
        }

        if($provider['url']){
            $v->hasMaxChars($provider['url'], 255, tr('Please ensure the URL has less than 255 characters'));
            $v->isURL($provider['url'], tr('Please a valid URL'));

        }else{
            $provider['url'] = null;
        }

        if($provider['email']){
            $v->hasMaxChars($provider['email'], 64, tr('Please ensure the email has less than 96 characters'));
            $v->isEmail($provider['email'], tr('Please specify a valid emailaddress'));

        }else{
            $provider['email'] = null;
        }

        if($provider['phones']){
            $v->hasMaxChars($provider['phones'], 36, tr('Please ensure the phones field has less than 36 characters'));

            foreach(array_force($provider['phones']) as &$phone){
                $v->isPhonenumber($phone, tr('Please ensure the phone number ":phone" is valid', array(':phone' => $phone)));
            }

            $provider['phones'] = str_force($provider['phones']);

        }else{
            $provider['phones'] = null;
        }

        if($provider['code']){
            $v->hasMinChars($provider['code'],  2, tr('Please ensure the provider\'s description has at least 2 characters'));
            $v->hasMaxChars($provider['code'], 64, tr('Please ensure the provider\'s description has less than 64 characters'));
            $v->isAlphaNumeric($provider['code'], tr('Please ensure the provider\'s description has less than 64 characters'), VALIDATE_IGNORE_SPACE|VALIDATE_IGNORE_DASH|VALIDATE_IGNORE_UNDERSCORE);

        }else{
            $provider['code'] = null;
        }

        /*
         * Does the provider already exist?
         */
        $exists = sql_get('SELECT `id` FROM `providers` WHERE `name` = :name AND `id` != :id', true, array(':name' => $provider['name'], ':id' => isset_get($provider['id'])));

        if($exists){
            $v->setError(tr('The provider ":provider" already exists with id ":id"', array(':provider' => $provider['name'], ':id' => $exists)));
        }

        $v->isValid();

        $provider['seoname'] = seo_unique($provider['name'], 'providers', isset_get($provider['id']));

        return $provider;

    }catch(Exception $e){
        throw new bException(tr('providers_validate(): Failed'), $e);
    }
}



/*
 * Return HTML for a providers select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available providers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package providers
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
 * @return string HTML for a providers select box within the specified parameters
 */
function providers_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'         , 'seoprovider');
        array_default($params, 'class'        , 'form-control');
        array_default($params, 'selected'     , null);
        array_default($params, 'categories_id', false);
        array_default($params, 'status'       , null);
        array_default($params, 'empty'        , tr('No providers available'));
        array_default($params, 'none'         , tr('Select a provider'));
        array_default($params, 'tabindex'     , 0);
        array_default($params, 'extra'        , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby'      , '`name`');

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

        $query              = 'SELECT `seoname`, `name` FROM `providers` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('providers_select(): Failed', $e);
    }
}



/*
 * Return data for the specified provider
 *
 * This function returns information for the specified provider. The provider can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package providers
 *
 * @param mixed $provider The requested provider. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status Filter by the specified status
 * @param natural $categories_id Filter by the specified categories_id. If NULL, the customer must NOT belong to any category
 * @return mixed The provider data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified provider does not exist, NULL will be returned.
 */
function providers_get($provider, $column = null, $status = null, $categories_id = false){
    try{
        if(is_numeric($provider)){
            $where[] = ' `providers`.`id` = :id ';
            $execute[':id'] = $provider;

        }else{
            $where[] = ' `providers`.`seoname` = :seoname ';
            $execute[':seoname'] = $provider;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `providers`.`status` '.sql_is($status).' :status';
        }

        if($categories_id !== false){
            $execute[':categories_id'] = $categories_id;
            $where[] = ' `customers`.`categories_id` '.sql_is($categories_id).' :categories_id';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `providers` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `providers`.`id`,
                                         `providers`.`createdon`,
                                         `providers`.`createdby`,
                                         `providers`.`meta_id`,
                                         `providers`.`status`,
                                         `providers`.`name`,
                                         `providers`.`seoname`,
                                         `providers`.`email`,
                                         `providers`.`phones`,
                                         `providers`.`code`,
                                         `providers`.`url`,
                                         `providers`.`description`,

                                         `categories`.`name`    AS `category`,
                                         `categories`.`seoname` AS `seocategory`

                               FROM      `providers`

                               LEFT JOIN `categories`
                               ON        `categories`.`id` = `providers`.`categories_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('providers_get(): Failed', $e);
    }
}
?>
