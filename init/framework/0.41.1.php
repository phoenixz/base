<?php
/*
 * Add support for domain providers
 */
sql_column_exists('domains', 'providers_id', '!ALTER TABLE `domains` ADD COLUMN `providers_id` INT(11) NULL AFTER `customers_id`');
sql_index_exists ('domains', 'providers_id', '!ALTER TABLE `domains` ADD INDEX (`providers_id`)');
sql_foreignkey_exists('domains', 'fk_domains_providers_id', '!ALTER TABLE `domains` ADD CONSTRAINT `fk_domains_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT;');
