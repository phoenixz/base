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
function storage_ui_panel_header($params, $section, $active){
    try{
        array_ensure($params, 'active');
        array_ensure($params['files']);
        array_ensure($params['urls']);
        array_ensure($params['tabs']);
        array_ensure($params['labels']);

        array_default($params, 'header_type'     , 'tabs');
        array_default($params, 'html_flash_class', 'storage');
        array_default($params, 'form_action'     , 'post');

        array_default($params['files'], 'sections'     , 'storage-sections');
        array_default($params['files'], 'documents'    , 'storage-documents');
        array_default($params['files'], 'categories'   , 'storage-categories');
        array_default($params['files'], 'files'        , 'storage-files');
        array_default($params['files'], 'configuration', 'storage-configuration');
        array_default($params['files'], 'comments'     , 'storage-comments');
        array_default($params['files'], 'keywords'     , 'storage-keywords');
        array_default($params['files'], 'key_values'   , 'storage-key-values');
        array_default($params['files'], 'resources'    , 'storage-resources');
        array_default($params['files'], 'ratings'      , 'storage-ratings');

        array_default($params['urls'], 'form'         , domain(true));
        array_default($params['urls'], 'create'       , domain('/'.$params['files']['section'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'section'      , domain('/'.$params['files']['section'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'documents'    , domain('/'.$params['files']['documents'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'categories'   , domain('/'.$params['files']['categories'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'files'        , domain('/'.$params['files']['files'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'configuration', domain('/'.$params['files']['configuration'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'comments'     , domain('/'.$params['files']['comments'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'keywords'     , domain('/'.$params['files']['keywords'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'key_values'   , domain('/'.$params['files']['key_values'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'resources'    , domain('/'.$params['files']['resources'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'ratings'      , domain('/'.$params['files']['ratings'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));

        array_default($params['labels'], 'title' , tr('Manage :objects', array(':objects' => $params['objects'])));
        array_default($params['labels'], 'filter', tr('Filter...'));

        array_default($params['tabs'], 'sections'     , tr('Sections'));
        array_default($params['tabs'], 'documents'    , tr('Documents'));
        array_default($params['tabs'], 'categories'   , tr('Categories'));
        array_default($params['tabs'], 'files'        , tr('Files'));
        array_default($params['tabs'], 'configuration', tr('Configuration'));
        array_default($params['tabs'], 'comments'     , tr('Comments'));
        array_default($params['tabs'], 'keywords'     , tr('Keywords'));
        array_default($params['tabs'], 'key_values'   , tr('Key values'));
        array_default($params['tabs'], 'resources'    , tr('Resources'));
        array_default($params['tabs'], 'ratings'      , tr('Ratings'));


        switch($params['header_type']){
            case 'tabs':
                $tab_div_open   = '<div class="tab-pane active" id="tab-blog">';
                $tab_div_close  = '</div>';
                $tabs           = ' tabs';
                $tab_content    = ' tab-content';
                $main_heading   = ' <div class="panel-heading">
                                        <div class="panel-title">
                                            <h2><span class="fa fa-blogs"></span> '.str_replace(':blog', $section['name'], $params['labels']['title']).'</h2>
                                        </div>
                                    </div>';
                $panel_heading  = ' <ul class="nav nav-tabs" role="tablist">
                                        <li'.(($params['active'] == 'sections')      ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['section']).'" role="tab">'.(is_new($section) ? $params['tabs']['section'] : $params['tabs']['sections']).'</a></li>
                                        <li'.(($params['active'] == 'configuration') ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['files']).'" role="tab">'.$params['tabs']['configuration'].'</a></li>
                                        <li'.(($params['active'] == 'categories')    ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['categories']).'" role="tab">'.$params['tabs']['categories'].'</a></li>
                                        <li'.(($params['active'] == 'documents')     ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['documents']).'" role="tab">'.$params['tabs']['documents'].'</a></li>
                                        <li'.(($params['active'] == 'keywords')      ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['keywords']).'" role="tab">'.$params['tabs']['keywords'].'</a></li>
                                        <li'.(($params['active'] == 'key_values')    ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['key_values']).'" role="tab">'.$params['tabs']['key_values'].'</a></li>
                                        <li'.(($params['active'] == 'files')         ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['files']).'" role="tab">'.$params['tabs']['files'].'</a></li>
                                        <li'.(($params['active'] == 'comments')      ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['comments']).'" role="tab">'.$params['tabs']['comments'].'</a></li>
                                        <li'.(($params['active'] == 'ratings')       ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['ratings']).'" role="tab">'.$params['tabs']['ratings'].'</a></li>
                                        <li'.(($params['active'] == 'resources')     ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['resources']).'" role="tab">'.$params['tabs']['resources'].'</a></li>
                                    </ul>';
                break;

            case 'default':
                $tab_div_open   = '';
                $tab_div_close  = '';
                $tabs           = '';
                $tab_content    = '';
                $main_heading   = '';
                $panel_heading  = ' <div class="panel-heading">
                                        <div class="panel-title">
                                            <h2><span class="fa fa-'.$params['icon'].'"></span> '.str_capitalize($params['title']).'</h2>
                                        </div>
                                    </div>';
                break;

            default:
                throw new bException(tr('storage_ui_panel_header(): Unknown header_type ":type" specified', array(':type' => $params['header_type'])), 'unknown');
        }

        $html  = '  <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            '.$main_heading.'
                            <div class="panel panel-default'.$tabs.'">
                                '.$panel_heading.'
                                <div class="panel-body'.$tab_content.'">
                                    '.html_flash($params['html_flash_class']).'
                                    '.$tab_div_open.'
                                        <div class="table-responsive">';

        return $html;

    }catch(Exception $e){
        throw new bException('storage_ui_panel_header(): Failed', $e);
    }
}
?>
