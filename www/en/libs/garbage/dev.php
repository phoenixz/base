<?php
/*
 * Dev library
 *
 * This is the development library, contains lots of functions to help with development
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 * Sync database and data files from this environment from the specified environment.
 */
function dev_sync_from($environment){
    global $_CONFIG;

    try{
        include(ROOT.'config/dev.php');

        /*
         * First check if specified environment exists.
         */
        if(empty($_CONFIG['dev']['environments'][$environment])){
            throw new bException('dev_sync_from(): Specified environment "'.$environment.'" does not exist', 'not-exist');
        }

        $environment = $_CONFIG['dev']['environments'][$environment];

        /*
         * Get SQL dump file
         */
        shell_exec('ssh');
        shell_exec('scp');
        shell_exec('tar -zxvf');
        shell_exec('mysql < ');

        /*
         * RSync data directories
         */
        shell_exec('rsync');

    }catch(Exception $e){
        throw new bException('dev_sync_from(): Failed', $e);
    }
}
?>
