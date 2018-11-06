<?php
/*
 * Add services table for new services library
 */
sql_query('DROP TABLE IF EXISTS `services_servers`');
sql_query('DROP TABLE IF EXISTS `services`');



sql_query('CREATE TABLE `services` (`id`          INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                    `createdon`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `createdby`   INT(11)           NULL DEFAULT NULL,
                                    `meta_id`     INT(11)       NOT NULL,
                                    `status`      VARCHAR(16)       NULL DEFAULT NULL,
                                    `name`        VARCHAR(32)   NOT NULL,
                                    `seoname`     VARCHAR(32)   NOT NULL,
                                    `description` VARCHAR(2040) NOT NULL,

                                           KEY `createdon` (`createdon`),
                                           KEY `createdby` (`createdby`),
                                           KEY `meta_id`   (`meta_id`),
                                           KEY `status`    (`status`),
                                           KEY `name`      (`name`),
                                    UNIQUE KEY `seoname`   (`seoname`),

                                    CONSTRAINT `fk_services_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_services_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `services_servers` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                            `createdon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby`   INT(11)         NULL DEFAULT NULL,
                                            `meta_id`     INT(11)     NOT NULL,
                                            `status`      VARCHAR(16)     NULL DEFAULT NULL,
                                            `services_id` INT(11)     NOT NULL,
                                            `servers_id`  INT(11)     NOT NULL,
                                            `name`        VARCHAR(32) NOT NULL,
                                            `seoname`     VARCHAR(32) NOT NULL,
                                            `public`      TINYINT(1)  NOT NULL,

                                                   KEY `createdon` (`createdon`),
                                                   KEY `createdby` (`createdby`),
                                                   KEY `meta_id`   (`meta_id`),
                                                   KEY `status`    (`status`),
                                                   KEY `name`      (`name`),
                                            UNIQUE KEY `seoname`   (`seoname`),

                                            CONSTRAINT `services_servers_createdby`   FOREIGN KEY (`createdby`)   REFERENCES `users`    (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `services_servers_meta_id`     FOREIGN KEY (`meta_id`)     REFERENCES `meta`     (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `services_servers_services_id` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `services_servers_servers_id`  FOREIGN KEY (`servers_id`)  REFERENCES `servers`  (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



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
 * Update ssh_fingerprints table to also use domain
 */
sql_index_exists('ssh_fingerprints', 'hostname'     , 'ALTER TABLE `ssh_fingerprints` DROP INDEX `hostname`');
sql_index_exists('ssh_fingerprints', 'seohostname'  , 'ALTER TABLE `ssh_fingerprints` DROP INDEX `seohostname`');
sql_index_exists('ssh_fingerprints', 'hostname_port', 'ALTER TABLE `ssh_fingerprints` DROP INDEX `hostname_port`');
sql_index_exists('ssh_fingerprints', 'domain'       , 'ALTER TABLE `ssh_fingerprints` DROP INDEX `domain`');
sql_index_exists('ssh_fingerprints', 'seodomain'    , 'ALTER TABLE `ssh_fingerprints` DROP INDEX `seodomain`');

sql_column_exists('ssh_fingerprints', 'hostname'   ,  'ALTER TABLE `ssh_fingerprints` CHANGE COLUMN    `hostname`    `domain` VARCHAR(64) NOT NULL');
sql_column_exists('ssh_fingerprints', 'seohostname',  'ALTER TABLE `ssh_fingerprints` CHANGE COLUMN `seohostname` `seodomain` VARCHAR(64) NOT NULL');

sql_query('ALTER TABLE `ssh_fingerprints` ADD KEY `domain`    (`domain`)');
sql_query('ALTER TABLE `ssh_fingerprints` ADD KEY `seodomain` (`seodomain`)');

/*
 * `email_servers` table now uses domain and seodomain
 */
sql_column_exists('email_servers', 'hostname'   ,  'ALTER TABLE `email_servers` CHANGE COLUMN    `hostname`    `domain` VARCHAR(64) NOT NULL');
sql_column_exists('email_servers', 'seohostname',  'ALTER TABLE `email_servers` CHANGE COLUMN `seohostname` `seodomain` VARCHAR(64) NOT NULL');

sql_index_exists('email_servers', 'hostname'   , 'ALTER TABLE `email_servers` DROP INDEX    `hostname`');
sql_index_exists('email_servers', 'seohostname', 'ALTER TABLE `email_servers` DROP INDEX `seohostname`');

sql_index_exists('email_servers', 'domain', '!ALTER TABLE `email_servers` ADD INDEX    `domain`    (`domain`)');
sql_index_exists('email_servers', 'domain', '!ALTER TABLE `email_servers` ADD INDEX `seodomain` (`seodomain`)');

sql_query('ALTER TABLE `email_servers` MODIFY `description` VARCHAR(2040) NULL DEFAULT NULL');
sql_query('ALTER TABLE `email_servers` MODIFY `createdby` INT(11) NULL DEFAULT NULL');

/*
 * Add missing required columns to domains_servers
 */
sql_column_exists('domains_servers', 'id'                   ,  'ALTER TABLE `domains_servers` MODIFY COLUMN `id` INT(11) NOT NULL;');
sql_index_exists ('domains_servers', 'PRIMARY'              ,  'ALTER TABLE `domains_servers` DROP PRIMARY KEY');
sql_index_exists ('domains_servers', 'domains_id_servers_id', '!ALTER TABLE `domains_servers` ADD UNIQUE KEY `domains_id_servers_id` (`domains_id`, `servers_id`)');

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
 * Fix users table missing foreign keys, customers_id as well. Update users table to use meta_id as well
 */
sql_column_exists('users', 'employees_id', 'ALTER TABLE `users` ADD COLUMN `customers_id` INT(11) NULL DEFAULT NULL AFTER `roles_id`');
sql_index_exists ('users', 'employees_id', 'ALTER TABLE `users` ADD KEY    `customers_id` (`customers_id`)');

sql_column_exists('users', 'meta_id', '!ALTER TABLE `users` ADD COLUMN `meta_id` INT(11) NULL AFTER `createdby`');
sql_index_exists ('users', 'meta_id', '!ALTER TABLE `users` ADD KEY    `meta_id` (`meta_id`)');

sql_foreignkey_exists('users', 'fk_users_createdby', '!ALTER TABLE `users` ADD CONSTRAINT `fk_users_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('users', 'fk_users_meta_id'  , '!ALTER TABLE `users` ADD CONSTRAINT `fk_users_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT;');

sql_query('TRUNCATE `domains_servers`');
sql_index_exists('domains_servers', 'PRIMARY',  '!ALTER TABLE `domains_servers` ADD PRIMARY KEY (`id`);');
sql_query('ALTER TABLE `domains_servers` MODIFY COLUMN `id` INT(11) NOT NULL AUTO_INCREMENT;');

/*
 * Fix meta_id
 */
$users  = sql_query('SELECT `id` FROM `users`');
$update = sql_prepare('UPDATE `users` SET `meta_id` = :meta_id WHERE `id` = :id');

log_console(tr('Setting up meta_id for users table'), '', false);

while($users_id = sql_fetch($users, true)){
    cli_dot(1);
    $meta_id = meta_action();
    $update->execute(array(':id'      => $users_id,
                           ':meta_id' => $meta_id));
}

cli_dot(false);

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
