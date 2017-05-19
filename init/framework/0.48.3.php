<?php
/*
 * Update user verification
 */
sql_column_exists('users', 'validated'     , 'ALTER TABLE `users` CHANGE COLUMN `validated`      `verify_code` VARCHAR(64) NULL');
sql_column_exists('users', 'date_validated', 'ALTER TABLE `users` CHANGE COLUMN `date_validated` `verifiedon`  DATETIME    NULL');

sql_table_exists('registrations', 'DROP TABLE `registrations`');
?>
