<?php
/*
 * geo_cities from now on have to be unique within a state
 */
sql_index_exists('geo_cities', 'seoname', 'ALTER TABLE `geo_cities` DROP INDEX `seoname`');
sql_query('ALTER TABLE `geo_cities` ADD UNIQUE `seoname` (`states_id`, `seoname`(32))');
?>
