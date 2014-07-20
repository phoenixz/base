<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin');
load_libs('admin,editors,blogs,user');



/*
 * Ensure we have an existing blog with access!
 */
if(empty($_GET['blog'])){
    html_flash_set(tr('Please select a blog'), 'error');
    redirect('/admin/blogs.php');
}

if(!$blog = sql_get('SELECT `id`, `name`, `createdby`, `seoname` FROM `blogs` WHERE `seoname` = :seoname', array(':seoname' => $_GET['blog']))){
    html_flash_set(tr('The specified blog "'.$_GET['blog'].'" does not exist'), 'error');
    redirect('/admin/blogs.php');
}

if(($blog['createdby'] != $_SESSION['user']['id']) and !has_right('god')) {
    html_flash_set(tr('You do not have access to the blog "'.$blog['name'].'"'), 'error');
    redirect('/admin/blogs_posts.php?blog='.$blog['seoname']);
}



/*
 * Do we have a category to filter on?
 */
if(!empty($_POST['category'])){
    if(!$category = sql_get('SELECT `id`, `createdby`, `name`, `seoname` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname' => $_POST['category']))){
        $category = array('id'      => null,
                          'seoname' => '');
    }

}else{
    $category = array('id'      => null,
                      'seoname' => '');
}



/*
 * Do we have a group to filter on?
 */
if(!empty($_POST['group'])){
    if(!$group = sql_get('SELECT `id`, `createdby`, `name`, `seoname` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname' => $_POST['group']))){
        $group = array('id'      => null,
                       'seoname' => '');
    }

}else{
    $group = array('id'      => null,
                   'seoname' => '');
}



/*
 * Do we have a group to filter on?
 */
if(!empty($_POST['group'])){
    if(!$group = sql_get('SELECT `id`, `createdby`, `name`, `seoname` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname' => $_POST['category']))){
        $group = array('id'      => null,
                       'seoname' => '');
    }

}else{
    $group = array('id'      => null,
                   'seoname' => '');
}



/*
 * Set parameter defaults
 */
if(!isset($params)){
    $params = null;
}

array_params($params);
array_default($params, 'blogs_post'     , 'blogs_post.php?blog='.$blog['seoname'].'&');
array_default($params, 'categories_none', tr('Select a category'));
array_default($params, 'groups_none'    , tr('Select a group'));
array_default($params, 'class'          , 'blog');

array_default($params, 'columns'        , array('id'        => 'id',
                                                'name'      => tr('Name'),
                                                'category'  => tr('Category'),
                                                'status'    => tr('Status'),
                                                'createdby' => tr('Created by'),
                                                'createdon' => tr('Created on'),
                                                'views'     => tr('Views')));

array_default($params, 'filter_category', false);
array_default($params, 'filter_group'   , false);
array_default($params, 'form_action'    , '/admin/blogs_posts.php?blog='.$blog['seoname']);
array_default($params, 'object_name'    , 'blog posts');
array_default($params, 'script'         , 'blogs_posts.php?blog='.$blog['seoname']);
array_default($params, 'show_groups'    , false);
array_default($params, 'show_categories', false);
array_default($params, 'status_default' , 'unpublished');

array_default($params, 'status_list'    , array('deleted'     => tr('Deleted'),
                                                'erased'      => tr('Erased'),
                                                'unpublished' => tr('Unpublished'),
                                                'published'   => tr('Published')));

array_default($params, 'status_set'     , html_status_select(array('selected'   => isset_get($_POST['status'], ''),
                                                                   'none'       => tr('Set status'),
                                                                   'autosubmit' => true,
                                                                   'resource'   => $params['status_list'])));

array_default($params, 'subtitle'       , tr('All '.$params['object_name'].' for blog "%blog%"', '%blog%', '<a href="'.domain('/admin/blogs.php?blog='.$blog['seoname']).'">'.$blog['name'].'</a>'));
array_default($params, 'title'          , tr('Blog posts'));



/*
 * We have to set status?
 */
