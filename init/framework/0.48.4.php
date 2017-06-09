<?php
/*
 * Blog comments now supports parent comments
 */
sql_column_exists('blogs_comments', 'parents_id', '!ALTER TABLE `blogs_comments` ADD COLUMN `parents_id` INT(11) NULL AFTER `blogs_posts_id`');
sql_index_exists ('blogs_comments', 'parents_id', '!ALTER TABLE `blogs_comments` ADD KEY `parents_id` (`parents_id`)');

sql_foreignkey_exists('blogs_comments', 'fk_blogs_comments_parents_id', '!ALTER TABLE `blogs_comments` ADD CONSTRAINT `fk_blogs_comments_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `blogs_comments` (`id`) ON DELETE CASCADE;');
?>
