<?php
sql_column_exists('inventories', 'set_with', '!ALTER TABLE `inventories` ADD COLUMN     `set_with` VARCHAR(65) NULL DEFAULT NULL AFTER `code`');
sql_index_exists ('inventories', 'set_with', '!ALTER TABLE `inventories` ADD UNIQUE KEY `set_with` (`set_with`)');
?>
