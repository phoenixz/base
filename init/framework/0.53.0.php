<?php
/*
 * Update the blogs_media table to handle mime types
 */
sql_query('ALTER TABLE `blogs_media` MODIFY COLUMN `type` VARCHAR(16) NULL');
sql_column_exists('blogs_media', 'mime1', '!ALTER TABLE `blogs_media` ADD COLUMN `mime1` VARCHAR(8) NULL');
sql_column_exists('blogs_media', 'mime2', '!ALTER TABLE `blogs_media` ADD COLUMN `mime2` VARCHAR(8) NULL');

sql_index_exists('blogs_media', 'mime1', '!ALTER TABLE `blogs_media` ADD INDEX `mime1` (`mime1`)');
sql_index_exists('blogs_media', 'mime2', '!ALTER TABLE `blogs_media` ADD INDEX `mime2` (`mime2`)');
?>