if(isset_get($_POST['status'])){
    if(empty($params['status_list'][$_POST['status']])){
        html_flash_set(tr('Unknown status "%status%" specified', '%status%', $_POST['status']), 'error');
        redirect('self');
    }

    switch($_POST['status']){
        case tr('published'):
            try{
                /*
                 * Publish the specified blog posts
                 */
                if(empty($_POST['id'])){
                    throw new lsException('No '.$params['object_name'].' selected to publish', 'notspecified');
                }

                $list = array_prefix(array_force($_POST['id']), ':id', true);

                if($blog){
                    $list[':blogs_id'] = $blog['id'];
                }

                $r = sql_query('UPDATE `blogs_posts`

                                SET    `status` = "published"

                                WHERE (`status` = "unpublished" OR `status` = "")
                                AND `id` IN ('.implode(', ', array_keys($list)).')'.

                                ($blog ? ' AND `blogs_id` = :blogs_id' : ''),

                           $list);

                if($r->rowCount()){
                    html_flash_set(tr('Published %count% '.$params['object_name'].'', '%count%', $r->rowCount()), 'success');

                }else{
                    throw new lsException(tr('Found no '.$params['object_name'].' to publish'), 'notfound');
                }

            }catch(Exception $e){
                html_flash_set(tr('Failed to publish '.$params['object_name'].' because "'.$e->getMessage().'"'), 'error');
            }

            redirect('self');
            break;

        case tr('deleted'):
            try{
                /*
                 * Delete the specified blog posts
                 */
                if(empty($_POST['id'])){
                    throw new lsException('No '.$params['object_name'].' selected to delete', 'notspecified');
                }

                $list = array_prefix(array_force($_POST['id']), ':id', true);

                if($blog){
                    $list[':blogs_id'] = $blog['id'];
                }

                $r = sql_query('UPDATE `blogs_posts`

                                SET    `status` = "deleted"

                                WHERE  `id` IN ('.implode(', ', array_keys($list)).')'.

                                ($blog ? ' AND `blogs_id` = :blogs_id' : ''),

                           $list);

                if($r->rowCount()){
                    html_flash_set(tr('Deleted %count% '.$params['object_name'].'', '%count%', $r->rowCount()), 'success');

                }else{
                    throw new lsException(tr('Found no '.$params['object_name'].' to delete'), 'notfound');
                }

            }catch(Exception $e){
                html_flash_set(tr('Failed to delete '.$params['object_name'].' because "'.$e->getMessage().'"'), 'error');
            }

            redirect('self');

        case tr('unpublished'):
            try{
                /*
                 * Delete the specified blog posts
                 */
                if(empty($_POST['id'])){
                    throw new lsException('No '.$params['object_name'].' selected to undelete', 'notspecified');
                }

                $list = array_prefix(array_force($_POST['id']), ':id', true);

                if($blog){
                    $list[':blogs_id'] = $blog['id'];
                }

                $r = sql_query('UPDATE `blogs_posts`

                                SET    `status` = "unpublished"

                                WHERE  `id` IN ('.implode(', ', array_keys($list)).')'.

                                ($blog ? ' AND `blogs_id` = :blogs_id' : ''),

                           $list);

                if($r->rowCount()){
                    html_flash_set(tr('Undeleted %count% '.$params['object_name'].'', '%count%', $r->rowCount()), 'success');

                }else{
                    throw new lsException(tr('Found no '.$params['object_name'].' to undelete'), 'notfound');
                }

            }catch(Exception $e){
                html_flash_set(tr('Failed to undelete '.$params['object_name'].' because "'.$e->getMessage().'"'), 'error');
            }

            redirect('self');

        case tr('erased'):
            try{
                /*
                 * Delete the specified blog posts
                 */
                if(empty($_POST['id'])){
                    throw new lsException('No '.$params['object_name'].' selected to erase', 'notspecified');
                }

                $list = array_prefix(array_force($_POST['id']), ':id', true);

                if($blog){
                    $list[':blogs_id'] = $blog['id'];
                }

                $r = sql_query('DELETE FROM `blogs_posts`

                                WHERE      (`status` = "deleted" OR `status` = "")

                                AND         `id` IN ('.implode(', ', array_keys($list)).')'.

                                ($blog ? ' AND `blogs_id` = :blogs_id' : ''),

                                $list);

                if($r->rowCount()){
                    html_flash_set(tr('Erased %count% '.$params['object_name'].'', '%count%', $r->rowCount()), 'success');

                }else{
                    throw new lsException(tr('Found no '.$params['object_name'].' to erase'), 'notfound');
                }

            }catch(Exception $e){
                html_flash_set(tr('Failed to erase '.$params['object_name'].' because "'.$e->getMessage().'"'), 'error');
            }

            redirect('self');

        default:
            try{
                /*
                 * Set status for the specified blog posts
                 */
                if(empty($_POST['id'])){
                    throw new lsException(tr('No %object% selected for status change', array('%object%' => $params['object_name'].'(s)')), 'notspecified');
                }

                $list = array_prefix(array_force($_POST['id']), ':id', true);

                if($blog){
                    $list[':blogs_id'] = $blog['id'];
                }

                $list[':status'] = $_POST['status'];

                $r = sql_query('UPDATE `blogs_posts`

                                SET    `status` = :status

                                WHERE  `id` IN ('.implode(', ', array_keys($list)).')'.

                                ($blog ? ' AND `blogs_id` = :blogs_id' : ''),

                           $list);

                if($r->rowCount()){
                    html_flash_set(tr('Updated status for %count% %object% to "'.status($_POST['status'], $params['status_list']).'"', array('%count%' => $r->rowCount(), '%object%' => $params['object_name'].'(s)')), 'success');

                }else{
                    throw new lsException(tr('Found no %object% to change status', array('%object%' => $params['object_name'].'(s)')), 'notfound');
                }

            }catch(Exception $e){
                html_flash_set(tr('Failed to set status "%status%" on %object% because "%message%"', array('%status%' => status($_POST['status'], $params['status_list']), '%message%' => $e->getMessage(), '%object%' => $params['object_name'].'(s)')), 'error');
            }

            redirect('self');
    }
}


