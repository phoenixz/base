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
function storage_ui_panel_header($params, $section){
    try{
        array_ensure($params);
        array_ensure($params['files']);
        array_ensure($params['urls']);
        array_ensure($params['tabs']);
        array_ensure($params['labels']);

        array_default($params, 'header_type'     , 'tabs');
        array_default($params, 'html_flash_class', 'storage');
        array_default($params, 'form_action'     , 'post');

        array_default($params['files'], 'section'      , 'storage-section');
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

        array_default($params['tabs'], 'section'      , tr('Section'));
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
                                        <li'.(($params['active'] == 'section')       ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['section']).'" role="tab">'.$params['tabs']['section'].'</a></li>
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
                                            <h2><span class="fa fa-'.$params['icon'].'"></span> '.str_capitalize($params['labels']['title']).'</h2>
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



/*
 *
 */
// :TODO: This function is not finished yet!!
function storage_ui_icon($file){
    try{
        load_libs('image');

        $icon      = $file;
        $filename  = $file['filename'];
        $data      = image_info($filename);
        $icon['x'] = $data['x'];
        $icon['y'] = $data['y'];

        return $icon;

    }catch(Exception $e){
        switch($e->getCode()){
            case 'not-exist':
                /*
                 * Show a "not exist" icon
                 */
                $icon['x'] = 100;
                $icon['y'] = 100;

                return $icon;

            case 'not-file':
                /*
                 * Show an "invalid file" icon
                 */
                $icon['x'] = 100;
                $icon['y'] = 100;

                return $icon;
        }

        throw new bException('storage_ui_icon(): Failed', $e);
    }
}



/*
 *
 */
function storage_ui_file($file, $tabindex = 0){
    try{
        load_libs('storage-files');

        $icon = storage_ui_icon($file);

        $html = '   <tr class="form-group photo" id="file'.$file['id'].'">
                        <td class="file">
                            <div>
                                <a target="_blank" class="fancy" href="'.storage_file_url($icon, 'icon').'">
                                    <img rel="blog-page" class="col-md-1 control-label" src="'.storage_file_url($icon, 'small').'" alt="'.html_safe('('.$icon['x'].' X '.$icon['y'].')').'" />
                                </a>
                            </div>
                        </td>
                        <td class="buttons">
                            <div>
                                <a class="col-md-5 btn btn-success blogpost photo up button">'.tr('Up').'</a>
                                <a class="col-md-5 btn btn-success blogpost photo down button">'.tr('Down').'</a>
                                <a class="col-md-5 btn btn-danger blogpost photo delete button">'.tr('Delete').'</a>
                            </div>
                        </td>
                        <td class="file_type">
                            <div>
                                '.html_select(array('name'     => 'file_status['.$icon['id'].']',
                                                    'class'    => 'btn blogpost photo type',
                                                    'extra'    => 'tabindex="'.++$tabindex.'"',
                                                    'selected' => $icon['type'],
                                                    'none'     => tr('Unspecified type'),
                                                    'resource' => null)).'
                            </div>
                        </td>
                        <td class="description">
                            <div>
                                <textarea class="blogpost photo description form-control" placeholder="'.tr('Description of this photo').'">'.$icon['description'].'</textarea>
                            </div>
                        </td>
                    </tr>';

        return $html;

    }catch(Exception $e){
        throw new bException('storage_ui_file(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available blog categories
 */
function storage_ui_categories_select($params) {
    try{
        array_ensure($params);
        array_default($params, 'number'      , 1);
        array_default($params, 'selected'    , 0);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'name'        , 'seocategory1');
        array_default($params, 'column'      , '`storage_categories`.`seoname`');
        array_default($params, 'none'        , tr('Select a category'));
        array_default($params, 'empty'       , tr('No categories available'));
        array_default($params, 'option_class', '');
        array_default($params, 'right'       , false);
        array_default($params, 'parent'      , false);
        array_default($params, 'filter'      , array());

        array_ensure($params['labels']);
        array_default($params['labels'], 'select_category', tr('Select a category'));
        array_default($params['labels'], 'empty_category' , tr('Select a category'));
        array_default($params['labels'], 'none_category'  , tr('Select a category'));

        if(empty($params['blogs_id'])){
            /*
             * Categories work per blog, so without a blog we cannot show
             * categories
             */
            $params['resource'] = null;

        }else{
            $execute = array(':blogs_id' => $params['blogs_id']);

            $query   = 'SELECT  '.$params['column'].' AS id,
                                `storage_categories`.`name`

                        FROM    `storage_categories` ';

            $join    = '';

            $where   = 'WHERE   `storage_categories`.`blogs_id` = :blogs_id
                        AND     `storage_categories`.`status`   IS NULL ';

            if($params['right']){
                /*
                 * User must have right of the category to be able to see it
                 */
                $join .= ' JOIN `users_rights`
                           ON   `users_rights`.`users_id` = :users_id
                           AND (`users_rights`.`name`     = `storage_categories`.`seoname`
                           OR   `users_rights`.`name`     = "god") ';

                $execute[':users_id'] = isset_get($_SESSION['user']['id']);
            }

            if($params['parent']){
                $join .= ' JOIN `storage_categories` AS parents
                           ON   `parents`.`seoname` = :parent
                           AND  `parents`.`id`      = `storage_categories`.`parents_id` ';

                $execute[':parent'] = $params['parent'];

            }elseif($params['parent'] === null){
                $where .= ' AND  `storage_categories`.`parents_id` IS NULL ';

            }elseif($params['parent'] === false){
                /*
                 * Don't filter for any parent
                 */

            }else{
                $where .= ' AND `storage_categories`.`parents_id` = 0 ';

            }

            /*
             * Filter specified values.
             */
            foreach($params['filter'] as $key => $value){
                if(!$value) continue;

                $where            .= ' AND `'.$key.'` != :'.$key.' ';
                $execute[':'.$key] = $value;
            }

            $params['resource'] = sql_query($query.$join.$where.' ORDER BY `name` ASC', $execute);
        }

        return html_select($params);

    }catch(Exception $e){
        throw new bException('storage_ui_categories_select(): Failed', $e);
    }
}
?>
