<?php
/*
 * Empty custom library
 *
 * This library can be used to add project specific functionalities
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */


/*
 * Custom page loader. Will add header and footer to the given HTML, then send
 * HTTP headers, and then HTML to client
 */
function c_page($params, $meta, $html){
    try{
        array_params($params);
        array_default($params, 'cache_namespace', 'htmlpage');
        array_default($params, 'cache_key'      , null);

        $page = c_html_header($params, $meta).$html.c_html_footer($params);

        http_headers($params, strlen($page));

        return cache_write($page, $params['cache_key'], $params['cache_namespace']);

    }catch(Exception $e){
        throw new bException('c_page(): Failed', $e);
    }
}



/*
 * Create and return the page header
 */
function c_html_header($params = null, $meta = null){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, '', '');

        html_load_css('style');
        html_load_js('');

        return html_header($params, $meta).c_page_header($params);

    }catch(Exception $e){
        throw new bException('c_html_header(): Failed', $e);
    }
}



/*
 * Create and return the page header
 */
function c_page_header($params){
    global $_CONFIG;

    try{
        $html = '';

        return $html;

    }catch(Exception $e){
        throw new bException('c_page_header(): Failed', $e);
    }
}



/*
 * Create and return the page footer
 */
function c_html_footer($params){
    try{
        $html = '';

        return $html.html_footer();

    }catch(Exception $e){
        throw new bException('c_html_footer(): Failed', $e);
    }
}
?>
