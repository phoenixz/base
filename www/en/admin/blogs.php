<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin');
load_libs('admin,user');

$selected = isset_get($_GET['blog']);

/*
 * We have to do something?
 */
switch(isset_get($_POST['doaction'])){
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
            html_flash_set(tr('Failed to delete blogs because "'.$e->getMessage().'"'), 'error');
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
            html_flash_set(tr('Failed to undelete blogs because "'.$e->getMessage().'"'), 'error');
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
            html_flash_set(tr('Failed to erase blogs because "'.$e->getMessage().'"'), 'error');
        }
}

$html = '<h2>'.tr('Available blogs').'</h2>
<div class="display">
    <form action="'.domain('/admin/blogs.php').'" method="post">
        <table class="link select">';

$r = sql_query('SELECT    `blogs`.`id`           AS blog_id,
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
                ON        `users`.`id` = `blogs`.`createdby`');

if(!$r->rowCount()){
    $html .= '<tr><td>'.tr('There are no blogs yet').'</td></tr>';

}else{
    $html .= '<thead><td class="select"><input type="checkbox" name="id[]" class="all"></td><td>'.tr('Blog').'</td><td>'.tr('Created on').'</td><td>'.tr('Owner').'</td><td>'.tr('Slogan').'</td><td>'.tr('Status').'</td></thead>';

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
}

$html .= '</table>
          <a class="button submit" href="'.domain('/admin/blog.php').'">'.tr('Create').'</a> ';

if($r->rowCount()){
    $html .= '<input type="submit" name="doaction" value="'.tr('Delete').'">
              <input type="submit" name="doaction" value="'.tr('Undelete').'">
              <input type="submit" name="doaction" value="'.tr('Erase').'">';
}

$html .= '</form>
        </div>';

/*
 * If a blog was selected, show it here
 */
if($selected){
    if(!$blog = sql_get('SELECT * FROM `blogs` WHERE `seoname` = :seoname', array(':seoname' => $_GET['blog']))){
        /*
         * Specified blog does not exist
         */
        $html .= '<hr>
                  <div class="blog">
                      <h2>The specified blog "'.str_log($_GET['blog']).'" does not exist</h2>
                  </div>';

    }else{
        $count_all        = sql_get('SELECT COUNT(`id`) AS count FROM `blogs_posts`      WHERE                            `blogs_id` = :id', array(':id' => $blog['id']), 'count');
        $count_published  = sql_get('SELECT COUNT(`id`) AS count FROM `blogs_posts`      WHERE `status` = "published" AND `blogs_id` = :id', array(':id' => $blog['id']), 'count');
        $count_categories = sql_get('SELECT COUNT(`id`) AS count FROM `blogs_categories` WHERE                            `blogs_id` = :id', array(':id' => $blog['id']), 'count');

        $categories       = sql_list('SELECT `name` FROM `blogs_categories` WHERE `blogs_id` = :id LIMIT 20', array(':id' => $blog['id']));

        $html .= '<hr>
                  <div class="blog">
                    <form>
                        <fieldset>
                            <legend>Blog "'.str_log($blog['name']).'"</legend>
                            <ul class="form display">
                                <li><label>'.tr('Name').'</label><p>'.$blog['name'].'</p></li>
                                <li><label>'.tr('Created by').'</label><p>'.user_name($blog['createdby']).'</p></li>
                                <li><label>'.tr('Slogan').'</label><p>'.$blog['slogan'].'</li>
                                <li><label>'.tr('Keywords').'</label><p>'.$blog['keywords'].'</p></li>
                                <li><label>'.tr('Description').'</label><p>'.$blog['description'].'</p></li>
                                <li><label>'.tr('Posts').'</label> '.$count_all.'</li>
                                <li><label>'.tr('Published posts').'</label> '.$count_published.'</li>
                                <li><label>'.tr('Categories').'</label> '.$count_categories.($count_categories ? ' ('.str_force($categories).')' : '').'</li>
                            </ul>
                            <a class="button" href="'.domain('/admin/blog.php?blog='.$blog['seoname']).'">'.tr('Modify').'</a>
                            <a class="button" href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname']).'">'.tr('Manage categories').'</a>
                            <a class="button" href="'.domain('/admin/blogs_posts.php?blog='.$blog['seoname']).'">'.tr('Manage posts').'</a>'.
                       '</fieldset>
                    </form>
                </div>';
    }
}

echo admin_page($html, array('title'  => tr('Blogs management'),
                             'script' => 'blogs.php'));
?>
