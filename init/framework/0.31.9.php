<?php
/*
 * Add support for blogs_key_values with a parent
 */
sql_column_exists('blogs_key_values', 'parent', '!ALTER TABLE `blogs_key_values` ADD COLUMN `parent` VARCHAR(32) NULL AFTER `blogs_posts_id`');
sql_index_exists ('blogs_key_values', 'parent', '!ALTER TABLE `blogs_key_values` ADD INDEX (`blogs_id`, `parent`)');
?>
