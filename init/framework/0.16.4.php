<?php
/*
 * meta description tag can have up to 160 characters!
 */
sql_query('ALTER TABLE `blogs`            CHANGE COLUMN  `description` `description` VARCHAR(160) NULL');
sql_query('ALTER TABLE `blogs_posts`      CHANGE COLUMN  `description` `description` VARCHAR(160) NULL');
sql_query('ALTER TABLE `blogs_categories` CHANGE COLUMN  `description` `description` VARCHAR(160) NULL');
?>