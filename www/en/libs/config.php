<?php
/*
 * Config library
 *
 * This library contains configuration functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */





/*
 * Return configuration for specified environment
 */
function config_get_for_environment($environment){
    try{
        include(ROOT.'config/base/default.php');
        include(ROOT.'config/production.php');
        include(ROOT.'config/deploy.php');

        if($environment != 'production'){
            include(ROOT.'config/'.$environment.'.php');
        }

        /*
         * Optionally load the platform specific configuration file, if it exists
         */
        if(file_exists($file = ROOT.'config/'.$environment.'_'.PLATFORM.'.php')){
            include($file);
        }

        return $_CONFIG;

    }catch(Exception $e){
        throw new bException(tr('config_get_for_environment(): Failed'), $e);
    }
}
?>
