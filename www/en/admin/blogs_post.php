<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');
load_libs('editors,blogs,validate,upload');



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

if(($blog['createdby'] != $_SESSION['user']['id']) and !has_rights($blog['seoname'])) {
    html_flash_set(tr('You do not have access to the %object% "%blog%"', array('%object%' => $params['object'], '%blog%' => $blog['name'])), 'error');
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
array_default($params, 'assigned_to_none'       , tr('Select to assign'));
array_default($params, 'assigned_to_empty'      , tr('No users available'));
array_default($params, 'groups_parent'          , null);
array_default($params, 'default_priority'       , 3);
array_default($params, 'priorities_none'        , '');
array_default($params, 'flash_created'          , tr('The post "%post%" has been created'));
array_default($params, 'flash_updated'          , tr('The post "%post%" has been updated'));
// array_default for form_action is done below!
array_default($params, 'label_assigned_to'      , tr('Assigned to'));
array_default($params, 'label_category'         , tr('Category'));
array_default($params, 'label_createdon'        , tr('Created on'));
array_default($params, 'label_group'            , tr('Group'));
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
array_default($params, 'use_assigned_to'        , false);
array_default($params, 'use_createdon'          , false);
array_default($params, 'use_category'           , true);
array_default($params, 'use_description'        , true);
array_default($params, 'use_groups'             , false);
array_default($params, 'use_keywords'           , true);
array_default($params, 'use_language'           , false);
array_default($params, 'use_priorities'         , false);
array_default($params, 'use_status'             , false);
array_default($params, 'use_key_value'          , false);
array_default($params, 'use_url'                , false);
array_default($params, 'use_append'             , false);
array_default($params, 'key_value'              , null);
array_default($params, 'use_history'            , false);

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
            blogs_validate_post($post, $blog, $params);

// :TODO: seo_generate_unique_name() only works on "seoname" column, but seoname is NOT unique, blogs_id, seoname is unique!

            $r = sql_query('INSERT INTO `blogs_posts` (`blogs_id`, `status`, `createdby`, `category`, `assigned_to_id`, `seocategory`, `priority`, `seogroup`, `group`, `keywords`, `seokeywords`, `description`, `url`, `urlref`, `language`, `name`, `seoname`, `body`)
                            VALUES                    (:blogs_id , :status , :createdby , :category , :assigned_to_id , :seocategory , :priority , :seogroup , :group , :keywords , :seokeywords , :description , :url , :urlref , :language , :name , :seoname , :body )',

                            array(':blogs_id'       => $post['blogs_id'],
                                  ':assigned_to_id' => $post['assigned_to_id'],
                                  ':status'         => $post['status'],
                                  ':createdby'      => $_SESSION['user']['id'],
                                  ':category'       => $post['category'],
                                  ':seocategory'    => $post['seocategory'],
                                  ':priority'       => $post['priority'],
                                  ':group'          => $post['group'],
                                  ':seogroup'       => $post['seogroup'],
                                  ':keywords'       => $post['keywords'],
                                  ':seokeywords'    => $post['seokeywords'],
                                  ':description'    => $post['description'],
                                  ':url'            => $post['url'],
                                  ':urlref'         => $post['urlref'],
                                  ':language'       => $post['language'],
                                  ':name'           => $post['name'],
                                  ':seoname'        => $post['seoname'],
                                  ':body'           => $post['body']));

            $post['id'] = sql_insert_id();
            blogs_update_keywords($post['blogs_id'], $post['id'], $post['keywords']);

            if(!empty($params['use_key_value'])){
                blogs_update_key_value_store($post['id'], $post, isset_get($params['key_value']));
            }

            html_flash_set(str_replace('%post%', str_log($post['name']), $params['flash_created']), 'success');
            redirect($params['redirect'].'post='.$post['seoname']);

        }catch(Exception $e){
            html_flash_set(tr('Failed to create blog post because: %message%', array('%message%' => $e->getMessage())), 'error');
        }
    }

}else{
    $mode = 'modify';
    $post = sql_get('SELECT    `blogs_posts`.*,
                               `users`.`name` AS `assigned_to`

                     FROM      `blogs_posts`

                     LEFT JOIN `users`
                     ON        `users`.`id` = `blogs_posts`.`assigned_to_id`

                     WHERE     `blogs_posts`.`blogs_id` = :blogs_id
                     AND       `blogs_posts`.`seoname`  = :seoname', array(':blogs_id' => $blog['id'], ':seoname' => $_GET['post']));

    if(!$post){
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
            blogs_validate_post($post, $blog, $params, isset_get($_GET['post']));


// :TODO: seo_generate_unique_name() only works on "seoname" column, but seoname is NOT unique, blogs_id, seoname is unique!
            $query = 'UPDATE  `blogs_posts`

                      SET     `blogs_id`       = :blogs_id,
                              `assigned_to_id` = :assigned_to_id,
                              `status`         = :status,
                              '.($params['use_createdon'] ? '`createdon`     = :createdon,' : '').'
                              `modifiedby`     = :modifiedby,
                              `modifiedon`     = NOW(),
                              `category`       = :category,
                              `seocategory`    = :seocategory,
                              `group`          = :group,
                              `seogroup`       = :seogroup,
                              `priority`       = :priority,
                              `language`       = :language,
                              `keywords`       = :keywords,
                              `seokeywords`    = :seokeywords,
                              `description`    = :description,
                              `url`            = :url,
                              `name`           = :name,
                              `seoname`        = :seoname,
                              `body`           = :body

                      WHERE   `id`             = :id';

            $execute = array(':id'             => $post['id'],
                             ':blogs_id'       => $post['blogs_id'],
                             ':assigned_to_id' => $post['assigned_to_id'],
                             ':status'         => $post['status'],
                             ':modifiedby'     => $_SESSION['user']['id'],
                             ':category'       => $post['category'],
                             ':seocategory'    => $post['seocategory'],
                             ':group'          => $post['group'],
                             ':seogroup'       => $post['seogroup'],
                             ':priority'       => $post['priority'],
                             ':language'       => $post['language'],
                             ':keywords'       => $post['keywords'],
                             ':seokeywords'    => $post['seokeywords'],
                             ':description'    => $post['description'],
                             ':url'            => $post['url'],
                             ':name'           => $post['name'],
                             ':seoname'        => $post['seoname'],
                             ':body'           => $post['body']);

            if($params['use_createdon']){
                $execute[':createdon'] = system_date_format($post['createdon'], 'mysql');
            }

            $r = sql_query($query, $execute);

            blogs_update_keywords($post['blogs_id'], $post['id'], $post['keywords']);

            if(!empty($params['use_key_value'])){
                blogs_update_key_value_store($post['id'], $post, isset_get($params['key_value']));
            }

            html_flash_set(str_replace('%post%', str_log($post['name']), $params['flash_updated']), 'success');
            redirect($params['redirect'].'post='.$post['seoname']);

        }catch(Exception $e){
            switch($e->getCode()){
                case 'validation':
                    html_flash_set(tr('Failed to update blog post because: %message%', array('%message%' => $e->getMessage())), 'error', tr('Failed to update blog post because "%message%"', '%message%', $e->getMessages(', ')));
                    break;

                default:
                    html_flash_set(tr('Failed to update blog post because: %message%', array('%message%' => $e->getMessage())), 'error');
            }
        }
    }
}

