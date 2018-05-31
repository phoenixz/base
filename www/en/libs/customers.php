<?php
/*
 * Rights library
 *
 * This is the customers library file, it contains customers functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copycustomer Sven Oostenbrink <support@capmega.com>
 */



/*
 *
 */
function customers_validate($customer){
    try{
        load_libs('validate,seo');

        $v = new validate_form($customer, 'name,description');
        $v->isNotEmpty ($customer['name']    , tr('No customers name specified'));
        $v->hasMinChars($customer['name'],  2, tr('Please ensure the customer\'s name has at least 2 characters'));
        $v->hasMaxChars($customer['name'], 32, tr('Please ensure the customer\'s name has less than 32 characters'));
        $v->isRegex    ($customer['name'], '/^[a-zA-Z- ]{2,32}$/', tr('Please ensure the customer\'s name contains only lower case letters, and dashes'));

        if($customer['description']){
            $v->hasMinChars($customer['description'],    8, tr('Please ensure the customer\'s description has at least 8 characters'));
            $v->hasMaxChars($customer['description'], 2047, tr('Please ensure the customer\'s description has less than 2047 characters'));

        }else{
            $customer['description'] = null;
        }

        if(is_numeric(substr($customer['name'], 0, 1))){
            $v->setError(tr('Please ensure that the customers\'s name does not start with a number'));
        }

        /*
         * Does the customer already exist?
         */
        if(empty($customer['id'])){
            if($id = sql_get('SELECT `id` FROM `customers` WHERE `name` = :name', array(':name' => $customer['name']))){
                $v->setError(tr('The customer ":customer" already exists with id ":id"', array(':customer' => $customer['name'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `customers` WHERE `name` = :name AND `id` != :id', array(':name' => $customer['name'], ':id' => $customer['id']))){
                $v->setError(tr('The customer ":customer" already exists with id ":id"', array(':customer' => $customer['name'], ':id' => $id)));
            }
        }

        $v->isValid();

        $customer['seoname'] = seo_unique($customer['name'], 'customers', isset_get($customer['id']));

        return $customer;

    }catch(Exception $e){
        throw new bException(tr('customers_validate(): Failed'), $e);
    }
}
?>
