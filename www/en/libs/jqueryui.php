<?php
/*
 * jQueryUI library
 *
 * This library contains functions to easily apply jQueryUI functionalities
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Creates HTML for a jquery-ui accordeon function
 */
function jqueryui_accordeon($selector, $options = 'collapsible: true,heightStyle: "content"'){
    try{
        html_load_js('base/jquery-ui/jquery-ui');

        if($options){
            $options = str_ends(str_starts(str_force($options), '{'), '}');
        }

        return html_script('$(function() {
                                $("'.$selector.'").accordion('.$options.');
                            });');

    }catch(Exception $e){
        throw new lsException('jqueryui_accordeon(): Failed', $e);
    }
}
?>
