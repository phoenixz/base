<?php
/*
 * Store original url and cluster name in forwarder clicks to be better able to debug cluster less clicks
 */
sql_column_exists('cdn_servers', 'seodomain', '!ALTER TABLE `cdn_servers` ADD COLUMN `seodomain` VARCHAR(64) NULL AFTER `domain`');
sql_column_exists('cdn_servers', 'api'      , '!ALTER TABLE `cdn_servers` ADD COLUMN `api`       VARCHAR(16) NULL AFTER `seodomain`');

sql_index_exists('cdn_servers' , 'domain'   ,  'ALTER TABLE `cdn_servers` DROP KEY `domain`');
sql_index_exists('cdn_servers' , 'seodomain', '!ALTER TABLE `cdn_servers` ADD UNIQUE KEY `seodomain` (`seodomain`)');
sql_index_exists('cdn_servers' , 'api'      , '!ALTER TABLE `cdn_servers` ADD UNIQUE KEY `api`       (`api`)');

sql_foreignkey_exists ('cdn_projects', 'fk_cdn_projects_customers_id', 'ALTER TABLE `cdn_projects` DROP FOREIGN KEY `fk_cdn_projects_customers_id`');
sql_index_exists ('cdn_projects', 'customers_id', 'ALTER TABLE `cdn_projects` DROP KEY    `customers_id`');
sql_column_exists('cdn_projects', 'customers_id', 'ALTER TABLE `cdn_projects` DROP COLUMN `customers_id`');

sql_foreignkey_exists ('cdn_projects', 'fk_cdn_projects_users_id', 'ALTER TABLE `cdn_projects` DROP FOREIGN KEY `fk_cdn_projects_users_id`');
sql_index_exists ('cdn_projects', 'users_id', 'ALTER TABLE `cdn_projects` DROP KEY    `users_id`');
sql_column_exists('cdn_projects', 'users_id', 'ALTER TABLE `cdn_projects` DROP COLUMN `users_id`');

sql_query('DELETE FROM `cdn_servers`');
sql_query('ALTER TABLE `cdn_servers` ADD UNIQUE KEY `domain` (`domain`)');
?>
