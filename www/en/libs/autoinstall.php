<?php
/*
 * autoinstall library
 *
 * This library can detect if other (external) libraries are installed and if
 * not, automatically install them and continue
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Check if specified library is installed. If not, then automatically install
 * the specified library
 */
function autoinstall($rules){
    try{
        /*
         * Detect if library is already installed
         */
        if(){
            return true;
        }

        /*
         * Library isn't installed yet. Install now
         */
        load_libs('file');


    }catch(Exception $e){
        throw new bException('autoinstall(): Failed', $e);
    }
}
?>
