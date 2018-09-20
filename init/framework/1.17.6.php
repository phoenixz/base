<?php
/*
 * Fix projects table column name
 */
sql_column_exists('projects', 'fcm_apikey', 'ALTER TABLE `projects` CHANGE COLUMN `fcm_apikey` `fcm_api_key` VARCHAR(511) NULL DEFAULT NULL AFTER `api_key`');
?>