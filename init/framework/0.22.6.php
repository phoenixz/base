<?php
/*
 * geo tables need status
 */
sql_column_exists('geo_timezones' , 'status', '!ALTER TABLE `geo_timezones`  ADD COLUMN `status` VARCHAR(16) NULL DEFAULT NULL');
sql_column_exists('geo_continents', 'status', '!ALTER TABLE `geo_continents` ADD COLUMN `status` VARCHAR(16) NULL DEFAULT NULL');
sql_column_exists('geo_countries' , 'status', '!ALTER TABLE `geo_countries`  ADD COLUMN `status` VARCHAR(16) NULL DEFAULT NULL');
sql_column_exists('geo_states'    , 'status', '!ALTER TABLE `geo_states`     ADD COLUMN `status` VARCHAR(16) NULL DEFAULT NULL');
sql_column_exists('geo_counties'  , 'status', '!ALTER TABLE `geo_counties`   ADD COLUMN `status` VARCHAR(16) NULL DEFAULT NULL');
sql_column_exists('geo_cities'    , 'status', '!ALTER TABLE `geo_cities`     ADD COLUMN `status` VARCHAR(16) NULL DEFAULT NULL');
sql_column_exists('geo_features'  , 'status', '!ALTER TABLE `geo_features`   ADD COLUMN `status` VARCHAR(16) NULL DEFAULT NULL');

sql_index_exists ('geo_timezones' , 'status', '!ALTER TABLE `geo_timezones`  ADD INDEX (`status`)');
sql_index_exists ('geo_continents', 'status', '!ALTER TABLE `geo_continents` ADD INDEX (`status`)');
sql_index_exists ('geo_countries' , 'status', '!ALTER TABLE `geo_countries`  ADD INDEX (`status`)');
sql_index_exists ('geo_states'    , 'status', '!ALTER TABLE `geo_states`     ADD INDEX (`status`)');
sql_index_exists ('geo_counties'  , 'status', '!ALTER TABLE `geo_counties`   ADD INDEX (`status`)');
sql_index_exists ('geo_cities'    , 'status', '!ALTER TABLE `geo_cities`     ADD INDEX (`status`)');
sql_index_exists ('geo_features'  , 'status', '!ALTER TABLE `geo_features`   ADD INDEX (`status`)');
?>
