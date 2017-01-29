<?php
/*
 * Add offline_until support, to put site in maintenance mode
 * Add "nickname" support for users
 */
sql_column_exists('versions', 'offline_until', '!ALTER TABLE `versions` ADD COLUMN `offline_until` DATETIME NULL AFTER `project`');

sql_column_exists('users', 'nickname', '!ALTER TABLE `users` ADD COLUMN `nickname` VARCHAR(64) NULL AFTER `name`');
sql_index_exists ('users', 'nickname', '!ALTER TABLE `users` ADD INDEX  `nickname` (`nickname`)');
?>
