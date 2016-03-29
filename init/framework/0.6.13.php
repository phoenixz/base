<?php
sql_index_exists('geo_countries', 'seoname', 'ALTER TABLE `geo_countries` DROP INDEX `seoname`');
sql_index_exists('geo_countries', 'name',    'ALTER TABLE `geo_countries` DROP INDEX `name`');

sql_index_exists('geo_cities'   , 'seoname', 'ALTER TABLE `geo_cities`    DROP INDEX `seoname`');
sql_index_exists('geo_cities'   , 'name',    'ALTER TABLE `geo_cities`    DROP INDEX `name`');
sql_index_exists('geo_cities'   , 'name_2',  'ALTER TABLE `geo_cities`    DROP INDEX `name_2`');

sql_index_exists('geo_states'   , 'seoname', 'ALTER TABLE `geo_states`    DROP INDEX `seoname`');
sql_index_exists('geo_states'   , 'name',    'ALTER TABLE `geo_states`    DROP INDEX `name`');
sql_index_exists('geo_states'   , 'name_2',  'ALTER TABLE `geo_states`    DROP INDEX `name_2`');



sql_query('ALTER TABLE `geo_countries` CHANGE COLUMN `name`    `name`    VARCHAR(200) NOT NULL;');
sql_query('ALTER TABLE `geo_countries` CHANGE COLUMN `seoname` `seoname` VARCHAR(200) NOT NULL;');

sql_query('ALTER TABLE `geo_states`    CHANGE COLUMN `name`    `name`    VARCHAR(200) NOT NULL;');
sql_query('ALTER TABLE `geo_states`    CHANGE COLUMN `seoname` `seoname` VARCHAR(200) NOT NULL;');

sql_query('ALTER TABLE `geo_cities`    CHANGE COLUMN `name`    `name`    VARCHAR(200) NOT NULL;');
sql_query('ALTER TABLE `geo_cities`    CHANGE COLUMN `seoname` `seoname` VARCHAR(200) NOT NULL;');



sql_query('ALTER TABLE `geo_countries` ADD INDEX (`seoname`(32))');
sql_query('ALTER TABLE `geo_states`    ADD INDEX (`seoname`(32))');
sql_query('ALTER TABLE `geo_cities`    ADD INDEX (`seoname`(32))');

sql_query('ALTER TABLE `geo_countries` ADD INDEX (`name`(32))');
sql_query('ALTER TABLE `geo_states`    ADD INDEX (`name`(32))');
sql_query('ALTER TABLE `geo_cities`    ADD INDEX (`name`(32))');

sql_query('ALTER TABLE `geo_states`    ADD UNIQUE(`name`(32),`latitude`,`longitude`,`countries_id`)');
sql_query('ALTER TABLE `geo_cities`    ADD UNIQUE(`name`(32),`latitude`,`longitude`)');
sql_query('ALTER TABLE `geo_cities`    ADD UNIQUE(`name`(32),`states_id`)');
?>
