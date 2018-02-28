<?php
/*
 * Fix storage_categories not having name and parents_id columns.. doh?
 */
sql_column_exists('storage_categories', 'name'      , '!ALTER TABLE `storage_categories` ADD COLUMN `name`       VARCHAR(32) NULL AFTER `status`');
sql_column_exists('storage_categories', 'seoname'   , '!ALTER TABLE `storage_categories` ADD COLUMN `seoname`    VARCHAR(32) NULL AFTER `name`');
sql_column_exists('storage_categories', 'parents_id', '!ALTER TABLE `storage_categories` ADD COLUMN `parents_id` INT(11)     NULL AFTER `seoname`');

sql_index_exists('storage_categories', 'seoname', '!ALTER TABLE `storage_categories` ADD KEY `seoname`    (`seoname`)');
sql_index_exists('storage_categories', 'seoname', '!ALTER TABLE `storage_categories` ADD KEY `parents_id` (`parents_id`)');

sql_foreignkey_exists('storage_categories', 'fk_storage_categories_parents_id' , '!ALTER TABLE `storage_categories` ADD CONSTRAINT `fk_storage_categories_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `storage_categories` (`id`) ON DELETE CASCADE;');
?>