array_default($params, 'form_action', '/admin/blogs_post.php?blog='.$blog['seoname'].(isset_get($post['seoname']) ? '&post='.$post['seoname'] : ''));

$html = '   <form id="blogpost" name="blogpost" action="'.domain($params['form_action']).'" method="post">
                <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.$params['subtitle'].'</h2>
                                <p>'.html_flash().'</p>
                            </header>
                            <div class="panel-body">';

$controls = array('left'  => array(),
                  'right' => array());

if($params['use_category']){
    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="category">'.$params['label_category'].'</label>
                                    <div class="col-md-9">
                                        '.blogs_categories_select(array('blogs_id' => $blog['id'],
                                                                        'class'    => 'form-control',
                                                                        'parent'   => $params['categories_parent'],
                                                                        'selected' => isset_get($post['seocategory'], ''),
                                                                        'none'     => $params['categories_none'])).'
                                    </div>
                                </div>';
}

if($params['use_createdon']){
    load_libs('jqueryui');

    $createdon  = array('class'       => 'form-control',
                        'placeholder' => '',
                        'value'       => isset_get($post['createdon'], ''));

    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="group">'.$params['label_createdon'].'</label>
                                    <div class="col-md-9">
                                        '.jqueryui_date('createdon', $createdon).'
                                    </div>
                                </div>';
}

