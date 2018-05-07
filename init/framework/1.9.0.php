<?php
/*
 *
 */
sql_column_exists('users', 'webpush', '!ALTER TABLE `users` ADD COLUMN `webpush` VARCHAR(511) NULL AFTER `timezone`;');
?>