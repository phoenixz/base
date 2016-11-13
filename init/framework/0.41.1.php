<?php
/*
 * Add support for domain providers
 */
sql_column_exists('domains', 'providers_id', '!ALTER TABLE `domains` ADD COLUMN `providers_id` INT(11) NULL AFTER `customers_id`');
sql_index_exists ('domains', 'providers_id', '!ALTER TABLE `domains` ADD INDEX (`providers_id`)');
sql_foreignkey_exists('domains', 'fk_domains_providers_id', '!ALTER TABLE `domains` ADD CONSTRAINT `fk_domains_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT;');

sql_column_exists('ssh_keys', 'ssh_key'     , 'ALTER TABLE `ssh_keys` MODIFY COLUMN `ssh_key` VARCHAR(8180) NULL');
sql_column_exists('ssh_keys', 'key_file'    , '!ALTER TABLE `ssh_keys` ADD COLUMN `key_file` VARCHAR(8) NULL AFTER `ssh_key`');
