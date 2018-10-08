<?php
/*
 * Fix missing `servers` and `databases` tables entries
 */
sql_column_exists('servers', 'replication_lock', '!ALTER TABLE `servers` ADD COLUMN `replication_lock` TINYINT DEFAULT 0');

sql_column_exists    ('servers', 'tasks_id'           , '!ALTER TABLE `servers` ADD COLUMN `tasks_id` INT(11) NULL DEFAULT NULL');
sql_foreignkey_exists('servers', 'fk_servers_tasks_id', '!ALTER TABLE `servers` ADD CONSTRAINT `fk_servers_tasks_id` FOREIGN KEY (`tasks_id`) REFERENCES `tasks` (`id`) ON DELETE RESTRICT');

sql_column_exists('databases', 'replication_status', 'ALTER TABLE `databases` CHANGE COLUMN `replication_status` `replication_status` ENUM("enabled", "enabling", "pausing", "resuming", "preparing", "paused", "disabled", "error") NULL DEFAULT "disabled"');

/*
 * Allow configuration for each server on if SSH server modifications are allowed or not
 */
sql_column_exists('servers', 'allow_sshd_modification', '!ALTER TABLE `servers` ADD COLUMN `allow_sshd_modification` TINYINT(11) NOT NULL DEFAULT 0 AFTER `tasks_id`');

/*
 * Make registered API accounts environment specific
 */
sql_column_exists('api_accounts', 'environment', '!ALTER TABLE `api_accounts` ADD COLUMN `environment` VARCHAR(32) NULL DEFAULT 0 AFTER `seoname`');

/*
 * Set all already registered servers to have SSHD modifications enabled
 */
sql_query('UPDATE `servers` SET `allow_sshd_modification` = 1');
?>