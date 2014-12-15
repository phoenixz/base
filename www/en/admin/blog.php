<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('validate');

/*
 * Edit or add?
 */
try{
    if(empty($_GET['blog'])){
        $mode  = 'create';

        switch(isset_get($_POST['formaction'])){
            case 'Create':
                load_libs('seo,blogs');

                /*
                 * Create the specified blog
                 */
                $blog = $_POST;

                // Validate input
                $v = new validate_form($blog, 'name,url_template,slogan,keywords,description');

                $v->isChecked  ($blog['name']            , tr('Please provide the name of your blog'));
                $v->isNotEmpty ($blog['slogan']          , tr('Please provide a slogan for your blog'));
                $v->isNotEmpty ($blog['keywords']        , tr('Please provide keywords for your blog'));
                $v->isNotEmpty ($blog['description']     , tr('Please provide a description of your blog'));

                $v->hasMinChars($blog['name']       ,   4, tr('Please ensure that the name has a minimum of 4 characters'));
                $v->hasMinChars($blog['slogan']     ,   6, tr('Please ensure that the slogan has a minimum of 6 characters'));
                $v->hasMinChars($blog['keywords']   ,   8, tr('Please ensure that the keywords have a minimum of 8 characters'));
                $v->hasMinChars($blog['description'],  32, tr('Please ensure that the description has a minimum of 32 characters'));
                $v->hasMaxChars($blog['description'], 160, tr('Please ensure that the description has a maximum of 160 characters'));

                if(!$v->isValid()) {
                   throw new bException(str_force($v->getErrors(), ', '), 'errors');
                }

                if(sql_get('SELECT `id` FROM `blogs` WHERE `name` = :name', array(':name' => $blog['name']), 'id')){
                    /*
                     * A blog with this name already exists
                     */
                    throw new bException(tr('A blog with the name "%blog%" already exists', '%blog%', $blog['name']), 'exists');

                }else{
                    $blog['seoname']     = seo_generate_unique_name($blog['name'], 'blogs', $blog['id']);
                    $blog['keywords']    = blogs_clean_keywords($blog['keywords']);
                    $blog['seokeywords'] = blogs_seo_keywords($blog['keywords']);

                    sql_query('INSERT INTO `blogs` (`createdby`, `name`, `seoname`, `slogan`, `url_template`, `keywords`, `seokeywords`, `description`)
                               VALUES              (:createdby , :name , :seoname , :slogan , :url_template , :keywords , :seokeywords , :description )',

                               array(':createdby'    => $_SESSION['user']['id'],
                                     ':name'         => $blog['name'],
                                     ':seoname'      => $blog['seoname'],
                                     ':url_template' => $blog['url_template'],
                                     ':slogan'       => $blog['slogan'],
                                     ':keywords'     => $blog['keywords'],
                                     ':seokeywords'  => $blog['seokeywords'],
                                     ':description'  => $blog['description']));

                    html_flash_set('The blog "'.str_log($blog['name']).'" has been created', 'success');
                    $blog      = array();
                }
        }

    }else{
        $mode  = 'modify';

        if(!$blog = sql_get('SELECT * FROM `blogs` WHERE `seoname` = :seoname',  array(':seoname' => $_GET['blog']))){
            /*
             * This blog does not exist
             */
            html_flash_set(tr('The blog "'.$_GET['blog'].'" does not exist'), 'error');
            redirect('/admin/blogs.php');
        }

        switch(isset_get($_POST['formaction'])){
            case 'Update':
                load_libs('seo,blogs');

                if(empty($_GET['blog'])){
                    throw new bException('No blog specified to update', 'not_specified');
                }

                /*
                 * Update the specified blog
                 */
                $blog = $_POST;

                // Validate input
                $v = new validate_form($blog, 'name,url_template,slogan,keywords,description');

                $v->isChecked  ($blog['name']             , tr('Please provide the name of your blog'));
                $v->isNotEmpty ($blog['slogan']           , tr('Please provide a slogan for your blog'));
                $v->isNotEmpty ($blog['keywords']         , tr('Please provide keywords for your blog'));
                $v->isNotEmpty ($blog['description']      , tr('Please provide a description of your blog'));

                $v->hasMinChars($blog['name']        ,   4, tr('Please ensure that the name has a minimum of 4 characters'));
                $v->hasMinChars($blog['slogan']      ,   6, tr('Please ensure that the slogan has a minimum of 6 characters'));
                $v->hasMinChars($blog['keywords']    ,   8, tr('Please ensure that the keywords have a minimum of 8 characters'));
                $v->hasMinChars($blog['description'] ,  50, tr('Please ensure that the description has a minimum of 50 characters'));
                $v->hasMaxChars($blog['url_template'], 255, tr('Please ensure that the URL has a maximum of 255 characters'));

                if(!$v->isValid()) {
                   throw new bException(str_force($v->getErrors(), ', '), 'errors');
                }

                if(!$dbblog = sql_get('SELECT * FROM `blogs` WHERE `seoname` = :seoname', array(':seoname' => $_GET['blog']))){
                    /*
                     * Cannot update this blog, it does not exist!
                     */
                    throw new bException(tr('The specified blogs id "'.str_log($blog['id']).'" does not exist'), 'notexists');
                }

                if(($dbblog['createdby'] != $_SESSION['user']['id']) and !has_rights('admin')){
                    /*
                     * This blog is not from this user and this user is also not an admin!
                     */
                    throw new bException(tr('This blog is not yours, and you are not an admin'), 'accessdenied');
                }

                if(sql_get('SELECT `id` FROM `blogs` WHERE `name` = :name AND `seoname` != :seoname', array(':name' => $blog['name'], ':seoname' => $_GET['blog']), 'id')){
                    /*
                     * Another blog with this name already exists
                     */
                    throw new bException(tr('A blog with the name "%blog%" already exists', '%blog%', $blog['name']));

                }else{
                    /*
                     * Copy new blog data over existing
                     */
                    $blog['seoname']     = seo_generate_unique_name($blog['name'], 'blogs', $dbblog['id']);
                    $blog['keywords']    = blogs_clean_keywords($blog['keywords']);
                    $blog['seokeywords'] = blogs_seo_keywords($blog['keywords']);
                    $blog                = array_copy_clean($blog, $dbblog);

                    /*
                     * Update the blog
                     */
                    sql_query('UPDATE `blogs`

                               SET    `modifiedby`   = :modifiedby,
                                      `modifiedon`   = NOW(),
                                      `name`         = :name,
                                      `seoname`      = :seoname,
                                      `url_template` = :url_template,
                                      `slogan`       = :slogan,
                                      `keywords`     = :keywords,
                                      `seokeywords`  = :seokeywords,
                                      `description`  = :description

                               WHERE  `id`           = :id',

                               array(':id'           => $dbblog['id'],
                                     ':modifiedby'   => $_SESSION['user']['id'],
                                     ':name'         => $blog['name'],
                                     ':seoname'      => $blog['seoname'],
                                     ':url_template' => $blog['url_template'],
                                     ':slogan'       => $blog['slogan'],
                                     ':keywords'     => $blog['keywords'],
                                     ':seokeywords'  => $blog['seokeywords'],
                                     ':description'  => $blog['description']));

                    /*
                     * Update all blog posts URL's since it might have changed
                     */
                    blogs_update_urls($blog['seoname']);

                    /*
                     * Due to the update, the name might have changed.
                     * Redirect to ensure that the name in the URL is correct
                     */
                    html_flash_set(tr('The blog "%blog%" has been updated', '%blog%', str_log($blog['name'])), 'success');
                    redirect('/admin/blogs.php?blog='.$blog['seoname']);
                }
        }
    }

}catch(Exception $e){
    html_flash_set($e);
}



