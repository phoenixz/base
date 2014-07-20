<?php
/*
 * Add new columns "priority" and "urlref" to blogs_posts
 */
sql_column_exists('blogs_posts', 'priority', '!ALTER TABLE `blogs_posts` ADD COLUMN `priority` INT(11)      NULL AFTER `description`');
sql_column_exists('blogs_posts', 'urlref'  , '!ALTER TABLE `blogs_posts` ADD COLUMN `urlref`   VARCHAR(255) NULL AFTER `url`');
?>
