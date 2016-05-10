<?php
/*
 * Add more media support to blog posts, like youtube videos, audio, etc.
 */
sql_table_exists('blogs_photos', 'RENAME TABLE `blogs_photos` TO `blogs_media`');

sql_column_exists('blogs_media', 'type', '!ALTER TABLE `blogs_media` ADD COLUMN `type` ENUM("photo", "youtube", "vimeo", "video", "audio", "other") NOT NULL DEFAULT "photo";');
sql_index_exists ('blogs_media', 'type', '!ALTER TABLE `blogs_media` ADD INDEX (`type`);');

/*
 * Fix blogs_id links in blogs_media and add foreign key to place constraint
 */
$r = sql_query('SELECT `blogs_posts_id` FROM `blogs_media` WHERE `blogs_id` = 0 OR `blogs_id` IS NULL GROUP BY `blogs_posts_id`');
$p = sql_prepare('UPDATE `blogs_media` SET `blogs_id` = :blogs_id WHERE `blogs_posts_id` = :blogs_posts_id');

while($entry = sql_fetch($r)){
    $blogs_id = sql_get('SELECT `blogs_id` FROM `blogs_posts` WHERE `id` = :id', 'blogs_id', array(':id' => $entry['blogs_posts_id']));

    $p->execute(array(':blogs_posts_id' => $entry['blogs_posts_id'],
                      ':blogs_id'       => $blogs_id));
}

sql_foreignkey_exists('blogs_media', 'fk_blogs_media_blogs_id' , '!ALTER TABLE `blogs_media` ADD CONSTRAINT `fk_blogs_media_blogs_id` FOREIGN KEY (`blogs_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT;');
?>
