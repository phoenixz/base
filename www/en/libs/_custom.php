<?php
/*
 * Empty custom library
 *
 * This library can be used to add project specific functionalities
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 *
 */
function c_page($params, $meta, $html){
    return c_html_header($params, $meta).$html.c_html_footer();
}



/*
 * Create and return the page header
 */
function c_html_header($params = null, $meta = null){
    global $_CONFIG;

    try{
        array_params($params);

        html_load_css('style');
        html_load_js('');

        return html_header($params, $meta).c_page_header($params);

    }catch(Exception $e){
        throw new lsException('c_page_header(): Failed', $e);
    }
}



/*
 * Create and return the page footer
 */
function c_html_footer(){
    return ''.html_footer();
}



/*
 * Create and return the page header
 */
function c_page_header(){
    global $_CONFIG;

    try{
        $html = '';

        return $html;

    }catch(Exception $e){
        throw new lsException('c_page_header(): Failed', $e);
    }
}
?>
