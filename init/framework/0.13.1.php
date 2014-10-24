<?php
/*
 * Add pages table
 */
sql_column_exists('users', 'type', '!ALTER TABLE `users` ADD COLUMN `type` VARCHAR(16) AFTER `admin`');
sql_index_exists ('users', 'type', '!ALTER TABLE `users` ADD INDEX(`type`)');
?>
