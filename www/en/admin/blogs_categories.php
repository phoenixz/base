<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin');
load_libs('admin,user,blogs,validate');

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

$action   = isset_get($_GET['doaction']);
$selected = isset_get($_GET['category']);

/*
 * Are we going to have to display a category
 */
if($action == 'add'){
    /*
     * We're going to add a new category
     */
    $category = array();

}elseif($selected){
    if(!$category = sql_get('SELECT * FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname' => $selected))){
        html_flash_set(tr('The specified category "'.str_log($selected).'" does not exist in this blog'), 'error');
        $selected = null;
    }
}


/*
 * We have to do something?
 */
switch(isset_get($_POST['doaction'])){
    case tr('Create'):
        try{
            load_libs('seo');

            /*
             * Create the specified category
             */
            $category = $_POST;

            // Validate input
            $v = new validate_form($category, 'name,keywords,description');

            $v->is_not_empty ($category['name']       , tr('Please provide the name of your category'));
            $v->is_not_empty ($category['keywords']   , tr('Please provide the keywords for your category'));
            $v->is_not_empty ($category['description'], tr('Please provide the description for your category'));

            $v->has_min_chars($category['name']       ,  3, tr('Please ensure that the name has a minimum of 3 characters'));
            $v->has_min_chars($category['keywords']   ,  8, tr('Please ensure that the keywords have a minimum of 8 characters'));
            $v->has_min_chars($category['description'], 32, tr('Please ensure that the description has a minimum of 32 characters'));

            if(empty($category['parent'])){
                $category['parents_id'] = null;

            }else{
                /*
                 * Make sure the parent category is inside this blog
                 */
                if(!$parent = sql_get('SELECT `id`, `blogs_id` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname' => $category['parent']))){
                    /*
                     * Specified parent does not exist at all
                     */
                    throw new lsException('The specified parent category does not exist', 'notexist');
                }

                if($parent['blogs_id'] != $blog['id']){
                    /*
                     * Specified parent does not exist inside this blog
                     */
                    throw new lsException('The specified parent category does not exist in this blog', 'notexist');
                }

                $category['parents_id'] = $parent['id'];
            }

            if(!$v->is_valid()) {
               throw new lsException(str_force($v->get_errors(), ', '), 'errors');

            }

            if(sql_get('SELECT `id` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `name` = :name', array(':blogs_id' => $blog['id'], ':name' => $category['name']), 'id')){
                /*
                 * A category with this name already exists
                 */
                throw new lsException(tr('A category with the name "%category%" already exists in blog "%blog%"', array('%category%', '%blog%'), array($category['name'], $blog['name'])), 'exists');

            }

            if(sql_get('SELECT `id` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `name` = :name', 'id', array(':blogs_id' => $blog['id'], ':name' => $category['name']))){
                throw new lsException('A category with the name "'.str_log($category['name']).'" already exists', 'exists');
            }

            $category['seoname']     = seo_generate_unique_name($category['name'], 'blogs_categories', $category['id']);
            $category['keywords']    = blogs_clean_keywords($category['keywords']);
            $category['seokeywords'] = blogs_seo_keywords($category['keywords']);

            sql_query('INSERT INTO `blogs_categories` (`createdby`, `blogs_id`, `parents_id`, `name`, `seoname`, `keywords`, `seokeywords`, `description`)
                       VALUES                         (:createdby , :blogs_id , :parents_id , :name , :seoname , :keywords , :seokeywords , :description )',

                       array(':createdby'   => $_SESSION['user']['id'],
                             ':blogs_id'    => $blog['id'],
                             ':parents_id'  => $category['parents_id'],
                             ':name'        => $category['name'],
                             ':seoname'     => $category['seoname'],
                             ':keywords'    => $category['keywords'],
                             ':seokeywords' => $category['seokeywords'],
                             ':description' => $category['description']));

            html_flash_set('The category "'.str_log($category['name']).'" has been created', 'success');
            $category  = array();

        }catch(Exception $e){
            $flash = tr('Failed to create new category because  "%message%"', '%message%', $e->getMessage());
        }

        break;

    case tr('Update'):
        try{
            load_libs('seo');

            /*
             * Update the specified category
             */
            $category = $_POST;

            // Validate input
            $v = new validate_form($category);

            $v->is_not_empty ($category['name']       , tr('Please provide the name of your category'));
            $v->is_not_empty ($category['keywords']   , tr('Please provide the keywords for your category'));
            $v->is_not_empty ($category['description'], tr('Please provide the description for your category'));

            $v->has_min_chars($category['name']       ,  3, tr('Please ensure that the name has a minimum of 3 characters'));
            $v->has_min_chars($category['keywords']   ,  8, tr('Please ensure that the keywords have a minimum of 8 characters'));
            $v->has_min_chars($category['description'], 32, tr('Please ensure that the descriptiin has a minimum of 32 characters'));

            if(empty($category['parent'])){
                $category['parents_id'] = null;

            }else{
                /*
                 * Make sure the parent category is inside this blog
                 */
                if(!$parent = sql_get('SELECT `id`, `blogs_id` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname' => $category['parent']))){
                    /*
                     * Specified parent does not exist at all
                     */
                    throw new lsException('The specified parent category does not exist', 'notexist');
                }

                if($parent['blogs_id'] != $blog['id']){
                    /*
                     * Specified parent does not exist inside this blog
                     */
                    throw new lsException('The specified parent category does not exist in this blog', 'notexist');
                }

                $category['parents_id'] = $parent['id'];
            }

            if(!$v->is_valid()) {
               throw new lsException(str_force($v->get_errors(), ', '), 'errors');
            }

            if(!$dbcategory = sql_get('SELECT * FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `id` = :id', array(':blogs_id' => $blog['id'], ':id' => $category['id']))){
                /*
                 * Cannot update this category, it does not exist in this blog!
                 */
                throw new lsException(tr('The specified categories id does not exist in the blog "'.$blog['name'].'"'), 'notexists');
            }

            if(($dbcategory['createdby'] != $_SESSION['user']['id']) and !$_SESSION['user']['admin']){
                /*
                 * This category is not from this user and this user is also not an admin!
                 */
                throw new lsException(tr('This category is not yours, and you are not an admin'), 'accessdenied');
            }

            if(sql_get('SELECT `id` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `name` = :name AND `id` != :id', array(':blogs_id' => $blog['id'], ':id' => $category['id'], ':name' => $category['name']), 'id')){
                /*
                 * Another category with this name already exists in this blog
                 */
                throw new lsException(tr('A category with the name "%category%" already exists in the blog "%blog%"', array('%category%', '%blog%'), array($category['name'], $blog['name'])), 'exists');
            }

            /*
             * Copy new category data over existing
             */
            $category['seoname']     = seo_generate_unique_name($category['name'], 'blogs_categories', $category['id']);
            $category['keywords']    = blogs_clean_keywords($category['keywords']);
            $category['seokeywords'] = blogs_seo_keywords($category['keywords']);
            $category                = array_copy_clean($category, $dbcategory);

            /*
             * Update the category
             */
            sql_query('UPDATE `blogs_categories`

                       SET    `modifiedby`  = :modifiedby,
                              `modifiedon`  = NOW(),
                              `parents_id`  = :parents_id,
                              `name`        = :name,
                              `seoname`     = :seoname,
                              `keywords`    = :keywords,
                              `seokeywords` = :seokeywords,
                              `description` = :description

                       WHERE  `id`          = :id',

                       array(':id'          => $category['id'],
                             ':modifiedby'  => $_SESSION['user']['id'],
                             ':parents_id'  => $category['parents_id'],
                             ':name'        => $category['name'],
                             ':seoname'     => $category['seoname'],
                             ':keywords'    => $category['keywords'],
                             ':seokeywords' => $category['seokeywords'],
                             ':description' => $category['description']));

            /*
             * Due to the update, the name might have changed.
             * Redirect to ensure that the name in the URL is correct
             */
            html_flash_set(tr('The category "%category%" has been updated', '%category%', str_log($category['name'])), 'success');
            redirect('/admin/blogs_categories.php?blog='.$blog['seoname']);

        }catch(Exception $e){
            $flash = tr('Failed to update category because "%message%"', '%message%', $e->getMessage());
        }

        break;

    case tr('Delete'):
        try{
            /*
             * Delete the specified categories
             */
            if(empty($_POST['id'])){
                throw new lsException('No categories selected to delete', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('UPDATE `blogs_categories`
                            SET    `status` = "deleted"
                            WHERE  `status` IS NULL AND `id` IN ('.implode(', ', array_keys($list)).')',

                       $list);

            if($r->rowCount()){
                html_flash_set(tr('Deleted %count% categories', '%count%', $r->rowCount()), 'success');

            }else{
                throw new lsException(tr('Found no categories to delete'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to delete categories because "'.$e->getMessage().'"'), 'error');
        }

        break;

    case tr('Undelete'):
        try{
            /*
             * Delete the specified categories
             */
            if(empty($_POST['id'])){
                throw new lsException('No categories selected to undelete', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('UPDATE `blogs_categories`
                            SET    `status` = NULL
                            WHERE  `status` = "deleted" AND `id` IN ('.implode(', ', array_keys($list)).')',

                       $list);

            if($r->rowCount()){
                html_flash_set(tr('Undeleted %count% categories', '%count%', $r->rowCount()), 'success');

            }else{
                throw new lsException(tr('Found no categories to undelete'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to undelete categories because "'.$e->getMessage().'"'), 'error');
        }

        break;

    case tr('Erase'):
        try{
            /*
             * Delete the specified categories
             */
            if(empty($_POST['id'])){
                throw new lsException('No categories selected to erase', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('DELETE FROM `blogs_categories` WHERE `status` = "deleted" AND `id` IN ('.implode(', ', array_keys($list)).')', $list);

            if($r->rowCount()){
                html_flash_set(tr('Erased %count% categories', '%count%', $r->rowCount()), 'success');

            }else{
                throw new lsException(tr('Found no categories to erase'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to erase categories because "'.$e->getMessage().'"'), 'error');
        }
}

$html = '   <h2>'.tr('Available categories for blog "'.$blog['name'].'"').'</h2>
            <div class="display">
                '.html_flash().'
                <form action="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname']).'" method="post">
                    <table class="link select">';

$categories = sql_list('SELECT    `blogs_categories`.`id`,
                                  `blogs_categories`.`createdon`,
                                  `blogs_categories`.`status`,
                                  `blogs_categories`.`name`,
                                  `blogs_categories`.`seoname`,
                                  `blogs_categories`.`parents_id`

                        FROM      `blogs_categories`

                        WHERE     `blogs_categories`.`blogs_id` = :blogs_id', array(':blogs_id' => $blog['id']));

if(!$categories){
    $html .= '<tr><td>'.tr('There are no categories for this blog yet').'</td></tr>';

}else{
    $html .= '<thead><td class="select"><input type="checkbox" name="all" class="all"></td><td>'.tr('Category').'</td><td>'.tr('Created on').'</td><td>'.tr('Parent').'</td><td>'.tr('Status').'</td></thead>';

    foreach($categories as $id => $cat){
        $html .= '<tr'.(($selected == $cat['seoname']) ? ' class="selected"' : '').'><td class="select"><input type="checkbox" name="id" id="id" value="'.$id.'"></td>
                      <td><a href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname'].'&category='.$cat['seoname']).'">'.$cat['name'].'</a></td>
                      <td><a href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname'].'&category='.$cat['seoname']).'">'.$cat['createdon'].'</a></td>
                      <td><a href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname'].'&category='.$cat['seoname']).'">'.isset_get($categories[$cat['parents_id']]['name']).'</a></td>
                      <td><a href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname'].'&category='.$cat['seoname']).'">'.status($cat['status']).'</a></td>
                  </tr>';
    }
}

$html .= '</table>
          <a class="button submit" href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname'].'&doaction=add').'">'.tr('Create').'</a>';

if($categories){
    $html .= ' <input type="submit" name="doaction" value="'.tr('Delete').'">
               <input type="submit" name="doaction" value="'.tr('Undelete').'">
               <input type="submit" name="doaction" value="'.tr('Erase').'">';
}

$html .= ' <a class="button submit" href="'.domain('/admin/blogs.php?blog='.$blog['seoname']).'">'.tr('Back').'</a>
        </form>
    </div>';

if($selected or $action == 'add'){
    if(isset_get($flash)){

    }

    $cat_select = array('name'     => 'parent',
                        'blogs_id' => $blog['id'],
                        'selected' => isset_get($categories[isset_get($category['parents_id'])]['name'], ''),
                        'filter'   => array('seoname' => isset_get($category['seoname'])));

    $html .= '<hr>
              <div class="category">
                '.html_flash().'
                <form id="category" name="category" action="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname'].(isset($_GET['doaction']) ? '&doaction='.$_GET['doaction'] : '')).'" method="post">
                    <fieldset>
                        <legend>'.str_log((isset($category['name']) ? 'Modify category "'.$category['name'].'"' : tr('Create new category'))).'</legend>
                        <ul class="form display">
                            <li><label>'.tr('Name').'</label><input type="text" name="name" id="name" value="'.isset_get($category['name']).'"  maxlength="64"></li>
                            <li><label>'.tr('Parent category').'</label>'.blogs_categories_select($cat_select).'</li>
                            <li><label for="keywords">'.tr('Keywords').'</label><input type="text" name="keywords" id="keywords" value="'.isset_get($category['keywords']).'" maxlength="255"></li>
                            <li><label for="description">'.tr('Description').'</label><input type="text" name="description" id="description" value="'.isset_get($category['description']).'"  maxlength="155"></li>
                        </ul>';

    if($action == 'add'){
        $html .= '<input type="submit" name="doaction" value="'.tr('Create').'">';

    }else{
        $html .= '<input type="submit" name="doaction" value="'.tr('Update').'">';
    }

    $html .= '                  <a class="button submit" href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname']).'">'.tr('Cancel').'</a>
                            </fieldset>
                            <input type="hidden" name="id" value="'.isset_get($category['id']).'">
                        </form>
                    </div>';

    /*
     * Add JS validation
     */
    $vj = new validate_jquery();

    $vj->validate('name'       , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the name of your blog category'));
    $vj->validate('keywords'   , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the keywords for your blog category'));
    $vj->validate('description', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the description for your blog category'));

    $vj->validate('name'       , 'minlength', '3'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the name has at least 3 characters'));
    $vj->validate('keywords'   , 'minlength', '8'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the keywords have at least 8 characters'));
    $vj->validate('description', 'minlength', '32'  , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 32 characters'));

    $params = array('id'   => 'category',
                    'json' => false);

    $html .= $vj->output_validation($params);
}

echo admin_page($html, tr('Blog categories management'));
?>
