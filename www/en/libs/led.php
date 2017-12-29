<?php
/*
 * LED library
 *
 * This library is based on the LED idea to have a led visible on sites in debug mode
 * This led's color can indicate issues on the page. Clicking the LED will then
 * show some debug interface
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Return HTML to show led
 */
function led(){
    try{
        if(!debug()){
            return '';
        }

        load_css('led');
        return '<div id="led" class="led"></div>';

    }catch(Exception $e){
        throw new bException('led(): Failed', $e);
    }
}
?>
