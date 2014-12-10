<?php
/*
 * Add remote IP to log table
 */
sql_index_exists ('log', 'added'    ,  'ALTER TABLE `log` DROP INDEX   `added`');
sql_index_exists ('log', 'users_id' ,  'ALTER TABLE `log` DROP INDEX   `users_id`');

sql_column_exists('log', 'added'    ,  'ALTER TABLE `log` CHANGE COLUMN `added`    `createdon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
sql_column_exists('log', 'users_id' ,  'ALTER TABLE `log` CHANGE COLUMN `users_id` `createdby` INT(11)');

sql_index_exists ('log', 'createdon', '!ALTER TABLE `log` ADD  INDEX  (`createdon`)');
sql_index_exists ('log', 'createdby', '!ALTER TABLE `log` ADD  INDEX  (`createdby`)');

sql_column_exists('log', 'ip', '!ALTER TABLE `log` ADD COLUMN  `ip` VARCHAR(15) NULL AFTER `createdby`');
sql_index_exists ('log', 'ip', '!ALTER TABLE `log` ADD INDEX  (`ip`)');
?>