if($params['use_groups']){
    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="group">'.$params['label_group'].'</label>
                                    <div class="col-md-9">
                                        '.blogs_categories_select(array('name'     => 'seogroup',
                                                                        'class'    => 'form-control',
                                                                        'parent'   => $params['groups_parent'],
                                                                        'blogs_id' => $blog['id'],
                                                                        'selected' => isset_get($post['seogroup'], ''),
                                                                        'none'     => $params['groups_none'])).'
                                    </div>
                                </div>';
}

if($params['use_assigned_to']){
    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="group">'.$params['label_assigned_to'].'</label>
                                    <div class="col-md-9">
                                        '.html_select(array('name'     => 'assigned_to',
                                                            'class'    => 'form-control',
                                                            'selected' => isset_get($post['assigned_to'], ''),
                                                            'none'     => $params['assigned_to_none'],
                                                            'empty'    => $params['assigned_to_empty'],
                                                            'resource' => sql_query('SELECT `name` AS `id`, `name` FROM `users` WHERE `type` IS NULL AND `status` IS NULL'))).'
                                    </div>
                                </div>';
}

if($params['use_priorities']){
    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="priority">'.$params['label_priority'].'</label>
                                    <div class="col-md-9">
                                        '.html_select(array('name'     => 'priority',
                                                            'class'    => 'form-control',
                                                            'selected' => isset_get($post['priority'], $params['default_priority']),
                                                            'none'     => $params['priorities_none'],
                                                            'resource' => array(4 => tr('Low'),
                                                                                3 => tr('Normal'),
                                                                                2 => tr('High'),
                                                                                1 => tr('Urgent'),
                                                                                0 => tr('Immediate')))).'
                                    </div>
                                </div>';
}

$controls[blog_side()][] = '<div class="form-group">
                                <label class="col-md-3 control-label" for="title">'.$params['label_title'].'</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" name="name" id="name" value="'.htmlentities(isset_get($post['name'], '')).'" placeholder="'.tr('Specify a good title for your blog post').'" maxlength="'.$params['namemax'].'">
                                </div>
                            </div>';

if($params['use_keywords']){
    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="keywords">'.$params['label_keywords'].'</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="keywords" id="keywords" value="'.htmlentities(isset_get($post['keywords'])).'" placeholder="'.$params['placeholder_keywords'].'" maxlength="255">
                                    </div>
                                </div>';
}

if($params['use_description']){
    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="description">'.$params['label_description'].'</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="description" id="description" value="'.htmlentities(isset_get($post['description'])).'" placeholder="'.$params['placeholder_description'].'" maxlength="160">
                                    </div>
                                </div>';
}

if($params['use_url']){
    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="urlref">'.tr('URL').'</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="urlref" id="urlref" value="'.htmlentities(isset_get($post['urlref'])).'" placeholder="'.$params['placeholder_url'].'" maxlength="255">
                                    </div>
                                </div>';
}

if($params['use_status']){
    $params['status_select']['selected'] = isset_get($post['status'], '');

    $controls[blog_side()][] = '<div class="form-group">
                                    <label class="col-md-3 control-label" for="status">'.tr('Status').'</label>
                                    <div class="col-md-9">
                                        '.html_status_select($params['status_select']).'
                                    </div>
                                </div>';
}

