<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('admin,editors,blogs,validate,upload');



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



/*
 * Set parameter defaults
 */
if(!isset($params)){
    $params = null;
}

array_params($params);
array_default($params, 'back'                   , '/admin/blogs_posts.php'.(!empty($blog['seoname']) ? '?blog='.$blog['seoname'] : ''));
array_default($params, 'bodymin'                , 200);
array_default($params, 'categories_none'        , tr('Select a category'));
array_default($params, 'categories_parent'      , null);
array_default($params, 'groups_none'            , tr('Select a group'));
array_default($params, 'groups_parent'          , null);
array_default($params, 'priorities_none'        , '');
array_default($params, 'flash_created'          , tr('The post "%post%" has been created'));
array_default($params, 'flash_updated'          , tr('The post "%post%" has been updated'));
// array_default for form_action is done below!
array_default($params, 'label_group'            , tr('Group'));
array_default($params, 'label_category'         , tr('Category'));
array_default($params, 'label_photos'           , tr('Save this blog to be able to add separate photos'));
array_default($params, 'label_priority'         , tr('Priority'));
array_default($params, 'label_title'            , tr('Title'));
array_default($params, 'label_keywords'         , tr('Keywords'));
array_default($params, 'label_description'      , tr('Description'));
array_default($params, 'namemax'                , 64);
array_default($params, 'placeholder_group'      , tr('Specify group'));
array_default($params, 'placeholder_description', tr('Specify short description about this blog post"'));
array_default($params, 'placeholder_keywords'   , tr('Specify keywords in a comma delimited list like "world,news,blog"'));
array_default($params, 'placeholder_url'        , tr('[optional] Specify relevant URL'));
array_default($params, 'redirect'               , '/admin/blogs_post.php?blog='.$blog['seoname'].'&');
array_default($params, 'status_select'          , array('class' => 'form-control'));
array_default($params, 'status_default'         , 'unpublished');
array_default($params, 'subtitle'               , (!empty($_GET['post']) ? tr('Edit post in blog "%blog%"', '%blog%', $blog['name']) : tr('Create a new post in blog "%blog%"', '%blog%', $blog['name'])));
array_default($params, 'script'                 , 'blogs_posts.php?blog='.$blog['seoname']);
array_default($params, 'title'                  , tr('Blog post'));
array_default($params, 'use_description'        , true);
array_default($params, 'use_groups'             , false);
array_default($params, 'use_keywords'           , true);
array_default($params, 'use_language'           , false);
array_default($params, 'use_priorities'         , false);
array_default($params, 'use_status'             , false);
array_default($params, 'use_key_value'          , false);
array_default($params, 'use_url'                , false);
array_default($params, 'key_value'              , null);

array_default($params['errors'], 'name_required'       , tr('Please provide the name of your blog post'));
array_default($params['errors'], 'blog_required'       , tr('Please select a blog for your post'));
array_default($params['errors'], 'category_required'   , tr('Please select a category for your blog post'));
array_default($params['errors'], 'body_required'       , tr('Please provide the body text of your blog post'));
array_default($params['errors'], 'status_required'     , tr('Please select the status of your blog post'));
array_default($params['errors'], 'group_required'      , tr('Please select a group for your blog post'));
array_default($params['errors'], 'priority_required'   , tr('Please select the priority of your blog post'));
array_default($params['errors'], 'description_required', tr('Please provide a description for your blog post'));
array_default($params['errors'], 'description_min'     , tr('Please ensure that the description has at least 16 characters'));
array_default($params['errors'], 'description_max'     , tr('Please ensure that the description has less than 160 characters'));
array_default($params['errors'], 'keywords_required'   , tr('Please provide keywords for your blog post'));
array_default($params['errors'], 'keywords_min'        , tr('Please provide more descriptive keywords for your blog post'));
array_default($params['errors'], 'body_min'            , tr('Please ensure that the body text has at least '.$params['bodymin'].' characters'));
array_default($params['errors'], 'name_min'            , tr('Please ensure that the name has at least 4 characters'));



/*
 * Edit or add?
 */