/*
 * Show only blog posts from the specified blog
 */
$query = 'SELECT `blogs_posts`.`id`        AS post_id,
                 `blogs_posts`.`createdon` AS post_createdon,
                 `blogs_posts`.`seoname`   AS post_seoname,
                 `blogs_posts`.`name`      AS post_name,
                 `blogs_posts`.`category`  AS post_category,
                 `blogs_posts`.`group`     AS post_group,
                 `blogs_posts`.`status`    AS post_status,
                 `blogs_posts`.`views`     AS post_views,
                 `blogs_posts`.`priority`  AS post_priority,

                 `users`.`name`,
                 `users`.`email`,
                 `users`.`username`,

                 `blogs`.`name`            AS blog_name

          FROM   `blogs_posts`

          JOIN   `blogs`
          ON     `blogs`.`id` = `blogs_posts`.`blogs_id`

          JOIN   `users`
          ON     `users`.`id` = `blogs_posts`.`createdby`

          WHERE  `blogs_id` = :blogs_id';

$execute = array(':blogs_id' => $blog['id']);

if(!empty($params['filter_category']) and $category['id']){
    $query .= ' AND `blogs_posts`.`categories_id` = :categories_id';
    $execute[':categories_id'] = $category['id'];
}

if(!empty($params['filter_group']) and $group['id']){
    $query .= ' AND `blogs_posts`.`groups_id` = :groups_id';
    $execute[':groups_id'] = $group['id'];
}

$r = sql_query($query, $execute);

/*
 * Show blog info
 */
$html  = '  <div class="display">
                <h2>'.$params['subtitle'].'</h2>
                <form action="'.domain($params['form_action']).'" method="post">';

if(!empty($params['show_categories'])){
    /*
     * Show the categories select
     */
    $html .= ' '.blogs_categories_select(array('blogs_id'   => $blog['id'],
                                               'selected'   => $category['seoname'],
                                               'autosubmit' => true,
                                               'parent'     => $params['show_categories'],
                                               'name'       => 'category',
                                               'none'       => $params['categories_none']));
}

if(!empty($params['show_groups'])){
    /*
     * Show the categories select
     */
    $html .= ' '.blogs_categories_select(array('blogs_id'   => $blog['id'],
                                               'selected'   => $group['seoname'],
                                               'autosubmit' => true,
                                               'parent'     => $params['show_groups'],
                                               'name'       => 'group',
                                               'none'       => $params['groups_none']));
}

if(!$r->rowCount()){
    /*
     * There are no blog posts
     */
    if($blog){
        $html .= '<h3>The blog "'.str_log($blog['name']).'" has no posts yet</h3>';

    }else{
        $html .= '<h3>There are no blog posts yet</h3>';
    }

}else{

    $html .= '<table class="link select"><thead>';

    foreach($params['columns'] as $column => $display){
        switch($column){
            case 'id':
                $html .= '<td class="select"><input type="checkbox" name="id[]" class="all"></td>';
                break;

            default:
                $html .= '<td>'.$display.'</td>';
        }
    }

    $html .= '</thead>';

    while($post = sql_fetch($r)){
        $html .= '<tr class="'.$params['class'].' '.$post['post_status'].'">';

        foreach($params['columns'] as $column => $display){
            switch($column){
                case 'id':
                    $html .= '<td class="select"><input type="checkbox" name="id[]" value="'.$post['post_id'].'"></td>';
                    break;

                case '#id':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.$post['post_id'].'</a></td>';
                    break;

                case 'group':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.$post['post_group'].'</a></td>';
                    break;

                case 'name':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.$post['post_name'].'</a></td>';
                    break;

                case 'blog':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.$post['blog_name'].'</a></td>';
                    break;

                case 'category':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.$post['post_category'].'</a></td>';
                    break;

                case 'status':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.status($post['post_status'], $params['status_list']).'</a></td>';
                    break;

                case 'priority':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.str_capitalize(blogs_priority($post['post_priority'])).'</a></td>';
                    break;

                case 'createdon':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.$post['post_createdon'].'</a></td>';
                    break;

                case 'createdby':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.user_name($post).'</a></td>';
                    break;

                case 'views':
                    $html .= '<td><a href="'.domain('/admin/'.$params['blogs_post'].'post='.$post['post_seoname']).'">'.$post['post_views'].'</a></td>';
                    break;

                default:
                    throw new lsException('Unknown column "'.str_log($column).'" specified');
            }
        }

        $html .= '</tr>';
    }

    $html .= '</table>';
}

$html .= '<a class="button submit" href="'.domain('/admin/'.$params['blogs_post']).'">'.tr('Create').'</a> ';

if($r->rowCount()){
    $html .= $params['status_set'];
}

$html .= '</form>';

echo admin_page($html, array('title'  => $params['title'],
                             'script' => $params['script']));
?>