if($params['use_key_value']){
    $side = blog_side();

    foreach($params['key_value'] as $keyvalue){
        $keyvalue['class'] = 'form-control';

        $keyvalue_html = '  <div class="form-group">
                                <label class="col-md-3 control-label" for="status">'.isset_get($keyvalue['label']).'</label>
                                <div class="col-md-9">';

        if(empty($keyvalue['resource'])){
            $keyvalue_html .= '<input type="text" class="form-control" name="key_value['.isset_get($keyvalue['name']).']" id="key_value['.isset_get($keyvalue['name']).']" value="'.isset_get($post['key_values'][$keyvalue['name']]).'" placeholder="'.isset_get($keyvalue['placeholder_url']).'" maxlength="255">';

        }else{
            $keyvalue['selected'] = isset_get($post['key_values'][$keyvalue['name']]);
            $keyvalue['name']     = 'key_value['.isset_get($keyvalue['name']).']';
            $keyvalue_html       .= html_select($keyvalue);
        }

        $keyvalue_html .= '     </div>
                            </div>';

        $controls[$side][] = $keyvalue_html;
    }
}

foreach($controls['left'] as $id => $control){
    $html .= '  <div class="row">
                    <div class="col-md-6">
                        '.$control.'
                    </div>';

    if(!empty($controls['right'][$id])){
        $html .= '  <div class="col-md-6">
                        '.$controls['right'][$id].'
                    </div>';
    }

    $html .= '  </div>';
}

if($params['use_append']){
    /*
     * Show a readonly history, allow only add hisory, no edits
     */
    if(isset_get($post['body'])){
        $html .= '                      <hr>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h2>'.tr('Ticket history').'</h2>
                                                <div class="form-group">
                                                    <div class="col-md-12">
                                                        '.$post['body'].'
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';

        $post['body']    = '';
    }
}

$html .= '                      <hr>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="col-md-12">
                                                '.editors_tinymce(array('name'  => 'body',
                                                                        'value' => isset_get($post['body']))).'
                                            </div>
                                        </div>
                                        <input type="hidden" name="id" value="'.isset_get($post['id']).'">'.
                                        (($mode == 'create') ? '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="docreate" id="docreate" value="'.tr('Create').'">'
                                                             : '<input type="submit" class="mb-xs mt-xs mr-xs btn btn-primary" name="doupdate" id="doupdate" value="'.tr('Update').'">').
                                         (empty($post['id']) ? '' : ' <button class="fileUpload mb-xs mt-xs mr-xs btn btn-primary">'.tr('Add more photos').'</button><input id="fileUpload" type="file" name="files[]" multiple> ').'
                                        <a class="mb-xs mt-xs mr-xs btn btn-primary" href="'.domain($params['back']).'">'.tr('Back').'</a>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </form>';

