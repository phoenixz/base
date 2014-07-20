<?php
    sql_column_exists('geo_states', 'country_code', '!ALTER TABLE `geo_states` ADD COLUMN `country_code` VARCHAR(2) NOT NULL AFTER `countries_id`;');
    sql_index_exists ('geo_states', 'country_code', '!ALTER TABLE `geo_states` ADD INDEX (`country_code`);');

    sql_foreignkey_exists('geo_states', 'fk_geo_states_country_code' , '!ALTER TABLE `geo_states` ADD CONSTRAINT `fk_geo_states_country_code` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE CASCADE;');
?>
