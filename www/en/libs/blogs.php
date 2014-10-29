<?php
/*
 * Blogs library
 *
 * This library contains functions to manage and display blogs and blog entries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



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

        if(!has_right('admin', false)){
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
 * Generate and return a URL for the specified blog post,
 * based on blog url configuration
 */
function blogs_post_url($post, $current_domain = true){
    global $_CONFIG;

    try{
        $url      = $_CONFIG['blogs']['url'];
        $sections = array('time',
                          'date',
                          'createdon',
                          'blog',
                          'seoname',
                          'category');

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
            return current_domain($url);
        }

        return domain($url);

    }catch(Exception $e){
        throw new bException('blogs_url(): Failed', $e);
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
        array_default($params, 'name'        , 'category');
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
function blogs_clean_keywords($keywords){
    try{
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
        $v = new validate_form($post, 'name,category,body,keywords,description,language,group,priority,urlref,status');

        $v->is_checked   ($post['name']       , tr('Please provide the name of your %objectname%', '%objectname%', $params['object_name']));
        $v->is_not_empty ($post['category']   , tr('Please provide a category for your %objectname%', '%objectname%', $params['object_name']));
        $v->is_not_empty ($post['body']       , tr('Please provide the body text of your %objectname%', '%objectname%', $params['object_name']));

        $v->has_min_chars($post['name']       ,                  4, tr('Please ensure that the name has a minimum of 4 characters'));
        $v->has_min_chars($post['body']       , $params['bodymin'], tr('Please ensure that the body text has a minimum of '.$params['bodymin'].' characters'));

        if(!empty($params['use_groups'])){
            $v->is_not_empty ($post['group'], tr('Please provide a group for your %objectname%', '%objectname%', $params['object_name']));
        }

        if(!empty($params['use_language'])){
            $v->is_not_empty($post['language'], tr('Please select a language for your %objectname%', '%objectname%', $params['object_name']));
            $v->hasChars($post['keywords'], 2, tr('Please provide a valid language'));
        }

        if($params['use_priorities']){
            $v->is_not_empty ($post['priority'], tr('Please provide a priority for your %objectname%', '%objectname%', $params['object_name']));
        }

        if(!empty($params['use_keywords'])){
            $v->has_min_chars($post['keywords'], 2, tr('Please ensure that the keywords have a minimum of 2 characters'));
            $v->is_not_empty ($post['keywords'],     tr('Please provide keywords for your %objectname%', '%objectname%', $params['object_name']));
        }

        if(!empty($params['use_description'])){
            $v->is_not_empty ($post['description'],      tr('Please provide a description for your %objectname%', '%objectname%', $params['object_name']));
            $v->has_min_chars($post['description'],  16, tr('Please ensure that the body text has a minimum of 16 characters'));
        }

        if(!empty($params['use_status'])){
            if(empty($params['status_select']['resource'][$post['status']])){
                    $v->setError(tr('Please provide a valid status for your %objectname%', '%objectname%', $params['object_name']));
            }

        }else{
            $post['status'] = null;
        }

        if(!$v->is_valid()) {
           throw new bException(str_force($v->get_errors(), ', '), 'validation');
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
    try{
        load_libs('file,image,upload');

        if(empty($post['id'])) {
            throw new bException('blogs_photos_upload(): No blog post specified', 'notspecified');
        }

        $post = sql_get('SELECT `blogs_posts`.`id`,
                                `blogs_posts`.`createdby`,
                                `blogs_posts`.`seoname`,
                                `blogs`.`seoname` AS blog_name

                         FROM   `blogs_posts`

                         JOIN   `blogs`
                         ON     `blogs_posts`.`blogs_id` = `blogs`.`id`

                         WHERE  `blogs_posts`.`id`       = '.cfi($post['id']));

        if(empty($post['id'])) {
            throw new bException('blogs_photos_upload(): Unknown blog post specified', 'unknown');
        }

        if(($post['createdby'] != $_SESSION['user']['id']) and !has_right('god')){
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
        $photo = $post['blog_name'].'/'.file_assign_target_clean(ROOT.'www/photos/'.$post['blog_name'].'/', '_small.jpg', false, 4);

        image_convert($file['tmp_name'][0], ROOT.'www/photos/'.$photo.'_small.jpg', 219, 130, 'thumb');
        image_convert($file['tmp_name'][0], ROOT.'www/photos/'.$photo.'_big.jpg'  , 640, 480, 'resize');

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

        if(($photo['createdby'] != $_SESSION['user']['id']) and !has_right('god')){
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
?>
