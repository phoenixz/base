<?php
/*
 * Add unique user key
 */
sql_column_exists('users', 'key', '!ALTER TABLE `users` ADD COLUMN `key` VARCHAR(255) NULL AFTER `status`');
?>
