<?php
/*
 * Add user API key column, fix user key column
 */
sql_column_exists('users', 'apikey', '!ALTER TABLE `users` ADD COLUMN `apikey` VARCHAR(64) NULL AFTER `key`');

sql_query('ALTER TABLE `users` CHANGE COLUMN `key` `key` VARCHAR(64) NULL');
?>
