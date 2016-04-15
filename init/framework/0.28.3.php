<?php
/*
 * Fix blog posts status column, add very needed email_subscriptions
 */
sql_index_exists('blogs_posts', 'url', 'ALTER TABLE `blogs_posts` DROP INDEX `url`;');
?>
