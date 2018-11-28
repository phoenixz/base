<?php
/*
 * Fix blogs_key_values indices
 */
sql_index_exists('blogs_key_values', 'blogs_id_2'        , '!ALTER TABLE `blogs_key_values` DROP       KEY `blogs_id_2`');
sql_index_exists('blogs_key_values', 'blogs_id_parent'   , '!ALTER TABLE `blogs_key_values` ADD        KEY `blogs_id_parent`    (`blogs_id`      , `parent`)');
sql_index_exists('blogs_key_values', 'blogs_posts_id_key', '!ALTER TABLE `blogs_key_values` ADD UNIQUE KEY `blogs_posts_id_key` (`blogs_posts_id`, `key`)');
?>