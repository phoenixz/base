<?php
sql_column_exists('inventories', 'serial', '!ALTER TABLE `inventories_items` ADD COLUMN     `serial` VARCHAR(32) NULL DEFAULT NULL');
sql_index_exists ('inventories', 'serial', '!ALTER TABLE `inventories_items` ADD UNIQUE KEY `serial` (`serial`)');
?>
