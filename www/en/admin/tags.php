<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('admin');

/*
 * Execute an action
 */
if(!empty($_POST['docreate'])){
    /*
     * Create a new tag
     */
    try{
        load_libs('seo');

        sql_query('INSERT INTO `tags` (`addedby`, `status`, `name`, `seoname`, `description`)
                   VALUES             (:addedby , :status , :name , :seoname , :description )',

                   array(':addedby'     => $_SESSION['user']['id'],
                         ':status'      => NULL,
                         ':name'        => $_POST['name'],
                         ':seoname'     => seo_generate_unique_name($_POST['name'], 'tags'),
                         ':description' => $_POST['description']));

        if(!sql_insert_id()){
            throw new bException('The database insert returned no id', 'noinsertid');
        }

        html_flash_set(tr('The tag "'.str_log($_POST['name']).'" has been added'), 'success');
        redirect(domain('/admin/tags.php'));

    }catch(Exception $e){
        html_flash_set($e, 'error');
    }

}elseif(!empty($_POST['doupdate'])){
    /*
     * Update the specified tag
     */
    try{
        load_libs('seo,validate');

        // Validate input
        $v = new validate_form($_POST, 'id,name,description');

        $v->isNotEmpty ($_POST['name']       , tr('Please provide your username or email address'));
        $v->isNotEmpty ($_POST['description'], tr('Please provide a password'));

        $v->hasMinChars($_POST['name']       , 3 , tr('Please ensure that the tag name has a minimum of 3 characters'));
        $v->hasMinChars($_POST['description'], 20, tr('Please ensure that the description has a minimum of 20 characters'));

        if(empty($_POST['id'])){
            html_flash_set(tr('Can not update, no id specified'), 'error');
            redirect(domain('/admin/tags.php'));
        }

        $r = sql_query('UPDATE `tags`

                        SET    `modifiedby`  = :modifiedby,
                               `modifiedon`  = NOW(),
                               `name`        = :name,
                               `seoname`     = :seoname,
                               `description` = :description

                        WHERE  `id`          = :id',

                        array(':id'          => $_POST['id'],
                              ':modifiedby'  => $_SESSION['user']['id'],
                              ':name'        => $_POST['name'],
                              ':seoname'     => seo_generate_unique_name($_POST['name'], 'tags', $_POST['id']),
                              ':description' => $_POST['description']));

        if(!$r->rowCount()){
            throw new bException('The database query returned no updated entries', 'noaffectedrows');
        }

        html_flash_set(tr('The tag "'.str_log($_POST['name']).'" has been updated'), 'success');
        redirect(domain('/admin/tags.php'));

    }catch(Exception $e){
        html_flash_set($e, 'error');
    }

}elseif(!empty($_POST['setstatus'])){
    /*
     * Delete selected tags
     */
    try{
        $in = sql_in(isset_get($_POST['id']));

        switch($_POST['setstatus']){
            case 'erased':
                $r = sql_query('DELETE FROM `tags` WHERE `id` IN ('.str_force(array_keys($in), ',').')', $in);
                html_flash_set('Erased "'.$r->rowCount().'" tags', 'success');
                break;

            case 'deleted':
                $r = sql_query('UPDATE `tags` SET `status` = "deleted" WHERE `id` IN ('.str_force(array_keys($in), ',').')', $in);
                html_flash_set('Deleted "'.$r->rowCount().'" tags', 'success');
                break;

            case 'undeleted':
                $r = sql_query('UPDATE `tags` SET `status` = NULL WHERE `id` IN ('.str_force(array_keys($in), ',').')', $in);
                html_flash_set('Deleted "'.$r->rowCount().'" tags', 'success');
                break;

            default:
                throw new bException('Unknown status action "'.str_log($_POST['setstatus']).'"', 'unknown');
        }

    }catch(Exception $e){
        if($e->getCode() === 'empty'){
            html_flash_set(tr('No tags selected'), 'error');

        }else{
            html_flash_set($e, 'error');
        }
    }
}



/*
 * Show a form perhaps?
 */
$html = html_flash();

$status_filter = session_request_register('status', 'deleted');

