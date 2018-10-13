<?php
/*
 * Fix email_servers indices, hostname should be unique
 * Fix servers table indices
 */
sql_index_exists('email_servers', 'hostname', 'ALTER TABLE `email_servers` DROP INDEX `hostname`');
sql_index_exists('email_servers', 'domain'  , 'ALTER TABLE `email_servers` DROP INDEX `domain`');

sql_query('TRUNCATE `email_servers`');
sql_query('ALTER TABLE `email_servers` ADD UNIQUE KEY `hostname` (`hostname`)');

sql_index_exists('servers', 'hostname', 'ALTER TABLE `servers` DROP INDEX `hostname`');
sql_query('ALTER TABLE `servers` ADD UNIQUE KEY `hostname_ssh_accounts_id` (`hostname`, `ssh_accounts_id`)');
?>