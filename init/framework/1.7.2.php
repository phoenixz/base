<?php
/*
 * Update crypto_transactions change column type
 * Remove "payment"
 * Add "invoice"
 */
sql_query('ALTER TABLE `crypto_transactions` CHANGE COLUMN `type` `type` ENUM("simple", "button", "cart", "donation", "deposit", "api", "withdrawal", "payment", "invoice") NOT NULL');
sql_query('UPDATE `crypto_transactions` SET `type` = "invoice" WHERE `type` = "payment"');
sql_query('ALTER TABLE `crypto_transactions` CHANGE COLUMN `type` `type` ENUM("simple", "button", "cart", "donation", "deposit", "api", "withdrawal", "invoice") NOT NULL');
?>