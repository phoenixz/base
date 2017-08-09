<?php
/*
 *
 */
sql_column_exists('crypto_transactions', 'data', '!ALTER TABLE `crypto_transactions` ADD COLUMN `data` VARCHAR(128) NULL AFTER `description`');
?>
