<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_access_denied('admin');
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

if(($blog['createdby'] != $_SESSION['user']['id']) and !has_rights('god')) {
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
switch(strtolower(isset_get($_POST['doaction']))){
    case tr('add'):
        redirect(domain(http_build_url($_SERVER['REQUEST_URI'], 'doaction=add')));

    case tr('create'):
        try{
            load_libs('seo');

            /*
             * Create the specified category
             */
            $category = $_POST;

            // Validate input
            $v = new validate_form($category, 'name,keywords,description');

            $v->isNotEmpty ($category['name']            , tr('Please provide the name of your category'));
            $v->isNotEmpty ($category['keywords']        , tr('Please provide the keywords for your category'));
            $v->isNotEmpty ($category['description']     , tr('Please provide the description for your category'));

            $v->hasMinChars($category['name']       ,   3, tr('Please ensure that the name has a minimum of 3 characters'));
            $v->hasMinChars($category['keywords']   ,   8, tr('Please ensure that the keywords have a minimum of 8 characters'));
            $v->hasMaxChars($category['keywords']   , 255, tr('Please ensure that the keywords have a maximum of 255 characters'));
            $v->hasMinChars($category['description'],  32, tr('Please ensure that the description has a minimum of 32 characters'));
            $v->hasMaxChars($category['description'], 160, tr('Please ensure that the description has a maximum of 160 characters'));

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
                    throw new bException('The specified parent category does not exist', 'notexist');
                }

                if($parent['blogs_id'] != $blog['id']){
                    /*
                     * Specified parent does not exist inside this blog
                     */
                    throw new bException('The specified parent category does not exist in this blog', 'notexist');
                }

                $category['parents_id'] = $parent['id'];
            }

            if(!$v->isValid()) {
               throw new bException(str_force($v->getErrors(), ', '), 'errors');

            }

            if(sql_get('SELECT `id` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `name` = :name', array(':blogs_id' => $blog['id'], ':name' => $category['name']), 'id')){
                /*
                 * A category with this name already exists
                 */
                throw new bException(tr('A category with the name "%category%" already exists in blog "%blog%"', array('%category%', '%blog%'), array($category['name'], $blog['name'])), 'exists');

            }

            if(sql_get('SELECT `id` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `name` = :name', 'id', array(':blogs_id' => $blog['id'], ':name' => $category['name']))){
                throw new bException('A category with the name "'.str_log($category['name']).'" already exists', 'exists');
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
            html_flash_set(tr('Failed to create new category because  "%message%"', array('%message%' => $e->getMessage())), 'error');
        }

        break;

    case tr('update'):
        try{
            load_libs('seo');

            /*
             * Update the specified category
             */
            $category = $_POST;

            // Validate input
            $v = new validate_form($category);

            $v->isNotEmpty ($category['name']       , tr('Please provide the name of your category'));
            $v->isNotEmpty ($category['keywords']   , tr('Please provide the keywords for your category'));
            $v->isNotEmpty ($category['description'], tr('Please provide the description for your category'));

            $v->hasMinChars($category['name']       ,  3, tr('Please ensure that the name has a minimum of 3 characters'));
            $v->hasMinChars($category['keywords']   ,  8, tr('Please ensure that the keywords have a minimum of 8 characters'));
            $v->hasMinChars($category['description'], 32, tr('Please ensure that the descriptiin has a minimum of 32 characters'));

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
                    throw new bException('The specified parent category does not exist', 'notexist');
                }

                if($parent['blogs_id'] != $blog['id']){
                    /*
                     * Specified parent does not exist inside this blog
                     */
                    throw new bException('The specified parent category does not exist in this blog', 'notexist');
                }

                $category['parents_id'] = $parent['id'];
            }

            if(!$v->isValid()) {
               throw new bException(str_force($v->getErrors(), ', '), 'errors');
            }

            if(!$dbcategory = sql_get('SELECT * FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `id` = :id', array(':blogs_id' => $blog['id'], ':id' => $category['id']))){
                /*
                 * Cannot update this category, it does not exist in this blog!
                 */
                throw new bException(tr('The specified categories id does not exist in the blog "'.$blog['name'].'"'), 'notexists');
            }

            if(($dbcategory['createdby'] != $_SESSION['user']['id']) and !has_rights('admin')){
                /*
                 * This category is not from this user and this user is also not an admin!
                 */
                throw new bException(tr('This category is not yours, and you are not an admin'), 'accessdenied');
            }

            if(sql_get('SELECT `id` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `name` = :name AND `id` != :id', array(':blogs_id' => $blog['id'], ':id' => $category['id'], ':name' => $category['name']), 'id')){
                /*
                 * Another category with this name already exists in this blog
                 */
                throw new bException(tr('A category with the name "%category%" already exists in the blog "%blog%"', array('%category%', '%blog%'), array($category['name'], $blog['name'])), 'exists');
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
             * Since blog category name may have changed, update all blog posts with that categories_id,
             * Since category name may also be a part of the blog posts URL, update all blog posts for
             * this blog and category as well
             */
            sql_query('UPDATE `blogs_posts`

                       SET    `category`      = :category,
                              `seocategory`   = :seocategory

                       WHERE  `categories_id` = :categories_id',

                       array(':category'      => $category['name'],
                             ':seocategory'   => $category['seoname'],
                             ':categories_id' => $category['id']));


            blogs_update_urls(null, $category['seoname']);


            /*
             * Due to the update, the name might have changed.
             * Redirect to ensure that the name in the URL is correct
             */
            html_flash_set(tr('The category "%category%" has been updated', '%category%', str_log($category['name'])), 'success');
            redirect('/admin/blogs_categories.php?blog='.$blog['seoname']);

        }catch(Exception $e){
            html_flash_set(tr('Failed to update category because "%message%"', array('%message%' => $e->getMessage())), 'error');
        }

        break;

    case tr('delete'):
        try{
            /*
             * Delete the specified categories
             */
            if(empty($_POST['id'])){
                throw new bException('No categories selected to delete', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r    = sql_query('UPDATE `blogs_categories`
                               SET    `status` = "deleted"
                               WHERE  `status` IS NULL AND `id` IN ('.implode(', ', array_keys($list)).')',

                               $list);

            if($r->rowCount()){
                html_flash_set(tr('Deleted %count% categories', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no categories to delete'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to delete categories because "%message%"', array('%message%' => $e->getMessage())), 'error');
        }

        break;

    case tr('undelete'):
        try{
            /*
             * Delete the specified categories
             */
            if(empty($_POST['id'])){
                throw new bException('No categories selected to undelete', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('UPDATE `blogs_categories`
                            SET    `status` = NULL
                            WHERE  `status` = "deleted" AND `id` IN ('.implode(', ', array_keys($list)).')',

                       $list);

            if($r->rowCount()){
                html_flash_set(tr('Undeleted %count% categories', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no categories to undelete'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to undelete categories because "%message%"', array('%message%' => $e->getMessage())), 'error');
        }

        break;

    case tr('erase'):
        try{
            /*
             * Delete the specified categories
             */
            if(empty($_POST['id'])){
                throw new bException('No categories selected to erase', 'notspecified');
            }

            $list = array_prefix(array_force($_POST['id']), ':id', true);

            $r = sql_query('DELETE FROM `blogs_categories` WHERE `status` = "deleted" AND `id` IN ('.implode(', ', array_keys($list)).')', $list);

            if($r->rowCount()){
                html_flash_set(tr('Erased %count% categories', '%count%', $r->rowCount()), 'success');

            }else{
                throw new bException(tr('Found no categories to erase'), 'notfound');
            }

        }catch(Exception $e){
            html_flash_set(tr('Failed to erase categories because "%message%"', array('%message%' => $e->getMessage())), 'error');
        }
}

/*
 * Do we have view filters?
 */
switch (isset_get($_POST['view'])){
    case 'all':
        $title     = '<h2 class="panel-title">'.tr('All categories for blog "'.$blog['name'].'"').'</h2>';

        $actions   = array('name'       => 'doaction',
                           'class'      => 'btn-primary mb-xs form-action input-sm',
                           'none'       => tr('Action'),
                           'resource'   => array('add'      => tr('Create'),
                                                 'delete'   => tr('Delete'),
                                                 'undelete' => tr('Undelete')),
                           'autosubmit' => true);
        break;

    case 'deleted':
        $title     = '<h2 class="panel-title">'.tr('Deleted categories for blog "'.$blog['name'].'"').'</h2>';

        $filters[] = ' `blogs_categories`.`status` = "deleted" ';

        $actions   = array('name'       => 'doaction',
                           'class'      => 'btn-primary mb-xs form-action input-sm',
                           'none'       => tr('Action'),
                           'resource'   => array('undelete' => tr('Undelete')),
                           'autosubmit' => true);
        break;

    case '':
        // FALLTHROUGH
    default:
        $title     = '<h2 class="panel-title">'.tr('Available categories for blog "'.$blog['name'].'"').'</h2>';

        $filters[] = ' `blogs_categories`.`status` IS NULL ';

        $actions   = array('name'       => 'doaction',
                           'class'      => 'btn-primary mb-xs form-action input-sm',
                           'none'       => tr('Action'),
                           'resource'   => array('add'    => tr('Create'),
                                                 'delete' => tr('Delete')),
                           'autosubmit' => true);
}



/*
 *
 */
$limit = 50;

$view  = array('name'       => 'view',
               'class'      => 'filter form-control mb-md',
               'none'       => tr('View'),
               'selected'   => isset_get($_POST['view']),
               'resource'   => array(''        => tr('Active'),
                                     'deleted' => tr('Deleted'),
                                     'empty'   => tr('Empty'),
                                     'all'     => tr('All')),
               'autosubmit' => true);

$query      = 'SELECT    `blogs_categories`.`id`,
                         `blogs_categories`.`createdon`,
                         `blogs_categories`.`status`,
                         `blogs_categories`.`name`,
                         `blogs_categories`.`seoname`,
                         `blogs_categories`.`parents_id`

               FROM      `blogs_categories`

               WHERE     `blogs_categories`.`blogs_id` = :blogs_id';



/*
 * Add filters to the query
 */
if(!empty($filters)){
    $query .= ' AND '.implode(' AND ', $filters);
}



/*
 *
 */
$execute    = array(':blogs_id' => $blog['id']);
$categories = sql_list($query.' ORDER BY `blogs_categories`.`name`', $execute);



/*
 *
 */
$html = '   <form action="'.domain(true).'" method="post">
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                '.$title.'
                                <p>
                                    '.html_flash().'
                                    <div class="form-group">
                                        <div class="col-sm-8">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    '.html_select($view).'
                                                </div>
                                                <div class="visible-xs mb-md"></div>
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



/*
 *
 */
if(!$categories){
    $html .= '<p>'.tr('No blogs found with this filter').'</p>';

}else{
    $html .= '  <div class="table-responsive">
                    <table class="link select table mb-none table-striped table-hover">
                        <thead>
                            <th class="select"><input type="checkbox" name="id[]" class="all"></th>
                            <th>'.tr('Category').'</th>
                            <th>'.tr('Category SEO').'</th>
                            <th>'.tr('Created on').'</th>
                            <th>'.tr('Parent').'</th>
                            <th>'.tr('Status').'</th>
                        </thead>';

    foreach($categories as $id => $cat){
        $a = '<a href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname'].'&category='.$cat['seoname']).'">';

        $html .= '<tr'.(($selected == $cat['seoname']) ? ' class="selected"' : '').'><td class="select"><input type="checkbox" name="id[]" value="'.$id.'"></td>
                      <td>'.$a.$cat['name'].'</a></td>
                      <td>'.$a.$cat['seoname'].'</a></td>
                      <td>'.$a.$cat['createdon'].'</a></td>
                      <td>'.$a.isset_get($categories[$cat['parents_id']]['name']).'</a></td>
                      <td>'.$a.status($cat['status']).'</a></td>
                  </tr>';
    }

    $html .= '</table>';
}

$html .=                    html_select($actions).'
                            <a class="mb-xs mt-xs mr-xs btn btn-primary" href="'.domain('/admin/blogs.php?blog='.$blog['seoname']).'">'.tr('Back').'</a>
                        </div>
                    </section>
                </div>
            </div>
        </form>';



/*
 *
 */
if($selected or $action == 'add'){
    if(isset_get($flash)){
        html_flash_set($flash, $flash_type);
    }

    $cat_select = array('name'     => 'parent',
                        'class'    => 'form-control mb-md',
                        'blogs_id' => $blog['id'],
                        'selected' => isset_get($categories[isset_get($category['parents_id'])]['name'], ''),
                        'filter'   => array('seoname' => isset_get($category['seoname'])));

    $html .= '  <form id="category" name="category" action="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname'].(isset($_GET['doaction']) ? '&doaction='.$_GET['doaction'] : '')).'" method="post">
                    <div class="row">
                        <div class="col-md-'.(empty($blog['id']) ? '12' : '6').'">
                            <section class="panel">
                                <header class="panel-heading">
                                    <h2 class="panel-title">'.(empty($blog['id']) ? tr('Create new blog') : tr('Modify blog')).'</h2>
                                    <p>'.html_flash().'</p>
                                </header>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label class="col-md-3 control-label" for="keywords">'.tr('Name').'</label>
                                        <div class="col-md-9">
                                            <input type="text" name="name" id="name" class="form-control" value="'.isset_get($category['name']).'" maxlength="64">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label" for="keywords">'.tr('Parent category').'</label>
                                        <div class="col-md-9">
                                            '.blogs_categories_select($cat_select).'
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label" for="keywords">'.tr('Keywords').'</label>
                                        <div class="col-md-9">
                                            <input type="text" name="keywords" id="keywords" class="form-control" value="'.isset_get($category['keywords']).'" maxlength="255">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label" for="keywords">'.tr('Description').'</label>
                                        <div class="col-md-9">
                                            <input type="text" name="description" id="description" class="form-control" value="'.isset_get($category['description']).'" maxlength="160">
                                        </div>
                                    </div>
                                    <input type="hidden" name="id" value="'.isset_get($category['id']).'">'.
                                (($action == 'add') ? '<input type="submit" class="mb-md mt-md mr-md btn btn-primary" name="doaction" id="doaction" value="'.tr('Create').'"> '
                                                    : '<input type="submit" class="mb-md mt-md mr-md btn btn-primary" name="doaction" id="doaction" value="'.tr('Update').'"> ').'
                                    <a class="mb-xs mt-xs mr-xs btn btn-primary" href="'.domain('/admin/blogs_categories.php?blog='.$blog['seoname']).'">'.tr('Cancel').'</a>
                                </div>
                            </section>
                        </div>
                    </div>
                </form>';

    /*
     * Add JS validation
     */
    $vj = new validate_jquery();

    $vj->validate('name'       , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the name of your blog category'));
    $vj->validate('keywords'   , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the keywords for your blog category'));
    $vj->validate('description', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the description for your blog category'));

    $vj->validate('name'       , 'minlength', '3'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the name has at least 3 characters'));
    $vj->validate('keywords'   , 'minlength', '8'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the keywords have at least 8 characters'));
    $vj->validate('description', 'minlength', '16'  , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 16 characters'));

    $params = array('id'   => 'category',
                    'json' => false);

    $html .= $vj->output_validation($params);
}


$params = array('icon'        => 'fa-users',
                'title'       => tr('Blog categories management'),
                'breadcrumbs' => array(tr('Blogs'), tr('Categories'), tr('Manage')),
                'script'      => 'blogs.php');

echo ca_page($html, $params);
?>
