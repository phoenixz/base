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

sql_query('DELETE FROM `cdn_servers`');

sql_index_exists ('cdn_servers', 'api',  'ALTER TABLE `cdn_servers` DROP KEY `api`');
sql_column_exists('cdn_servers', 'api',  'ALTER TABLE `cdn_servers` CHANGE COLUMN `api` `api_accounts_id` INT(11) NOT NULL');

sql_index_exists ('cdn_servers', 'api_accounts_id', '!ALTER TABLE `cdn_servers` ADD KEY             `api_accounts_id` (`api_accounts_id`)');

sql_foreignkey_exists('cdn_servers', 'fk_cdn_servers_api_accounts_id', '!ALTER TABLE `cdn_servers` ADD CONSTRAINT `fk_cdn_servers_api_accounts_id` FOREIGN KEY (`api_accounts_id`) REFERENCES `api_accounts` (`id`) ON DELETE RESTRICT;');

sql_index_exists ('cdn_servers', 'domain'   ,  'ALTER TABLE `cdn_servers` DROP KEY `domain`');
sql_index_exists ('cdn_servers', 'seodomain',  'ALTER TABLE `cdn_servers` DROP KEY `seodomain`');

sql_column_exists('cdn_servers', 'domain'   ,  'ALTER TABLE `cdn_servers` CHANGE `domain`    `name`    VARCHAR(64)  NOT NULL');
sql_column_exists('cdn_servers', 'seodomain',  'ALTER TABLE `cdn_servers` CHANGE `seodomain` `seoname` VARCHAR(64)  NOT NULL');
sql_column_exists('cdn_servers', 'root'     ,  'ALTER TABLE `cdn_servers` CHANGE `root`      `baseurl` VARCHAR(127) NOT NULL');

sql_index_exists ('cdn_servers', 'name'     , '!ALTER TABLE `cdn_servers` ADD KEY `name`    (`name`)');
sql_index_exists ('cdn_servers', 'seoname'  , '!ALTER TABLE `cdn_servers` ADD KEY `seoname` (`seoname`)');

sql_column_exists('cdn_storage', 'file'   , '!ALTER TABLE `cdn_storage` ADD COLUMN `file`    VARCHAR(128) NOT NULL');
sql_column_exists('cdn_storage', 'section', '!ALTER TABLE `cdn_storage` ADD COLUMN `section` VARCHAR(24)  NOT NULL');
sql_index_exists ('cdn_storage', 'section', '!ALTER TABLE `cdn_storage` ADD KEY    `section` (`section`)');

sql_foreignkey_exists('cdn_storage', 'fk_cdn_storage_objects_id', 'ALTER TABLE `cdn_storage` DROP FOREIGN KEY `fk_cdn_storage_objects_id`');
sql_index_exists ('cdn_storage', 'objects_id', '!ALTER TABLE `cdn_storage` DROP KEY    `objects_id`');
sql_column_exists('cdn_storage', 'objects_id', '!ALTER TABLE `cdn_storage` DROP COLUMN `objects_id`');

sql_table_exists('cdn_files', 'DROP TABLE `cdn_files`');
?>
