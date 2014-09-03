<?php
/*
 * Popup library
 *
 * This contains functions to create HTML popups
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Crfeate HTML popup with the specified content
 */
function popup_create($content){
    try{
        return '<div class="popup container">
                    <div class="popup cover">
                    </div>
                    <div class="popup window">
                        <div class="popup close"></div>
                        <div class="popup content">
                        '.$content.'
                        </div>
                    </div>
                </div>';

    }catch(Exception $e){
        throw new bException('popup_create(): Failed', $e);
    }
}
?>
