<?php
/*
 * Add hash column to blogs_media
 */

sql_column_exists('blogs_media', 'hash' , '!ALTER TABLE `blogs_media` ADD COLUMN `hash`  VARCHAR(40) NULL DEFAULT NULL AFTER `original`');

?>
