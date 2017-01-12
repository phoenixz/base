<?php
/*
 * Add description
 */
sql_column_exists('domains_groups', 'description', 'ALTER TABLE `description` ADD COLUMN `description` VARCHAR(2047) NULL');
sql_table_exists('domains_servers_links', 'RENAME TABLE `domains_servers_links` TO `domains_servers`');

sql_foreignkey_exists ('domains_servers', 'fk_domains_servers_customers_id', 'ALTER TABLE `domains_servers` DROP FOREIGN KEY `fk_domains_servers_customers_id`');
sql_foreignkey_exists ('domains_servers', 'fk_domains_servers_servers_id'  , 'ALTER TABLE `domains_servers` DROP FOREIGN KEY `fk_domains_servers_servers_id`');

sql_index_exists ('domains_servers', 'customers_id', 'ALTER TABLE `domains_servers` DROP INDEX    `customers_id`');
sql_column_exists('domains_servers', 'customers_id', 'ALTER TABLE `domains_servers` CHANGE COLUMN `customers_id` `domains_id` INT(11) NOT NULL');

sql_index_exists('domains_servers', 'domains_id'     , '!ALTER TABLE `domains_servers` ADD        KEY `domains_id`      (`domains_id`)');
sql_index_exists('domains_servers', 'domains_servers', '!ALTER TABLE `domains_servers` ADD UNIQUE KEY `domains_servers` (`domains_id`, `servers_id`)');

sql_foreignkey_exists ('domains_servers', 'fk_domains_servers_domains_id', '!ALTER TABLE `domains_servers` ADD CONSTRAINT `fk_domains_servers_domains_id` FOREIGN KEY (`domains_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE;');
sql_foreignkey_exists ('domains_servers', 'fk_domains_servers_servers_id', '!ALTER TABLE `domains_servers` ADD CONSTRAINT `fk_domains_servers_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE;');
?>