/*
 *
 */
$html = '   <form id="blog" name="blog" action="'.domain('/admin/blog.php'.(isset($blog['seoname']) ? '?blog='.$blog['seoname'] : '')).'" method="post">
                <div class="row">
                    <div class="col-md-'.(empty($blog['id']) ? '12' : '6').'">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.(empty($blog['id']) ? tr('Create new blog') : tr('Modify blog')).'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="name">'.tr('Name').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="name" id="name" class="form-control" value="'.isset_get($blog['name']).'" maxlength="64">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="url_template">'.tr('URL template').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="url_template" id="url_template" class="form-control" value="'.not_empty(isset_get($blog['url_template']), isset_get($_CONFIG['blogs']['url'])).'" maxlength="255">
                                    </div>
                                </div>';

if(!empty($blog['id'])){
    $html .= '  <div class="form-group">
                    <label class="col-md-3 control-label" for="status">'.tr('Status').'</label>
                    <div class="col-md-9">
                        <input type="text" name="status" id="status" class="form-control" value="'.status(isset_get($blog['status'])).'" readonly maxlength="16">
                    </div>
                </div>';
}

$html .= '                      <div class="form-group">
                                    <label class="col-md-3 control-label" for="slogan">'.tr('Slogan').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="slogan" id="slogan" class="form-control" value="'.isset_get($blog['slogan']).'" maxlength="255">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="keywords">'.tr('Keywords').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="keywords" id="keywords" class="form-control" value="'.isset_get($blog['keywords']).'" maxlength="255">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="code">'.tr('Description').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="description" id="description" class="form-control" value="'.isset_get($blog['description']).'" maxlength="160">
                                    </div>
                                </div>'.
                                (isset_get($blog['id']) ? '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="formaction" id="formaction" value="'.tr('Update').'"> '
                                                            : '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="formaction" id="formaction" value="'.tr('Create').'"> ').'
                                <a class="mb-xs mt-xs mr-xs btn btn-primary" href="'.domain('/admin/blogs.php'.((empty($blog['seoname'])) ? '' : '?blog='.$blog['seoname'])).'">'.tr('Return').'</a>
                            </div>
                        </section>
                    </div>
                </div>
            </form>';

