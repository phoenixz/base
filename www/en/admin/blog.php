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
                $blog = s_validate_blog($_POST);

                if(sql_get('SELECT `id` FROM `blogs` WHERE `name` = :name', array(':name' => $blog['name']), 'id')){
                    /*
                     * A blog with this name already exists
                     */
                    throw new bException(tr('A blog with the name "%blog%" already exists', '%blog%', $blog['name']), 'exists');

                }else{
                    $blog['seoname']     = seo_generate_unique_name($blog['name'], 'blogs', $blog['id']);
                    $blog['keywords']    = blogs_clean_keywords($blog['keywords']);
                    $blog['seokeywords'] = blogs_seo_keywords($blog['keywords']);

                    sql_query('INSERT INTO `blogs` (`createdby`, `name`, `seoname`, `slogan`, `url_template`, `thumbs_x`, `thumbs_y`, `images_x`, `images_y`, `keywords`, `seokeywords`, `description`)
                               VALUES              (:createdby , :name , :seoname , :slogan , :url_template , :thumbs_x , :thumbs_y , :images_x , :images_y , :keywords , :seokeywords , :description )',

                               array(':createdby'    => $_SESSION['user']['id'],
                                     ':name'         => $blog['name'],
                                     ':seoname'      => $blog['seoname'],
                                     ':url_template' => $blog['url_template'],
                                     ':thumbs_x'     => not_empty($blog['thumbs_x']),
                                     ':thumbs_y'     => not_empty($blog['thumbs_y']),
                                     ':images_x'     => not_empty($blog['images_x']),
                                     ':images_y'     => not_empty($blog['images_y']),
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
                $blog = s_validate_blog($_POST);

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
                                      `thumbs_x`     = :thumbs_x,
                                      `thumbs_y`     = :thumbs_y,
                                      `images_x`     = :images_x,
                                      `images_y`     = :images_y,
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
                                     ':thumbs_x'     => not_empty($blog['thumbs_x']),
                                     ':thumbs_y'     => not_empty($blog['thumbs_y']),
                                     ':images_x'     => not_empty($blog['images_x']),
                                     ':images_y'     => not_empty($blog['images_y']),
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
                                <p>
                                    '.tr('Here you can modify the blog basic configuration. Each blog will have its own, unique configuration').'
                                </p>
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
                                </div>
                                <hr>
                                <p>
                                    '.tr('If specified, these will be the standard sizes for images and thumbnails in the blogs. If left empty, the X and or Y values will not be modified').'
                                </p>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="thumbs_x">'.tr('Thumbs X').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="thumbs_x" id="thumbs_x" class="form-control" value="'.isset_get($blog['thumbs_x']).'" maxlength="5">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="thumbs_y">'.tr('Thumbs Y').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="thumbs_y" id="thumbs_y" class="form-control" value="'.isset_get($blog['thumbs_y']).'" maxlength="5">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="images_x">'.tr('Images X').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="images_x" id="images_x" class="form-control" value="'.isset_get($blog['images_x']).'" maxlength="5">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="images_y">'.tr('Image Y').'</label>
                                    <div class="col-md-9">
                                        <input type="text" name="images_y" id="images_y" class="form-control" value="'.isset_get($blog['images_y']).'" maxlength="5">
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

$vj->validate('name'       , 'required' ,              'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the name of your blog'));
$vj->validate('slogan'     , 'required' ,              'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description of your blog'));
$vj->validate('keywords'   , 'required' ,              'true', '<span class="FcbErrorTail"></span>'.tr('Please provide keywords for your blog'));
$vj->validate('description', 'required' ,              'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description of your blog'));

