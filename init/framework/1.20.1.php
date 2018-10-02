<?php
/*
 * Fix email_servers table
 */
sql_column_exists('email_servers', 'hostname'   , '!ALTER TABLE `email_servers` CHANGE COLUMN `domain` `hostname` VARCHAR(64) NULL DEFAULT "" AFTER `domains_id`');
sql_column_exists('email_servers', 'seohostname', '!ALTER TABLE `email_servers` ADD COLUMN `seohostname` VARCHAR(64) NULL DEFAULT "" AFTER `hostname`');
?>