//                    <li><label for="name">'.tr('Name').'</label><input type="text" name="name" id="name" value="'.isset_get($blog['name']).'" maxlength="64"></li>
//                    <li><label for="slogan">'.tr('Slogan').'</label><input type="text" name="slogan" id="slogan" value="'.isset_get($blog['slogan']).'" maxlength="255"></li>
//                    <li><label for="keywords">'.tr('Keywords').'</label><input type="text" name="keywords" id="keywords" value="'.isset_get($blog['keywords']).'" maxlength="255"></li>
//                    <li><label for="description">'.tr('Description').'</label><input type="text" name="description" id="description" value="'.isset_get($blog['description']).'" maxlength="160"></li>
//                </ul>
//                <input type="hidden" name="id" id="id" value="'.isset_get($blog['id']).'">'.
//                (($mode == 'create') ? '<input type="submit" name="docreate" id="docreate" value="'.tr('Create').'"> <a class="button submit" href="'.domain('/admin/blogs.php'.(empty($blog['seoname']) ? '' : '?blog='.$blog['seoname'])).'">'.tr('Back').'</a>'
//                                     : '<input type="submit" name="doupdate" id="doupdate" value="'.tr('Update').'"> <a class="button submit" href="'.domain('/admin/blogs.php'.(empty($blog['seoname']) ? '' : '?blog='.$blog['seoname'])).'">'.tr('Back').'</a>').

/*
 * Add JS validation
 */
$vj = new validate_jquery();

$vj->validate('name'       , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the name of your blog'));
$vj->validate('slogan'     , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description of your blog'));
$vj->validate('keywords'   , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide keywords for your blog'));
$vj->validate('description', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description of your blog'));

$vj->validate('name'       , 'minlength',    '4', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the name has at least 4 characters'));
$vj->validate('slogan'     , 'minlength',    '6', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the slogan has at least 6 characters'));
$vj->validate('keywords'   , 'minlength',    '8', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the keywords have at least 8 characters'));

$vj->validate('description', 'minlength',   '16', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 16 characters'));
$vj->validate('description', 'maxlength',  '160', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 160 characters'));

$params = array('id'   => 'blog',
                'json' => false);

$params = array('title'       => tr('Blog'),
                'icon'        => 'fa-user',
                'breadcrumbs' => array(tr('Blog'), tr('Modify')));

echo ca_page($html, $params);
?>
