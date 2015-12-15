<?php
sql_foreignkey_exists('geo_countries', 'fk_geo_countries_continents_id'    , 'ALTER TABLE `geo_countries` DROP FOREIGN KEY `fk_geo_countries_continents_id`');

sql_query('DROP TABLE IF EXISTS `geo_continents`;');

sql_query('CREATE TABLE `geo_continents` (`id`             INT(11)        NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `updatedon`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `geonames_id`    INT(11)        NOT NULL,
                                          `code`           VARCHAR(2)     NOT NULL,
                                          `name`           VARCHAR(32)    NOT NULL,
                                          `seoname`        VARCHAR(32)    NOT NULL,
                                          `alternate_names` VARCHAR(4000) NOT NULL,
                                          `latitude`       DECIMAL(10,7)  NOT NULL,
                                          `longitude`      DECIMAL(10,7)  NOT NULL,
                                          `timezones_id`   INT(11)            NULL,
                                          `moddate`        DATETIME           NULL,

                                           INDEX (`geonames_id`),
                                           INDEX (`code`),
                                           UNIQUE(`name`),
                                           UNIQUE(`seoname`),
                                           INDEX (`latitude`),
                                           INDEX (`longitude`),
                                           INDEX (`timezones_id`),
                                           INDEX (`moddate`),

                                           CONSTRAINT `fk_geo_continents_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE CASCADE

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Fix timezones table
 */
sql_index_exists ('geo_timezones', 'cc'  ,  'ALTER TABLE `geo_timezones` DROP INDEX `cc`');
sql_column_exists('geo_timezones', 'cc'  ,  'ALTER TABLE `geo_timezones` CHANGE COLUMN `cc` `code` VARCHAR(2) NULL');
sql_index_exists ('geo_timezones', 'code', '!ALTER TABLE `geo_timezones` ADD INDEX (`code`)');



/*
 * Add all the extra data to countries
 */
sql_foreignkey_exists('geo_countries', 'fk_geo_countries_regions_id'    , 'ALTER TABLE geo_countries DROP FOREIGN KEY `fk_geo_countries_regions_id`');
sql_foreignkey_exists('geo_countries', 'fk_geo_countries_subregions_id' , 'ALTER TABLE geo_countries DROP FOREIGN KEY `fk_geo_countries_subregions_id`');

sql_index_exists ('geo_countries', 'regions_id'          , 'ALTER TABLE geo_countries DROP   INDEX `regions_id`');
sql_index_exists ('geo_countries', 'subregions_id'       , 'ALTER TABLE geo_countries DROP   INDEX `subregions_id`');

sql_column_exists('geo_countries', 'regions_id'          , 'ALTER TABLE geo_countries DROP   COLUMN `regions_id`');
sql_column_exists('geo_countries', 'subregions_id'       , 'ALTER TABLE geo_countries DROP   COLUMN `subregions_id`');

sql_column_exists('geo_countries', 'code_iso'            , 'ALTER TABLE geo_countries CHANGE COLUMN `code_iso` `iso_alpha2` CHAR(2) AFTER `code`');

sql_column_exists('geo_countries', 'updatedon'           , '!ALTER TABLE `geo_countries` ADD COLUMN `updatedon`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`');
sql_column_exists('geo_countries', 'continents_id'       , '!ALTER TABLE `geo_countries` ADD COLUMN `continents_id`        INT(11)       AFTER `geonames_id`');
sql_column_exists('geo_countries', 'timezones_id'        , '!ALTER TABLE `geo_countries` ADD COLUMN `timezones_id`         INT(11)       AFTER `continents_id`');
sql_column_exists('geo_countries', 'iso_alpha3'          , '!ALTER TABLE `geo_countries` ADD COLUMN `iso_alpha3`           CHAR(3)       AFTER `iso_alpha2`');
sql_column_exists('geo_countries', 'iso_numeric'         , '!ALTER TABLE `geo_countries` ADD COLUMN `iso_numeric`          CHAR(3)       AFTER `iso_alpha3`');
sql_column_exists('geo_countries', 'iso_numeric'         , '!ALTER TABLE `geo_countries` ADD COLUMN `iso_numeric`          CHAR(3)       AFTER `iso_alpha3`');
sql_column_exists('geo_countries', 'fips_code'           , '!ALTER TABLE `geo_countries` ADD COLUMN `fips_code`            VARCHAR(3)    AFTER `iso_numeric`');
sql_column_exists('geo_countries', 'capital'             , '!ALTER TABLE `geo_countries` ADD COLUMN `capital`              VARCHAR(200)  AFTER `seoname`');
sql_column_exists('geo_countries', 'areainsqkm'          , '!ALTER TABLE `geo_countries` ADD COLUMN `areainsqkm`           DOUBLE        AFTER `capital`');
sql_column_exists('geo_countries', 'population'          , '!ALTER TABLE `geo_countries` ADD COLUMN `population`           INT(11)       AFTER `areainsqkm`');
sql_column_exists('geo_countries', 'tld'                 , '!ALTER TABLE `geo_countries` ADD COLUMN `tld`                  VARCHAR(3)    AFTER `population`');
sql_column_exists('geo_countries', 'currency'            , '!ALTER TABLE `geo_countries` ADD COLUMN `currency`             VARCHAR(3)    AFTER `tld`');
sql_column_exists('geo_countries', 'currency_name'       , '!ALTER TABLE `geo_countries` ADD COLUMN `currency_name`        VARCHAR(20)   AFTER `currency`');
sql_column_exists('geo_countries', 'phone'               , '!ALTER TABLE `geo_countries` ADD COLUMN `phone`                VARCHAR(10)   AFTER `currency_name`');
sql_column_exists('geo_countries', 'postal_code_format'  , '!ALTER TABLE `geo_countries` ADD COLUMN `postal_code_format`   VARCHAR(100)  AFTER `phone`');
sql_column_exists('geo_countries', 'postal_code_regex'   , '!ALTER TABLE `geo_countries` ADD COLUMN `postal_code_regex`    VARCHAR(255)  AFTER `postal_code_format`');
sql_column_exists('geo_countries', 'languages'           , '!ALTER TABLE `geo_countries` ADD COLUMN `languages`            VARCHAR(200)  AFTER `postal_code_regex`');
sql_column_exists('geo_countries', 'neighbours'          , '!ALTER TABLE `geo_countries` ADD COLUMN `neighbours`           VARCHAR(100)  AFTER `languages`');
sql_column_exists('geo_countries', 'equivalent_fips_code', '!ALTER TABLE `geo_countries` ADD COLUMN `equivalent_fips_code` VARCHAR(10)   AFTER `neighbours`');
sql_column_exists('geo_countries', 'latitude'            , '!ALTER TABLE `geo_countries` ADD COLUMN `latitude`             FLOAT(10,6)   AFTER `equivalent_fips_code`');
sql_column_exists('geo_countries', 'longitude'           , '!ALTER TABLE `geo_countries` ADD COLUMN `longitude`            FLOAT(10,6)   AFTER `latitude`');
sql_column_exists('geo_countries', 'alternate_names'     , '!ALTER TABLE `geo_countries` ADD COLUMN `alternate_names`      VARCHAR(4000) AFTER `longitude`');
sql_column_exists('geo_countries', 'moddate'             , '!ALTER TABLE `geo_countries` ADD COLUMN `moddate`              DATETIME      AFTER `population`');

sql_index_exists ('geo_countries', 'continents_id'       , '!ALTER TABLE `geo_countries` ADD INDEX (`continents_id`)');
sql_index_exists ('geo_countries', 'timezones_id'        , '!ALTER TABLE `geo_countries` ADD INDEX (`timezones_id`)');
sql_index_exists ('geo_countries', 'iso_alpha2'          , '!ALTER TABLE `geo_countries` ADD INDEX (`iso_alpha2`)');
sql_index_exists ('geo_countries', 'iso_alpha3'          , '!ALTER TABLE `geo_countries` ADD INDEX (`iso_alpha3`)');
sql_index_exists ('geo_countries', 'iso_numeric'         , '!ALTER TABLE `geo_countries` ADD INDEX (`iso_numeric`)');
sql_index_exists ('geo_countries', 'iso_numeric'         , '!ALTER TABLE `geo_countries` ADD INDEX (`iso_numeric`)');
sql_index_exists ('geo_countries', 'fips_code'           , '!ALTER TABLE `geo_countries` ADD INDEX (`fips_code`)');
sql_index_exists ('geo_countries', 'capital'             , '!ALTER TABLE `geo_countries` ADD INDEX (`capital`)');
sql_index_exists ('geo_countries', 'areainsqkm'          , '!ALTER TABLE `geo_countries` ADD INDEX (`areainsqkm`)');
sql_index_exists ('geo_countries', 'population'          , '!ALTER TABLE `geo_countries` ADD INDEX (`population`)');
sql_index_exists ('geo_countries', 'tld'                 , '!ALTER TABLE `geo_countries` ADD INDEX (`tld`)');
sql_index_exists ('geo_countries', 'currency'            , '!ALTER TABLE `geo_countries` ADD INDEX (`currency`)');
sql_index_exists ('geo_countries', 'currency_name'       , '!ALTER TABLE `geo_countries` ADD INDEX (`currency_name`)');
sql_index_exists ('geo_countries', 'phone'               , '!ALTER TABLE `geo_countries` ADD INDEX (`phone`)');
sql_index_exists ('geo_countries', 'postal_code_format'  , '!ALTER TABLE `geo_countries` ADD INDEX (`postal_code_format`)');
sql_index_exists ('geo_countries', 'postal_code_regex'   , '!ALTER TABLE `geo_countries` ADD INDEX (`postal_code_regex`)');
sql_index_exists ('geo_countries', 'languages'           , '!ALTER TABLE `geo_countries` ADD INDEX (`languages`)');
sql_index_exists ('geo_countries', 'neighbours'          , '!ALTER TABLE `geo_countries` ADD INDEX (`neighbours`)');
sql_index_exists ('geo_countries', 'equivalent_fips_code', '!ALTER TABLE `geo_countries` ADD INDEX (`equivalent_fips_code`)');
sql_index_exists ('geo_countries', 'latitude'            , '!ALTER TABLE `geo_countries` ADD INDEX (`latitude`)');
sql_index_exists ('geo_countries', 'longitude'           , '!ALTER TABLE `geo_countries` ADD INDEX (`longitude`)');
sql_index_exists ('geo_countries', 'moddate'             , '!ALTER TABLE `geo_countries` ADD INDEX (`moddate`)');

sql_foreignkey_exists('geo_countries', 'fk_geo_countries_continents_id' , '!ALTER TABLE `geo_countries` ADD CONSTRAINT `fk_geo_countries_continents_id` FOREIGN KEY (`continents_id`) REFERENCES `geo_continents` (`id`) ON DELETE CASCADE;');
sql_foreignkey_exists('geo_countries', 'fk_geo_countries_timezones_id'  , '!ALTER TABLE `geo_countries` ADD CONSTRAINT `fk_geo_countries_timezones_id`  FOREIGN KEY (`timezones_id`)  REFERENCES `geo_timezones`  (`id`) ON DELETE CASCADE;');

sql_query('ALTER TABLE `geo_countries` CHANGE COLUMN `latitude`  `latitude`  DECIMAL(10,7);');
sql_query('ALTER TABLE `geo_countries` CHANGE COLUMN `longitude` `longitude` DECIMAL(10,7);');



/*
 * Add all the extra data to states
 */
sql_foreignkey_exists('geo_states', 'fk_geo_states_regions_id'    , 'ALTER TABLE geo_states DROP FOREIGN KEY `fk_geo_states_regions_id`');
sql_foreignkey_exists('geo_states', 'fk_geo_states_subregions_id' , 'ALTER TABLE geo_states DROP FOREIGN KEY `fk_geo_states_subregions_id`');

sql_index_exists ('geo_states', 'regions_id'   ,  'ALTER TABLE `geo_states` DROP INDEX `regions_id`');
sql_index_exists ('geo_states', 'subregions_id',  'ALTER TABLE `geo_states` DROP INDEX `subregions_id`');

sql_column_exists('geo_states', 'regions_id'   ,  'ALTER TABLE `geo_states` DROP COLUMN `regions_id`');
sql_column_exists('geo_states', 'subregions_id',  'ALTER TABLE `geo_states` DROP COLUMN `subregions_id`');

sql_column_exists('geo_states', 'population'   , '!ALTER TABLE `geo_states` ADD COLUMN `population`   INT(11)     AFTER `longitude`');
sql_column_exists('geo_states', 'elevation'    , '!ALTER TABLE `geo_states` ADD COLUMN `elevation`    INT(11)     AFTER `population`');
sql_column_exists('geo_states', 'timezones_id' , '!ALTER TABLE `geo_states` ADD COLUMN `timezones_id` INT(11)     AFTER `countries_id`');
sql_column_exists('geo_states', 'admin1'       , '!ALTER TABLE `geo_states` ADD COLUMN `admin1`       VARCHAR(20) AFTER `elevation`');
sql_column_exists('geo_states', 'admin2'       , '!ALTER TABLE `geo_states` ADD COLUMN `admin2`       VARCHAR(20) AFTER `admin1`');
sql_column_exists('geo_states', 'moddate'      , '!ALTER TABLE `geo_states` ADD COLUMN `moddate`      DATETIME    AFTER `admin2`');

sql_index_exists ('geo_states', 'population'   , '!ALTER TABLE `geo_states` ADD INDEX (`population`)');
sql_index_exists ('geo_states', 'elevation'    , '!ALTER TABLE `geo_states` ADD INDEX (`elevation`)');
sql_index_exists ('geo_states', 'timezones_id' , '!ALTER TABLE `geo_states` ADD INDEX (`timezones_id`)');
sql_index_exists ('geo_states', 'admin1'       , '!ALTER TABLE `geo_states` ADD INDEX (`admin1`)');
sql_index_exists ('geo_states', 'admin2'       , '!ALTER TABLE `geo_states` ADD INDEX (`admin2`)');
sql_index_exists ('geo_states', 'moddate'      , '!ALTER TABLE `geo_states` ADD INDEX (`moddate`)');

sql_foreignkey_exists('geo_states', 'fk_geo_states_timezones_id'  , '!ALTER TABLE `geo_states` ADD CONSTRAINT `fk_geo_states_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE CASCADE;');

sql_query('ALTER TABLE `geo_states` CHANGE COLUMN `countries_id` `countries_id` INT(11) NOT NULL');

sql_query('ALTER TABLE `geo_states` CHANGE COLUMN `latitude`  `latitude`  DECIMAL(10,7);');
sql_query('ALTER TABLE `geo_states` CHANGE COLUMN `longitude` `longitude` DECIMAL(10,7);');



/*
 * States are unique by name, latitude and longitude
 */
sql_query('ALTER TABLE `geo_states` DROP INDEX `name`;');
sql_query('ALTER TABLE `geo_states` ADD INDEX (`name`);');
sql_query('ALTER TABLE `geo_states` ADD UNIQUE (`name`, `latitude`, `longitude`);');



/*
 * Now work the counties table. Drop all "provences" referernces since this table no longer exists
 */
sql_foreignkey_exists('geo_counties', 'fk_geo_counties_states_id'     , 'ALTER TABLE geo_counties DROP FOREIGN KEY `fk_geo_counties_states_id`');
sql_foreignkey_exists('geo_counties', 'fk_geo_counties_regions_id'    , 'ALTER TABLE geo_counties DROP FOREIGN KEY `fk_geo_counties_regions_id`');
sql_foreignkey_exists('geo_counties', 'fk_geo_counties_subregions_id' , 'ALTER TABLE geo_counties DROP FOREIGN KEY `fk_geo_counties_subregions_id`');

sql_foreignkey_exists('geo_counties', 'fk_geo_provences_states_id'    , 'ALTER TABLE geo_counties DROP FOREIGN KEY `fk_geo_provences_states_id`');
sql_foreignkey_exists('geo_counties', 'fk_geo_provences_regions_id'   , 'ALTER TABLE geo_counties DROP FOREIGN KEY `fk_geo_provences_regions_id`');
sql_foreignkey_exists('geo_counties', 'fk_geo_provences_subregions_id', 'ALTER TABLE geo_counties DROP FOREIGN KEY `fk_geo_provences_subregions_id`');

sql_index_exists ('geo_counties', 'regions_id'   ,  'ALTER TABLE `geo_counties` DROP INDEX `regions_id`');
sql_index_exists ('geo_counties', 'subregions_id',  'ALTER TABLE `geo_counties` DROP INDEX `subregions_id`');

sql_column_exists('geo_counties', 'regions_id'   ,  'ALTER TABLE `geo_counties` DROP COLUMN `regions_id`');
sql_column_exists('geo_counties', 'subregions_id',  'ALTER TABLE `geo_counties` DROP COLUMN `subregions_id`');

sql_column_exists('geo_counties', 'population'   , '!ALTER TABLE `geo_counties` ADD COLUMN `population`   INT(11)     AFTER `longitude`');
sql_column_exists('geo_counties', 'elevation'    , '!ALTER TABLE `geo_counties` ADD COLUMN `elevation`    INT(11)     AFTER `population`');
sql_column_exists('geo_counties', 'timezones_id' , '!ALTER TABLE `geo_counties` ADD COLUMN `timezones_id` INT(11)     AFTER `countries_id`');
sql_column_exists('geo_counties', 'admin1'       , '!ALTER TABLE `geo_counties` ADD COLUMN `admin1`       VARCHAR(20) AFTER `elevation`');
sql_column_exists('geo_counties', 'admin2'       , '!ALTER TABLE `geo_counties` ADD COLUMN `admin2`       VARCHAR(20) AFTER `admin1`');
sql_column_exists('geo_counties', 'moddate'      , '!ALTER TABLE `geo_counties` ADD COLUMN `moddate`      DATETIME    AFTER `admin2`');

sql_index_exists ('geo_counties', 'population'   , '!ALTER TABLE `geo_counties` ADD INDEX (`population`)');
sql_index_exists ('geo_counties', 'elevation'    , '!ALTER TABLE `geo_counties` ADD INDEX (`elevation`)');
sql_index_exists ('geo_counties', 'timezones_id' , '!ALTER TABLE `geo_counties` ADD INDEX (`timezones_id`)');
sql_index_exists ('geo_counties', 'admin1'       , '!ALTER TABLE `geo_counties` ADD INDEX (`admin1`)');
sql_index_exists ('geo_counties', 'admin2'       , '!ALTER TABLE `geo_counties` ADD INDEX (`admin2`)');
sql_index_exists ('geo_counties', 'moddate'      , '!ALTER TABLE `geo_counties` ADD INDEX (`moddate`)');

sql_query('ALTER TABLE `geo_counties` CHANGE COLUMN `countries_id` `countries_id` INT(11) NOT NULL');
sql_query('ALTER TABLE `geo_counties` CHANGE COLUMN `states_id`    `states_id`    INT(11) NOT NULL');

sql_query('ALTER TABLE `geo_counties` CHANGE COLUMN `latitude`  `latitude`  DECIMAL(10,7);');
sql_query('ALTER TABLE `geo_counties` CHANGE COLUMN `longitude` `longitude` DECIMAL(10,7);');

sql_foreignkey_exists('geo_counties', 'fk_geo_counties_timezones_id', '!ALTER TABLE `geo_counties` ADD CONSTRAINT `fk_geo_counties_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE CASCADE;');
sql_foreignkey_exists('geo_counties', 'fk_geo_counties_states_id'   , '!ALTER TABLE `geo_counties` ADD CONSTRAINT `fk_geo_counties_states_id`    FOREIGN KEY (`states_id`)    REFERENCES `geo_states`    (`id`) ON DELETE CASCADE;');



/*
 * States are unique by name, latitude and longitude
 */
sql_query('ALTER TABLE `geo_counties` DROP INDEX `name`;');
sql_query('ALTER TABLE `geo_counties` ADD INDEX (`name`);');
sql_query('ALTER TABLE `geo_counties` ADD UNIQUE (`name`, `latitude`, `longitude`, `countries_id`); ');



/*
 * Now work the cities table
 */
sql_foreignkey_exists('geo_cities', 'fk_geo_cities_regions_id'    , 'ALTER TABLE `geo_cities` DROP FOREIGN KEY `fk_geo_cities_regions_id`');
sql_foreignkey_exists('geo_cities', 'fk_geo_cities_subregions_id' , 'ALTER TABLE `geo_cities` DROP FOREIGN KEY `fk_geo_cities_subregions_id`');
sql_foreignkey_exists('geo_cities', 'fk_geo_cities_countries_id'  , 'ALTER TABLE `geo_cities` DROP FOREIGN KEY `fk_geo_cities_countries_id`');
sql_foreignkey_exists('geo_cities', 'fk_geo_cities_states_id'     , 'ALTER TABLE `geo_cities` DROP FOREIGN KEY `fk_geo_cities_states_id`');

sql_index_exists ('geo_cities', 'regions_id'       ,  'ALTER TABLE `geo_cities` DROP INDEX `regions_id`');
sql_index_exists ('geo_cities', 'subregions_id'    ,  'ALTER TABLE `geo_cities` DROP INDEX `subregions_id`');

sql_column_exists('geo_cities', 'regions_id'       ,  'ALTER TABLE `geo_cities` DROP COLUMN `regions_id`');
sql_column_exists('geo_cities', 'subregions_id'    ,  'ALTER TABLE `geo_cities` DROP COLUMN `subregions_id`');

sql_column_exists('geo_cities', 'admin1'           , '!ALTER TABLE `geo_cities` ADD COLUMN `admin1` VARCHAR(20) AFTER `elevation`');
sql_column_exists('geo_cities', 'admin2'           , '!ALTER TABLE `geo_cities` ADD COLUMN `admin2` VARCHAR(20) AFTER `admin1`');

sql_index_exists ('geo_cities', 'admin1'           , '!ALTER TABLE `geo_cities` ADD INDEX (`admin1`)');
sql_index_exists ('geo_cities', 'admin2'           , '!ALTER TABLE `geo_cities` ADD INDEX (`admin2`)');

sql_index_exists ('geo_cities', 'realname'         ,  'ALTER TABLE `geo_cities` DROP INDEX  `realname`');
sql_column_exists('geo_cities', 'realname'         ,  'ALTER TABLE `geo_cities` DROP COLUMN `realname`');

sql_index_exists ('geo_cities', 'modification_date',  'ALTER TABLE `geo_cities` DROP INDEX  `modification_date`');
sql_column_exists('geo_cities', 'modification_date',  'ALTER TABLE `geo_cities` DROP COLUMN `modification_date`');

sql_index_exists ('geo_cities', 'dem'              ,  'ALTER TABLE `geo_cities` DROP INDEX  `dem`');
sql_column_exists('geo_cities', 'dem'              ,  'ALTER TABLE `geo_cities` DROP COLUMN `dem`');

sql_column_exists('geo_cities', 'updatedon'        , '!ALTER TABLE `geo_cities` ADD COLUMN `updatedon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`');
sql_index_exists ('geo_cities', 'updatedon'        , '!ALTER TABLE `geo_cities` ADD INDEX (`updatedon`)');

sql_column_exists('geo_cities', 'moddate'          , '!ALTER TABLE `geo_cities` ADD COLUMN `moddate` DATETIME AFTER `feature_code`');
sql_index_exists ('geo_cities', 'moddate'          , '!ALTER TABLE `geo_cities` ADD INDEX (`moddate`)');

sql_query('ALTER TABLE `geo_cities` CHANGE COLUMN `countries_id` `countries_id` INT(11) NOT NULL');
sql_query('ALTER TABLE `geo_cities` CHANGE COLUMN `states_id`    `states_id`    INT(11) NOT NULL');

sql_query('ALTER TABLE `geo_cities` CHANGE COLUMN `latitude`  `latitude`  DECIMAL(10,7);');
sql_query('ALTER TABLE `geo_cities` CHANGE COLUMN `longitude` `longitude` DECIMAL(10,7);');

sql_foreignkey_exists('geo_cities', 'fk_geo_cities_countries_id', '!ALTER TABLE `geo_cities` ADD CONSTRAINT `fk_geo_cities_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE CASCADE;');
sql_foreignkey_exists('geo_cities', 'fk_geo_cities_states_id'   , '!ALTER TABLE `geo_cities` ADD CONSTRAINT `fk_geo_cities_states_id`    FOREIGN KEY (`states_id`)    REFERENCES `geo_states`    (`id`) ON DELETE CASCADE;');



/*
 * States are unique by name, latitude and longitude
 */
sql_query('ALTER TABLE `geo_cities` DROP INDEX `name`');
sql_query('ALTER TABLE `geo_cities` ADD INDEX (`name`)');
sql_query('ALTER TABLE `geo_cities` ADD UNIQUE (`name`, `latitude`, `longitude`, `countries_id`);');



/*
 * Dump the no longer used subregions and regions tables
 */
sql_query('DROP TABLE IF EXISTS `geo_subregions`;');
sql_query('DROP TABLE IF EXISTS `geo_regions`;');
?>
