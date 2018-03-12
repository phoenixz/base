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
function storage_ui_panel_header($params, $section, $page = null){
    try{
        array_ensure($params);
        array_ensure($params['files']);
        array_ensure($params['urls']);
        array_ensure($params['tabs']);
        array_ensure($params['labels']);

        array_default($params, 'header_type'     , 'tabs');
        array_default($params, 'html_flash_class', 'storage');
        array_default($params, 'form_action'     , 'post');
        array_default($params, 'icon'            , '');

        array_default($params['files'], 'section'        , 'storage-section');
        array_default($params['files'], 'documents'      , 'storage-documents');
        array_default($params['files'], 'image_documents', 'storage-image-documents');
        array_default($params['files'], 'categories'     , 'storage-categories');
        array_default($params['files'], 'files'          , 'storage-files');
        array_default($params['files'], 'configuration'  , 'storage-configuration');
        array_default($params['files'], 'comments'       , 'storage-comments');
        array_default($params['files'], 'keywords'       , 'storage-keywords');
        array_default($params['files'], 'key_values'     , 'storage-key-values');
        array_default($params['files'], 'resources'      , 'storage-resources');
        array_default($params['files'], 'ratings'        , 'storage-ratings');

        array_default($params['urls'], 'form'           , domain(true));
        array_default($params['urls'], 'create'         , domain('/'.$params['files']['section'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'section'        , domain('/'.$params['files']['section'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'documents'      , domain('/'.$params['files']['documents'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'image_documents', domain('/'.$params['files']['image_documents'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'categories'     , domain('/'.$params['files']['categories'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'files'          , domain('/'.$params['files']['files'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'configuration'  , domain('/'.$params['files']['configuration'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'comments'       , domain('/'.$params['files']['comments'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'keywords'       , domain('/'.$params['files']['keywords'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'key_values'     , domain('/'.$params['files']['key_values'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'resources'      , domain('/'.$params['files']['resources'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));
        array_default($params['urls'], 'ratings'        , domain('/'.$params['files']['ratings'].'.html?'.$params['seosection'].'='.$_GET[$params['seosection']]));

        array_default($params['labels'], 'title' , tr('Manage :objects', array(':objects' => $params['objects'])));
        array_default($params['labels'], 'filter', tr('Filter...'));

        array_default($params['tabs'], 'section'        , tr('Section'));
        array_default($params['tabs'], 'documents'      , tr('Documents'));
        array_default($params['tabs'], 'image_documents', tr('Image documents'));
        array_default($params['tabs'], 'categories'     , tr('Categories'));
        array_default($params['tabs'], 'files'          , tr('Files'));
        array_default($params['tabs'], 'configuration'  , tr('Configuration'));
        array_default($params['tabs'], 'comments'       , tr('Comments'));
        array_default($params['tabs'], 'keywords'       , tr('Keywords'));
        array_default($params['tabs'], 'key_values'     , tr('Key values'));
        array_default($params['tabs'], 'resources'      , tr('Resources'));
        array_default($params['tabs'], 'ratings'        , tr('Ratings'));


        switch($params['header_type']){
            case 'tabs':
                $tab_div_open   = '<div class="tab-pane active" id="tab-'.$params['icon'].'">';
                $tab_div_close  = '</div>';
                $tabs           = ' tabs';
                $tab_content    = ' tab-content';
                $main_heading   = ' <div class="panel-heading">
                                        <div class="panel-title">
                                            <h2><span class="fa fa-'.$params['icon'].'"></span> '.str_replace(':object', $section['name'], $params['labels']['title']).'</h2>
                                        </div>
                                    </div>';
                $panel_heading  = ' <ul class="nav nav-tabs" role="tablist">
                                        <li'.(($params['active'] == 'section')         ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['section']).'" role="tab">'.$params['tabs']['section'].'</a></li>
                                        <li'.(($params['active'] == 'configuration')   ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['files']).'" role="tab">'.$params['tabs']['configuration'].'</a></li>
                                        <li'.(($params['active'] == 'categories')      ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['categories']).'" role="tab">'.$params['tabs']['categories'].'</a></li>';

                if(empty($page)){
                    /*
                     * Link to pages table
                     */
                    $panel_heading .= ' <li'.(($params['active'] == 'documents')       ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['documents']).'" role="tab">'.$params['tabs']['documents'].'</a></li>
                                        <li'.(($params['active'] == 'image_documents') ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['image_documents']).'" role="tab">'.$params['tabs']['image_documents'].'</a></li>';

                }else{
                    /*
                     * Link to single page
                     */
                    $panel_heading .= ' <li'.(($params['active'] == 'documents')       ? ' class="active"' : '').'><a href="'.storage_url($params['urls']['document'], $section, $page).'" role="tab">'.$params['tabs']['documents'].'</a></li>
                                        <li'.(($params['active'] == 'image_documents') ? ' class="active"' : '').'><a href="'.storage_url($params['urls']['image_document'], $section, $page).'" role="tab">'.$params['tabs']['image_documents'].'</a></li>';
                }


                $panel_heading .= '     <li'.(($params['active'] == 'keywords')        ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['keywords']).'" role="tab">'.$params['tabs']['keywords'].'</a></li>
                                        <li'.(($params['active'] == 'key_values')      ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['key_values']).'" role="tab">'.$params['tabs']['key_values'].'</a></li>
                                        <li'.(($params['active'] == 'files')           ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['files']).'" role="tab">'.$params['tabs']['files'].'</a></li>
                                        <li'.(($params['active'] == 'comments')        ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['comments']).'" role="tab">'.$params['tabs']['comments'].'</a></li>
                                        <li'.(($params['active'] == 'ratings')         ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['ratings']).'" role="tab">'.$params['tabs']['ratings'].'</a></li>
                                        <li'.(($params['active'] == 'resources')       ? ' class="active"' : '').'><a href="'.str_replace(':'.$params['seosection'], $_GET[$params['seosection']], $params['urls']['resources']).'" role="tab">'.$params['tabs']['resources'].'</a></li>
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
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div id="result" class="hidden"></div>
                                        </div>
                                    </div>
                                    '.html_flash($params['html_flash_class']).'
                                    '.$tab_div_open.'
                                        <div class="table-responsive">';

        return $html;

    }catch(Exception $e){
        throw new bException('storage_ui_panel_header(): Failed', $e);
    }
}



/*
 * Process user actions for document page
 */
function storage_ui_process_dosubmit($params, $section, $page){
    try{
        switch(isset_get($_POST['dosubmit'])){
            case '':
                /*
                 * Do nothing
                 */
                break;

            case $params['buttons']['redetect_scanners']:
                $devices = scanimage_update_devices();
                html_flash_set(tr('Device detection successful, found ":count" device(s)', array(':count' => count($devices))), 'success', 'documents');
                redirect(domain(true));
                break;

            case $params['buttons']['create']:
                /*
                 * Create the document
                 */
                $page['_new'] = true;
                $page = storage_pages_update($page, $params);
                $url  = str_replace(':seoname', $page['seoname'], storage_url($params['urls']['create'], $section, $page));

                html_flash_set(log_database(tr('Created :labeldocument ":document"', array(':labeldocument' => $params['document'], ':document' => '<a href="'.$url.'">'.html_safe($page['name']).'</a>')), 'storage-documents/create'), 'success', 'documents');
                redirect($url);

            case $params['buttons']['update']:
                /*
                 * Update the document
                 */
                $page = storage_pages_update($page, $params);
                $url  = str_replace(':seoname', $page['seoname'], storage_url($params['urls']['update'], $section, $page));

                html_flash_set(log_database(tr('Updated :labeldocument ":document"', array(':labeldocument' => $params['document'], ':document' => '<a href="'.$url.'">'.html_safe($page['name']).'</a>')), 'storage-documents/update'), 'success', 'documents');
                redirect($url);

            default:
                /*
                 * Unknown action specified
                 */
                throw new bException(tr('storage_ui_process_dosubmit(): Unknown action ":action" specified', array(':action' => $_POST['dosubmit'])), 'warning/unknown');
        }

    }catch(Exception $e){
        html_flash_set($e, 'documents');
    }

    return $page;
}



/*
 * Get section data from database and $_POST
 */
function storage_ui_get_section($params){
    try{
        if(empty($_GET[$params['seosection']])){
            /*
             * No section available, we cannot do anything!
             */
            html_flash_set(tr('No :labelsection specified', array(':labelsection' => $params['section'])), 'warning', 400);
            page_show(400);

        }

        $section = storage_sections_get($_GET['section']);

        if(!$section or is_new($section)){
            html_flash_set(log_database(tr('Specified :labelsection ":section" does not exist', array(':labelsection' => $params['section'], ':section' => $_GET[$params['seosection']])), 'not-exist'), 'error', 404);
            page_show(404);
        }

        return $section;

    }catch(Exception $e){
        throw new bException(tr('storage_ui_get_section(): Failed'), $e);
    }
}



/*
 * Get the page for the UI
 */
function storage_ui_get_page($params, $section, $object){
    try{
        /*
         * Get page
         */
        if(empty($_GET[$params['seo'.$object]])){
            $page = storage_pages_get($section, null, true);

        }else{
            $page = storage_pages_get($section, $_GET[$params['seo'.$object]]);

            if(!$page){
                html_flash_set(log_database(tr('Specified :labeldocument ":document" does not exist', array(':labeldocument' => $params[$object], ':document' => $_GET[$params['seo'.$object]])), 'not-exist'), 'error', 404);
                page_show(404);
            }

            log_database(tr('View :labeldocument ":document"', array(':labeldocument' => $params[$object], ':document' => $page['name'])), 'storage-documents/view');
            meta_action($page['meta_id'], 'view');
        }

        $page = storage_ui_pages_merge($page, $_POST, $params);

        return $page;

    }catch(Exception $e){
        throw new bException(tr('storage_ui_get_page(): Failed'), $e);
    }
}



/*
 * Perform an SQL merge with $post_page element filtered by the available
 * elements specified in $params[labels]. Basically, this sql_merge() variant
 * will force all $post_page keys that have no label entries to be NULL, THEN it
 * will perform the sql_merge(). This way we can enforce that users cannot force
 * values, even thought they should not be able to be updated
 */
function storage_ui_pages_merge($db_page, $post_page, $params){
    try{
        if(empty($params['show']['body'])){
             unset($post_page['body']);
        }

        $keys = array('name',
                      'description',
                      'category1',
                      'category2',
                      'category3',
                      'assigned_to_id',
                      'status');

        foreach($keys as $key){
            if(empty($params['labels'][$key])){
                unset($post_page[$key]);
            }
        }

        return sql_merge($db_page, $post_page);

    }catch(Exception $e){
        throw new bException('storage_ui_pages_merge(): Failed', $e);
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
                                    <img rel="document-file" class="col-md-1 control-label" src="'.storage_file_url($icon, 'small').'" alt="'.html_safe('('.$icon['x'].' X '.$icon['y'].')').'" />
                                </a>
                            </div>
                        </td>
                        <td class="buttons">
                            <div>
                                <a class="col-md-5 btn btn-success storage-page photo up button">'.tr('Up').'</a>
                                <a class="col-md-5 btn btn-success storage-page photo down button">'.tr('Down').'</a>
                                <a class="col-md-5 btn btn-danger storage-page photo delete button">'.tr('Delete').'</a>
                            </div>
                        </td>
                        <td class="file_type">
                            <div>
                                '.html_select(array('name'     => 'file_status['.$icon['id'].']',
                                                    'class'    => 'btn storage-page photo type',
                                                    'extra'    => 'tabindex="'.++$tabindex.'"',
                                                    'selected' => $icon['type'],
                                                    'none'     => tr('Unspecified type'),
                                                    'resource' => null)).'
                            </div>
                        </td>
                        <td class="description">
                            <div>
                                <textarea class="storage-page photo description form-control" placeholder="'.tr('Description of this photo').'">'.$icon['description'].'</textarea>
                            </div>
                        </td>
                    </tr>';

        return $html;

    }catch(Exception $e){
        throw new bException('storage_ui_file(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available storage categories
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

        if(empty($params['sections_id'])){
            /*
             * Categories work per section, so without a section we cannot show
             * categories
             */
            $params['resource'] = null;

        }else{
            $execute = array(':sections_id' => $params['sections_id']);

            $query   = 'SELECT  '.$params['column'].' AS id,
                                `storage_categories`.`name`

                        FROM    `storage_categories` ';

            $join    = '';

            $where   = 'WHERE   `storage_categories`.`sections_id` = :sections_id
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
