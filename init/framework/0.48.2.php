<?php
/*
 * api
 */
sql_column_exists('api_accounts', 'last_error', '!ALTER TABLE `api_accounts` ADD COLUMN `last_error` VARCHAR(4096) NULL AFTER `description`');
?>
