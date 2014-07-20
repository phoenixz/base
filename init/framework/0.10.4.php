<?php
/*
 * Fix blogs_categories indices
 */
sql_index_exists('blogs_categories', 'name'   , 'ALTER TABLE `blogs_categories` DROP INDEX `name`');

sql_query('ALTER TABLE `blogs_categories` ADD INDEX(`name`)');
sql_query('ALTER TABLE `blogs_categories` ADD UNIQUE(`blogs_id`, `name`)');
?>
