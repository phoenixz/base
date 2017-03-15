<?php
/*
 * Fix geo_location table name
 */
sql_table_exists('geo_location', 'RENAME TABLE `geo_location` TO `geo_locations`');
?>
