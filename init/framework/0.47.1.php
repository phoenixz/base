<?php
/*
 * Fixed users apikey uniqueness
 */
sql_column_exists('users', 'key'   , '!ALTER TABLE `users` ADD COLUMN `key`    VARCHAR(255) NULL AFTER `status`');
sql_column_exists('users', 'apikey', '!ALTER TABLE `users` ADD COLUMN `apikey` VARCHAR(64)  NULL AFTER `key`');

sql_index_exists('users', 'apikey', 'ALTER TABLE `users` DROP KEY `apikey`');
sql_query('ALTER TABLE `users` ADD UNIQUE KEY `apikey` (`apikey`)');
?>
