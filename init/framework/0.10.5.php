<?php
/*
 * Add some more columns for blogging
 */
sql_column_exists('blogs'           , 'keywords'   , '!ALTER TABLE `blogs`            ADD COLUMN `keywords`    VARCHAR(255) NULL AFTER `seoname`');
sql_column_exists('blogs'           , 'seokeywords', '!ALTER TABLE `blogs`            ADD COLUMN `seokeywords` VARCHAR(255) NULL AFTER `keywords`');
sql_column_exists('blogs_categories', 'keywords'   , '!ALTER TABLE `blogs_categories` ADD COLUMN `keywords`    VARCHAR(255) NULL AFTER `seoname`');
sql_column_exists('blogs_categories', 'seokeywords', '!ALTER TABLE `blogs_categories` ADD COLUMN `seokeywords` VARCHAR(255) NULL AFTER `keywords`');
sql_column_exists('blogs_categories', 'description', '!ALTER TABLE `blogs_categories` ADD COLUMN `description` VARCHAR(155) NULL AFTER `seokeywords`');
sql_column_exists('blogs_posts'     , 'description', '!ALTER TABLE `blogs_posts`      ADD COLUMN `description` VARCHAR(155) NULL AFTER `seokeywords`');

sql_query('ALTER TABLE `blogs`        CHANGE COLUMN `description` `description` VARCHAR(155) NULL');
sql_query('ALTER TABLE `blogs_photos` CHANGE COLUMN `description` `description` VARCHAR(255) NULL');
?>
