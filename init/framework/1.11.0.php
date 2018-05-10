<?php
/*
 * Fields to store server passthrough relation
 */
sql_column_exists('servers', 'ipv4', '!ALTER TABLE `servers` ADD COLUMN `ipv4` VARCHAR(15) NULL DEFAULT NULL AFTER `database`;');
sql_column_exists('servers', 'ipv6', '!ALTER TABLE `servers` ADD COLUMN `ipv6` VARCHAR(39) NULL DEFAULT NULL AFTER `ipv4`;');

sql_column_exists('servers', 'ssh_proxy_id', '!ALTER TABLE `servers` ADD COLUMN `ssh_proxy_id` INT(11) NULL DEFAULT NULL AFTER `ipv6`;');
sql_index_exists ('servers', 'ssh_proxy_id', '!ALTER TABLE `servers` ADD INDEX  `ssh_proxy_id` (`ssh_proxy_id`)');

sql_foreignkey_exists('servers', 'fk_servers_ssh_proxy_id', '!ALTER TABLE `servers` ADD CONSTRAINT `fk_servers_ssh_proxy_id` FOREIGN KEY (`ssh_proxy_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');
?>