if(empty($post['id'])){
    $html .= $params['label_photos'];

}else{
    $photos = sql_list('SELECT `id`, `file`, `description` FROM `blogs_photos` WHERE `blogs_posts_id` = :blogs_posts_id ORDER BY `priority`', array(':blogs_posts_id' => $post['id']));

    $html  .= ' <div class="row">
                    <div class="col-md-12">
                        <section class="panel">
                            <header class="panel-heading">
                                <h2 class="panel-title">'.tr('Manage photos').'</h2>
                            </header>
                            <div class="panel-body blogpost photos">';

    if(!count($photos)){
        $html .= '<div class="blogpost nophotos">'.tr('This post has no separate photos yet').'</div>';

    }else{
        foreach($photos as $id => $photo){
            /*
             * Get photo dimensions
             */
            try{
                $image = getimagesize(ROOT.'www/en/photos/'.$photo['file'].'_big.jpg');

            }catch(Exception $e){
                $image = false;
            }

            if(!$image){
                $image = array(tr('Invalid image'), tr('Invalid image'));
            }

            $html .= '  <div class="form-group photo" id="photo'.$id.'">
                            <a target="_blank" href="'.blogs_photo_url($photo['file']).'">
                                <img class="col-md-1 control-label" src="'.blogs_photo_url($photo['file'], true).'" />
                            </a>
                            <div class="col-md-11 blogpost">
                                <textarea class="blogpost photo description form-control" placeholder="'.tr('Description of this photo').'">'.$photo['description'].'</textarea>
                                <p>
                                    (Dimensions '.$image[0].' X '.$image[1].')
                                    <a <a class="mb-xs mt-xs mr-xs btn btn-primary blogpost photo up button">'.tr('Up').'</a>
                                    <a <a class="mb-xs mt-xs mr-xs btn btn-primary blogpost photo down button">'.tr('Down').'</a>
                                    <a <a class="mb-xs mt-xs mr-xs btn btn-primary blogpost photo delete button">'.tr('Delete this photo').'</a>
                                </p>
                            </div>
                        </div>';
        }
    }

    $html .= '          </div>
                    </section>
                </div>
            </div>';
}

$html .= '      </fieldset>'.

html_script('
    $(document).on("change", "textarea.photo.description", function(event){
        var desc  = $(this).val();
        var id    = $(this).closest("div.photo").prop("id");
        var jqxhr = $.post("/ajax/blog/photos/description.php", {desc:desc, id:id}, function() {
            //ok, no need to do anything
        })
        .fail(function() { $.flashMessage("'.tr('Something went wrong, please try again later').'", "error"); })
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

            .fail(function() { $.flashMessage("'.tr('Something went wrong, please try again later').'", "error"); })
    });

    $(document).on("click", ".blogpost.photo.up", function(event){
        var self  = $(this).closest("div.form-group");
        var id    = $(this).closest("div.photo").prop("id").from("photo");
        var jqxhr = $.post("/ajax/blog/photos/up.php", {id:id})
            .done(function() {
                self.prev().insertAfter(self);
            })

            .fail(function() { $.flashMessage("'.tr('Something went wrong, please try again later').'", "error"); })
    });

    $(document).on("click", ".blogpost.photo.down", function(event){
        var self  = $(this).closest("div.form-group");
        var id    = $(this).closest("div.photo").prop("id").from("photo");
        var jqxhr = $.post("/ajax/blog/photos/down.php", {id:id})
            .done(function() {
                self.next().insertBefore(self);
            })

            .fail(function() { $.flashMessage("'.tr('Something went wrong, please try again later').'", "error"); })
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
        $("div.blogpost.photos").append($obj);
        $obj.fadeIn("slow");
    }', false).

upload_multi_js('#fileUpload','/ajax/blog/photos/upload.php', 'function(data){add_image(data);}', 'function(e){ console.log(e); /* Show HTML flash */ }');

// :TODO: The categories should reload when the blog changes!!
//html_script('$().change(function(){});');

/*
 * Add JS validation
 */
$vj = new validate_jquery();

$vj->validate('name'       , 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['name_required']);
$vj->validate('blog'       , 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['blog_required']);
$vj->validate('seocategory', 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['category_required']);
$vj->validate('body'       , 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['body_required']);

$vj->validate('name'    , 'minlength', '2'               , '<span class="FcbErrorTail"></span>'.$params['errors']['name_min']);
$vj->validate('body'    , 'minlength', $params['bodymin'], '<span class="FcbErrorTail"></span>'.$params['errors']['body_min']);

if($params['use_status']){
    $vj->validate('status', 'required' , 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['status_required']);
}

if($params['use_groups']){
    $vj->validate('seogroup', 'required', 'true', '<span class="FcbErrorTail"></span>'.$params['errors']['group_required']);
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



/*
 *
 */
function blog_side(){
    static $side;

    if(!$side or ($side == 'right')){
        return $side = 'left';
    }

    return $side = 'right';
}
?>