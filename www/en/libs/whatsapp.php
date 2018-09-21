<?php
/*
 * Whatsapp library
 *
 * This library contains  Whatsapp API functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @return void
 */
function whatsapp_library_init(){
    try{
        ensure_installed(array('name'      => 'empty',
                               'project'   => 'emptyear',
                               'callback'  => 'empty_install',
                               'checks'    => array(ROOT.'libs/external/empty/')));

    }catch(Exception $e){
        throw new bException('whatsapp_library_init(): Failed', $e);
    }
}



?>
