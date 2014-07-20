<?php
sql_index_exists('geo_states', 'latitude' , '!ALTER TABLE `geo_states` ADD INDEX (`latitude`);');
sql_index_exists('geo_states', 'longitude', '!ALTER TABLE `geo_states` ADD INDEX (`longitude`);');

if(sql_table_exists('geo_provences')){
    sql_query('RENAME TABLE `geo_provences` TO `geo_counties`;');

    sql_foreignkey_exists('geo_cities', 'fk_geo_cities_provences_id', 'ALTER TABLE `geo_cities` DROP FOREIGN KEY `fk_geo_cities_provences_id`;');
    sql_index_exists('geo_cities', 'provences_id', 'ALTER TABLE `geo_cities` DROP INDEX `provences_id`;');

    sql_column_exists('geo_cities', 'provences_id', 'ALTER TABLE `geo_cities` CHANGE COLUMN `provences_id` `counties_id` INT(11) NULL;');

    sql_index_exists('geo_cities', 'counties_id' , '!ALTER TABLE `geo_cities` ADD INDEX (`counties_id`);');
    sql_foreignkey_exists('geo_cities', 'fk_geo_cities_counties_id' , '!ALTER TABLE `geo_cities` ADD CONSTRAINT `fk_geo_cities_counties_id` FOREIGN KEY (`counties_id`) REFERENCES `geo_counties` (`id`) ON DELETE CASCADE;');
}

sql_query('ALTER TABLE `geo_counties` CHANGE COLUMN `states_id` `states_id` INT(11) NULL;');
?>