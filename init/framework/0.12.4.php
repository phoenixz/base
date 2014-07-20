<?php
/*
 * Add missing index
 */
sql_index_exists('blogs_posts', 'priority', '!ALTER TABLE `blogs_posts` ADD INDEX(`priority`)');
?>
