<?php
/*
 * UXDM library
 *
 * This library is a front-end library for the UXDM Universal Extensible Data Migrator library
 *
 * @see https://github.com/DivineOmega/uxdm
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function uxdm_library_init(){
    try{
        // Github URL https://github.com/DivineOmega/uxdm.git
        // Composer command composer require divineomega/uxdm

    }catch(Exception $e){
        throw new bException('uxdm_library_init(): Failed', $e);
    }
}
?>
