<?php
/*
 * Remove old modifiedby and modifiedon columns
 * Add new meta_id column
 * Add support for twilio_numbers type and data columns
 */
sql_foreignkey_exists('twilio_groups'  , 'fk_twilio_groups_modifiedby'  , 'ALTER TABLE `twilio_groups`   DROP FOREIGN KEY `fk_twilio_groups_modifiedby`;');
sql_foreignkey_exists('twilio_numbers' , 'fk_twilio_numbers_modifiedby' , 'ALTER TABLE `twilio_numbers`  DROP FOREIGN KEY `fk_twilio_numbers_modifiedby`;');
sql_foreignkey_exists('twilio_accounts', 'fk_twilio_accounts_modifiedby', 'ALTER TABLE `twilio_accounts` DROP FOREIGN KEY `fk_twilio_accounts_modifiedby`;');

sql_index_exists ('twilio_groups', 'modifiedby', 'ALTER TABLE `twilio_groups` DROP KEY    `modifiedby`');
sql_column_exists('twilio_groups', 'modifiedby', 'ALTER TABLE `twilio_groups` DROP COLUMN `modifiedby`');

sql_index_exists ('twilio_groups', 'modifiedon', 'ALTER TABLE `twilio_groups` DROP KEY    `modifiedon`');
sql_column_exists('twilio_groups', 'modifiedon', 'ALTER TABLE `twilio_groups` DROP COLUMN `modifiedon`');

sql_index_exists ('twilio_accounts', 'modifiedby', 'ALTER TABLE `twilio_accounts` DROP KEY    `modifiedby`');
sql_column_exists('twilio_accounts', 'modifiedby', 'ALTER TABLE `twilio_accounts` DROP COLUMN `modifiedby`');

sql_index_exists ('twilio_accounts', 'modifiedon', 'ALTER TABLE `twilio_accounts` DROP KEY    `modifiedon`');
sql_column_exists('twilio_accounts', 'modifiedon', 'ALTER TABLE `twilio_accounts` DROP COLUMN `modifiedon`');

sql_index_exists ('twilio_numbers', 'modifiedby', 'ALTER TABLE `twilio_numbers` DROP KEY    `modifiedby`');
sql_column_exists('twilio_numbers', 'modifiedby', 'ALTER TABLE `twilio_numbers` DROP COLUMN `modifiedby`');

sql_index_exists ('twilio_numbers', 'modifiedon', 'ALTER TABLE `twilio_numbers` DROP KEY    `modifiedon`');
sql_column_exists('twilio_numbers', 'modifiedon', 'ALTER TABLE `twilio_numbers` DROP COLUMN `modifiedon`');

sql_column_exists('twilio_numbers', 'meta_id', '!ALTER TABLE `twilio_numbers` ADD COLUMN `meta_id` INT(11) NULL DEFAULT NULL AFTER `createdby`');
sql_index_exists ('twilio_numbers', 'meta_id', '!ALTER TABLE `twilio_numbers` ADD KEY    `meta_id` (`meta_id`)');

sql_column_exists('twilio_accounts', 'meta_id', '!ALTER TABLE `twilio_accounts` ADD COLUMN `meta_id` INT(11) NULL DEFAULT NULL AFTER `createdby`');
sql_index_exists ('twilio_accounts', 'meta_id', '!ALTER TABLE `twilio_accounts` ADD KEY    `meta_id` (`meta_id`)');

sql_column_exists('twilio_groups', 'meta_id', '!ALTER TABLE `twilio_groups` ADD COLUMN `meta_id` INT(11) NULL DEFAULT NULL AFTER `createdby`');
sql_index_exists ('twilio_groups', 'meta_id', '!ALTER TABLE `twilio_groups` ADD KEY    `meta_id` (`meta_id`)');

sql_foreignkey_exists('twilio_groups'  , 'fk_twilio_groups_meta_id'  , '!ALTER TABLE `twilio_groups`   ADD CONSTRAINT `fk_twilio_groups_meta_id`   FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('twilio_numbers' , 'fk_twilio_numbers_meta_id' , '!ALTER TABLE `twilio_numbers`  ADD CONSTRAINT `fk_twilio_numbers_meta_id`  FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('twilio_accounts', 'fk_twilio_accounts_meta_id', '!ALTER TABLE `twilio_accounts` ADD CONSTRAINT `fk_twilio_accounts_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');

sql_column_exists('twilio_numbers', 'type', '!ALTER TABLE `twilio_numbers` ADD COLUMN `type` ENUM("forward", "simulring") NULL DEFAULT NULL AFTER `status`');
sql_column_exists('twilio_numbers', 'data', '!ALTER TABLE `twilio_numbers` ADD COLUMN `data` VARCHAR(4090) NULL DEFAULT NULL AFTER `number`');
?>