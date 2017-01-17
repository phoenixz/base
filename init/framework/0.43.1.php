<?php
/*
 * Add API call registry table
 */
sql_column_exists('email_accounts'       , 'forward_option', '!ALTER TABLE `email_accounts`        ADD COLUMN `forward_option` ENUM("source", "target")');
sql_column_exists('email_client_accounts', 'forward_option', '!ALTER TABLE `email_client_accounts` ADD COLUMN `forward_option` ENUM("source", "target")');
?>