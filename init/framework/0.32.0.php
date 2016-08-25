<?php
/*
 * Add API key support
 */
sql_column_exists('users', 'api_key', '!ALTER TABLE `users` ADD COLUMN `api_key` VARCHAR(64) NULL AFTER `password`');
sql_column_exists('users', 'api_key', '!ALTER TABLE `users` ADD INDEX (`api_key`)');
?>
