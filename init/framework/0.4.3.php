<?php
/*
 * Add "status" column
 */
sql_column_exists('users', 'status', '!ALTER TABLE `users` ADD COLUMN `status` VARCHAR(16) NULL AFTER `id`');
?>
