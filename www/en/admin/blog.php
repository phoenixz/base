<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin');
load_libs('admin,validate');

/*
 * Edit or add?
 */
try{
    if(empty($_GET['blog'])){
        $mode  = 'create';

        if(!empty($_POST['docreate'])){
            load_libs('seo,blogs');

            /*
             * Create the specified blog
             */
            $blog = $_POST;

            // Validate input
            $v = new validate_form($blog, 'name,slogan,keywords,description');

            $v->is_checked   ($blog['name']       , tr('Please provide the name of your blog'));
            $v->is_not_empty ($blog['slogan']     , tr('Please provide a slogan for your blog'));
            $v->is_not_empty ($blog['keywords']   , tr('Please provide keywords for your blog'));
            $v->is_not_empty ($blog['description'], tr('Please provide a description of your blog'));

            $v->has_min_chars($blog['name']       ,  4, tr('Please ensure that the name has a minimum of 4 characters'));
            $v->has_min_chars($blog['slogan']     ,  6, tr('Please ensure that the slogan has a minimum of 6 characters'));
            $v->has_min_chars($blog['keywords']   ,  8, tr('Please ensure that the keywords have a minimum of 8 characters'));
            $v->has_min_chars($blog['description'], 50, tr('Please ensure that the description has a minimum of 50 characters'));

            if(!$v->is_valid()) {
               throw new bException(str_force($v->get_errors(), ', '), 'errors');
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

                sql_query('INSERT INTO `blogs` (`createdby`, `name`, `seoname`, `slogan`, `keywords`, `seokeywords`, `description`)
                           VALUES              (:createdby , :name , :seoname , :slogan , :keywords , :seokeywords , :description )',

                           array(':createdby'   => $_SESSION['user']['id'],
                                 ':name'        => $blog['name'],
                                 ':seoname'     => $blog['seoname'],
                                 ':slogan'      => $blog['slogan'],
                                 ':keywords'    => $blog['keywords'],
                                 ':seokeywords' => $blog['seokeywords'],
                                 ':description' => $blog['description']));

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

        if(!empty($_POST['doupdate'])){
            load_libs('seo,blogs');

            /*
             * Update the specified blog
             */
            $blog = $_POST;

            // Validate input
            $v = new validate_form($blog);

            $v->is_checked   ($blog['name']       , tr('Please provide the name of your blog'));
            $v->is_not_empty ($blog['slogan']     , tr('Please provide a slogan for your blog'));
            $v->is_not_empty ($blog['keywords']   , tr('Please provide keywords for your blog'));
            $v->is_not_empty ($blog['description'], tr('Please provide a description of your blog'));

            $v->has_min_chars($blog['name']       ,  4, tr('Please ensure that the name has a minimum of 4 characters'));
            $v->has_min_chars($blog['slogan']     ,  6, tr('Please ensure that the slogan has a minimum of 6 characters'));
            $v->has_min_chars($blog['keywords']   ,  8, tr('Please ensure that the keywords have a minimum of 8 characters'));
            $v->has_min_chars($blog['description'], 50, tr('Please ensure that the description has a minimum of 50 characters'));

            if(!$v->is_valid()) {
               throw new bException(str_force($v->get_errors(), ', '), 'errors');
            }

            if(!$dbblog = sql_get('SELECT * FROM `blogs` WHERE `id` = :id', array(':id' => $blog['id']))){
                /*
                 * Cannot update this blog, it does not exist!
                 */
                throw new bException(tr('The specified blogs id does not exist'), 'notexists');
            }

            if(($dbblog['createdby'] != $_SESSION['user']['id']) and !$_SESSION['user']['admin']){
                /*
                 * This blog is not from this user and this user is also not an admin!
                 */
                throw new bException(tr('This blog is not yours, and you are not an admin'), 'accessdenied');
            }

            if(sql_get('SELECT `id` FROM `blogs` WHERE `name` = :name AND `id` != :id', array(':name' => $blog['name'], ':id' => $blog['id']), 'id')){
                /*
                 * Another blog with this name already exists
                 */
                throw new bException(tr('A blog with the name "%blog%" already exists', '%blog%', $blog['name']));

            }else{
                /*
                 * Copy new blog data over existing
                 */
                $blog['seoname']     = seo_generate_unique_name($blog['name'], 'blogs', $blog['id']);
                $blog['keywords']    = blogs_clean_keywords($blog['keywords']);
                $blog['seokeywords'] = blogs_seo_keywords($blog['keywords']);
                $blog                = array_copy_clean($blog, $dbblog);

                /*
                 * Update the blog
                 */
                sql_query('UPDATE `blogs`
                           SET    `modifiedby`  = :modifiedby,
                                  `modifiedon`  = NOW(),
                                  `name`        = :name,
                                  `seoname`     = :seoname,
                                  `slogan`      = :slogan,
                                  `keywords`    = :keywords,
                                  `seokeywords` = :seokeywords,
                                  `description` = :description

                           WHERE  `id`          = :id',

                           array(':id'          => $blog['id'],
                                 ':modifiedby'  => $_SESSION['user']['id'],
                                 ':name'        => $blog['name'],
                                 ':seoname'     => $blog['seoname'],
                                 ':slogan'      => $blog['slogan'],
                                 ':keywords'    => $blog['keywords'],
                                 ':seokeywords' => $blog['seokeywords'],
                                 ':description' => $blog['description']));

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
    $flash     = str_from($e->getMessage(), ':');
    $flashtype = 'error';
}

$html = html_flash(isset_get($flash), isset_get($flashtype, 'error')).'<form id="blog" name="blog" action="'.domain('/admin/blog.php?blog='.isset_get($blog['seoname'])).'" method="post">
            <fieldset>
                <legend>'.(isset_get($blog['id']) ? tr('Edit blog') : tr('Add a new blog')).'</legend>
                <ul class="form">
                    <li><label for="name">'.tr('Name').'</label><input type="text" name="name" id="name" value="'.isset_get($blog['name']).'" maxlength="64"></li>
                    <li><label for="slogan">'.tr('Slogan').'</label><input type="text" name="slogan" id="slogan" value="'.isset_get($blog['slogan']).'" maxlength="255"></li>
                    <li><label for="keywords">'.tr('Keywords').'</label><input type="text" name="keywords" id="keywords" value="'.isset_get($blog['keywords']).'" maxlength="255"></li>
                    <li><label for="description">'.tr('Description').'</label><input type="text" name="description" id="description" value="'.isset_get($blog['description']).'" maxlength="155"></li>
                </ul>
                <input type="hidden" name="id" id="id" value="'.isset_get($blog['id']).'">'.
                (($mode == 'create') ? '<input type="submit" name="docreate" id="docreate" value="'.tr('Create').'"> <a class="button submit" href="'.domain('/admin/blogs.php'.(empty($blog['seoname']) ? '' : '?blog='.$blog['seoname'])).'">'.tr('Back').'</a>'
                                     : '<input type="submit" name="doupdate" id="doupdate" value="'.tr('Update').'"> <a class="button submit" href="'.domain('/admin/blogs.php'.(empty($blog['seoname']) ? '' : '?blog='.$blog['seoname'])).'">'.tr('Back').'</a>').
           '</fieldset>
        </form>';

/*
 * Add JS validation
 */
$vj = new validate_jquery();

$vj->validate('name'       , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide the name of your blog'));
$vj->validate('slogan'     , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description of your blog'));
$vj->validate('keywords'   , 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide keywords for your blog'));
$vj->validate('description', 'required' , 'true', '<span class="FcbErrorTail"></span>'.tr('Please provide a description of your blog'));

$vj->validate('name'       , 'minlength', '4'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the name has at least 4 characters'));
$vj->validate('slogan'     , 'minlength', '6'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the slogan has at least 6 characters'));
$vj->validate('keywords'   , 'minlength', '8'   , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the keywords have at least 8 characters'));
$vj->validate('description', 'minlength', '50'  , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the description has at least 50 characters'));

$params = array('id'   => 'blog',
                'json' => false);

$html .= $vj->output_validation($params);

echo admin_page($html, tr('Manage blogs'));
?>
