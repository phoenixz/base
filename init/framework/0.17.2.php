<?php
/*
 * Fix priority index
 */
sql_index_exists ('blogs_photos', 'priority_2', 'ALTER TABLE `blogs_photos` DROP INDEX `priority_2`');

/*
 * Fix invalid image priorities
 */
$r = sql_query('SELECT `id` FROM `blogs_posts`');
$s = sql_prepare('UPDATE `blogs_photos` SET `priority` = :priority WHERE `id` = :id');

load_libs('blogs');

while($post = sql_fetch($r)){
    $u = sql_query('SELECT `id` FROM `blogs_photos` WHERE `blogs_posts_id` = :blogs_posts_id AND `priority` IS NULL', array(':blogs_posts_id' => $post['id']));

    while($photo = sql_fetch($u)){
        $s->execute(array(':id'       => $photo['id'],
                          ':priority' => blogs_photos_get_free_priority($post['id'])));
    }
}

/*
 * Fix priority column
 */
sql_query('ALTER TABLE `blogs_photos` CHANGE COLUMN `priority` `priority` INT(11) NOT NULL');
?>