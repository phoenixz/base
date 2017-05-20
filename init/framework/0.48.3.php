<?php
/*
 * Update user verification
 */
sql_column_exists('users', 'validated'     , 'ALTER TABLE `users` CHANGE COLUMN `validated`      `verify_code` VARCHAR(128) NULL');
sql_column_exists('users', 'date_validated', 'ALTER TABLE `users` CHANGE COLUMN `date_validated` `verifiedon`  DATETIME     NULL');

sql_table_exists('registrations', 'DROP TABLE `registrations`');

sql_query('ALTER TABLE `users` MODIFY COLUMN `verify_code` VARCHAR(128) NULL');

sql_index_exists('users', 'verify_code', '!ALTER TABLE `users` ADD UNIQUE KEY `verify_code` (`verify_code`)');
?>
