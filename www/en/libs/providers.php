<?php
/*
 * Rights library
 *
 * This is the providers library file, it contains providers functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyprovider Sven Oostenbrink <support@ingiga.com>
 */



/*
 *
 */
function providers_validate($provider){
    try{
        load_libs('validate,seo');

        $v = new validate_form($provider, 'name,description');
        $v->isNotEmpty ($provider['name']    , tr('No providers name specified'));
        $v->hasMinChars($provider['name'],  2, tr('Please ensure the provider\'s name has at least 2 characters'));
        $v->hasMaxChars($provider['name'], 32, tr('Please ensure the provider\'s name has less than 32 characters'));
        $v->isRegex    ($provider['name'], '/^[a-zA-Z- ]{2,32}$/', tr('Please ensure the provider\'s name contains only lower case letters, and dashes'));

        $v->isNotEmpty ($provider['description']      , tr('No provider\'s description specified'));
        $v->hasMinChars($provider['description'],    2, tr('Please ensure the provider\'s description has at least 2 characters'));
        $v->hasMaxChars($provider['description'], 2047, tr('Please ensure the provider\'s description has less than 2047 characters'));

        if(is_numeric(substr($provider['name'], 0, 1))){
            $v->setError(tr('Please ensure that the providers\'s name does not start with a number'));
        }

        /*
         * Does the provider already exist?
         */
        if(empty($provider['id'])){
            if($id = sql_get('SELECT `id` FROM `providers` WHERE `name` = :name', array(':name' => $provider['name']))){
                $v->setError(tr('The provider ":provider" already exists with id ":id"', array(':provider' => $provider['name'], ':id' => $id)));
            }

        }else{
            if($id = sql_get('SELECT `id` FROM `providers` WHERE `name` = :name AND `id` != :id', array(':name' => $provider['name'], ':id' => $provider['id']))){
                $v->setError(tr('The provider ":provider" already exists with id ":id"', array(':provider' => $provider['name'], ':id' => $id)));
            }
        }

        $v->isValid();

        $provider['seoname'] = seo_unique($provider['name'], 'providers', isset_get($provider['id']));

        return $provider;

    }catch(Exception $e){
        throw new bException(tr('providers_validate(): Failed'), $e);
    }
}
?>
