<?php
/*
 * Add GEO tables
 */
sql_column_exists('rights', 'tag', 'ALTER TABLE `extended_sessions` ADD COLUMN `tag` VARCHAR(32)  NOT NULL BEFORE `name`;');
sql_column_exists('rights', 'url', 'ALTER TABLE `extended_sessions` ADD COLUMN `url` VARCHAR(255) NOT NULL BEFORE `description`;');

sql_index_exists('rights', 'tag', 'ALTER TABLE `extended_sessions` ADD CONSTRAINT `` UNIQUE(`tag`) ;');
sql_index_exists('rights', 'url', 'ALTER TABLE `extended_sessions` ADD CONSTRAINT `` INDEX (`url`) ;');
?>