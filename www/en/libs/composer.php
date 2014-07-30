<?php
/*
 * PHP Composer library
 *
 * This library contains all required functions to work with PHP composer
 *
 * @url https://getcomposer.org/
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Do a version check so we're sure this stuff is supported
 */
if(version_compare(PHP_VERSION, '5.3.2') < 0){
    throw new lsException('composer library: PHP composer requires PHP 5.3.2+', 'notsupported');
}



/*
 *
 */
function composer_phar_path($global){
    if($global){
        /*
         * Global path
         */
        return '/usr/bin/';

    }else{
        /*
         * Local path
         */
        return ROOT.'data/bin/';
    }
}



/*
 * Check if composer is installed, and return its location. False if no location was found
 */
function composer_phar_location(){
    try{
        /*
         * Check both local and global paths
         */
        if(file_exists($file = ROOT.'data/bin/composer.phar')){
            return $file;
        }

        if(file_exists($file = '/usr/bin/composer.phar')){
            return $file;
        }
        /*
         * Composer not found
         */
        return false;

    }catch(Exception $e){
        throw new lsException('composer_phar_location(): Failed', $e);
    }
}



/*
 * Install composer
 * @$path If TRUE, will do a global install in /usr/bin. If set to FALSE will do a local install for this project only in ROOT/data/bin.
 */
function composer_phar_install($global = null){
    global $_CONFIG;

    try{
        if($global === null){
            /*
             * Get global from configuration, default to local
             */
            $global = isset_get($_CONFIG['composer']['global'], false);
        }

        $path = composer_phar_path($global);

        /*
         * Ensure that the path is okay and writable
         */
        load_libs('file');
        file_check_dir($path, true);

        /*
         * Get the composer installer and run it to get the composer.phar file
         */
        $data = file_get_contents('https://getcomposer.org/installer');
        file_put_contents(TMP.'composer_phar_installer', $data);

        if(PLATFORM == 'shell'){
            passthru(TMP.'composer_phar_installer');

        }else{
            safe_exec(TMP.'composer_phar_installer');
        }

        /*
         * Move composer phar file to target and cleanup installer
         */
        rename(TMP.'composer.phar', $path);
        ulink(TMP.'composer_phar_installer');

        return $path;

    }catch(Exception $e){
        throw new lsException('composer_phar_install(): Failed', $e);
    }
}



/*
 * Ensure that composer is installed, and return its path
 * @$path If not specified, will search in global path /usr/bin, and local path ROOT/data/bin/. If set to string value, it will install in the specified path
 */
function composer_phar_ensure($global = null){
    try{
        if($phar = composer_phar_location()){
            return $phar;
        }

        return composer_phar_install($global);

    }catch(Exception $e){
        throw new lsException('composer_phar_ensure(): Failed', $e);
    }
}



/*
 * Run php composer.phar install
 */
function composer_install($arguments, $global = null){
    try{
        $phar    = composer_phar_ensure();
        $command = 'php '.$phar.' install';

        if(PLATFORM == 'shell'){
            passthru($command);

        }else{
            safe_exec($command);
        }

    }catch(Exception $e){
        throw new lsException('composer_install(): Failed', $e);
    }
}
?>
