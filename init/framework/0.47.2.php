<?php
/*
 * Add CDN group support
 */
sql_column_exists('cdn_files', 'group', '!ALTER TABLE `cdn_files` ADD COLUMN `group` VARCHAR(16) NULL AFTER `section`');
sql_index_exists ('cdn_files', 'group', '!ALTER TABLE `cdn_files` ADD KEY    `group` (`group`)');

sql_column_exists('cdn_storage', 'group', '!ALTER TABLE `cdn_storage` ADD COLUMN `group` VARCHAR(16) NULL AFTER `section`');
sql_column_exists('cdn_storage', 'group', '!ALTER TABLE `cdn_storage` ADD KEY    `group` (`group`)');
?>
