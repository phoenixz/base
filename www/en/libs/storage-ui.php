<?php
/*
 * Storage web ui library
 *
 * This library contains functions to build the web ui for the storage system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Return atlant style HTML for the storage webui header
 */
function storage_ui_panel_header(){
    try{
        $html = '';

        return $html;

    }catch(Exception $e){
        throw new bException('storage_ui_panel_header(): Failed', $e);
    }
}
?>
