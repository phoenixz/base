<?php
/*
 * Add support for domain providers and ssh accounts to servers
 */
sql_table_exists('ssh_keys', 'RENAME TABLE `ssh_keys` TO `ssh_accounts`');
sql_column_exists('ssh_accounts', 'ssh_key', 'ALTER TABLE `ssh_accounts` MODIFY COLUMN `ssh_key` VARCHAR(8180) NULL');
sql_column_exists('ssh_accounts', 'username', '!ALTER TABLE `ssh_accounts` ADD  COLUMN `username` VARCHAR(32) NOT NULL AFTER `seoname`');

sql_column_exists('domains', 'providers_id', '!ALTER TABLE `domains` ADD COLUMN `providers_id` INT(11) NULL AFTER `customers_id`');
sql_index_exists ('domains', 'providers_id', '!ALTER TABLE `domains` ADD INDEX (`providers_id`)');
sql_foreignkey_exists('domains', 'fk_domains_providers_id', '!ALTER TABLE `domains` ADD CONSTRAINT `fk_domains_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT;');

sql_column_exists('servers', 'user'           ,  'ALTER TABLE `servers` DROP COLUMN `user`');
sql_column_exists('servers', 'ssh_accounts_id', '!ALTER TABLE `servers` ADD  COLUMN `ssh_accounts_id` INT(11) NULL AFTER `customers_id`');
sql_index_exists ('servers', 'ssh_accounts_id', '!ALTER TABLE `servers` ADD  INDEX (`ssh_accounts_id`)');

sql_index_exists ('servers', 'hostname', 'ALTER TABLE `servers` DROP INDEX `hostname`');
sql_query('ALTER TABLE `servers` ADD UNIQUE KEY `hostname` (`hostname`, `ssh_accounts_id`)');

sql_foreignkey_exists('servers', 'fk_servers_ssh_accounts_id', '!ALTER TABLE `servers` ADD CONSTRAINT `fk_servers_ssh_accounts_id` FOREIGN KEY (`ssh_accounts_id`) REFERENCES `ssh_accounts` (`id`) ON DELETE RESTRICT;');
?>
