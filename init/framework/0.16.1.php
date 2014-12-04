<?php
/*
 * Add extra standard columns to users table
 */
sql_column_exists('users', 'code'      , '!ALTER TABLE `users` ADD COLUMN `code`       VARCHAR(16)   NULL AFTER `email`');
sql_column_exists('users', 'commentary', '!ALTER TABLE `users` ADD COLUMN `commentary` VARCHAR(2047) NULL AFTER `country`');
?>
