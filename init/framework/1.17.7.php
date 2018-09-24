<?php
/*
 * Add type support to domains tables
 */
sql_query('ALTER TABLE `domains` MODIFY `ssh_port` INT(11)      NULL DEFAULT 22');
sql_query('ALTER TABLE `domains` MODIFY `ssh_path` VARCHAR(255) NULL DEFAULT NULL');

sql_column_exists('domains', 'type', '!ALTER TABLE `domains` ADD COLUMN `type` VARCHAR(16) NULL DEFAULT NULL');
sql_index_exists ('domains', 'type', '!ALTER TABLE `domains` ADD KEY    `type` (`type`)');

sql_foreignkey_exists('domains'  , 'fk_domains_modifiedby'  , 'ALTER TABLE `domains` DROP FOREIGN KEY `fk_domains_modifiedby`;');

sql_index_exists ('domains', 'modifiedon', 'ALTER TABLE `domains` DROP KEY `modifiedon`');
sql_index_exists ('domains', 'modifiedby', 'ALTER TABLE `domains` DROP KEY `modifiedby`');

sql_column_exists('domains', 'modifiedon', 'ALTER TABLE `domains` DROP COLUMN `modifiedon`');
sql_column_exists('domains', 'modifiedby', 'ALTER TABLE `domains` DROP COLUMN `modifiedby`');

sql_column_exists('domains', 'meta_id', '!ALTER TABLE `domains` ADD COLUMN `meta_id` INT(11) NULL DEFAULT NULL AFTER `createdby`');
sql_index_exists ('domains', 'meta_id', '!ALTER TABLE `domains` ADD KEY    `meta_id` (`meta_id`)');

sql_foreignkey_exists('domains', 'fk_domains_meta_id', '!ALTER TABLE `domains` ADD CONSTRAINT `fk_domains_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');
?>