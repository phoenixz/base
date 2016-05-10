<?php
/*
 * Fix blogs "createdby" columns
 */
sql_query('ALTER TABLE `blogs`                        CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
sql_query('ALTER TABLE `blogs_categories`             CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
sql_query('ALTER TABLE `blogs_comments`               CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
sql_query('ALTER TABLE `blogs_key_value_descriptions` CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
sql_query('ALTER TABLE `blogs_media`                  CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
sql_query('ALTER TABLE `blogs_posts`                  CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
sql_query('ALTER TABLE `blogs_updates`                CHANGE COLUMN `createdby` `createdby` INT(11) NULL');

sql_index_exists('email_messages', 'messages_id', 'ALTER TABLE `email_messages` ADD INDEX (`messages_id` (16))');
?>