if(isset_get($_GET['action']) == 'create'){
    /*
     * Show the form to create a new tag
     */
    $html .= '  <form id="tag" name="tag" action="'.domain('/admin/tags.php').'" method="post">
                    <fieldset>
                        <legend>'.tr('Add a new tag').'</legend>
                        <ul class="form">
                            <li><label for="name">'.tr('Name').'</label><input type="text" name="name" id="name" value="'.isset_get($_POST['name'], value('word')).'" maxlength="64"></li>
                            <li><label for="description">'.tr('Description').'</label><input type="text" name="description" id="description" value="'.isset_get($_POST['description'], value('words')).'" maxlength="255"></li>
                        </ul>
                        <input type="submit" name="docreate" id="docreate" value="'.tr('Create').'">
                        <a class="button submit" href="'.domain('/admin/tags.php').'">'.tr('Back').'</a>
                    </fieldset>
                </form>';

}elseif(!empty($_GET['tag'])){
    /*
     * Edit specified tag
     */
    if(!$tag = sql_get('SELECT * FROM `tags` WHERE `seoname` = :seoname', array(':seoname' => $_GET['tag']))){
        html_flash_set(tr('Specified tag "'.str_log($_GET['tag']).'" does not exist'), 'error');
        redirect(domain('/admin/tags.php'));
    }

    $html .= '  <form id="tag" name="tag" action="'.domain('/admin/tags.php?tag='.$tag['seoname']).'" method="post">
                    <fieldset>
                        <legend>'.tr('Update tag "'.$tag['name'].'"').'</legend>
                        <ul class="form">
                            <li><label for="name">'.tr('Name').'</label><input type="text" name="name" id="name" value="'.isset_get($_POST['name'], $tag['name']).'" maxlength="64"></li>
                            <li><label for="description">'.tr('Description').'</label><input type="text" name="description" id="description" value="'.isset_get($_POST['description'], $tag['description']).'" maxlength="255"></li>
                        </ul>
                        <input type="hidden" name="id" id="id" value="'.cfi($tag['id']).'">
                        <input type="submit" name="doupdate" id="doupdate" value="'.tr('Update').'">
                        <a class="button submit" href="'.domain('/admin/tags.php').'">'.tr('Back').'</a>
                    </fieldset>
                </form>';

}else{
    $html .= '<form method="post" action="'.domain('/admin/tags.php').'">';

    $query = 'SELECT `id`, `name`, `seoname`, `status`, `description` FROM `tags`';

    switch($status_filter){
        case 'deleted':
            $status_options = array('undeleted' => tr('Undelete'),
                                    'erased'    => tr('Erase'));

            $query .= ' WHERE `status` = "deleted"';
            break;

        case '':
            // FALLTHROUGH
        default:
            /*
             * Any wrong filter will just filter normal
             */
            $status_options = array('deleted' => tr('Delete'));

            $query .= ' WHERE `status` IS NULL';
    }

    $tags  = sql_list($query);


    if(empty($tags)){
        if($status_filter == 'deleted'){
            $html .= '<p>'.tr('No tags have been deleted yet').'</p>';

        }else{
            $html .= '<p>'.tr('No tags have been defined yet').'</p>';
        }

    }else{
        $html .= '  <table class="link select">
                        <thead>
                            <tr><td class="select"><input type="checkbox" name="id[]" class="all"></td><td>'.tr('Name').'</td><td>'.tr('Status').'</td><td>'.tr('Description').'</td></tr>
                        </thead>';

        foreach($tags as $id => $tag){
            $a = '<a href="'.domain('/admin/tags.php?tag='.$tag['seoname']).'">';

            $html .= '  <tr>
                            <td class="select"><input type="checkbox" name="id[]" id="tag_'.$tag['name'].'" value="'.$id.'"'.html_checked(isset_get($_POST['id']), $id).'></td>
                            <td>'.$a.$tag['name'].'</a></td>
                            <td>'.$a.status($tag['status']).'</a></td>
                            <td>'.$a.str_truncate($tag['description'], 100).'</a></td>
                        </tr>';
        }
    }

    $html .= '  <tr>
                    <td colspan="4">
                        <a class="button" href="'.domain('/admin/tags.php?action=create').'">'.tr('Add').'</a> '.
                        html_status_select(array('selected'   => null,
                                                 'name'       => 'setstatus',
                                                 'none'       => tr('Bulk action'),
                                                 'autosubmit' => true,
                                                 'resource'   => $status_options)).' '.

                        html_status_select(array('name'       => 'status',
                                                 'selected'   => $status_filter,
                                                 'autosubmit' => true,
                                                 'none'       => tr('Filter by'),
                                                 'resource'   => array('deleted' => tr('Show Deleted'),
                                                                       ''        => tr('Show Normal')))).'
                    </td>
                </tr>
            </table></form>';

}

echo admin_start(tr('Tag management')).
     $html.
     admin_end();
?>
