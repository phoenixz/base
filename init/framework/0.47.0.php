<?php
/*
 * Add api_accounts support
 */
sql_foreignkey_exists('cdn_servers', 'fk_cdn_servers_api_accounts_id', 'ALTER TABLE `cdn_servers` DROP FOREIGN KEY `fk_cdn_servers_api_accounts_id`');

sql_query('DROP TABLE IF EXISTS `api_accounts`');

sql_query('CREATE TABLE `api_accounts` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                        `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `createdby`    INT(11)           NULL,
                                        `modifiedon`   DATETIME          NULL DEFAULT NULL,
                                        `modifiedby`   INT(11)           NULL DEFAULT NULL,
                                        `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                        `servers_id`   INT(11)       NOT NULL,
                                        `customers_id` INT(11)       NOT NULL,
                                        `name`         VARCHAR(32)   NOT NULL,
                                        `seoname`      VARCHAR(32)   NOT NULL,
                                        `apikey`       VARCHAR(64)   NOT NULL,
                                        `baseurl`      VARCHAR(127)  NOT NULL,
                                        `verify_ssl`   TINYINT(1)    NOT NULL,
                                        `description`  VARCHAR(2047) NOT NULL,

                                        PRIMARY KEY `id`           (`id`),
                                                KEY `createdon`    (`createdon`),
                                                KEY `createdby`    (`createdby`),
                                                KEY `modifiedon`   (`modifiedon`),
                                                KEY `status`       (`status`),
                                                KEY `customers_id` (`customers_id`),
                                                KEY `servers_id`   (`servers_id`),
                                        UNIQUE  KEY `seoname`      (`seoname`),
                                        UNIQUE  KEY `baseurl`      (`baseurl`),

                                        CONSTRAINT `fk_api_accounts_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_api_accounts_modifiedby`   FOREIGN KEY (`modifiedby`)   REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_api_accounts_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_api_accounts_servers_id`   FOREIGN KEY (`servers_id`)   REFERENCES `servers`   (`id`) ON DELETE RESTRICT

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

load_libs('file');
file_delete(ROOT.'data/cdn');

sql_query('DELETE FROM `cdn_storage`');
sql_query('DELETE FROM `cdn_projects`');
sql_query('DELETE FROM `cdn_files`');
sql_query('DELETE FROM `cdn_servers`');

sql_index_exists ('cdn_servers', 'api'            ,  'ALTER TABLE `cdn_servers` DROP KEY `api`');
sql_column_exists('cdn_servers', 'api'            ,  'ALTER TABLE `cdn_servers` CHANGE COLUMN `api` `api_accounts_id` INT(11) NOT NULL');
sql_column_exists('cdn_servers', 'api_accounts_id', '!ALTER TABLE `cdn_servers` ADD COLUMN          `api_accounts_id` INT(11) NOT NULL AFTER `seodomain`');
sql_index_exists ('cdn_servers', 'api_accounts_id', '!ALTER TABLE `cdn_servers` ADD KEY             `api_accounts_id` (`api_accounts_id`)');

sql_foreignkey_exists('cdn_servers', 'fk_cdn_servers_api_accounts_id', '!ALTER TABLE `cdn_servers` ADD CONSTRAINT `fk_cdn_servers_api_accounts_id` FOREIGN KEY (`api_accounts_id`) REFERENCES `api_accounts` (`id`) ON DELETE RESTRICT;');

sql_index_exists ('cdn_servers', 'domain'   ,  'ALTER TABLE `cdn_servers` DROP KEY `domain`');
sql_index_exists ('cdn_servers', 'seodomain',  'ALTER TABLE `cdn_servers` DROP KEY `seodomain`');

sql_column_exists('cdn_servers', 'domain'   ,  'ALTER TABLE `cdn_servers` CHANGE `domain`    `name`    VARCHAR(64)  NOT NULL');
sql_column_exists('cdn_servers', 'seodomain',  'ALTER TABLE `cdn_servers` CHANGE `seodomain` `seoname` VARCHAR(64)  NOT NULL');
sql_column_exists('cdn_servers', 'root'     ,  'ALTER TABLE `cdn_servers` CHANGE `root`      `baseurl` VARCHAR(127) NOT NULL');

sql_index_exists ('cdn_servers', 'name'     , '!ALTER TABLE `cdn_servers` ADD KEY `name`    (`name`)');
sql_index_exists ('cdn_servers', 'seoname'  , '!ALTER TABLE `cdn_servers` ADD KEY `seoname` (`seoname`)');

sql_foreignkey_exists('cdn_storage', 'fk_cdn_storage_projects_id', 'ALTER TABLE `cdn_storage` DROP FOREIGN KEY `fk_cdn_storage_projects_id`');
sql_foreignkey_exists('cdn_storage', 'fk_cdn_storage_servers_id' , 'ALTER TABLE `cdn_storage` DROP FOREIGN KEY `fk_cdn_storage_servers_id`');

sql_index_exists ('cdn_storage', 'servers_id' ,  'ALTER TABLE `cdn_storage` DROP KEY      `servers_id`');
sql_column_exists('cdn_storage', 'servers_id' ,  'ALTER TABLE `cdn_storage` CHANGE COLUMN `servers_id`  `projects_id` INT(11) NOT NULL');
sql_index_exists ('cdn_storage', 'projects_id', '!ALTER TABLE `cdn_storage` ADD KEY                     `projects_id` (`projects_id`)');

sql_foreignkey_exists('cdn_storage', 'fk_cdn_storage_projects_id', '!ALTER TABLE `cdn_storage` ADD CONSTRAINT `fk_cdn_storage_projects_id` FOREIGN KEY (`projects_id`) REFERENCES `cdn_projects` (`id`) ON DELETE RESTRICT');

sql_column_exists('cdn_storage', 'section', '!ALTER TABLE `cdn_storage` ADD COLUMN `section` VARCHAR(24) NULL');
sql_index_exists ('cdn_storage', 'section', '!ALTER TABLE `cdn_storage` ADD KEY    `section` (`section`)');

sql_column_exists('cdn_storage', 'file'   , '!ALTER TABLE `cdn_storage` ADD COLUMN `file`    VARCHAR(128) NOT NULL AFTER `projects_id`');
sql_column_exists('cdn_storage', 'size'   , '!ALTER TABLE `cdn_storage` ADD COLUMN `size`    INT(11)      NOT NULL AFTER `file`');

sql_foreignkey_exists('cdn_storage', 'fk_cdn_storage_objects_id', 'ALTER TABLE `cdn_storage` DROP FOREIGN KEY `fk_cdn_storage_objects_id`');
sql_index_exists ('cdn_storage', 'objects_id', 'ALTER TABLE `cdn_storage` DROP KEY    `objects_id`');
sql_column_exists('cdn_storage', 'objects_id', 'ALTER TABLE `cdn_storage` DROP COLUMN `objects_id`');

sql_index_exists ('cdn_storage', 'entry'     ,  'ALTER TABLE `cdn_storage` DROP KEY `entry`');
sql_index_exists ('cdn_storage', 'servers_id',  'ALTER TABLE `cdn_storage` DROP KEY `servers_id`');
sql_index_exists ('cdn_storage', 'file'      , '!ALTER TABLE `cdn_storage` ADD KEY (`projects_id`, `file`)');

sql_foreignkey_exists('cdn_files', 'fk_cdn_files_projects_id', 'ALTER TABLE `cdn_files` DROP FOREIGN KEY `fk_cdn_files_projects_id`');
sql_index_exists ('cdn_files', 'projects_id',  'ALTER TABLE `cdn_files` DROP KEY `projects_id`');

sql_column_exists('cdn_files', 'filesize'   ,  'ALTER TABLE `cdn_files` DROP   COLUMN `filesize`');
sql_column_exists('cdn_files', 'projects_id',  'ALTER TABLE `cdn_files` CHANGE COLUMN `projects_id` `servers_id` INT(11) NOT NULL');
sql_column_exists('cdn_files', 'section'    , '!ALTER TABLE `cdn_files` ADD    COLUMN `section` VARCHAR(24) NULL AFTER `servers_id`');
sql_column_exists('cdn_files', 'projects_id',  'ALTER TABLE `cdn_files` CHANGE COLUMN `projects_id` `servers_id` INT(11) NOT NULL');

sql_index_exists ('cdn_files', 'servers_id' , '!ALTER TABLE `cdn_files` ADD KEY                     `servers_id` (`servers_id`)');
sql_index_exists ('cdn_files', 'section'    , '!ALTER TABLE `cdn_files` ADD KEY                     `section`    (`section`)');

sql_index_exists ('cdn_files', 'file'           ,  'ALTER TABLE `cdn_files` DROP KEY `file`');
sql_index_exists ('cdn_files', 'servers_id_file', '!ALTER TABLE `cdn_files` ADD UNIQUE KEY `servers_id_file` (`servers_id`, `file`)');

sql_foreignkey_exists('cdn_files', 'fk_cdn_files_servers_id', '!ALTER TABLE `cdn_files` ADD CONSTRAINT `fk_cdn_files_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `cdn_servers` (`id`) ON DELETE RESTRICT');
?>
