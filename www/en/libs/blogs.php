<?php
/*
 * Blogs library
 *
 * This library contains functions to manage and display blogs and blog entries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



load_config('blogs');



/*
 * List available blogs
 */
function blogs_list($user, $from = null, $until = null, $limit = null){
    try{
        if(is_array($user)){
            $user = isset_get($user['id']);
        }

        $execute =  array();

        $query   = 'SELECT `addedon`,
                           `rights_id`,
                           `title`

                    FROM   `blogs_posts`

                    WHERE  `status`  = "posted"';

        if($user){
            $query    .= ' AND `createdby` = :createdby';
            $execute[] = array(':createdby' => $user);
        }

        if($from){
            $query    .= ' AND `addedon` >= :from';
            $execute[] = array(':from' => $from);
        }

        if($until){
            $query    .= ' AND `addedon` <= :until';
            $execute[] = array(':until' => $until);
        }

        if($limit){
            $query    .= ' LIMIT '.cfi($limit);
        }

        return sql_list($query, $execute);

    }catch(Exception $e){
        throw new bException('blogs_list(): Failed', $e);
    }
}



/*
 * Set the status of the specified blog
 */
function blogs_post($blog){
    try{
        /*
         * Only users may post blogs
         */
        user_or_redirect();

        if(is_array($blog)){
            $blog = isset_get($blog['id']);
        }

        if(!$blog){
            throw new bException('blogs_post(): No blog specified', 'notspecified');
        }

        $execute = array(':id' => $blog);

        $query   = 'UPDATE `blogs_posts`

                    SET    `status` = "posted"

                    WHERE  `id`     = :id';

        if(!has_rights('admin')){
            /*
             * Only the user itself can post this
             */
            $query               .= ' AND `createdby` = :createdby';
            $execute[':createdby']  = $_SESSION['user']['id'];
        }

        return sql_query($query, $execute);

    }catch(Exception $e){
        throw new bException('blogs_post(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available blogs
 */
function blogs_select($params, $selected = 0, $name = 'blog', $none = '', $class = '', $option_class = '', $disabled = false) {
    try{
        array_params ($params, 'seoname');
        array_default($params, 'selected'    , $selected);
        array_default($params, 'class'       , $class);
        array_default($params, 'disabled'    , $disabled);
        array_default($params, 'name'        , $name);
        array_default($params, 'none'        , not_empty($none, tr('Select a blog')));
        array_default($params, 'option_class', $option_class);

        $params['resource'] = sql_query('SELECT   `seoname` AS id,
                                                  `name`
                                         FROM     `blogs`
                                         WHERE    `status` IS NULL
                                         ORDER BY `name` ASC');

        return html_select($params);

    }catch(Exception $e){
        throw new bException('blogs_select(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available blogs
 */
function blogs_categories_select($params) {
    try{
        array_params ($params);
        array_default($params, 'selected'    , 0);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'name'        , 'seocategory');
        array_default($params, 'column'      , '`blogs_categories`.`seoname`');
        array_default($params, 'none'        , tr('Select a category'));
        array_default($params, 'option_class', '');
        array_default($params, 'parent'      , null);
        array_default($params, 'filter'      , array());

        if(empty($params['blogs_id'])){
            throw new bException('blogs_categories_select(): No blog specified', 'notspecified');
        }

        $execute = array(':blogs_id' => $params['blogs_id']);

        $query   = 'SELECT   '.$params['column'].' AS id,
                             `blogs_categories`.`name`

                    FROM     `blogs_categories`

                    '.($params['parent'] ? ' JOIN `blogs_categories` AS parents ON `parents`.`seoname` = :parent AND `parents`.`id` = `blogs_categories`.`parents_id`' : '').'

                    WHERE    `blogs_categories`.`blogs_id` = :blogs_id
                    AND      `blogs_categories`.`status`   IS NULL';

        /*
         * Filter specified values.
         */
        foreach($params['filter'] as $key => $value){
            if(!$value) continue;

            $query            .= ' AND `'.$key.'` != :'.$key.' ';
            $execute[':'.$key] = $value;
        }

        $query  .= ' ORDER BY `name` ASC';

        if($params['parent']){
            $execute[':parent'] = $params['parent'];
        }

        $params['resource'] = sql_query($query, $execute);

        return html_select($params);

    }catch(Exception $e){
        throw new bException('blogs_categories_select(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available blogs
 */
function blogs_priorities_select($params, $selected = 0, $name = 'priority', $none = '', $class = '', $option_class = '', $disabled = false) {
    try{
        array_params ($params, 'seoname');
        array_default($params, 'selected'    , $selected);
        array_default($params, 'class'       , $class);
        array_default($params, 'disabled'    , $disabled);
        array_default($params, 'name'        , $name);
        array_default($params, 'column'      , 'seoname');
        array_default($params, 'none'        , not_empty($none, tr('Select a priority')));
        array_default($params, 'option_class', $option_class);
        array_default($params, 'filter'      , array());

        if(empty($params['blogs_id'])){
            throw new bException('blogs_priorities_select(): No blog specified', 'notspecified');
        }

        $params['resource'] = array('low'       => tr('Low'),
                                    'normal'    => tr('Normal'),
                                    'high'      => tr('High'),
                                    'urgent'    => tr('Urgent'),
                                    'immediate' => tr('Immediate'));

        return html_select($params);

    }catch(Exception $e){
        throw new bException('blogs_priorities_select(): Failed', $e);
    }
}



/*
 * Update the key-value store for this blog post
 */
function blogs_update_key_value_store($blogs_posts_id, $post, $data){
    try{
        load_libs('seo');

        foreach($post['key_value'] as $key => $value){
            sql_query('INSERT INTO `blogs_key_values` (`blogs_posts_id`, `key`, `value`, `seovalue`)
                       VALUES                         (:blogs_posts_id , :key , :value , :seovalue )

                       ON DUPLICATE KEY UPDATE `value`    = :value,
                                               `seovalue` = :seovalue',

                       array(':blogs_posts_id' => $blogs_posts_id,
                             ':key'            => $key,
                             ':value'          => $value,
                             ':seovalue'       => seo_create_string($value)));
        }

    }catch(Exception $e){
        throw new bException('blogs_update_key_value_store(): Failed', $e);
    }
}



/*
 * Update the keywords in the blogs_keywords table and the
 * seokeywords column in the blogs_posts table
 */
function blogs_update_keywords($blogs_id, $post_id, $keywords){
    try{
        /*
         * Ensure all keywords of this blog post are gone
         */
        sql_query('DELETE FROM `blogs_keywords` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post_id));

        /*
         * Store the keywords
         */
        $p = sql_prepare('INSERT INTO `blogs_keywords` (`blogs_id`, `blogs_posts_id`, `createdby`, `name`, `seoname`)
                          VALUES                       (:blogs_id , :blogs_posts_id , :createdby , :name , :seoname )');

        foreach(array_force($keywords, ',') as $keyword){
            if(strlen($keyword) < 2) continue;

            $p->execute(array(':blogs_id'       => $blogs_id,
                              ':blogs_posts_id' => $post_id,
                              ':createdby'      => $_SESSION['user']['id'],
                              ':name'           => mb_trim($keyword),
                              ':seoname'        => seo_create_string($keyword)));
        }

    }catch(Exception $e){
        throw new bException('blogs_update_keywords(): Failed', $e);
    }
}



/*
 * Return keywords string for the specified keyword string where all keywords are trimmed
 */
function blogs_clean_keywords($keywords, $allow_empty = false){
    try{
        if(!$keywords and $allow_empty){
            return '';
        }

        $retval = array();

        foreach(array_force($keywords) as $keyword){
            $retval[] = mb_trim($keyword);
        }

        $retval = array_unique($retval);

        if(count($retval) > 15){
            throw new bException('blogs_clean_keywords(): Too many keywords. Do not use more than 15 keywords', 'invalid');
        }

        $retval = implode(',', $retval);

        if(strlen($retval) < 8){
            throw new bException('blogs_clean_keywords(): Keywords too short after cleanup (Possibly due to doubles?)', 'invalid');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('blogs_clean_keywords(): Failed', $e);
    }
}



/*
 * Return the seokeywords as a csv string
 */
function blogs_seo_keywords($keywords){
    try{
        $retval = array();

        foreach(array_force($keywords) as $keyword){
            $retval[] = seo_create_string($keyword);
        }

        return implode(',', $retval);

    }catch(Exception $e){
        throw new bException('blogs_generate_seokeywords(): Failed', $e);
    }
}



/*
 * Ensure that all post data is okay
 */
function blogs_validate_post($post, $params = null){
    try{
        array_params($params);
        array_default($params, 'namemax'    , 64);
        array_default($params, 'bodymin'    , 100);
        array_default($params, 'object_name', 'blog posts');

        // Validate input
        $v = new validate_form($post, 'name,seocategory,body,keywords,description,language,group,priority,urlref,status');

        $v->isChecked   ($post['name']      , tr('Please provide the name of your %objectname%'     , '%objectname%', $params['object_name']));
        $v->isNotEmpty ($post['seocategory'], tr('Please provide a category for your %objectname%'  , '%objectname%', $params['object_name']));
        $v->isNotEmpty ($post['body']       , tr('Please provide the body text of your %objectname%', '%objectname%', $params['object_name']));

        $v->hasMinChars($post['name']       ,                  4, tr('Please ensure that the name has a minimum of 4 characters'));
        $v->hasMinChars($post['body']       , $params['bodymin'], tr('Please ensure that the body text has a minimum of '.$params['bodymin'].' characters'));

        if(!empty($params['use_groups'])){
            $v->isNotEmpty ($post['group'], tr('Please provide a group for your %objectname%', '%objectname%', $params['object_name']));
        }

        if(!empty($params['use_language'])){
            $v->isNotEmpty($post['language'],    tr('Please select a language for your %objectname%', '%objectname%', $params['object_name']));
            $v->hasChars($post['keywords']  , 2, tr('Please provide a valid language'));
        }

        if($params['use_priorities']){
            $v->isNotEmpty ($post['priority'], tr('Please provide a priority for your %objectname%', '%objectname%', $params['object_name']));
        }

        if(!empty($params['use_keywords'])){
            $v->hasMinChars($post['keywords'], 2, tr('Please ensure that the keywords have a minimum of 2 characters'));
            $v->isNotEmpty ($post['keywords'],    tr('Please provide keywords for your %objectname%', '%objectname%', $params['object_name']));
        }

        if(!empty($params['use_description'])){
            $v->isNotEmpty ($post['description'],       tr('Please provide a description for your %objectname%', '%objectname%', $params['object_name']));
            $v->hasMinChars($post['description'],   16, tr('Please ensure that the description has a minimum of 16 characters'));
            $v->hasMaxChars($post['description'],  160, tr('Please ensure that the description has a maximum of 160 characters'));
        }

        if(!empty($params['use_status'])){
            if(empty($params['status_select']['resource'][$post['status']])){
                    $v->setError(tr('Please provide a valid status for your %objectname%', '%objectname%', $params['object_name']));
            }

        }else{
            $post['status'] = null;
        }

        if(!$v->isValid()) {
           throw new bException(str_force($v->getErrors(), ', '), 'validation');
        }

        return $post;

    }catch(Exception $e){
        if($e->getCode() == 'validation'){
            /*
             * Just throw the list of validation errors.
             */
            throw $e;
        }

        throw new bException('blogs_validate_post(): Failed', $e);
    }
}



/*
 * Process uploaded club photo
 */
function blogs_photos_upload($files, $post){
    global $_CONFIG;

    try{
        load_libs('file,image,upload');

        if(empty($post['id'])) {
            throw new bException('blogs_photos_upload(): No blog post specified', 'notspecified');
        }

        $post = sql_get('SELECT `blogs_posts`.`id`,
                                `blogs_posts`.`createdby`,
                                `blogs_posts`.`seoname`,

                                `blogs`.`seoname` AS blog_name,
                                `blogs`.`images_x`,
                                `blogs`.`images_y`,
                                `blogs`.`thumbs_x`,
                                `blogs`.`thumbs_y`

                         FROM   `blogs_posts`

                         JOIN   `blogs`
                         ON     `blogs_posts`.`blogs_id` = `blogs`.`id`

                         WHERE  `blogs_posts`.`id`       = '.cfi($post['id']));

        if(empty($post['id'])) {
            throw new bException('blogs_photos_upload(): Unknown blog post specified', 'unknown');
        }

        if(($post['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new bException('blogs_photos_upload(): Cannot upload photos, this post is not yours', 'accessdenied');
        }

        /*
         * Check for upload errors
         */
        $failed = upload_check_files($files);

        if(!empty($failed)){
            throw new bException($failed[0]['message'], 'failed');
        }

        /*
         *
         */
        $file  = $files;
        $file  = file_get_local($file['tmp_name'][0]);
        $photo = $post['blog_name'].'/'.file_assign_target_clean(ROOT.'www/photos/'.$post['blog_name'].'/', '_small.jpg', false, 4);

        if(!empty($post['thumbs_x']) or !empty($post['thumbs_y'])){
            image_convert($file, ROOT.'www/photos/'.$photo.'_small.jpg', $post['thumbs_x'], $post['thumbs_y'], 'thumb');

        }else{
            copy($file, ROOT.'www/photos/'.$photo.'_small.jpg');
        }

        if(!empty($post['images_x']) or !empty($post['images_y'])){
            image_convert($file, ROOT.'www/photos/'.$photo.'_big.jpg'  , $post['images_x'], $post['images_y'], 'resize');

        }else{
            copy($file, ROOT.'www/photos/'.$photo.'_big.jpg');
        }

        /*
         *
         */
        $res  = sql_query('INSERT INTO `blogs_photos` (`createdby`, `blogs_posts_id`, `file`)
                           VALUES                     (:createdby , :blogs_posts_id , :file )',

                          array(':createdby'      => $_SESSION['user']['id'],
                                ':blogs_posts_id' => cfi($post['id']),
                                ':file'           => $photo));

        $id   = sql_insert_id($res);

// :DELETE: This block is replaced by the code below. Only left here in case it contains something usefull still
//	$html = '<li style="display:none;" id="photo'.$id.'" class="myclub photo">
//                <img style="width:219px;height:130px;" src="/photos/'.$photo.'_small.jpg" />
//                <a class="myclub photo delete">'.tr('Delete this photo').'</a>
//                <textarea placeholder="'.tr('Description of this photo').'" class="myclub photo description"></textarea>
//            </li>';

        return array('id'    => $id,
                     'photo' => $photo);

    }catch(Exception $e){
        throw new bException('blogs_photos_upload(): Failed', $e);
    }
}



/*
 * Find and return a free priority for blog photo
 */
function blogs_photos_get_free_priority($blogs_posts_id, $insert = false){
    global $_CONFIG;

    try{
        if($insert){
            /*
             * Insert mode, return the first possible priority, in case there is a gap (ideally should be highest though, if there are no gaps)
             */
            $list = sql_list('SELECT `priority` FROM `blogs_photos` WHERE `blogs_posts_id` = :blogs_posts_id ORDER BY `priority` ASC', array(':blogs_posts_id' => $blogs_posts_id));

            for($current = 1; ; $current++){
                if(!in_array($current, $list)){
                    return $current;
                }
            }

            return $current;
        }

        /*
         * Highest mode, return the highest priority + 1
         */
        return (integer) sql_get('SELECT MAX(`priority`) FROM `blogs_photos` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $blogs_posts_id)) + 1;

    }catch(Exception $e){
        throw new bException('blogs_photos_get_free_priority(): Failed', $e);
    }
}



/*
 * Photo description
 */
function blogs_photo_description($user, $photo_id, $description){
    try{
        if(!is_numeric($photo_id)){
            $photo_id = str_from($photo_id, 'photo');
        }

        $photo    = sql_get('SELECT `blogs_photos`.`id`,
                                    `blogs_photos`.`createdby`

                             FROM   `blogs_photos`

                             JOIN   `blogs_posts`

                             WHERE  `blogs_photos`.`blogs_posts_id` = `blogs_posts`.`id`
                             AND    `blogs_photos`.`id`             = '.cfi($photo_id));

        if(empty($photo['id'])) {
            throw new bException('blogs_photo_description(): Unknown blog post photo specified', 'unknown');
        }

        if(($photo['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new bException('blogs_photo_description(): Cannot upload photos, this post is not yours', 'accessdenied');
        }

        sql_query('UPDATE `blogs_photos`

                   SET    `description` = :description

                   WHERE  `id`          = :id',

                   array(':description' => cfm($description),
                         ':id'          => cfi($photo_id)));

    }catch(Exception $e){
        throw new bException('blogs_photo_description(): Failed', $e);
    }
}



/*
 * Get a full URL of the photo
 */
function blogs_photo_url($photo, $small = false){
    try{
        return current_domain('photos/'.$photo.($small ? '_small.jpg' : '_big.jpg'));

    }catch(Exception $e){
        throw new bException('blogs_photo_url(): Failed', $e);
    }
}



/*
 * Get a display priority for the priority ID or vice versa
 */
function blogs_priority($priority){
    static $list, $rlist;

    try{
        if(empty($list)){
            $list = array(0 => 'low',
                          1 => 'normal',
                          2 => 'high',
                          3 => 'urgent',
                          4 => 'immediate');
        }

        if(is_numeric($priority)){
            if(isset($list[$priority])){
                return $list[$priority];
            }

            throw new bException('blogs_priority(): Unknown priority "'.str_log($priority).'" specified', 'unknown');
        }

        if($priority === null){
            return 'Unknown';
        }

        /*
         * Reverse lookup
         */
        if(empty($rlist)){
            $rlist = array_flip($list);
        }

        $priority = strtolower($priority);

        if(isset($rlist[$priority])){
            return $rlist[$priority];
        }

        throw new bException('blogs_priority(): Unknown priority "'.str_log($priority).'" specified', 'unknown');

    }catch(Exception $e){
        throw new bException('blogs_priority(): Failed', $e);
    }
}



/*
 * Validate the specified category
 */
function blogs_validate_category($category, $blog){
    try{
        if(!$retval = sql_get('SELECT `id`, `blogs_id`, `name`, `seoname` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname' => $category))){
// :DELETE: Delete following 2 debug code lines
//show(current_file(1).current_line(1));
//showdie(tr('The specified category "%category%" does not exists', '%category%', str_log($category)));
            /*
             * The specified category does not exist
             */
            throw new bException(tr('The specified category "%category%" does not exists', '%category%', str_log($category)), 'notexist');
        }

        if($retval['blogs_id'] != $blog['id']){
            /*
             * The specified category is not of this blog
             */
            throw new bException(tr('The specified category "%category%" is not of another blog', '%category%', str_log($category)), 'invalid');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('blogs_validate_category(): Failed', $e);
    }
}



/*
 * Generate and return a URL for the specified blog post,
 * based on blog url configuration
 */
function blogs_post_url($post, $current_domain = true){
    global $_CONFIG;

    try{
        /*
         * What URL template to use?
         */
        if(empty($post['url_template'])){
            if(empty($post['blogs_id'])){
                throw new bException('blogs_post_url(): No URL template or blogs_id specified for post "'.str_log($post).'"', 'not_specified');
            }

            $post['url_template'] = sql_get('SELECT `url_template` FROM `blogs` WHERE `id` = :id', array(':id' => $post['blogs_id']), 'url_template');
        }

        if(empty($post['url_template'])){
            /*
             * This blog has no URL template configured, so use the default one
             */
            $url = $_CONFIG['blogs']['url'];

        }else{
            $url = $post['url_template'];
        }

        if(!$url){
            throw new bException('blogs_post_url(): Unable to find a URL template', 'not_specified');
        }

        $sections = array('time',
                          'date',
                          'createdon',
                          'blog',
                          'seoname',
                          'category',
                          'seocategory');

        if(empty($post['blog'])){
            $post['blog'] = sql_get('SELECT `seoname` FROM `blogs` WHERE `id` = :id', array(':id' => $post['blogs_id']), 'seoname');
        }

        foreach($sections as $section){
            switch($section){
                case 'date':
                    $post[$section] = str_until(isset_get($post['createdon']), ' ');
                    break;

                case 'time':
                    $post[$section] = str_from(isset_get($post['createdon']), ' ');
            }

            $url = str_replace('%'.$section.'%', isset_get($post[$section]), $url);
        }

        if($current_domain){
            load_libs('http');
            return current_domain($url);
        }

        return domain($url);

    }catch(Exception $e){
        throw new bException('blogs_post_url(): Failed', $e);
    }
}



/*
 * Update the URL's for blog posts
 * Can update all posts, all posts for multiple blogs, or all posts within one category within one blog
 */
function blogs_update_urls($blogs = null, $category = null){
    try{
        if($category){
            /*
             * Only update for a specific category
             * Ensure that the category exists. If no blog was specified, then get the blog from the specified category
             */
            if(is_numeric($category)){
                $category = sql_get('SELECT `id`, `blogs_id`, `seoname`, `name` FROM `blogs_categories` WHERE `id`      = :id'     , array(':id'     => $category));

            }elseif(is_scalar($category)){
                $category = sql_get('SELECT `id`, `blogs_id`, `seoname`, `name` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname'=> $category));

            }elseif(!is_array($category)){
                throw bException('blogs_update_urls(): Invalid category datatype specified. Either specify id, seoname, or full array', 'invalid');
            }

            if(!$blogs){
                $blogs = $category['blogs_id'];
            }
        }

        if(!$blogs){
            /*
             * No specific blog was specified? process the posts for all blogs
             */
            $r = sql_query('SELECT `id` FROM `blogs`');

            log_console('blogs_update_urls(): Updating posts for all blogs', 'blogs_update_urls');

            while($blog = sql_fetch($r)){
                blogs_update_urls($blog['id'], $category);
            }

            return;
        }

        foreach(array_force($blogs) as $blogname){
            /*
             * Get blog data either from ID or seoname
             */
            if(is_numeric($blogname)){
                $blog = sql_get('SELECT `id`, `name`, `seoname`, `url_template` FROM `blogs` WHERE `id`      = :id'     , array(':id'      => $blogname));

            }else{
                $blog = sql_get('SELECT `id`, `name`, `seoname`, `url_template` FROM `blogs` WHERE `seoname` = :seoname', array(':seoname' => $blogname));
            }

            if(!$blog){
                log_console('blogs_update_urls(): Specified blog "'.str_log($blogname).'" does not exist, skipping', 'skip', 'yellow');
                continue;
            }

            log_console('blogs_update_urls(): Updating posts for blog '.str_log(str_size('"'.str_truncate($blog['name'], 40).'"', 42, ' ')), 'blogs_update_urls', 'white');

            /*
             * Walk over all posts of the specified blog
             */
            $query   = 'SELECT `id`,
                               `blogs_id`,
                               `url`,
                               `name`,
                               `seoname`,
                               `createdon`,
                               `modifiedon`,
                               `createdby`,
                               `category`,
                               `seocategory`

                        FROM   `blogs_posts`

                        WHERE  `blogs_id` = :id';

            $execute = array(':id' => $blog['id']);

            if($category){
                /*
                 * Add category filter
                 * Since categories are limited to specific blogs, ensure
                 * that this category is available within the blog
                 */
                if($category['blogs_id'] != $blog['id']){
                    log_console('blogs_update_urls(): The category "'.str_log($category['name']).'" does not exist for the blog "'.str_log($blog['name']).'", skipping', 'skip', 'yellow');
                    continue;
                }

                $query              .= ' AND `seocategory` = :seoname';
                $execute[':seoname'] = $category['seoname'];
            }

            /*
             * Walk over all posts in the selected filter, and update the URL's
             */
            $r = sql_query($query, $execute);

            while($post = sql_fetch($r)){
                $post['url_template'] = $blog['url_template'];
                $url                  = blogs_post_url($post);

                log_console('blogs_update_urls(): Updating blog post '.str_log(str_size('"'.str_truncate($post['seoname'], 40).'"', 42, ' ')).' to URL "'.str_log($url).'"', 'blogs_update_urls');

                sql_query('UPDATE `blogs_posts`

                           SET    `url` = :url

                           WHERE  `id`  = :id',

                           array(':url' => $url,
                                 ':id'  => $post['id']));
            }
        }

    }catch(Exception $e){
        throw new bException('blogs_update_urls(): Failed', $e);
    }
}
?>
