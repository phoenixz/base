<?php
/*
 * geo states now contains filter type for cities
 */
sql_column_exists('geo_states' , 'filter', '!ALTER TABLE `geo_states` ADD COLUMN `filter` ENUM("default", "selective") NOT NULL DEFAULT "default"');
?>
