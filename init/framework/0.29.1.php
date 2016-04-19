<?php
/*
 * Default characterset and collations have changed. Ensure that database and tables follow current settings
 */
sql_column_exists('blogs_key_values', 'seokey', '!ALTER TABLE `blogs_key_values` ADD COLUMN `seokey` VARCHAR(16) NULL AFTER `blogs_posts_id`;');

sql_index_exists ('blogs_key_values', 'seokey', '!ALTER TABLE `blogs_key_values` ADD  INDEX (`seokey`);');
sql_index_exists ('blogs_key_values', 'seokey',  'ALTER TABLE `blogs_key_values` DROP INDEX `key`;');
?>
