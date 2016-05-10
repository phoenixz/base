<?php
/*
 *
 */
sql_column_exists('email_domains', 'poll_interval', '!ALTER TABLE `email_domains` ADD COLUMN `poll_interval` INT(11) NULL AFTER `imap`');

sql_column_exists('email_users', 'poll_interval', '!ALTER TABLE `email_users` ADD COLUMN `poll_interval` INT(11)  NOT NULL AFTER `domains_id`');
sql_column_exists('email_users', 'last_poll'    , '!ALTER TABLE `email_users` ADD COLUMN `last_poll`     DATETIME     NULL AFTER `poll_interval`');

sql_index_exists ('email_users', 'last_poll'    , '!ALTER TABLE `email_users` ADD INDEX (`poll_interval`)');
sql_index_exists ('email_users', 'last_poll'    , '!ALTER TABLE `email_users` ADD INDEX (`last_poll`)');

sql_index_exists ('email_users', 'realname', 'ALTER TABLE `email_users` DROP INDEX  `realname`');
sql_column_exists('email_users', 'realname', 'ALTER TABLE `email_users` DROP COLUMN `realname`');

sql_index_exists ('email_users', 'seoname' , 'ALTER TABLE `email_users` DROP INDEX  `seoname`');
sql_column_exists('email_users', 'seoname' , 'ALTER TABLE `email_users` DROP COLUMN `seoname`');
?>
