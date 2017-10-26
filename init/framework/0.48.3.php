<?php
/*
 * Update user verification
 */
sql_column_exists('users', 'validated'     ,  'ALTER TABLE `users` CHANGE COLUMN `validated`      `verify_code` VARCHAR(128) NULL');
sql_column_exists('users', 'date_validated',  'ALTER TABLE `users` CHANGE COLUMN `date_validated` `verifiedon`  DATETIME     NULL');
sql_column_exists('users', 'credits'       , '!ALTER TABLE `users` ADD    COLUMN `credits`                      DOUBLE(7,2)  NULL');
sql_column_exists('users', 'verify_code'   , '!ALTER TABLE `users` ADD    COLUMN `verify_code`                  VARCHAR(128) NULL');

sql_table_exists('registrations', 'DROP TABLE `registrations`');

sql_query('UPDATE `users` SET `verify_code` = NULL WHERE `verify_code`');

sql_index_exists('users', 'verify_code', '!ALTER TABLE `users` ADD UNIQUE KEY `verify_code` (`verify_code`)');
?>
