<?php
/*
 * Environments library
 *
 * This library has functiosn to work with base environments
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package environments
 */



/*
 * Return HTML for an environments select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available environments
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package environments
 *
 * @param array $params The parameters required
 * @paramkey string $params name
 * @paramkey string $params empty
 * @paramkey string $params none
 * @return string HTML for a environments select box within the specified parameters
 */
function environments_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name' , 'seoenvironment');
        array_default($params, 'empty', tr('No environments available'));
        array_default($params, 'none' , tr('Select an environment'));

        $params['resource'] = array();

        foreach(get_config('deploy')['deploy'] as $key => $config){
            $params['resource'][$key] = str_capitalize($key);
        }

        $retval = html_select($params);
        return $retval;

    }catch(Exception $e){
        throw new bException('environments_select(): Failed', $e);
    }
}
?>