$vj->validate('name'       , 'minlength',                 '4', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the name has at least 4 characters'));
$vj->validate('slogan'     , 'minlength',                 '6', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the slogan has at least 6 characters'));
$vj->validate('keywords'   , 'minlength',                 '8', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the keywords have at least 8 characters'));

$vj->validate('description', 'minlength',                '16', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 16 characters'));
$vj->validate('description', 'maxlength',               '160', '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 160 characters'));

$vj->validate('thumbs_x'   , 'regex'    , '^[0-9]{0,3}(px)?$', '<span class="FcbErrorTail"></span>'.tr('Please specify a valid thumbs X value between 10 - 500'));
$vj->validate('thumbs_y'   , 'regex'    , '^[0-9]{0,3}(px)?$', '<span class="FcbErrorTail"></span>'.tr('Please specify a valid thumbs Y value between 10 - 500'));
$vj->validate('images_x'   , 'regex'    , '^[0-9]{0,4}(px)?$', '<span class="FcbErrorTail"></span>'.tr('Please specify a valid thumbs X value between 50 - 5000'));
$vj->validate('images_y'   , 'regex'    , '^[0-9]{0,4}(px)?$', '<span class="FcbErrorTail"></span>'.tr('Please specify a valid thumbs Y value between 50 - 5000'));

$html .= $vj->output_validation(array('id'   => 'blog',
                                      'json' => false));

$params = array('title'       => tr('Blog'),
                'icon'        => 'fa-user',
                'breadcrumbs' => array(tr('Blog'), tr('Modify')));

echo ca_page($html, $params);


/*
 *
 */
function s_validate_blog($blog){
    try{
        // Validate input
        $v = new validate_form($blog, 'name,url_template,slogan,keywords,description,thumbs_x,thumbs_y,images_x,images_y');

        $blog['thumbs_x'] = str_replace('px', '', str_force($blog['thumbs_x']));
        $blog['thumbs_y'] = str_replace('px', '', str_force($blog['thumbs_y']));
        $blog['images_x'] = str_replace('px', '', str_force($blog['images_x']));
        $blog['images_y'] = str_replace('px', '', str_force($blog['images_y']));

        $v->isChecked  ($blog['name']            , tr('Please provide the name of your blog'));
        $v->isNotEmpty ($blog['slogan']          , tr('Please provide a slogan for your blog'));
        $v->isNotEmpty ($blog['keywords']        , tr('Please provide keywords for your blog'));
        $v->isNotEmpty ($blog['description']     , tr('Please provide a description of your blog'));
        $v->isNumeric  ($blog['thumbs_x']        , tr('Please ensure that the thumbs x size is numeric'));
        $v->isNumeric  ($blog['thumbs_y']        , tr('Please ensure that the thumbs y size is numeric'));
        $v->isNumeric  ($blog['images_x']        , tr('Please ensure that the images x size is numeric'));
        $v->isNumeric  ($blog['images_y']        , tr('Please ensure that the images y size is numeric'));

        $v->hasMinChars($blog['name']       ,   4, tr('Please ensure that the name has a minimum of 4 characters'));
        $v->hasMinChars($blog['slogan']     ,   6, tr('Please ensure that the slogan has a minimum of 6 characters'));
        $v->hasMinChars($blog['keywords']   ,   8, tr('Please ensure that the keywords have a minimum of 8 characters'));
        $v->hasMinChars($blog['description'],  32, tr('Please ensure that the description has a minimum of 32 characters'));
        $v->hasMaxChars($blog['description'], 160, tr('Please ensure that the description has a maximum of 160 characters'));

        if(!$blog['thumbs_x']){
            $blog['thumbs_x'] = null;

        }else{
            if(($blog['thumbs_x'] < 10) or ($blog['thumbs_x'] > 500)){
                $v->setError(tr('Please ensure that the thumbs x value is between 10 and 500'));
            }
        }

        if(!$blog['thumbs_y']){
            $blog['thumbs_y'] = null;

        }else{
            if(($blog['thumbs_y'] < 10) or ($blog['thumbs_y'] > 500)){
                $v->setError(tr('Please ensure that the thumbs y value is between 10 and 500'));
            }
        }

        if(!$blog['images_x']){
            $blog['images_x'] = null;

        }else{
            if(($blog['images_x'] < 50) or ($blog['images_x'] > 5000)){
                $v->setError(tr('Please ensure that the images x value is between 50 and 5000'));
            }
        }

        if(!$blog['images_y']){
            $blog['images_y'] = null;

        }else{
            if(($blog['images_y'] < 50) or ($blog['images_y'] > 5000)){
                $v->setError(tr('Please ensure that the images y value is between 50 and 5000'));
            }
        }

        if(!$v->isValid()) {
           throw new bException($v->getErrors(), 'validation');
        }

        return $blog;

    }catch(Exception $e){
        throw new bException('s_validate_blog(): Failed', $e);
    }
}
?>
