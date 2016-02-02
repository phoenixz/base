<?php
/*
 * Fix cdn_commands table
 */
sql_query('ALTER TABLE `cdn_commands` CHANGE COLUMN `command` `command` VARCHAR(24)  NOT NULL');

sql_column_exists('cdn_commands', 'cdn', '!ALTER TABLE `cdn_commands` ADD COLUMN `cdn` INT(11) NOT NULL');
sql_index_exists ('cdn_commands', 'cdn', '!ALTER TABLE `cdn_commands` ADD INDEX (`cdn`)');
?>
