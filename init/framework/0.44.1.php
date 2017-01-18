<?php
/*
 * Add hash to no save duplicated images in blogs_media
 */

sql_column_exists('blogs_media', 'hash', '!ALTER TABLE `blogs_media` ADD COLUMN `hash` CHAR(40) NULL DEFAULT NULL AFTER `original`');

?>
