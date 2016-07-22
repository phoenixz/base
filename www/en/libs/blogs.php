<?php
/*
 * Blogs library
 *
 * This library contains functions to manage and display blogs and blog entries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_config('blogs');



/*
 * Get a new or existing blog post
 */
function blogs_post_get($post = null, $blog = null, $columns = null){
    try{
        if($blog){
            /*
             * Verify the specified blog
             */
            if(is_numeric($blog)){
                $blogs_id = sql_get('SELECT `id` FROM `blogs` WHERE `id`      = :blog AND `status` IS NULL', 'id', array(':blog' => $blog));

            }else{
                $blogs_id = sql_get('SELECT `id` FROM `blogs` WHERE `seoname` = :blog AND `status` IS NULL', 'id', array(':blog' => $blog));
            }

            if(!$blogs_id){
                throw new bException(tr('blogs_post_get(): Specified blog ":blog" does not exist, or is not available because of its status', array(':blog' => $blog)), 'not-exist');
            }
        }

        if(!$post){
            if(empty($blogs_id)){
                throw new bException(tr('blogs_post_get(): No blog and no post specified. For a new post, specify at least a blog'), 'not-specified');
            }

            /*
             * Is there already a post available for this user?
             * If so, use that one
             */
            $post = sql_get('SELECT `id`

                             FROM   `blogs_posts`

                             WHERE  `createdby` = :createdby
                             AND    `blogs_id`  = :blogs_id
                             AND    `status`    = "new"',

                             'id', array(':createdby' => isset_get($_SESSION['user']['id']),
                                         ':blogs_id'  => $blogs_id));

            if(!$post){
                sql_query('INSERT INTO `blogs_posts` (`status`, `blogs_id`, `createdby`)
                           VALUES                    ("new"   , :blogs_id , :createdby )',

                           array(':createdby' => isset_get($_SESSION['user']['id']),
                                 ':blogs_id'  => $blogs_id));

                $post = sql_insert_id();
            }
        }

        if(!$columns){
            /*
             * Select default columns
             */
            $columns = '`blogs_posts`.`id`,
                        `blogs_posts`.`createdon`,
                        `blogs_posts`.`createdby`,
                        `blogs_posts`.`modifiedby`,
                        `blogs_posts`.`modifiedon`,
                        `blogs_posts`.`status`,
                        `blogs_posts`.`blogs_id`,
                        `blogs_posts`.`assigned_to_id`,
                        `blogs_posts`.`seocategory`,
                        `blogs_posts`.`category`,
                        `blogs_posts`.`seogroup`,
                        `blogs_posts`.`group`,
                        `blogs_posts`.`keywords`,
                        `blogs_posts`.`seokeywords`,
                        `blogs_posts`.`featured_until`,
                        `blogs_posts`.`upvotes`,
                        `blogs_posts`.`downvotes`,
                        `blogs_posts`.`description`,
                        `blogs_posts`.`priority`,
                        `blogs_posts`.`views`,
                        `blogs_posts`.`rating`,
                        `blogs_posts`.`comments`,
                        `blogs_posts`.`language`,
                        `blogs_posts`.`url`,
                        `blogs_posts`.`urlref`,
                        `blogs_posts`.`name`,
                        `blogs_posts`.`seoname`,
                        `blogs_posts`.`body`,
                        `blogs_posts`.`parents_id`,
                        `users`.`name` AS `assigned_to`';
        }

        if(is_numeric($post)){
            $where = ' WHERE `blogs_posts`.`id`      = :post';

        }else{
            $where = ' WHERE `blogs_posts`.`seoname` = :post';
        }

        $execute = array(':post' => $post);

        $retval  = sql_get('SELECT    '.$columns.'

                            FROM      `blogs_posts`

                            LEFT JOIN `users`
                            ON        `users`.`id` = `blogs_posts`.`assigned_to_id`'.$where, $execute);

        if($retval){
            return $retval;
        }

        /*
         * So the post with the specified ID doesn't exist, create it now and then return
         */
        sql_query('INSERT INTO `blogs_posts` (`id`, `status`, `blogs_id`, `createdby`)
                   VALUES                    (:id , "new"   , :blogs_id , :createdby )',

                   array(':id'        => $post,
                         ':createdby' => isset_get($_SESSION['user']['id']),
                         ':blogs_id'  => $blogs_id));

        return blogs_post_get($post, $blog, $columns);

    }catch(Exception $e){
        throw new bException('blogs_post_get(): Failed', $e);
    }
}



/*
 *
 */
function blogs_post_update($post, $params = null){
    try{
        load_libs('seo');

        $post    = blogs_validate_post($post, $params);
        $seoname = seo_unique($post['name'], 'blogs_posts', $post['id']);

        $query   = 'UPDATE  `blogs_posts`

                    SET     `blogs_id`       = :blogs_id,
                            `assigned_to_id` = :assigned_to_id,
                            `modifiedby`     = :modifiedby,
                            `modifiedon`     = NOW(),
                            `featured_until` = :featured_until,
                            `parents_id`     = :parents_id,
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

        $execute   = array(':id'             => $post['id'],
                           ':blogs_id'       => $post['blogs_id'],
                           ':assigned_to_id' => $post['assigned_to_id'],
                           ':parents_id'     => isset_get($post['parents_id']),
                           ':modifiedby'     => isset_get($_SESSION['user']['id']),
                           ':featured_until' => $post['featured_until'],
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
                           ':seoname'        => $seoname,
                           ':body'           => $post['body']);

        if($execute[':featured_until']){
            $execute[':featured_until'] = system_date_format($post['featured_until'], 'mysql');

        }else{
            $execute[':featured_until'] = null;
        }

        /*
         * Update the post, and ensure it is no longer registered as "new".
         */
        sql_query($query, $execute);
        sql_query('UPDATE `blogs_posts` SET `status` = "unpublished" WHERE `id` = :id AND `status` = "new"', array(':id' => $post['id']));

        /*
         * Update keywords and key_value store
         */
        blogs_update_keywords($post);
        blogs_update_key_value_store($post, isset_get($params['key_values']));

        return $seoname;

    }catch(Exception $e){
        throw new bException(tr('blogs_post_update(): Failed'), $e);
    }
}



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
                           `name`

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
            throw new bException('blogs_post(): No blog specified', 'not-specified');
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
        array_default($params, 'empty'       , tr('No categories available'));
        array_default($params, 'option_class', '');
        array_default($params, 'parent'      , null);
        array_default($params, 'filter'      , array());

        if(empty($params['blogs_id'])){
            /*
             * Categories work per blog, so without a blog we cannot show
             * categories
             */
            $params['resource'] = null;

        }else{
            $execute = array(':blogs_id' => $params['blogs_id']);

            $query   = 'SELECT  '.$params['column'].' AS id,
                                `blogs_categories`.`name`

                        FROM    `blogs_categories`';

            $where   = ' WHERE  `blogs_categories`.`blogs_id` = :blogs_id
                         AND    `blogs_categories`.`status`   IS NULL ';

            if($params['parent']){
                $parent = ' JOIN `blogs_categories` AS parents
                            ON   `parents`.`seoname` = :parent
                            AND  `parents`.`id`      = `blogs_categories`.`parents_id`';

                $execute[':parent'] = $params['parent'];

            }elseif($params['parent'] !== false){
                $where .= ' AND `blogs_categories`.`parents_id` IS NULL ';
            }

            /*
             * Filter specified values.
             */
            foreach($params['filter'] as $key => $value){
                if(!$value) continue;

                $where            .= ' AND `'.$key.'` != :'.$key.' ';
                $execute[':'.$key] = $value;
            }

            $params['resource'] = sql_query($query.isset_get($parent).$where.' ORDER BY `name` ASC', $execute);
        }

        return html_select($params);

    }catch(Exception $e){
        throw new bException('blogs_categories_select(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available parent posts
 */
function blogs_parents_select($params) {
    try{
        array_params ($params);
        array_default($params, 'selected'    , null);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'name'        , 'seoparent');
        array_default($params, 'column'      , '`blogs_posts`.`seoname`');
        array_default($params, 'none'        , tr('Select a parent'));
        array_default($params, 'empty'       , tr('No parents available'));
        array_default($params, 'blogs_id'    , null);
        array_default($params, 'filter'      , array());

        if(empty($params['blogs_id'])){
            throw new bException('blogs_parents_select(): No blog specified', 'not-specified');
        }

        $execute = array(':blogs_id' => $params['blogs_id']);

        $query   = 'SELECT   '.$params['column'].' AS id,
                             `blogs_posts`.`name`

                    FROM     `blogs_posts`';

        $where[] = '`blogs_posts`.`status`   = "published"';

        if($params['blogs_id']){
            $where[]              = ' `blogs_posts`.`blogs_id` = :blogs_id ';
            $execute[':blogs_id'] = $params['blogs_id'];
        }

        /*
         * Filter specified values.
         */
        if(!empty($params['filter'])){
            foreach($params['filter'] as $key => $value){
                if(!$value) continue;

                $query            .= ' AND `'.$key.'` != :'.$key.' ';
                $execute[':'.$key] = $value;
            }
        }

        if(!empty($where)){
            $query .= ' WHERE '.implode(' AND ', $where);
        }

        $query  .= ' ORDER BY `name` ASC';

        $params['resource'] = sql_query($query, $execute);

        return html_select($params);

    }catch(Exception $e){
        throw new bException('blogs_parents_select(): Failed', $e);
    }
}



///*
// * Return HTML select list containing all available blogs
// */
//function blogs_priorities_select($params, $selected = 0) {
//    try{
//        array_params ($params, 'seoname');
//        array_default($params, 'selected'    , $selected);
//        array_default($params, 'class'       , $class);
//        array_default($params, 'disabled'    , $disabled);
//        array_default($params, 'name'        , $name);
//        array_default($params, 'none'        , not_empty($none, tr('Select a priority')));
//        array_default($params, 'option_class', $option_class);
//        array_default($params, 'filter'      , array());
//
//        if(empty($params['blogs_id'])){
//            throw new bException('blogs_priorities_select(): No blog specified', 'not-specified');
//        }
//
//        $params['resource'] = array(4 => tr('Low'),
//                                    3 => tr('Normal'),
//                                    2 => tr('High'),
//                                    1 => tr('Urgent'),
//                                    0 => tr('Immediate'));
//
//        return html_select($params);
//
//    }catch(Exception $e){
//        throw new bException('blogs_priorities_select(): Failed', $e);
//    }
//}



/*
 * Update the key-value store for this blog post
 */
function blogs_update_key_value_store($post, $limit_key_values){
    try{
        load_libs('seo');
        sql_query('DELETE FROM `blogs_key_values` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post['id']));

        if(empty($post['key_values'])){
            /*
             * There are no key_values for this post
             */
            return false;
        }

        if($limit_key_values){
// :TODO: Implement
            //foreach($limit_key_values as $data){
            //    foreach($post['key_values'] as $seokey => $seovalue){
            //        if($data['name'] == $seokey){
            //            if(!empty($data['resource'])){
            //                /*
            //                 * This key-value is from a list, get the real value.
            //                 */
            //                if(empty($data['resource'][$seovalue])){
            //                    if($seovalue){
            //                        throw new bException(tr('blogs_update_key_value_store(): Key ":key" has unknown value ":value"', array(':key' => $seokey, ':value' => $seovalue)),  'unknown');
            //                    }
            //
            //                    $seovalue = null;
            //                    $value    = null;
            //
            //                }else{
            //                    $value = $data['resource'][$seovalue];
            //                }
            //            }
            //
            //            break;
            //        }
            //    }
            //}
        }

        /*
         * Scalars before arrays because the arrays can contain parents that are defined in the scalars.
         */
        uasort($post['key_values'], 'blogs_update_key_value_sort');

        $p = sql_prepare('INSERT INTO `blogs_key_values` (`blogs_id`, `blogs_posts_id`, `parent`, `key`, `seokey`, `value`, `seovalue`)
                          VALUES                         (:blogs_id , :blogs_posts_id , :parent , :key , :seokey , :value , :seovalue )');

        foreach($post['key_values'] as $key => $values){
            if(!is_array($values)){
                $values = array($values);
            }

            $parent = isset_get($values['parent']);
            unset($values['parent']);

            foreach($values as $value){
                $p->execute(array(':blogs_id'       => $post['blogs_id'],
                                  ':blogs_posts_id' => $post['id'],
                                  ':parent'         => $parent,
                                  ':key'            => $key,
                                  ':seokey'         => seo_create_string($key),
                                  ':value'          => $value,
                                  ':seovalue'       => seo_create_string($value)));
            }
        }

    }catch(Exception $e){
        throw new bException('blogs_update_key_value_store(): Failed', $e);
    }
}



/*
 * Sort key_value arrays
 * scalars before arrays
 * then order by parent
 * then order by value
 */
function blogs_update_key_value_sort($a, $b){
    try{
        if(is_scalar($a)){
            /*
             * A is scalar
             */
            if(is_scalar($b)){
                /*
                 * A and B are scalar
                 */
                return 0;
            }

            /*
             * A is scalar, B is array
             */
            return -1;
        }

        /*
         * A is array
         */
        if(is_array($b)){
            /*
             * A and B are array
             */
            return 0;

        }

        /*
         * A is array, B is scalar
         */
        return 1;

    }catch(Exception $e){
        throw new bException('blogs_update_key_value_sort(): Failed', $e);
    }
}



/*
 * Update the keywords in the blogs_keywords table and the
 * seokeywords column in the blogs_posts table
 */
function blogs_update_keywords($post){
    try{
        /*
         * Ensure all keywords of this blog post are gone
         */
        sql_query('DELETE FROM `blogs_keywords` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post['id']));

        /*
         * Store the keywords
         */
        $p = sql_prepare('INSERT INTO `blogs_keywords` (`blogs_id`, `blogs_posts_id`, `name`, `seoname`)
                          VALUES                       (:blogs_id , :blogs_posts_id , :name , :seoname )');

        foreach(array_force($post['keywords'], ',') as $keyword){
            if(strlen($keyword) < 2) continue;

            $p->execute(array(':blogs_id'       => $post['blogs_id'],
                              ':blogs_posts_id' => $post['id'],
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
function blogs_validate_post(&$post, $params = null){
    try{
        array_params($params);
        array_default($params, 'force_id'       , false);
        array_default($params, 'use_id'         , false);
        array_default($params, 'use_parent'     , false);
        array_default($params, 'use_categories' , false);
        array_default($params, 'namemax'        , 64);
        array_default($params, 'bodymin'        , 100);
        array_default($params, 'object_name'    , 'blog posts');

        load_libs('seo,validate');

        /*
         * Validate input
         */
        $v = new validate_form($post, 'id,name,featured_until,assigned_to,seocategory,category,body,keywords,description,language,group,priority,urlref,status');

        /*
         * Just ensure that the specified id is a valid number
         */
        if(!$post['id']){
            throw new bException(tr('Blog post has no id specified'), 'not-specified');
        }

        $v->isNatural($post['id'], tr('Please ensure that the specified post id is a natural number; numeric, integer, and > 0'));

        if(is_numeric($post['name'])){
            throw new bException(tr('Blog post name can not be numeric'), 'invalid');
        }

        $v->isNotEmpty($post['name']    , tr('Please provide a name for your :objectname', array(':objectname' => $params['object_name'])));
        $v->isNotEmpty($post['blogs_id'], tr('Please provide a blog for your :objectname', array(':objectname' => $params['object_name'])));
        $v->isNumeric ($post['blogs_id'], tr('Please provide a valid blog for your :objectname', array(':objectname' => $params['object_name'])));

        $id = sql_get('SELECT `id` FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `id` = :id', 'id', array(':blogs_id' => $post['blogs_id'], ':id' => $post['id']));

        if(!$id){
            /*
             * This blog post does not exist
             */
            throw new bException(tr('Can not update blog ":blog" post ":name", it does not exist', array(':blog' => $post['blogs_id'], ':name' => str_log($post['name']))), 'not-exist');
        }

        if(empty($params['allow_duplicate_name'])){
            if(sql_get('SELECT `id` FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `id` != :id AND `name` = :name', array(':blogs_id' => $post['blogs_id'], ':id' => $id, ':name' => $post['name']), 'id')){
                /*
                 * Another post with this name already exists
                 */
                $v->setError(tr('A post with the name ":name" already exists', array(':name' => str_log($post['name']))), $params['object_name']);
            }
        }

        if(!empty($params['use_append'])){
            /*
             * Only allow data to be appended to this post
             * Find changes between current and previous state and store those as well
             */
            load_libs('user');

            $changes = array();
            $oldpost = sql_get('SELECT `assigned_to_id`, `priority`, `status`, `name`, `urlref`, `body` FROM `blogs_posts` WHERE `id` = :id', array(':id' => $id));
        }else{
            /*
             * Only if we're editing in use_append mode we don't have to check body size
             */
            if($params['bodymin']){
                $v->hasMinChars($post['body'], $params['bodymin'], tr('Please ensure that the body text has a minimum of :bodymin characters', array(':bodymin' => $params['bodymin'])));
                $v->isNotEmpty ($post['body']                    , tr('Please provide the body text of your :objectname', array(':objectname' => $params['object_name'])));
            }
        }

        $v->isChecked  ($post['name']   , tr('Please provide the name of your :objectname'     , array(':objectname' => $params['object_name'])));
        $v->hasMinChars($post['name'], 1, tr('Please ensure that the name has a minimum of 1 character'));

        if(empty($params['use_parent'])){
            $post['parents_id'] = null;

        }else{
            $post['parents_id'] = blogs_validate_parent($post['seoparent'], $params['use_parent']);
        }

        if(empty($params['use_categories'])){
            $post['category']    = '';
            $post['seocategory'] = null;

        }else{
            if(empty($post['seocategory'])){
                if(!empty($params['errors']['category_required'])){
                    /*
                     * Category required
                     */
                    $v->setError($params['errors']['category_required']);

                }else{
                    $post['category']    = '';
                    $post['seocategory'] = null;
                }

            }else{

                $category = blogs_validate_category($post['seocategory'], $post['blogs_id'], isset_get($params['categories_parent']));

                $post['category']    = $category['name'];
                $post['seocategory'] = $category['seoname'];
            }
        }

        if(empty($params['use_groups'])){
            $post['group']    = '';
            $post['seogroup'] = null;

        }else{
            if(empty($post['seogroup'])){
                if(!empty($params['errors']['group_required'])){
                    /*
                     * Category required
                     */
                    $v->setError($params['errors']['group_required']);

                }else{
                    $post['group']    = '';
                    $post['seogroup'] = null;
                }

            }else{

                $group = blogs_validate_category($post['seogroup'], $post['blogs_id'], isset_get($params['groups_parent']));

                $post['group']    = $group['name'];
                $post['seogroup'] = $group['seoname'];
            }
        }

        if(empty($params['use_keywords'])){
            $post['keywords']    = '';
            $post['seokeywords'] = '';

        }else{
            $post['keywords']    = blogs_clean_keywords($post['keywords']);
            $post['seokeywords'] = blogs_seo_keywords($post['keywords']);

            $v->hasMinChars($post['keywords'], 1, tr('Please ensure that the keywords have a minimum of 1 character'));
            $v->isNotEmpty ($post['keywords'],    tr('Please provide keywords for your :objectname', array(':objectname' => $params['object_name'])));
        }

        if(empty($post['assigned_to'])){
            $post['assigned_to_id'] = null;

        }else{
            if(!$post['assigned_to_id'] = sql_get('SELECT `id` FROM `users` WHERE `name` = :name', 'id', array(':name' => $post['assigned_to']))){
                $v->setError('The specified assigned-to-user "'.str_log($post['assigned_to']).'" does not exist');
            }
        }

        if(!empty($params['use_featured'])){
            if($post['featured_until']){
                $post['featured_until'] = system_date_format($post['featured_until'], 'mysql');

            }else{
                $post['featured_until'] = null;
            }
        }

        if(!empty($params['use_status'])){
            if(!isset($params['status_list'][$post['status']])){
                $v->setError('The specified status "'.str_log($post['status']).'" is invalid, it must be either one of "'.str_log(str_force($params['status_list'])).'"');
            }
        }

        if(!empty($params['use_language'])){
            $v->isNotEmpty($post['language'],    tr('Please select a language for your :objectname', array(':objectname' => $params['object_name'])));
            $v->hasChars($post['keywords']  , 2, tr('Please provide a valid language'));
        }

        if(!empty($params['use_priorities'])){
            $v->isNotEmpty ($post['priority'], tr('Please provide a priority for your :objectname', array(':objectname' => $params['object_name'])));

            if(!is_numeric($post['priority']) or ($post['priority'] < 1) or ($post['priority'] > 5) or (fmod($post['priority'], 1))){
                $v->setError('The specified priority "'.str_log($post['priority']).'" is invalid, it must be one of 1, 2, 3, 4, or 5');
            }
        }

        if(!empty($params['use_description'])){
            $v->isNotEmpty ($post['description'],      tr('Please provide a description for your :objectname', array(':objectname' => $params['object_name'])));
            $v->hasMinChars($post['description'],   4, tr('Please ensure that the description has a minimum of 4 characters'));
            $v->hasMaxChars($post['description'], 160, tr('Please ensure that the description has a maximum of 160 characters'));
        }

        if(!empty($params['use_status'])){
            if(empty($params['status_select']['resource'][$post['status']])){
                $v->setError(tr('Please provide a valid status for your :objectname', array(':objectname' => $params['object_name'])));
            }

        }else{
            $post['status'] = null;
        }

        $v->isValid();

        /*
         * Set extra parameters
         */
        $post['seoname']  = seo_unique($post['name'], 'blogs_posts', $id);
        $post['url']      = blogs_post_url($post);

        /*
         * Append post to current body?
         */
        if(!empty($params['use_append'])){
            /*
             * Only allow data to be appended to this post
             * Find changes between current and previous state and store those as well
             */
            load_libs('user');

            $changes      = array();
            $oldpost      = sql_get('SELECT `assigned_to_id`, `priority`, `status`, `name`, `urlref`, `seogroup`, `seocategory`, `body` FROM `blogs_posts` WHERE `id` = :id', array(':id' => $id));

            if(isset_get($oldpost['assigned_to_id']) != $post['assigned_to_id']){
                $user = sql_get('SELECT `id`, `name`, `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $post['assigned_to_id']));

                if(isset_get($oldpost['assigned_to_id'])){
                    $changes[] = tr('Re-assigned post to ":user"', array(':user' => user_name($user)));

                }else{
                    $changes[] = tr('Assigned post to ":user"', array(':user' => user_name($user)));
                }
            }

            if(isset_get($oldpost['priority']) != $post['priority']){
                $changes[] = tr('Set priority to ":priority"', array(':priority' => blogs_priority($post['priority'])));
            }

            if(isset_get($oldpost['urlref']) != $post['urlref']){
                $changes[] = tr('Set URL to ":url"', array(':url' => $post['urlref']));
            }

            if(isset_get($oldpost['name']) != $post['name']){
                $changes[] = tr('Set name to ":name"', array(':name' => $post['name']));
            }

            if(isset_get($oldpost['status']) != $post['status']){
                $changes[] = tr('Set status to ":status"', array(':status' => $post['status']));
            }

            if(isset_get($oldpost['seocategory']) != $post['seocategory']){
                $changes[] = tr('Set :categoryname to ":category"', array(':categoryname' => strtolower($params['label_category']), ':category' => $post['category']));
            }

            if(isset_get($oldpost['seogroup']) != $post['seogroup']){
                $changes[] = tr('Set :groupname to ":group"', array(':groupname' => strtolower($params['label_group']), ':group' => $post['group']));
            }

            /*
             * If no body was given, and no changes were made, then we don't update
             */
            if(!$post['body'] and !$changes){
                throw new bException('blogs_validate_post(): No changes were made', 'nochanges');
            }

            $post['body'] = str_replace('&nbsp;', ' ', $post['body']);
            $post['body'] = '<h3>'.user_name($_SESSION['user']).' <small>['.system_date_format().']</small></h3><p><small>'.implode('<br>', $changes).'</small></p><p>'.$post['body'].'</p><hr>'.isset_get($oldpost['body'], '');
        }

        return $post;

    }catch(Exception $e){
        if(!empty($oldpost['body'])){
            $post['body'] = $oldpost['body'];
        }

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
 * Process uploaded blog post media file
 */
function blogs_media_upload($files, $post, $priority = null){
    global $_CONFIG;

    try{
        /*
         * Check for upload errors
         */
        upload_check_files(1);

        if(!empty($_FILES['files'][0]['error'])) {
            throw new bException($_FILES['files'][0]['error_message'], 'uploaderror');
        }

        $file = $files;
        $file = file_get_local($file['tmp_name'][0]);

        return blogs_media_process($file, $post, $priority);

    }catch(Exception $e){
        throw new bException('blogs_media_upload(): Failed', $e);
    }
}



/*
 * Process local blog post media file
 */
function blogs_media_add($file, $post, $priority = null){
    global $_CONFIG;

    try{
        /*
         * Check for upload errors
         */
        if(!file_exists($file)){
            throw new bException(tr('blogs_media_add(): Specified file ":file" does not exist', array(':file' => $file)), 'uploaderror');
        }

        return blogs_media_process($file, $post, $priority);

    }catch(Exception $e){
        throw new bException('blogs_media_add(): Failed', $e);
    }
}



/*
 * Process blog media file
 */
function blogs_media_process($file, $post, $priority = null){
    global $_CONFIG;

    try{
        load_libs('file,image,upload');

        if(empty($post['id'])) {
            throw new bException('blogs_media_process(): No blog post specified', 'not-specified');
        }

        $post = sql_get('SELECT `blogs_posts`.`id`,
                                `blogs_posts`.`blogs_id`,
                                `blogs_posts`.`createdby`,
                                `blogs_posts`.`seoname`,

                                `blogs`.`seoname` AS blog_name,
                                `blogs`.`large_x`,
                                `blogs`.`large_y`,
                                `blogs`.`medium_x`,
                                `blogs`.`medium_y`,
                                `blogs`.`small_x`,
                                `blogs`.`small_y`,
                                `blogs`.`wide_x`,
                                `blogs`.`wide_y`,
                                `blogs`.`thumb_x`,
                                `blogs`.`thumb_y`,
                                `blogs`.`wide_x`,
                                `blogs`.`wide_y`,
                                `blogs`.`retina`

                         FROM   `blogs_posts`

                         JOIN   `blogs`
                         ON     `blogs_posts`.`blogs_id` = `blogs`.`id`

                         WHERE  `blogs_posts`.`id`       = '.cfi($post['id']));

        if(empty($post['id'])) {
            throw new bException('blogs_media_process(): Unknown blog post specified', 'unknown');
        }

        if((PLATFORM == 'http') and ($post['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new bException('blogs_media_process(): Cannot upload photos, this post is not yours', 'accessdenied');
        }

        /*
         *
         */
        $prefix = ROOT.'data/content/photos/';
        $file   = $post['blog_name'].'/'.file_move_to_target($file, $prefix.$post['blog_name'].'/', '-original.jpg', false, 4);
        $photo  = str_runtil($file, '-');
        $types  = $_CONFIG['blogs']['images'];

        /*
         * Process all image types
         */
        foreach($types as $type => $params){
            if($params['method'] and (!empty($post[$type.'_x']) or !empty([$type.'_y']))){
                $params['x'] = $post[$type.'_x'];
                $params['y'] = $post[$type.'_y'];

                image_convert($prefix.$file, $prefix.$photo.'-'.$type.'.jpg', $params);

            }else{
                copy($prefix.$file, $prefix.$photo.'-'.$type.'.jpg');
            }

            if($post['retina']){
                if($params['method'] and (!empty($post[$type.'_x']) or !empty($post[$type.'_y']))){
                    $params['x'] = $post[$type.'_x'] * 2;
                    $params['y'] = $post[$type.'_y'] * 2;

                    image_convert($prefix.$file, $prefix.$photo.'-'.$type.'@2x.jpg', $params);

                }else{
                    symlink($prefix.$photo.'-'.$type.'.jpg', $prefix.$photo.'-'.$type.'@2x.jpg');
                }

            }else{
                /*
                 * If retina images are not supported, then just symlink them so that they at least are available
                 */
                symlink(basename($prefix.$photo.'-'.$type.'.jpg')  , $prefix.$photo.'-'.$type.'@2x.jpg');
            }
        }

        /*
         * If no priority has been specified then get the highest one
         */
        if(!$priority){
            $priority = sql_get('SELECT (COALESCE(MAX(`priority`), 0) + 1) AS `priority` FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', 'priority', array(':blogs_posts_id' => $post['id']));
        }

        /*
         * Store blog post photo in database
         */
        $res  = sql_query('INSERT INTO `blogs_media` (`createdby`, `blogs_posts_id`, `blogs_id`, `file`, `priority`)
                           VALUES                    (:createdby , :blogs_posts_id , :blogs_id , :file , :priority )',

                          array(':createdby'      => isset_get($_SESSION['user']['id']),
                                ':blogs_posts_id' => $post['id'],
                                ':blogs_id'       => $post['blogs_id'],
                                ':file'           => $photo,
                                ':priority'       => $priority));

        $id   = sql_insert_id();

// :DELETE: This block is replaced by the code below. Only left here in case it contains something usefull still
//    $html = '<li style="display:none;" id="photo'.$id.'" class="myclub photo">
//                <img style="width:219px;height:130px;" src="/photos/'.$photo.'_small.jpg" />
//                <a class="myclub photo delete">'.tr('Delete this photo').'</a>
//                <textarea placeholder="'.tr('Description of this photo').'" class="myclub photo description"></textarea>
//            </li>';

        return array('id'    => $id,
                     'photo' => $photo);

    }catch(Exception $e){
        throw new bException('blogs_media_process(): Failed', $e);
    }
}



/*
 *
 */
function blog_media_delete($blogs_posts_id){
    try{
        load_libs('file');

        $media = sql_query('SELECT `id`, `file` FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $blogs_posts_id));

        while($file = sql_fetch($media)){
            file_delete(ROOT.'data/content/');
        }

    }catch(Exception $e){
        throw new bException('blog_media_delete(): Failed', $e);
    }
}



/*
 * Process uploaded club photo
 */
function blogs_url_upload($files, $post, $priority = null){
    global $_CONFIG;

    try{
        load_libs('upload');

        /*
         * Check for upload errors
         */
        upload_check_files(1);

        /*
         * Check for errors
         */
        if(!empty($_FILES['files'][0]['error'])) {
            throw new bException($_FILES['files'][0]['error_message'], 'uploaderror');
        }

        /*
         *
         */
        $file = $files;
        $file = file_get_local($file['tmp_name'][0]);

        return blogs_media_process($file, $post, $priority);

    }catch(Exception $e){
        throw new bException('blogs_url_upload(): Failed', $e);
    }
}



/*
 * Find and return a free priority for blog photo
 */
function blogs_media_get_free_priority($blogs_posts_id, $insert = false){
    global $_CONFIG;

    try{
        if($insert){
            /*
             * Insert mode, return the first possible priority, in case there is a gap (ideally should be highest though, if there are no gaps)
             */
            $list = sql_list('SELECT `priority` FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id ORDER BY `priority` ASC', array(':blogs_posts_id' => $blogs_posts_id));

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
        return (integer) sql_get('SELECT MAX(`priority`) FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $blogs_posts_id)) + 1;

    }catch(Exception $e){
        throw new bException('blogs_media_get_free_priority(): Failed', $e);
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

        $photo    = sql_get('SELECT `blogs_media`.`id`,
                                    `blogs_media`.`createdby`

                             FROM   `blogs_media`

                             JOIN   `blogs_posts`

                             WHERE  `blogs_media`.`blogs_posts_id` = `blogs_posts`.`id`
                             AND    `blogs_media`.`id`             = '.cfi($photo_id));

        if(empty($photo['id'])) {
            throw new bException('blogs_photo_description(): Unknown blog post photo specified', 'unknown');
        }

        if(($photo['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new bException('blogs_photo_description(): Cannot upload photos, this post is not yours', 'accessdenied');
        }

        sql_query('UPDATE `blogs_media`

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
function blogs_photo_url($photo, $size){
    try{
        switch($size){
            case 'large':
                // FALLTHROUGH
            case 'medium':
                // FALLTHROUGH
            case 'small':
                // FALLTHROUGH
            case 'wide':
                // FALLTHROUGH
            case 'thumb':
                /*
                 * Valid
                 */
                return current_domain('/photos/'.$photo.'-'.$size.'.jpg');

            default:
                throw new bException(tr('blogs_photo_url(): Unknown size ":size" specified', array(':size' => str_log($size))), 'unknown');
        }

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
            $list = array(5 => tr('Low'),
                          4 => tr('Normal'),
                          3 => tr('High'),
                          2 => tr('Urgent'),
                          1 => tr('Immediate'));
        }

        if(is_numeric($priority)){
            if(isset($list[$priority])){
                return $list[$priority];
            }

            return $list[3];
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

        return 3;

    }catch(Exception $e){
        throw new bException('blogs_priority(): Failed', $e);
    }
}



/*
 * Validate the specified category
 */
function blogs_validate_category($category, $blogs_id){
    try{
        if(!$category){
            throw new bException(tr('blogs_validate_category(): No category specified'), 'not-exist');
        }

        if(!$retval = sql_get('SELECT `id`, `blogs_id`, `name`, `seoname` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `seoname` = :seoname', array(':blogs_id' => $blogs_id, ':seoname' => $category))){
// :DELETE: Delete following 2 debug code lines
//show(current_file(1).current_line(1));
//showdie(tr('The specified category ":category" does not exists', ':category', str_log($category)));
            /*
             * The specified category does not exist
             */
            throw new bException(tr('blogs_validate_category(): The specified category ":category" does not exists in blog ":blogs_id"', array(':blogs_id' => $blogs_id, ':category' => str_log($category))), 'not-exist');
        }

// :DELETE: This check is no longer needed since the query now filters on blogs_id
        //if($retval['blogs_id'] != $blogs_id){
        //    /*
        //     * The specified category is not of this blog
        //     */
        //    throw new bException(tr('blogs_validate_category(): The specified category ":category" is not of this blog', array(':category' => str_log($category))), 'invalid');
        //}

        return $retval;

    }catch(Exception $e){
        throw new bException('blogs_validate_category(): Failed', $e);
    }
}



/*
 *
 */
function blogs_validate_parent($blog_post_seoname, $blogs_id){
    try{
        $id = sql_get('SELECT `id` FROM `blogs_posts` WHERE `seoname` = :seoname AND `blogs_id` = :blogs_id', 'id', array(':blogs_id' => $blogs_id,
                                                                                                                          ':seoname'  => $blog_post_seoname));

        if(!$id){
            throw new bException(tr('blogs_validate_parent(): Blog ":blog" does not contain a blog post named ":post"', array(':blog' => $blogs_id, ':post' => $blog_post_seoname)), 'notmember');
        }

        return $id;

    }catch(Exception $e){
        throw new bException('blogs_validate_parent(): Failed', $e);
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

        $sections = array('id',
                          'time',
                          'date',
                          'createdon',
                          'blog',
                          'seoname',
                          'seoparent',
                          'category',
                          'seocategory',
                          'group',
                          'seogroup');

        if(empty($post['blog'])){
            $post['blog'] = sql_get('SELECT `seoname` FROM `blogs` WHERE `id` = :id', array(':id' => $post['blogs_id']), 'seoname');
        }

        foreach($sections as $section){
            switch($section){
                case 'seoparent':
                    $post[$section] = sql_get('SELECT `seoname` FROM `blogs_posts` WHERE `id` = :id', 'seoname', array(':id' => isset_get($post['parents_id'])));
                    break;

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
 * Update the URL's for the specified blog post
 */
function blogs_update_url($post){
    try{
        $url = blogs_post_url($post);

        if((PLATFORM == 'shell') and VERBOSE){
            cli_log(tr('blogs_update_url(): Updating blog post :post to URL ":url"', array(':url' => $url, ':post' => str_size('"'.str_truncate($post['seoname'], 40).'"', 42, ' '))));
        }

        sql_query('UPDATE `blogs_posts`

                   SET    `url` = :url

                   WHERE  `id`  = :id',

                   array(':url' => $url,
                         ':id'  => $post['id']));

    }catch(Exception $e){
        throw new bException('blogs_update_url(): Failed', $e);
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

            cli_log(tr('blogs_update_urls(): Updating posts for all blogs'));

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
                if(PLATFORM == 'shell'){
                    cli_log(tr('blogs_update_urls(): Specified blog ":blog" does not exist, skipping', array(':blog' => $blogname)), 'yellow');
                }

                continue;
            }

            if(PLATFORM == 'shell'){
                cli_log(tr('blogs_update_urls(): Updating posts for blog :blog', array(':blog' => str_size('"'.str_truncate($blog['name'], 40).'"', 42, ' '))), 'white');
            }

            /*
             * Walk over all posts of the specified blog
             */
            $query   = 'SELECT `id`,
                               `blogs_id`,
                               `parents_id`,
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
                    if(PLATFORM == 'shell'){
                        cli_log(tr('blogs_update_urls(): The category ":category" does not exist for the blog ":blog", skipping', array(':category' => $category['name'], ':blog' => $blog['name'])), 'yellow');
                    }

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
                blogs_update_url($post);
            }
        }

    }catch(Exception $e){
        throw new bException('blogs_update_urls(): Failed', $e);
    }
}


/*
 *
 */
function blogs_post_erase($post){
    global $_CONFIG;

    try{
        if(is_array($post)){
            $count = 0;

            foreach($post as $id){
                $count += blogs_post_erase($id);
            }

            return $count;
        }

        load_libs('file');
        load_config('blogs');

        if(is_numeric($post)){
            $post = sql_get('SELECT `id` FROM `blogs_posts` WHERE `id` = :id', 'id', array(':id' => $post));

        }else{
            $post = sql_get('SELECT `id` FROM `blogs_posts` WHERE `seoname` = :seoname', 'id', array(':seoname' => $post));
        }

        /*
         * First delete the physical image files
         */
        $r = sql_query('SELECT `file` FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post));

        while($media = sql_fetch($r)){
            foreach($_CONFIG['blogs']['images'] as $type => $config){
                file_delete(ROOT.'data/content/photos/'.$media['file'].'_'.$type.'.jpg');
            }
        }

        file_clear_path(ROOT.'data/content/photos/'.$media['file']);

        sql_query('DELETE FROM `blogs_media`      WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post));
        sql_query('DELETE FROM `blogs_keywords`   WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post));
        sql_query('DELETE FROM `blogs_key_values` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post));
        sql_query('DELETE FROM `blogs_posts`      WHERE `id`             = :id'            , array(':id'             => $post));

        return 1;

    }catch(Exception $e){
        throw new bException('blogs_update_urls(): Failed', $e);
    }
}
?>
