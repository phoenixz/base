<?php
/*
 * Fix blogs_posts category and seocategory columns
 */
$r = sql_query('SELECT `id`, `categories_id` FROM `blogs_posts`');

while($post = sql_fetch($r)){
    $category = sql_get('SELECT `name`, `seoname` FROM `blogs_categories` WHERE `id` = :id', array(':id' => $post['categories_id']));

    sql_query('UPDATE `blogs_posts`

               SET    `category`    = :category,
                      `seocategory` = :seocategory

               WHERE  `id`          = :id',

               array(':id'          => $post['id'],
                     ':category'    => $category['name'],
                     ':seocategory' => $category['seoname']));
}
?>
