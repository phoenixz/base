<?php
/*
 * Add more media support to blog posts, like youtube videos, audio, etc.
 */
sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_category', 'ALTER TABLE `blogs_posts` DROP FOREIGN KEY `fk_blogs_posts_category`');
sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_group'   , 'ALTER TABLE `blogs_posts` DROP FOREIGN KEY `fk_blogs_posts_group`');

sql_index_exists('blogs_categories', 'seoname'   , 'ALTER TABLE `blogs_categories` DROP INDEX `seoname`');
sql_index_exists('blogs_categories', 'blogs_id_2', 'ALTER TABLE `blogs_categories` DROP INDEX `blogs_id_2`');

sql_query('ALTER TABLE `blogs_categories` ADD INDEX  `seoname` (`seoname`)');

sql_index_exists('blogs_categories', 'blogs_id_seoname', '!ALTER TABLE `blogs_categories` ADD UNIQUE `blogs_id_seoname` (`blogs_id`, `seoname`)');

sql_query('ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_category` FOREIGN KEY (`seocategory`) REFERENCES `blogs_categories` (`seoname`) ON DELETE RESTRICT;');
sql_query('ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_group`    FOREIGN KEY (`seogroup`)    REFERENCES `blogs_categories` (`seoname`) ON DELETE RESTRICT;');
?>
