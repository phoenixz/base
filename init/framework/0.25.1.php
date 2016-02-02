<?php
/*
 * Fix cdn_commands table
 */
sql_column_exists('cdn_commands', 'path', '!ALTER TABLE `cdn_commands` ADD COLUMN `path` VARCHAR(255)     NULL AFTER `command`');
sql_column_exists('cdn_commands', 'from', '!ALTER TABLE `cdn_commands` ADD COLUMN `from` VARCHAR(15)  NOT NULL AFTER `command`');
?>