if(empty($_GET['post'])){
    $mode = 'create';
    $post = array('blog' => isset($blog['seoname']));

    if(!empty($_POST['docreate'])){
        try{
            /*
             * Create the specified post
             */
            $post = $_POST;
            $post = blogs_validate_post($post, $params);

            if(sql_get('SELECT `id` FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `name` = :name', array(':blogs_id' => $blog['id'],':name' => $post['name']), 'id')){
                /*
                 * A post with this name already exists
                 */
                throw new bException(tr('A post with the name "%name%" already exists', '%name%', str_log($post['name'])), 'exists');
            }

            $category = blogs_validate_category($post['seocategory'], $blog, $params['categories_parent']);

            $post['categories_id'] = $category['id'];
            $post['seocategory']   = $category['seoname'];
            $post['category']      = $category['name'];

            if($params['use_groups']){
                $group = blogs_validate_category($post['group']   , $blog, $params['groups_parent']);

                $post['groups_id']     = $group['id'];
                $post['group']         = $group['seoname'];

            }else{
                $post['groups_id']     = null;
                $post['group']         = '';
            }

// :TODO: seo_generate_unique_name() only works on "seoname" column, but seoname is NOT unique, blogs_id, seoname is unique!
            load_libs('seo');

            if($params['use_keywords']){
                $post['keywords']      = blogs_clean_keywords($post['keywords']);
                $post['seokeywords']   = blogs_seo_keywords($post['keywords']);

            }else{
                $post['keywords']      = '';
                $post['seokeywords']   = '';
            }

            $post['seoname']       = seo_generate_unique_name($post['name'], 'blogs_posts', $post['id']);
            $post['blogs_id']      = $blog['id'];
            $post['blog']          = $blog['seoname'];
            $post['priority']      = blogs_priority($post['priority']);
            $post['url']           = blogs_post_url($post);
            $post['status']        = $params['status_default'];

            $r = sql_query('INSERT INTO `blogs_posts` (`blogs_id`, `status`, `createdby`, `categories_id`, `category`, `seocategory`, `priority`, `groups_id`, `group`, `keywords`, `seokeywords`, `description`, `url`, `urlref`, `language`, `name`, `seoname`, `body`)
                            VALUES                    (:blogs_id , :status , :createdby , :categories_id , :category , :seocategory , :priority , :groups_id , :group , :keywords , :seokeywords , :description , :url , :urlref , :language , :name , :seoname , :body )',

                            array(':blogs_id'      => $post['blogs_id'],
                                  ':status'        => $post['status'],
                                  ':createdby'     => $_SESSION['user']['id'],
                                  ':categories_id' => $post['categories_id'],
                                  ':category'      => $post['category'],
                                  ':seocategory'   => $post['seocategory'],
                                  ':priority'      => $post['priority'],
                                  ':groups_id'     => $post['groups_id'],
                                  ':group'         => $post['group'],
                                  ':keywords'      => $post['keywords'],
                                  ':seokeywords'   => $post['seokeywords'],
                                  ':description'   => $post['description'],
                                  ':url'           => $post['url'],
                                  ':urlref'        => $post['urlref'],
                                  ':language'      => $post['language'],
                                  ':name'          => $post['name'],
                                  ':seoname'       => $post['seoname'],
                                  ':body'          => $post['body']));

            $post['id'] = sql_insert_id();
            blogs_update_keywords($post['blogs_id'], $post['id'], $post['keywords']);

            if(!empty($params['use_key_value'])){
                blogs_update_key_value_store($post['id'], $post, isset_get($params['key_value']));
            }

            html_flash_set(str_replace('%post%', str_log($post['name']), $params['flash_created']), 'success');
            redirect($params['redirect'].'post='.$post['seoname']);

        }catch(Exception $e){
            html_flash_set(tr('Failed to create blog post because "%message%"', array('%message%' => $e->getMessage())), 'error');
        }
    }

}else{
    $mode = 'modify';

    if(!$post = sql_get('SELECT * FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `seoname` = :seoname', array(':blogs_id' => $blog['id'], ':seoname' => $_GET['post']))){
        /*
         *
         */
        html_flash_set('The specified blog post "'.$_GET['post'].'" was not found', 'error');
        redirect('/admin/');
    }

    $post['blog']       = $blog['seoname'];
    $post['key_values'] = sql_list('SELECT `key`, `value` FROM `blogs_key_values` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post['id']));

    if(!empty($_POST['doupdate'])){
        /*
         * Modify the specified post
         */
        try{
            $post = $_POST;
            $post = blogs_validate_post($post, $params);

            if(!$dbpost = sql_get('SELECT * FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `id` = :id', array(':blogs_id' => $blog['id'], ':id' => $post['id']))){
                /*
                 * This blog post does not exist
                 */
                throw new bException(tr('Can not update blog post "%name%", it does not exist', '%name%', str_log($post['name'])), 'notexists');
            }

            if(sql_get('SELECT `id` FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `id` != :id AND `name` = :name', array(':blogs_id' => $blog['id'], ':id' => $post['id'], ':name' => $post['name']), 'id')){
                /*
                 * Another post with this name already exists
                 */
                throw new bException(tr('Another post with the name "%name%" already exists', '%name%', str_log($post['name'])), 'exists');
            }

            $category = blogs_validate_category($post['seocategory'], $blog, $params['categories_parent']);

            $post['categories_id'] = $category['id'];
            $post['seocategory']   = $category['seoname'];
            $post['category']      = $category['name'];

            if($params['use_groups']){
                $group = blogs_validate_category($post['group']   , $blog, $params['groups_parent']);

                $post['groups_id'] = $group['id'];
                $post['group']     = $group['seoname'];

            }else{
                $post['groups_id']     = null;
                $post['group']         = '';
            }

            load_libs('seo');

// :TODO: seo_generate_unique_name() only works on "seoname" column, but seoname is NOT unique, blogs_id, seoname is unique!
            if($params['use_keywords']){
                $post['keywords']      = blogs_clean_keywords($post['keywords']);
                $post['seokeywords']   = blogs_seo_keywords($post['keywords']);

            }else{
                $post['keywords']      = '';
                $post['seokeywords']   = '';
            }

            $post['seoname']       = seo_generate_unique_name($post['name'], 'blogs_posts', $post['id']);
            $post['blogs_id']      = $blog['id'];
            $post['blog']          = $blog['seoname'];
            $post['priority']      = blogs_priority($post['priority']);
            $post                  = array_copy_clean($post, $dbpost);
            $post['url']           = blogs_post_url($post);

            $r = sql_query('UPDATE  `blogs_posts`

                            SET     `blogs_id`      = :blogs_id,
                                    `status`        = :status,
                                    `modifiedby`    = :modifiedby,
                                    `modifiedon`    = NOW(),
                                    `categories_id` = :categories_id,
                                    `category`      = :category,
                                    `seocategory`   = :seocategory,
                                    `groups_id`     = :groups_id,
                                    `group`         = :group,
                                    `priority`      = :priority,
                                    `language`      = :language,
                                    `keywords`      = :keywords,
                                    `seokeywords`   = :seokeywords,
                                    `description`   = :description,
                                    `url`           = :url,
                                    `name`          = :name,
                                    `seoname`       = :seoname,
                                    `body`          = :body

                            WHERE   `id`            = :id',

                            array(':id'            => $post['id'],
                                  ':blogs_id'      => $post['blogs_id'],
                                  ':status'        => $post['status'],
                                  ':modifiedby'    => $_SESSION['user']['id'],
                                  ':categories_id' => $post['categories_id'],
                                  ':category'      => $post['category'],
                                  ':seocategory'   => $post['seocategory'],
                                  ':groups_id'     => $post['groups_id'],
                                  ':group'         => $post['group'],
                                  ':priority'      => $post['priority'],
                                  ':language'      => $post['language'],
                                  ':keywords'      => $post['keywords'],
                                  ':seokeywords'   => $post['seokeywords'],
                                  ':description'   => $post['description'],
                                  ':url'           => $post['url'],
                                  ':name'          => $post['name'],
                                  ':seoname'       => $post['seoname'],
                                  ':body'          => $post['body']));

            blogs_update_keywords($post['blogs_id'], $post['id'], $post['keywords']);

            if(!empty($params['use_key_value'])){
                blogs_update_key_value_store($post['id'], $post, isset_get($params['key_value']));
            }

            html_flash_set(str_replace('%post%', str_log($post['name']), $params['flash_updated']), 'success');
            redirect($params['redirect'].'post='.$post['seoname']);

        }catch(Exception $e){
            switch($e->getCode()){
                case 'validation':
                    html_flash_set(tr('Failed to update blog post because "%message%"', array('%message%' => $e->getMessage())), 'error', tr('Failed to update blog post because "%message%"', '%message%', $e->getMessages(', ')));
                    break;

                default:
                    html_flash_set(tr('Failed to update blog post because "%message%"', array('%message%' => $e->getMessage())), 'error');
            }
        }
    }
}

array_default($params, 'form_action', '/admin/blogs_post.php?blog='.$blog['seoname'].(isset_get($post['seoname']) ? '&post='.$post['seoname'] : ''));

$blogs      = array('selected' => isset_get($post['blog'], ''));

$editor     = array('name'     => 'body',
                    'value'    => isset_get($post['body']));

$categories = array('blogs_id' => $blog['id'],
                    'class'    => 'form-control',
                    'parent'   => $params['categories_parent'],
                    'selected' => isset_get($post['seocategory'], ''),
                    'none'     => $params['categories_none']);

$groups     = array('name'     => 'group',
                    'class'    => 'form-control',
                    'parent'   => $params['groups_parent'],
                    'blogs_id' => $blog['id'],
                    'selected' => isset_get($post['group'], ''),
                    'none'     => $params['groups_none']);

$priorities = array('blogs_id' => $blog['id'],
                    'class'    => 'form-control',
                    'selected' => isset_get($post['priority'], ''),
                    'none'     => $params['priorities_none']);

if($params['use_status']){
    $params['status_select']['selected'] = isset_get($post['status'], '');
}

$html = '   <form id="blogpost" name="blogpost" action="'.domain($params['form_action']).'" method="post">
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.$params['subtitle'].'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="col-md-3 control-label" for="category">'.$params['label_category'].'</label>
                                    <div class="col-md-9">
                                        '.blogs_categories_select($categories).'
                                    </div>
                                </div>';

if($params['use_groups']){
    $html .= '  <div class="form-group">
                    <label class="col-md-3 control-label" for="group">'.$params['label_group'].'</label>
                    <div class="col-md-9">
                        '.blogs_categories_select($groups).'
                    </div>
                </div>';
}

if($params['use_priorities']){
    $html .= '  <div class="form-group">
                    <label class="col-md-3 control-label" for="priority">'.$params['label_priority'].'</label>
                    <div class="col-md-9">
                        '.blogs_priorities_select($priorities).'
                    </div>
                </div>';
}

$html .= '  <div class="form-group">
                <label class="col-md-3 control-label" for="title">'.$params['label_title'].'</label>
                <div class="col-md-9">
                    <input type="text" class="form-control" name="name" id="name" value="'.htmlentities(isset_get($post['name'], '')).'" placeholder="'.tr('Specify a good title for your blog post').'" maxlength="'.$params['namemax'].'">
                </div>
            </div>';

if($params['use_keywords']){
    $html .= '  <div class="form-group">
                    <label class="col-md-3 control-label" for="keywords">'.$params['label_keywords'].'</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="keywords" id="keywords" value="'.htmlentities(isset_get($post['keywords'])).'" placeholder="'.$params['placeholder_keywords'].'" maxlength="255">
                    </div>
                </div>';
}

if($params['use_description']){
    $html .= '  <div class="form-group">
                    <label class="col-md-3 control-label" for="description">'.$params['label_description'].'</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="description" id="description" value="'.htmlentities(isset_get($post['description'])).'" placeholder="'.$params['placeholder_description'].'" maxlength="160">
                    </div>
                </div>';
}

if($params['use_url']){
    $html .= '  <div class="form-group">
                    <label class="col-md-3 control-label" for="urlref">'.tr('URL').'</label>
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="urlref" id="urlref" value="'.htmlentities(isset_get($post['urlref'])).'" placeholder="'.$params['placeholder_url'].'" maxlength="255">
                    </div>
                </div>';
}

if($params['use_status']){
    $html .= '  <div class="form-group">
                    <label class="col-md-3 control-label" for="status">'.tr('Status').'</label>
                    <div class="col-md-9">
                        '.html_status_select($params['status_select']).'
                    </div>
                </div>';
}

if($params['use_key_value']){
    foreach($params['key_value'] as $keyvalue){
        $keyvalue['class'] = 'form-control';

        $html .= '  <div class="form-group">
                        <label class="col-md-3 control-label" for="status">'.isset_get($keyvalue['label']).'</label>
                        <div class="col-md-9">';

        if(empty($keyvalue['resource'])){
            $html .= '<input type="text" class="form-control" name="key_value['.isset_get($keyvalue['name']).']" id="key_value['.isset_get($keyvalue['name']).']" value="'.isset_get($post['key_values'][$keyvalue['name']]).'" placeholder="'.isset_get($keyvalue['placeholder_url']).'" maxlength="255">';

        }else{
            $keyvalue['selected'] = isset_get($post['key_values'][$keyvalue['name']]);
            $keyvalue['name']     = 'key_value['.isset_get($keyvalue['name']).']';
            $html                .= html_select($keyvalue);
        }

        $html .= '     </div>
                    </div>';
    }
}

$html .= '                      <div class="form-group">
                                    <div class="col-md-12">
                                        '.editors_tinymce($editor).'
                                    </div>
                                </div>
                                <input type="hidden" name="id" value="'.isset_get($post['id']).'">'.
                                (($mode == 'create') ? '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="docreate" id="docreate" value="'.tr('Create').'">'
                                                     : '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="doupdate" id="doupdate" value="'.tr('Update').'">').
                                 (empty($post['id']) ? '' : ' <button class="fileUpload mb-xs mt-xs mr-xs btn btn-primary">'.tr('Add more photos').'</button><input id="fileUpload" type="file" name="files[]" multiple> ').'
                                <a class="mb-xs mt-xs mr-xs btn btn-primary" href="'.domain($params['back']).'">'.tr('Back').'</a>
                            </div>
                        </section>
                    </div>
                </div>
            </form>';

if(empty($post['id'])){
    $html .= $params['label_photos'];

}else{
    $photos = sql_list('SELECT `id`, `file`, `description` FROM `blogs_photos` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post['id']));

    if(!count($photos)){
        $html .= '  <div class="blogpost photos">
                        <div class="blogpost nophotos">'.tr('This post has no separate photos yet').'</div>
                    </div>';

    }else{
        $html .= '<div class="blogpost photos">';

        foreach($photos as $id => $photo){
            $html .= '<div class="blogpost photo" id="photo'.$id.'">
                        <img src="'.blogs_photo_url($photo['file'], true).'" />
                        <textarea class="blogpost photo description" placeholder="'.tr('Description of this photo').'">'.$photo['description'].'</textarea>
                        <a class="blogpost photo delete button">'.tr('Delete this photo').'</a>
                    </div>';
        }

        $html .= '</div>';
    }
}

$html .= '      </fieldset>'.

html_script('
    $(document).on("change", "textarea.photo.description", function(event){
        var desc  = $(this).val();
        var id    = $(this).closest("div.photo").prop("id");
        var jqxhr = $.post("/ajax/blog/photos/description.php", {desc:desc, id:id}, function() {
            //ok, no need to do anything
        })
        .fail(function() { alert("'.tr('Something went wrong, please try again later').'"); })
    });

    $(document).on("click", ".blogpost.photo.delete", function(event){
        var id    = $(this).closest("div.photo").prop("id").from("photo");
        var jqxhr = $.post("/ajax/blog/photos/delete.php", {id:id})

            .done(function() {
                $("#photo" + id).animate({opacity : 0}, 200, function(){
                    $("#photo" + id).animate({height : 0}, 200, function(){
                        $("#photo" + id).remove();
                    });
                });
            })

            .fail(function() { alert("'.tr('Something went wrong, please try again later').'"); })
    });

    $("#fileUploadCapture").click(function(){
        $("#fileupload").trigger("click");
    });

    $("button.fileUpload").click(function(e){
        e.stopPropagation();
        $("#fileUpload").trigger("click");
        return false;
    });
').

html_script('
    function add_image(data){
        var $obj = $(data.html);
        $("div.blogpost.nophotos").remove();
        $("div.blogpost.photos").prepend($obj);
        $obj.fadeIn("slow");
    }', false).

upload_multi_js('#fileUpload','/ajax/blog/photos/upload.php', 'function(data){add_image(data);}', 'function(e){ console.log(e); /* Show HTML flash */ }');

// :TODO: The categories should reload when the blog changes!!
//html_script('$().change(function(){});');

/*
 * Add JS validation
 */
$vj = new validate_jquery();

$vj->validate('name'    , 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['name_required']);
$vj->validate('blog'    , 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['blog_required']);
$vj->validate('category', 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['category_required']);
$vj->validate('body'    , 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['body_required']);

$vj->validate('name'    , 'minlength', '2'               , '<span class="FcbErrorTail"></span>'.$params['errors']['name_min']);
$vj->validate('body'    , 'minlength', $params['bodymin'], '<span class="FcbErrorTail"></span>'.$params['errors']['body_min']);

if($params['use_status']){
    $vj->validate('status', 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['status_required']);
}

if($params['use_groups']){
    $vj->validate('group', 'required', 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['group_required']);
}

if($params['use_priorities']){
    $vj->validate('priority', 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['priority_required']);
}

if($params['use_keywords']){
    $vj->validate('keywords'   , 'minlength', '2'   , '<span class="FcbErrorTail"></span>'.$params['errors']['keywords_required']);
    $vj->validate('keywords'   , 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['keywords_min']);
}

if($params['use_description']){
    $vj->validate('description', 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['description_required']);
    $vj->validate('description', 'minlength', '16'  , '<span class="FcbErrorTail"></span>'.$params['errors']['description_min']);
    $vj->validate('description', 'maxlength', '160' , '<span class="FcbErrorTail"></span>'.$params['errors']['description_max']);
}

$html .= $vj->output_validation(array('id'   => 'blogpost',
                                      'json' => false));

$params = array('icon'        => 'fa-users',
                'title'       => $params['title'],
                'breadcrumbs' => array(tr('Rererrers'), tr('Manage')),
                'script'      => $params['script']);

echo ca_page($html, $params);
?>