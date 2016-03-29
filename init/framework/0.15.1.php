<?php
/*
 * Fix sitemap tables
 */
sql_table_exists ('sitemap_builds', 'RENAME TABLE `sitemap_builds` TO `sitemap_scans`');

sql_index_exists ('sitemap_scans', 'build_time',  'ALTER TABLE `sitemap_scans` DROP INDEX `build_time`');

sql_column_exists('sitemap_scans', 'build_time',  'ALTER TABLE `sitemap_scans` CHANGE COLUMN `build_time` `scan_time` DATETIME');

sql_index_exists ('sitemap_scans', 'scan_time' , '!ALTER TABLE `sitemap_scans` ADD  INDEX (`scan_time`)');



sql_index_exists ('sitemap_data', 'file'         ,  'ALTER TABLE `sitemap_data` DROP INDEX `file`');
sql_index_exists ('sitemap_data', 'file_original',  'ALTER TABLE `sitemap_data` DROP INDEX `file_original`');

sql_column_exists('sitemap_data', 'file_original',  'ALTER TABLE `sitemap_data` DROP   COLUMN `file_original`');
sql_column_exists('sitemap_data', 'file'         ,  'ALTER TABLE `sitemap_data` CHANGE COLUMN `file` `url` VARCHAR(255)');

sql_index_exists ('sitemap_data', 'url'          , '!ALTER TABLE `sitemap_data` ADD  INDEX (`url`)');



sql_foreignkey_exists('sitemap_data', 'fk_sitemap_data_builds_id', 'ALTER TABLE `sitemap_data` DROP FOREIGN KEY `fk_sitemap_data_builds_id`');

sql_column_exists('sitemap_data', 'builds_id'    ,  'ALTER TABLE `sitemap_data` CHANGE COLUMN `builds_id` `scans_id` INT(11) NOT NULL');

sql_index_exists ('sitemap_data', 'builds_id'    ,  'ALTER TABLE `sitemap_data` DROP INDEX `builds_id`');
sql_index_exists ('sitemap_data', 'scans_id'     , '!ALTER TABLE `sitemap_data` ADD  INDEX (`scans_id`)');

sql_index_exists ('sitemap_data', 'builds_id_2'  ,  'ALTER TABLE `sitemap_data` DROP INDEX `builds_id_2`');
sql_index_exists ('sitemap_data', 'scans_id_2'   , '!ALTER TABLE `sitemap_data` ADD  UNIQUE(`scans_id`, `url`(32))');

sql_foreignkey_exists('sitemap_data', 'fk_sitemap_data_scans_id', '!ALTER TABLE `sitemap_data` ADD CONSTRAINT `fk_sitemap_data_scans_id` FOREIGN KEY (`scans_id`) REFERENCES `sitemap_scans` (`id`) ON DELETE CASCADE;');



sql_column_exists('sitemap_data', 'disallow', '!ALTER TABLE `sitemap_data` ADD COLUMN `disallow` INT(11)');

sql_index_exists ('sitemap_data', 'disallow', '!ALTER TABLE `sitemap_data` ADD INDEX (`disallow`)');
?>
