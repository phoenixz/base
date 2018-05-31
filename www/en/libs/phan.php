<?php
/*
 * Phan  library
 *
 * This library is a front-end library for the Phan static analyzer for PHP library
 *
 * @see https://github.com/phan/phan
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function phan_library_init(){
    try{
        // Github URL https://github.com/phan/phan.git
        // Composer command composer require phan/phan

    }catch(Exception $e){
        throw new bException('phan_library_init(): Failed', $e);
    }
}
?>
