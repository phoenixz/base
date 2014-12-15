<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('user');

$selected = isset_get($_GET['blog']);

if($selected){
    $selected_blog = sql_get('SELECT * FROM `blogs` WHERE `seoname` = :seoname', array(':seoname' => $selected));

    if($selected_blog){
        /*
         *
         */
        switch(isset_get($_POST['doblogaction'])){
            case 'modify_blog':
                redirect(domain('/admin/blog.php?blog='.$selected_blog['seoname']));

            case 'manage_categories':
                redirect(domain('/admin/blogs_categories.php?blog='.$selected_blog['seoname']));

            case 'manage_posts':
                redirect(domain('/admin/blogs_posts.php?blog='.$selected_blog['seoname']));
        }
    }
}



/*
 *
 */
switch(isset_get($_POST['doaction'])){
    case tr('create'):
        redirect('/admin/blog.php');

    case tr('Delete'):
        try{
            /*
             * Delete the specified blogs
             */
            if(empty($_POST['id'])){
                throw new bException('No blogs selected to delete', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('UPDATE `blogs`
                            SET    `status` = "deleted"
                            WHERE  `status` IS NULL AND `id` IN ('.implode(', ', array_keys($list)).')',

                       $list);

            if($r->rowCount()){
                html_flash_set(tr('Deleted %count% blogs', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no blogs to delete'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to delete blogs because "%message%"', array('%message%' => $e->getMessage())), 'error');
        }

        break;

    case tr('Undelete'):
        try{
            /*
             * Delete the specified blogs
             */
            if(empty($_POST['id'])){
                throw new bException('No blogs selected to undelete', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('UPDATE `blogs`
                            SET    `status` = NULL
                            WHERE  `status` = "deleted" AND `id` IN ('.implode(', ', array_keys($list)).')',

                       $list);

            if($r->rowCount()){
                html_flash_set(tr('Undeleted %count% blogs', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no blogs to undelete'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to undelete blogs because "%message%"', array('%message%' => $e->getMessage())), 'error');
        }

        break;

    case tr('Erase'):
        try{
            /*
             * Delete the specified blogs
             */
            if(empty($_POST['id'])){
                throw new bException('No blogs selected to erase', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('DELETE FROM `blogs` WHERE `status` = "deleted" AND `id` IN ('.implode(', ', array_keys($list)).')', $list);

            if($r->rowCount()){
                html_flash_set(tr('Erased %count% blogs', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no blogs to erase'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to erase blogs because "%message%"', array('%message%' => $e->getMessage())), 'error');
        }
}



$filters = array();



/*
 * Do we have view filters?
 */
switch (isset_get($_GET['view'])){
    case 'all':
        $title     = '<h2 class="panel-title">'.tr('All blogs').'</h2>';

        $actions   = array('name'       => 'doaction',
                           'none'       => tr('Action'),
                           'resource'   => array('create'   => tr('Create'),
                                                 'delete'   => tr('Delete'),
                                                 'undelete' => tr('Undelete')),
                           'autosubmit' => true);
        break;

    case 'deleted':
        $title     = '<h2 class="panel-title">'.tr('Deleted blogs').'</h2>';

        $filters[] = ' `blogs`.`status` = "deleted" ';

        $actions   = array('name'       => 'doaction',
                           'none'       => tr('Action'),
                           'resource'   => array('undelete' => tr('Undelete')),
                           'autosubmit' => true);
        break;

    case '':
        // FALLTHROUGH
    default:
        $title     = '<h2 class="panel-title">'.tr('Blogs').'</h2>';

        $filters[] = ' `blogs`.`status` IS NULL ';

        $actions   = array('name'       => 'doaction',
                           'none'       => tr('Action'),
                           'resource'   => array('create' => tr('Create'),
                                                 'delete' => tr('Delete')),
                           'autosubmit' => true);
        break;
}



/*
 *
 */
$limit = 50;

$view  = array('name'       => 'view',
               'class'      => 'filter form-control mb-md',
               'none'       => tr('View'),
               'selected'   => isset_get($_GET['view']),
               'resource'   => array(''        => tr('Active'),
                                     'deleted' => tr('Deleted'),
                                     'empty'   => tr('Empty'),
                                     'all'     => tr('All')),
               'autosubmit' => true);

$query = 'SELECT    `blogs`.`id`           AS blog_id,
                    `blogs`.`createdon`    AS blog_createdon,
                    `blogs`.`status`       AS blog_status,
                    `blogs`.`name`         AS blog_name,
                    `blogs`.`seoname`      AS blog_seoname,
                    `blogs`.`slogan`       AS blog_slogan,

                    `users`.`name`,
                    `users`.`email`,
                    `users`.`username`

          FROM      `blogs`

          LEFT JOIN `users`
          ON        `users`.`id` = `blogs`.`createdby`';



/*
 * Do we have a generic filter?
 */
if(!empty($_GET['filter'])){
    $filters[] = ' (`users`.`name` LIKE :name OR `leaders`.`name` LIKE :leader OR `users`.`code` LIKE :code OR `users`.`phones` LIKE :phone) ';

    $execute[':name']   = '%'.$_GET['filter'].'%';
    $execute[':leader'] = '%'.$_GET['filter'].'%';
    $execute[':code']   = '%'.$_GET['filter'].'%';
    $execute[':phone']  = '%'.$_GET['filter'].'%';
}


/*
 * Add filters to the query
 */
if(!empty($filters)){
    $query .= ' WHERE '.implode(' AND ', $filters);
}



$r = sql_query($query, isset_get($execute));



/*
 *
 */
$html = '   <div class="row">
                <div class="col-md-12">
                    <section class="panel">
                        <header class="panel-heading">
                            '.$title.'
                            <p>
                                '.html_flash().'
                                <div class="form-group">
                                    <div class="col-sm-8">
                                        <form action="'.domain(true).'" method="get">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    '.html_select($view).'
                                                </div>
                                                <div class="visible-xs mb-md"></div>
                                                <div class="col-sm-4">
                                                    <div class="input-group input-group-icon">
                                                        <input type="text" class="filter form-control col-md-3" name="filter" value="'.isset_get($_GET['filter']).'" placeholder="Filter...">
                                                        <span class="input-group-addon">
                                                            <span class="icon"><i class="fa fa-search"></i></span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </p>
                        </header>
                        <form action="'.domain(true).'" method="post">
                            <div class="panel-body">';

if(!$r->rowCount()){
    $html .= '<p>'.tr('No blogs found with this filter').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="link select table mb-none table-striped table-hover">
                        <thead>
                            <th class="select"><input type="checkbox" name="id[]" class="all"></th>
                            <th>'.tr('Blog').'</th>
                            <th>'.tr('Created on').'</th>
                            <th>'.tr('Owner').'</th>
                            <th>'.tr('Slogan').'</th>
                            <th>'.tr('Status').'</th>
                        </thead>';

    while($blog = sql_fetch($r)){
        $html .= '<tr'.($selected == $blog['blog_seoname'] ? ' class="selected"' : '').'>
                      <td class="select"><input type="checkbox" name="id[]" value="'.$blog['blog_id'].'"></td>
                      <td><a href="'.domain('/admin/blogs.php?blog='.$blog['blog_seoname']).'">'.$blog['blog_name'].'</a></td>
                      <td><a href="'.domain('/admin/blogs.php?blog='.$blog['blog_seoname']).'">'.$blog['blog_createdon'].'</a></td>
                      <td><a href="'.domain('/admin/blogs.php?blog='.$blog['blog_seoname']).'">'.user_name($blog).'</a></td>
                      <td><a href="'.domain('/admin/blogs.php?blog='.$blog['blog_seoname']).'">'.$blog['blog_slogan'].'</a></td>
                      <td><a href="'.domain('/admin/blogs.php?blog='.$blog['blog_seoname']).'">'.status($blog['blog_status']).'</a></td>
                  </tr>';
    }

    $html .= '  </table>
            </div>';
}

$html .=                    (empty($actions) ? '' : html_select($actions)).'
                        </div>
                    </form>
                </section>
            </div>
        </div>';



/*
 * If a blog was selected, show it here
 */
if($selected){
    if(!$selected_blog){
        /*
         * Specified blog does not exist
         */
        $html .= '  <div class="row">
                        <div class="col-md-'.(empty($selected_blog['id']) ? '12' : '6').'">
                            <section class="panel">
                                <header class="panel-heading">
                                    <h2 class="panel-title">'.tr('Specified blog does not exist').'</h2>
                                </header>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label class="col-md-3 control-label" for="name">'.tr('Name').'</label>
                                        <div class="col-md-9">
                                            <p>'.tr('The specified blog "%blog%" does not exist', array('%blog%' => str_log($selected))).'</p>
                                        </div>
                                    </div>
                                </div>';

    }else{
        $count_all        = sql_get('SELECT COUNT(`id`) AS count FROM `blogs_posts`      WHERE                            `blogs_id` = :id', array(':id' => $selected_blog['id']), 'count');
        $count_published  = sql_get('SELECT COUNT(`id`) AS count FROM `blogs_posts`      WHERE `status` = "published" AND `blogs_id` = :id', array(':id' => $selected_blog['id']), 'count');
        $count_categories = sql_get('SELECT COUNT(`id`) AS count FROM `blogs_categories` WHERE                            `blogs_id` = :id', array(':id' => $selected_blog['id']), 'count');

        $categories       = sql_list('SELECT `name` FROM `blogs_categories` WHERE `blogs_id` = :id LIMIT 20', array(':id' => $selected_blog['id']));

        $blog_button      = array('name'       => 'doblogaction',
                                  'class'      => 'btn-primary',
                                  'none'       => tr('Action'),
                                  'resource'   => array('modify_blog'       => tr('Modify'),
                                                        'manage_categories' => tr('Manage categories'),
                                                        'manage_posts'      => tr('Manage posts')),
                                  'autosubmit' => true);

        $html .= '  <form id="blogdata" name="blogdata" action="'.domain('/admin/blogs.php?blog='.$selected_blog['seoname']).'" method="post">
                        <div class="row">
                            <div class="col-md-'.(empty($selected_blog['id']) ? '12' : '6').'">
                                <section class="panel">
                                    <header class="panel-heading">
                                        <h2 class="panel-title">'.tr('Blog information').'</h2>
                                        <p>'.html_flash().'</p>
                                    </header>
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <div class="col-md-9">
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('Name').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.$selected_blog['name'].'</p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('URL template').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.isset_get($selected_blog['url_template'], isset_get($_CONFIG['blogs']['url'])).'</p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('Created by').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.user_name($selected_blog['createdby']).'</p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('Slogan').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.$selected_blog['slogan'].'</p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('Keywords').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.$selected_blog['keywords'].'</p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('Description').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.$selected_blog['description'].'</p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('Posts').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.$count_all.'</p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('Published posts').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.$count_published.'</p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class=" col-md-3 control-label">'.tr('Categories').'</label>
                                                    <div class="col-lg-6">
                                                        <p class="form-control-static">'.$count_categories.($count_categories ? ' ('.str_force($categories).')' : '').'</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        '.html_select($blog_button).'
                                    </div>
                                </section>
                            </div>
                        </div>
                    </form>';
    }
}

$params = array('icon'        => 'fa-users',
                'title'       => tr('Blogs management'),
                'breadcrumbs' => array(tr('Rererrers'), tr('Manage')),
                'script'      => 'blogs.php');

echo ca_page($html, $params);
?>
