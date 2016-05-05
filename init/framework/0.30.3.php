<?php
/*
 * Add more media support to blog posts, like youtube videos, audio, etc.
 */
sql_table_exists('blogs_photos', 'RENAME TABLE `blogs_photos` TO `blogs_media`');

sql_column_exists('blogs_media', 'type', 'ALTER TABLE `blogs_media` ADD COLUMN `type` ENUM("photo", "youtube", "vimeo", "audio", "other") NOT NULL;');
sql_index_exists ('blogs_media', 'type', 'ALTER TABLE `blogs_media` ADD INDEX (`type`);');
?>
