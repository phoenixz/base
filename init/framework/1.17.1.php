<?php
sql_index_exists('inventories_items', 'seobrand'         ,  'ALTER TABLE `inventories_items` DROP INDEX `seobrand`');
sql_index_exists('inventories_items', 'seobrand_seomodel', '!ALTER TABLE `inventories_items` ADD  UNIQUE KEY `seobrand_seomodel` (`seobrand`, `seomodel`)');
?>
