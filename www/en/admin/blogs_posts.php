<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('admin,editors,blogs,user');

array_params($params);
array_default($params, 'object'         , 'blog');
array_default($params, 'objects'        , 'blogs');
array_default($params, 'noblog'         , 'The specified %object% "'.isset_get($_GET['blog']).'" does not exist');
array_default($params, 'redirects'      , array('blogs'       => '/admin/blogs.php',
                                                'blogs_posts' => '/admin/blogs_posts.php'));



/*
 * Ensure we have an existing blog with access!
 */
if(empty($_GET['blog'])){
    html_flash_set(tr('Please select %object%', array('%object%' => $params['object'])), 'error');
    redirect($params['redirects']['blogs']);
}

if(!$blog = sql_get('SELECT `id`, `name`, `createdby`, `seoname` FROM `blogs` WHERE `seoname` = :seoname', array(':seoname' => $_GET['blog']))){
    html_flash_set(tr($params['noblog'], array('%object%' => $params['object'])), 'error');
    redirect($params['redirects']['blogs']);
}

if(($blog['createdby'] != $_SESSION['user']['id']) and !has_rights('god')) {
    html_flash_set(tr('You do not have access to the %object% "'.$blog['name'].'"', array('%object%' => $params['object'])), 'error');
    redirect($params['redirects']['blogs_posts']);
}



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

array_default($params, 'create_url'     , '/admin/blogs_post.php?blog=%blog%');
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
                                                                   'id'         => 'setstatus',
                                                                   'resource'   => $params['status_list'])));

array_default($params, 'subtitle'       , tr('All '.$params['object_name'].' for blog "%blog%"', '%blog%', '<a href="'.domain('/admin/blogs.php?blog='.$blog['seoname']).'">'.$blog['name'].'</a>'));
array_default($params, 'title'          , tr('Blog posts'));



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
                    throw new bException('No '.$params['object_name'].' selected to publish', 'notspecified');
                }

                $list = array_prefix(array_force($_POST['id']), ':id', true);

                if($blog){
                    $list[':blogs_id'] = $blog['id'];
                }

                $r = sql_query('UPDATE `blogs_posts`

                                SET    `status` = "published"

                                WHERE (`status` != "deleted")
                                AND `id` IN ('.implode(', ', array_keys($list)).')'.

                                ($blog ? ' AND `blogs_id` = :blogs_id' : ''),

                           $list);

                if(!$r->rowCount()){
                    throw new bException(tr('Found no %object% to publish', array('%object%' => $params['object_name'])), 'notfound');
                }

                html_flash_set(tr('Published "%count%" %object%', array('%count%' => $r->rowCount(), '%object%' => $params['object_name'])), 'success');

            }catch(Exception $e){
                html_flash_set(tr('Failed to publish %object% because "%message%"', array('%name%' => $params['object_name'] , '%message%' => $e->getMessage())), 'error');
            }

            redirect('self');
            break;

        case tr('deleted'):
            try{
                /*
                 * Delete the specified blog posts
                 */
                if(empty($_POST['id'])){
                    throw new bException('No '.$params['object_name'].' selected to delete', 'notspecified');
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

                if(!$r->rowCount()){
                    throw new bException(tr('Found no %object% to delete', array('%object%' => $params['object_name'])), 'notfound');
                }

                html_flash_set(tr('Deleted "%count%" %object%', array('%count%' => $r->rowCount(), '%object%' => $params['object_name'])), 'success');

            }catch(Exception $e){
                html_flash_set(tr('Failed to delete %object% because "%message%"', array('%message%' => $e->getMessage(), '%object%' => $params['object_name'])), 'error');
            }

            redirect('self');

        case tr('unpublished'):
            try{
                /*
                 * Delete the specified blog posts
                 */
                if(empty($_POST['id'])){
                    throw new bException('No '.$params['object_name'].' selected to undelete', 'notspecified');
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

                if(!$r->rowCount()){
                    throw new bException(tr('Found no %object% to undelete', array('%object%' => $params['object_name'])), 'notfound');
                }

                html_flash_set(tr('Undeleted "%count%" %object%', array('%count%' => $r->rowCount(), '%object%' => $params['object_name'])), 'success');

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
                    throw new bException('No '.$params['object_name'].' selected to erase', 'notspecified');
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

                if(!$r->rowCount()){
                    throw new bException(tr('Found no %object% to erase', array('%object%' => $params['object_name'])), 'notfound');
                }

                html_flash_set(tr('Erased "%count%" %object%', array('%count%' => $r->rowCount(), '%object%' => $params['object_name'])), 'success');

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
                    throw new bException(tr('No %object% selected for status change', array('%object%' => $params['object_name'].'(s)')), 'notspecified');
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

                if(!$r->rowCount()){
                    throw new bException(tr('Found no %object% to change status', array('%object%' => $params['object_name'])), 'notfound');
                }

                html_flash_set(tr('Updated status for "%count%" %object% to "%status%"', array('%count%' => $r->rowCount(), '%object%' => $params['object_name'].'(s)', '%status%' => status($_POST['status'], $params['status_list']))), 'success');

            }catch(Exception $e){
                html_flash_set(tr('Failed to set status "%status%" on %object% because "%message%"', array('%status%' => status($_POST['status'], $params['status_list']), '%message%' => $e->getMessage(), '%object%' => $params['object_name'].'(s)')), 'error');
            }

            redirect('self');
    }
}



