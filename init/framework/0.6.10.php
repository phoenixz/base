<?php
/*
 * Correct user tables
 */
sql_query('ALTER TABLE `users` CHANGE COLUMN `username` `username` VARCHAR(64) NULL');



 /*
  * Correct GEO library tables
  */
sql_column_exists('geo_states'   , 'alternate_names', '!ALTER TABLE `geo_states` ADD COLUMN `alternate_names`    TEXT        NULL AFTER `seoname`');
sql_column_exists('geo_states'   , 'latitude'       , '!ALTER TABLE `geo_states` ADD COLUMN `latitude`           FLOAT(10,6) NULL AFTER `alternate_names`');
sql_column_exists('geo_states'   , 'longitude'      , '!ALTER TABLE `geo_states` ADD COLUMN `longitude`          FLOAT(10,6) NULL AFTER `latitude`');

sql_column_exists('geo_provences', 'alternate_names', '!ALTER TABLE `geo_provences` ADD COLUMN `alternate_names` TEXT        NULL AFTER `seoname`');
sql_column_exists('geo_provences', 'latitude'       , '!ALTER TABLE `geo_provences` ADD COLUMN `latitude`        FLOAT(10,6) NULL AFTER `alternate_names`');
sql_column_exists('geo_provences', 'longitude'      , '!ALTER TABLE `geo_provences` ADD COLUMN `longitude`       FLOAT(10,6) NULL AFTER `latitude`');

sql_query('ALTER TABLE `geo_states`    CHANGE COLUMN `regions_id`    `regions_id`    INT(11) NULL');
sql_query('ALTER TABLE `geo_states`    CHANGE COLUMN `subregions_id` `subregions_id` INT(11) NULL');

sql_query('ALTER TABLE `geo_provences` CHANGE COLUMN `regions_id`    `regions_id`    INT(11) NULL');
sql_query('ALTER TABLE `geo_provences` CHANGE COLUMN `subregions_id` `subregions_id` INT(11) NULL');

sql_column_exists('geo_regions'   , 'geonames_id', '!ALTER TABLE `geo_regions`    ADD COLUMN `geonames_id` INT(11) NULL AFTER `id`');
sql_column_exists('geo_subregions', 'geonames_id', '!ALTER TABLE `geo_subregions` ADD COLUMN `geonames_id` INT(11) NULL AFTER `id`');
sql_column_exists('geo_countries' , 'geonames_id', '!ALTER TABLE `geo_countries`  ADD COLUMN `geonames_id` INT(11) NULL AFTER `id`');
sql_column_exists('geo_states'    , 'geonames_id', '!ALTER TABLE `geo_states`     ADD COLUMN `geonames_id` INT(11) NULL AFTER `id`');
sql_column_exists('geo_provences' , 'geonames_id', '!ALTER TABLE `geo_provences`  ADD COLUMN `geonames_id` INT(11) NULL AFTER `id`');
sql_column_exists('geo_cities'    , 'geonames_id', '!ALTER TABLE `geo_cities`     ADD COLUMN `geonames_id` INT(11) NULL AFTER `id`');
?>