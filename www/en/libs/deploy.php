<?php
/*
 * Deploy library
 *
 * This library contains various functions used to deploy websites
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Return deploy configuration for the specified subenvironment
 */
function deploy_update_config($subenvironment){
	global $_CONFIG;

    try{
		if(!$subenvironment){
			return false;
		}

		$_CONFIG['deploy'] = array_merge($_CONFIG['deploy'], deploy_get_config($subenvironment));

    }catch(Exception $e){
        throw new bException('deploy_update_config(): Failed', $e);
    }
}



/*
 * Return deploy configuration for the specified subenvironment
 */
function deploy_get_config($subenvironment){
    try{
		if(!$subenvironment){
			throw new bException('deploy_get_config(): No subenvironment specified');
		}

		$_CONFIG = array('deploy' => array());

		include(ROOT.'config/production_'.$subenvironment.'.php');

		return $_CONFIG['deploy'];

    }catch(Exception $e){
        throw new bException('deploy_get_config(): Failed', $e);
    }
}
?>
