<?php
/*
 * NodeJS library
 *
 * This library contains various NodeJS functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Check if node is installed and available
 */
function node_check(){
    try{
        log_console('node_check_npm(): Checking NodeJS availability', 'node', 'white');

        try{
            $result = safe_exec('which nodejs');
            $result = array_shift($result);

        }catch(Exception $e){
            $result = safe_exec('which node');
            $result = array_shift($result);
        }

        log_console('node_check_npm(): Using NodeJS "'.str_log($result).'"', 'node', 'green');

        return $result;

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new bException('node_check(): Failed to find a node installation on this computer for this user', 'node_not_installed');
        }

        if($e->getCode() == 'node_modules_path_not_found'){
            throw $e;
        }

        throw new bException('node_check(): Failed', $e);
    }
}



/*
 * Check if node is installed and available
 */
function node_check_modules(){
    try{
        log_console('node_check_npm(): Checking node_modules availability', 'node', 'white');

        /*
         * Find node_modules path
         */
        if(!$home = getenv('HOME')){
            throw new bException('node_check_modules(): Environment variable "HOME" not found, failed to locate users home directory', 'environment_variable_not_found');
        }

        $home = slash($home);

        if(!file_exists($home.'node_modules')){
            if(!file_exists($home.'.node_modules')){
                throw new bException('node_check_modules(): node_modules path not found', 'node_modules_path_not_found');
            }

            return $home.'.node_modules/';

        }

        $home .= 'node_modules/';

        log_console('node_check_npm(): Using node_modules "'.str_log($home).'"', 'node', 'green');

        return $home;

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new bException('node_check_modules(): Failed to find a node installation on this computer for this user', 'node_not_installed');
        }

        if($e->getCode() == 'node_modules_path_not_found'){
            throw $e;
        }

        throw new bException('node_check_modules(): Failed', $e);
    }
}



/*
 * Check if npm is installed and available
 */
function node_check_npm(){
    try{
        log_console('node_check_npm(): Checking NodeJS npm availability', 'node', 'white');

        $result = safe_exec('which npm');
        $result = array_shift($result);

        log_console('node_check_npm(): Using npm "'.str_log($result).'"', 'node', 'green');

        return $result;

    }catch(Exception $e){
        if($e->getCode() == 1){
            throw new bException('node_check_npm(): Failed to find an npm installation on this computer for this user', 'npm_not_installed');
        }

        throw new bException('node_check_npm(): Failed', $e);
    }
}
?>
