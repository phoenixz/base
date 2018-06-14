<?php
/*
 * Add support for customers and providers in the storage system
 */
sql_column_exists     ('storage_documents', 'providers_id'                     , '!ALTER TABLE `storage_documents` ADD COLUMN `providers_id` INT(11) NULL DEFAULT NULL AFTER `assigned_to_id`');
sql_index_exists      ('storage_documents', 'providers_id'                     , '!ALTER TABLE `storage_documents` ADD KEY    `providers_id` (`providers_id`)');
sql_foreignkey_exists ('storage_documents', 'fk_storage_documents_providers_id', '!ALTER TABLE `storage_documents` ADD CONSTRAINT `fk_storage_documents_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT');

sql_column_exists     ('storage_documents', 'customers_id'                     , '!ALTER TABLE `storage_documents` ADD COLUMN `customers_id` INT(11) NULL DEFAULT NULL AFTER `providers_id`');
sql_index_exists      ('storage_documents', 'customers_id'                     , '!ALTER TABLE `storage_documents` ADD KEY  `customers_id` (`customers_id`)');
sql_foreignkey_exists ('storage_documents', 'fk_storage_documents_customers_id', '!ALTER TABLE `storage_documents` ADD CONSTRAINT `fk_storage_documents_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT');

sql_column_exists     ('customers', 'code'    , '!ALTER TABLE `customers` ADD COLUMN `code`     VARCHAR(64) NULL DEFAULT NULL AFTER `seoname`');
sql_column_exists     ('customers', 'company' , '!ALTER TABLE `customers` ADD COLUMN `company`  VARCHAR(64) NULL DEFAULT NULL AFTER `code`');
sql_column_exists     ('customers', 'address1', '!ALTER TABLE `customers` ADD COLUMN `address1` VARCHAR(64) NULL DEFAULT NULL AFTER `company`');
sql_column_exists     ('customers', 'address2', '!ALTER TABLE `customers` ADD COLUMN `address2` VARCHAR(64) NULL DEFAULT NULL AFTER `address1`');
sql_column_exists     ('customers', 'address3', '!ALTER TABLE `customers` ADD COLUMN `address3` VARCHAR(64) NULL DEFAULT NULL AFTER `address2`');
sql_column_exists     ('customers', 'zipcode' , '!ALTER TABLE `customers` ADD COLUMN `zipcode`  VARCHAR(6)  NULL DEFAULT NULL AFTER `address3`');

sql_column_exists     ('customers', 'countries_id'             , '!ALTER TABLE `customers` ADD COLUMN     `countries_id` INT(11) NULL DEFAULT NULL AFTER `zipcode`');
sql_index_exists      ('customers', 'countries_id'             , '!ALTER TABLE `customers` ADD KEY        `countries_id` (`countries_id`)');
sql_foreignkey_exists ('customers', 'fk_customers_countries_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT');

sql_column_exists     ('customers', 'states_id'             , '!ALTER TABLE `customers` ADD COLUMN     `states_id` INT(11) NULL DEFAULT NULL AFTER `countries_id`');
sql_index_exists      ('customers', 'states_id'             , '!ALTER TABLE `customers` ADD KEY        `states_id` (`states_id`)');
sql_foreignkey_exists ('customers', 'fk_customers_states_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT');

sql_column_exists     ('customers', 'cities_id'             , '!ALTER TABLE `customers` ADD COLUMN     `cities_id` INT(11) NULL DEFAULT NULL AFTER `states_id`');
sql_index_exists      ('customers', 'cities_id'             , '!ALTER TABLE `customers` ADD KEY        `cities_id` (`cities_id`)');
sql_foreignkey_exists ('customers', 'fk_customers_cities_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT');
?>