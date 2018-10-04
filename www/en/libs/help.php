<?php
/*
 * Help library
 *
 * The help library contains functions for command line help
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */


/*
 * Show help information
 */
function help($section){
    try{
        switch($section){
            case 'system':
                log_console('System options:', 'white');
                echo load_content('help/system')."\n";
                break;

            default:
                throw new bException(tr('help(): Unknown section ":section" specified', array(':section' => $section)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('help(): Failed', $e);
    }
}
?>
