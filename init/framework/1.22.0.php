<?php
/*
 * Remove garbage from `domains` table
 * Change `hostname` to `domain` and `seohostname` to `seodomain` on `servers` and `email_servers` tables
 */
sql_index_exists('domains', 'web'          , 'ALTER TABLE `domains` DROP INDEX `web`');
sql_index_exists('domains', 'mail'         , 'ALTER TABLE `domains` DROP INDEX `mail`');

sql_column_exists('domains', 'web'         , 'ALTER TABLE `domains` DROP COLUMN `web`');
sql_column_exists('domains', 'mail'        , 'ALTER TABLE `domains` DROP COLUMN `mail`');
sql_column_exists('domains', 'ssh_hostname', 'ALTER TABLE `domains` DROP COLUMN `ssh_hostname`');
sql_column_exists('domains', 'ssh_port'    , 'ALTER TABLE `domains` DROP COLUMN `ssh_port`');
sql_column_exists('domains', 'ssh_user'    , 'ALTER TABLE `domains` DROP COLUMN `ssh_user`');
sql_column_exists('domains', 'ssh_path'    , 'ALTER TABLE `domains` DROP COLUMN `ssh_path`');

sql_query('ALTER TABLE `domains` MODIFY `createdby` INT(11) NULL DEFAULT NULL');

sql_foreignkey_exists('domains', 'fk_domains_mx_domains_id' , '!ALTER TABLE `domains` ADD CONSTRAINT `fk_domains_mx_domains_id` FOREIGN KEY (`mx_domains_id`) REFERENCES `email_servers` (`id`) ON DELETE RESTRICT;');

/*
 * Drop the servers_hostnames table, since this task will be now fulfilled by the servers_hostnames table
 */
sql_table_exists('servers_hostnames', 'DROP TABLE `servers_hostnames`');

/*
* `servers` table now uses domain and seodomain
  */
sql_column_exists('servers', 'hostname'   , 'ALTER TABLE `servers` CHANGE COLUMN    `hostname`    `domain` VARCHAR(64) NOT NULL');
sql_column_exists('servers', 'seohostname', 'ALTER TABLE `servers` CHANGE COLUMN `seohostname` `seodomain` VARCHAR(64) NOT NULL');

sql_index_exists('servers', 'hostname'   , 'ALTER TABLE `servers` DROP INDEX    `hostname`');
sql_index_exists('servers', 'seohostname', 'ALTER TABLE `servers` DROP INDEX `seohostname`');

sql_index_exists('servers', 'domain', '!ALTER TABLE `servers` ADD INDEX    `domain`    (`domain`)');
sql_index_exists('servers', 'domain', '!ALTER TABLE `servers` ADD INDEX `seodomain` (`seodomain`)');

/*
 * `email_servers` table now uses domain and seodomain
 */
sql_column_exists('email_servers', 'hostname'   , 'ALTER TABLE `email_servers` CHANGE COLUMN    `hostname`    `domain` VARCHAR(64) NOT NULL');
sql_column_exists('email_servers', 'seohostname', 'ALTER TABLE `email_servers` CHANGE COLUMN `seohostname` `seodomain` VARCHAR(64) NOT NULL');

sql_index_exists('email_servers', 'hostname'   , 'ALTER TABLE `email_servers` DROP INDEX    `hostname`');
sql_index_exists('email_servers', 'seohostname', 'ALTER TABLE `email_servers` DROP INDEX `seohostname`');

sql_index_exists('email_servers', 'domain', '!ALTER TABLE `email_servers` ADD INDEX    `domain`    (`domain`)');
sql_index_exists('email_servers', 'domain', '!ALTER TABLE `email_servers` ADD INDEX `seodomain` (`seodomain`)');

sql_query('ALTER TABLE `email_servers` MODIFY `description` VARCHAR(2040) NULL DEFAULT NULL');
sql_query('ALTER TABLE `email_servers` MODIFY `createdby` INT(11) NULL DEFAULT NULL');

/*
 * Add missing required columns to domains_servers
 */
sql_index_exists('domains_servers', 'PRIMARY', 'ALTER TABLE `domains_servers` DROP PRIMARY KEY');

sql_index_exists('domains_servers', 'domains_id_servers_id', '!ALTER TABLE `domains_servers` ADD UNIQUE KEY `domains_id_servers_id` (`domains_id`, `servers_id`)');

sql_column_exists('domains_servers', 'id'       , '!ALTER TABLE `domains_servers` ADD COLUMN `id`        INT(11)   NOT NULL PRIMARY KEY FIRST');
sql_column_exists('domains_servers', 'createdon', '!ALTER TABLE `domains_servers` ADD COLUMN `createdon` TIMESTAMP     NULL AFTER `id`');
sql_column_exists('domains_servers', 'createdby', '!ALTER TABLE `domains_servers` ADD COLUMN `createdby` INT(11)       NULL AFTER `createdon`');
sql_column_exists('domains_servers', 'meta_id'  , '!ALTER TABLE `domains_servers` ADD COLUMN `meta_id`   INT(11)   NOT NULL AFTER `createdby`');

sql_index_exists('domains_servers', 'createdby', '!ALTER TABLE `domains_servers` ADD INDEX `createdby` (`createdby`)');
sql_index_exists('domains_servers', 'createdon', '!ALTER TABLE `domains_servers` ADD INDEX `createdon` (`createdon`)');
sql_index_exists('domains_servers', 'meta_id'  , '!ALTER TABLE `domains_servers` ADD INDEX `meta_id`   (`meta_id`)');

sql_foreignkey_exists('domains_servers', 'fk_domains_servers_createdby' , '!ALTER TABLE `domains_servers` ADD CONSTRAINT `fk_domains_servers_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('domains_servers', 'fk_domains_servers_meta_id'   , '!ALTER TABLE `domains_servers` ADD CONSTRAINT `fk_domains_servers_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT;');

/*
 * Update domains table with domains from servers table
 */
$servers = sql_query('SELECT `id`, `domain`, `seodomain`, `ipv4` FROM `servers`');

load_libs('seo,servers,domains');
log_console(tr('Updating `domains` table with `domains` from `servers` table'));

while($server = sql_fetch($servers)){
    domains_ensure($server['domain']);
    servers_add_domain($server['id'], $server['domain']);
}

cli_dot(false);
?>
