<?php
/*
 * Also store the original file name
 */
sql_column_exists('blogs_media', 'original', '!ALTER TABLE `blogs_media` ADD COLUMN `original` VARCHAR(255) NOT NULL AFTER `file`');
?>
