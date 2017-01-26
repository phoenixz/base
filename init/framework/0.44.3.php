<?php
/*
 * Preparing `blogs_posts` table for multilingual support
 */
sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_masters_id', 'ALTER TABLE `blogs_posts` DROP FOREIGN KEY `fk_blogs_posts_masters_id`');

sql_index_exists('blogs_posts', 'masters_id_language'      , 'ALTER TABLE `blogs_posts` DROP INDEX `masters_id_language`');
sql_index_exists('blogs_posts', 'blogs_id_language_seoname',  'ALTER TABLE `blogs_posts` DROP INDEX `blogs_id_language_seoname`');

sql_query('ALTER TABLE `blogs_posts` MODIFY COLUMN `language` VARCHAR(2) NOT NULL');
sql_column_exists('blogs_posts', 'masters_id', '!ALTER TABLE `blogs_posts` ADD COLUMN `masters_id` INT(11) NULL AFTER `id`');

sql_query('UPDATE `blogs_posts` SET `language` = "en" WHERE `language` = ""');

sql_foreignkey_exists ('blogs_posts', 'fk_blogs_posts_masters_id', '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_masters_id` FOREIGN KEY (`masters_id`) REFERENCES `blogs_posts` (`id`) ON DELETE RESTRICT');

sql_index_exists('blogs_posts', 'masters_id_language'      , '!ALTER TABLE `blogs_posts` ADD UNIQUE INDEX `masters_id_language`       (`masters_id`, `language`)');
sql_index_exists('blogs_posts', 'blogs_id_language_seoname', '!ALTER TABLE `blogs_posts` ADD UNIQUE INDEX `blogs_id_language_seoname` (`blogs_id`, `language`, `seoname` (80))');
?>
