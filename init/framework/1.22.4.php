<?php
/*
 * Fix twilio accounts table
 */
sql_column_exists('twilio_accounts', 'account_tokens', 'ALTER TABLE `twilio_accounts` CHANGE COLUMN `account_tokens` `account_token` VARCHAR(40) NULL');
?>