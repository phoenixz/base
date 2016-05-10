<?php
/*
 * Email column can now also be null because only either a username or email
 * must be supplied
 */
sql_query('ALTER TABLE `users` CHANGE COLUMN `email` `email` VARCHAR(128) NULL');

sql_column_exists('email_users', 'users_id', '!ALTER TABLE `email_users` ADD COLUMN `users_id` INT(11) NULL AFTER `domains_id`');
?>
