<?php
/*
 * Add priority to blog post images
 */
sql_column_exists('blogs_photos', 'priority'  , '!ALTER TABLE `blogs_photos` ADD COLUMN `priority` INT(11) NULL');
sql_index_exists ('blogs_photos', 'priority'  , '!ALTER TABLE `blogs_photos` ADD INDEX (`priority`)');

sql_query('UPDATE `blogs_photos` SET `priority` = NULL');

$r = sql_query('SELECT `id` FROM `blogs_posts`');
$p = sql_prepare('UPDATE `blogs_photos` SET `priority` = :priority WHERE `id` = :id');

while($post = sql_fetch($r)){
    $priority = 0;
    $s        = sql_query('SELECT `id` FROM `blogs_photos` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post['id']));

    while($photo = sql_fetch($s)){
        $p->execute(array(':id'       => $photo['id'],
                          ':priority' => $priority++));
    }
}

sql_index_exists ('blogs_photos', 'priority_2', '!ALTER TABLE `blogs_photos` ADD UNIQUE(`priority`, `blogs_posts_id`)');
?>