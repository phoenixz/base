<?php
sql_column_exists('inventories', 'serial', '!ALTER TABLE `inventories` ADD COLUMN     `serial` VARCHAR(32) NULL DEFAULT NULL AFTER `code`');
sql_index_exists ('inventories', 'serial', '!ALTER TABLE `inventories` ADD UNIQUE KEY `serial` (`serial`)');
?>
