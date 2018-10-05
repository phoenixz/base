<?php
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