/*
 *
 */
switch(isset_get($_POST['doaction'])){
    case tr('create'):
        redirect(str_replace('%blog%', $blog['seoname'], $params['create_url']));
}



/*
 *
 */
$actions   = array('name'       => 'doaction',
                   'none'       => tr('Action'),
                   'resource'   => array('create'   => tr('Create'),
                                         'delete'   => tr('Delete'),
                                         'undelete' => tr('Undelete')),
                   'autosubmit' => true);

$limit     = 50;

$view      = array('name'       => 'view',
                   'class'      => 'filter form-control mb-md',
                   'none'       => tr('View'),
                   'selected'   => isset_get($_POST['view']),
                   'resource'   => array(''        => tr('Active'),
                                         'deleted' => tr('Deleted'),
                                         'empty'   => tr('Empty'),
                                         'all'     => tr('All')),
                   'autosubmit' => true);



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



/*
 *
 */
$html = '   <form action="'.domain($params['form_action']).'" method="post">
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.$params['subtitle'].'</h2>
                                <p>
                                    '.html_flash().'
                                    <div class="form-group">
                                        <div class="col-sm-8">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    '.html_select($view).'
                                                </div>';

if(!empty($params['show_categories'])){
    /*
     * Show the categories select
     */
    $html .= '  <div class="col-sm-4">'.
                    blogs_categories_select(array('blogs_id'   => $blog['id'],
                                                  'class'      => 'form-control mb-md',
                                                  'selected'   => $category['seoname'],
                                                  'autosubmit' => true,
                                                  'parent'     => $params['show_categories'],
                                                  'name'       => 'category',
                                                  'none'       => $params['categories_none'])).
                '</div>';
}

if(!empty($params['show_groups'])){
    /*
     * Show the categories select
     */
    $html .= '  <div class="col-sm-4">'.
                    blogs_categories_select(array('blogs_id'   => $blog['id'],
                                                  'class'      => 'form-control mb-md',
                                                  'selected'   => $group['seoname'],
                                                  'autosubmit' => true,
                                                  'parent'     => $params['show_groups'],
                                                  'name'       => 'group',
                                                  'none'       => $params['groups_none']));
                '</div>';
}


$html .= '                                      <div class="visible-xs mb-md"></div>
                                                <div class="col-sm-4">
                                                    <div class="input-group input-group-icon">
                                                        <input type="text" class="filter form-control col-md-3" name="filter" value="'.isset_get($_POST['filter']).'" placeholder="Filter...">
                                                        <span class="input-group-addon">
                                                            <span class="icon"><i class="fa fa-search"></i></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </p>
                            </header>
                            <div class="panel-body">';

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
    $html .= '<table class="link select table mb-none table-striped table-hover">';

    foreach($params['columns'] as $column => $display){
        switch($column){
            case 'id':
                $html .= '<th class="select"><input type="checkbox" name="id[]" class="all"></th>';
                break;

            default:
                $html .= '<th>'.$display.'</th>';
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
                    throw new bException('Unknown column "'.str_log($column).'" specified');
            }
        }

        $html .= '</tr>';
    }

    $html .= '</table>';
}



/*
 *
 */
if(!empty($actions)){
    $html .= ' '.html_select($actions);
}

if($r->rowCount()){
    $html .= ' '.$params['status_set'];
}



/*
 *
 */
$html .= '              </div>
                    </section>
                </div>
            </div>
        </form>';

$params = array('icon'        => 'fa-users',
                'title'       => $params['title'],
                'breadcrumbs' => array(tr('Rererrers'), tr('Manage')),
                'script'      => $params['script']);

echo ca_page($html, $params);
?>