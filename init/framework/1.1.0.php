<?php
/*
 * Upgrade the notifications library
 *
 * Add tables for the messaging library (Inter website user messaging system)
 *
 * Add tables for the files library (generic files storage system)
 *
 * Update storage library to use the generic files management library
 *
 * Fix missing columns and indices in storage tables
 */
sql_foreignkey_exists ('notifications', 'fk_notifications_createdby' , 'ALTER TABLE `notifications` DROP FOREIGN KEY `fk_notifications_createdby`');
sql_foreignkey_exists ('notifications', 'fk_notifications_classes_id', 'ALTER TABLE `notifications` DROP FOREIGN KEY `fk_notifications_classes_id`');

sql_index_exists('notifications', 'createdon' , 'ALTER TABLE `notifications` DROP INDEX `createdon`');
sql_index_exists('notifications', 'createdby' , 'ALTER TABLE `notifications` DROP INDEX `createdby`');
sql_index_exists('notifications', 'classes_id', 'ALTER TABLE `notifications` DROP INDEX `classes_id`');
sql_index_exists('notifications', 'classes_id', 'ALTER TABLE `notifications` DROP INDEX `classes_id`');

sql_column_exists('notifications', 'classes_id',  'ALTER TABLE `notifications` DROP COLUMN `classes_id`');
sql_column_exists('notifications', 'createdon' ,  'ALTER TABLE `notifications` DROP COLUMN `createdon`');
sql_column_exists('notifications', 'createdby' ,  'ALTER TABLE `notifications` CHANGE COLUMN `createdby` `meta_id` INT(11)      NOT NULL');
sql_column_exists('notifications', 'event'     ,  'ALTER TABLE `notifications` CHANGE COLUMN `event`     `title`   VARCHAR(255) NOT NULL');
sql_column_exists('notifications', 'url'       , '!ALTER TABLE `notifications` ADD COLUMN  `url`    VARCHAR(255) NULL AFTER `title`');
sql_column_exists('notifications', 'status'    , '!ALTER TABLE `notifications` ADD COLUMN  `status` VARCHAR(16)  NULL AFTER `meta_id`');

sql_index_exists('notifications', 'meta_id', '!ALTER TABLE `notifications` ADD KEY `meta_id` (`meta_id`)');
sql_index_exists('notifications', 'status' , '!ALTER TABLE `notifications` ADD KEY `status`  (`status`)');

sql_foreignkey_exists('notifications', 'fk_notifications_meta_id' , '!ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');
sql_query('ALTER TABLE `notifications` MODIFY COLUMN `description` TEXT NULL');




sql_query('DROP TABLE IF EXISTS `messages`');
sql_query('DROP TABLE IF EXISTS `messages_users`');

sql_query('CREATE TABLE `messages_users` (`id`         INT(11)      NOT NULL AUTO_INCREMENT,
                                          `meta_id`    INT(11)          NULL,
                                          `status`     VARCHAR(16)      NULL,
                                          `users_id`   INT(11)          NULL,
                                          `servers_id` INT(11)          NULL,
                                          `username`   VARCHAR(64)      NULL,
                                          `nickname`   VARCHAR(64)      NULL,
                                          `email`      VARCHAR(64)      NULL,
                                          `avatar`     VARCHAR(128)     NULL,

                                          PRIMARY KEY `id`         (`id`),
                                                  KEY `meta_id`    (`meta_id`),
                                                  KEY `servers_id` (`servers_id`),
                                                  KEY `users_id`   (`users_id`),
                                                  KEY `username`   (`username`),
                                                  KEY `email`      (`email`),

                                          CONSTRAINT `fk_messages_users_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_messages_users_users_id`   FOREIGN KEY (`users_id`)   REFERENCES `users`   (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_messages_users_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `messages` (`id`      INT(11)      NOT NULL AUTO_INCREMENT,
                                    `meta_id` INT(11)          NULL,
                                    `status`  VARCHAR(16)      NULL,
                                    `from_id` INT(11)          NULL,
                                    `to_id`   INT(11)          NULL,
                                    `name`    VARCHAR(255)     NULL,
                                    `body`    TEXT             NULL,

                                    PRIMARY KEY `id`      (`id`),
                                            KEY `meta_id` (`meta_id`),
                                            KEY `to_id`   (`to_id`),
                                            KEY `from_id` (`from_id`),
                                            KEY `name`    (`name`),

                                    CONSTRAINT `fk_messages_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta`           (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_messages_to_id`   FOREIGN KEY (`to_id`)   REFERENCES `messages_users` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_messages_from_id` FOREIGN KEY (`from_id`) REFERENCES `messages_users` (`id`) ON DELETE RESTRICT

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('DROP TABLE IF EXISTS `files`');

sql_query('CREATE TABLE `files` (`id`          INT(11)       NOT NULL AUTO_INCREMENT,
                                 `meta_id`     INT(11)           NULL,
                                 `status`      VARCHAR(16)       NULL,
                                 `filename`    VARCHAR(64)       NULL,
                                 `original`    VARCHAR(255)      NULL,
                                 `hash`        VARCHAR(64)       NULL,
                                 `type`        VARCHAR(16)       NULL,
                                 `meta1`       VARCHAR(16)       NULL,
                                 `meta2`       VARCHAR(16)       NULL,
                                 `description` VARCHAR(1023)     NULL,

                                 PRIMARY KEY `id`         (`id`),
                                         KEY `meta_id`    (`meta_id`),
                                         KEY `filename`   (`filename`),
                                         KEY `hash`       (`hash`),
                                         KEY `type`       (`type`),
                                         KEY `meta1`      (`meta1`),
                                         KEY `meta2`      (`meta2`),

                                 CONSTRAINT `fk_files_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT

                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('DROP TABLE IF EXISTS `storage_files`');

sql_query('CREATE TABLE `storage_files` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                         `storage_id`   INT(11)      NOT NULL,
                                         `documents_id` INT(11)      NOT NULL,
                                         `pages_id`     INT(11)          NULL,
                                         `files_id`     INT(11)      NOT NULL,

                                         PRIMARY KEY `id`           (`id`),
                                                 KEY `storage_id`   (`storage_id`),
                                                 KEY `documents_id` (`documents_id`),
                                                 KEY `pages_id`     (`pages_id`),
                                                 KEY `files_id`     (`files_id`),

                                         CONSTRAINT `fk_storage_file_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_storage_file_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_storage_file_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE CASCADE

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_column_exists('storage', 'name'   , '!ALTER TABLE `storage` ADD COLUMN `name`    VARCHAR(64) NULL AFTER `restrict_file_types`');
sql_column_exists('storage', 'seoname', '!ALTER TABLE `storage` ADD COLUMN `seoname` VARCHAR(64) NULL AFTER `name`');

sql_index_exists('storage' , 'seoname', '!ALTER TABLE `storage` ADD INDEX `seoname` (`seoname`)');

sql_index_exists('storage_pages', 'seoname'             , '!ALTER TABLE `storage_pages` ADD KEY    `seoname`              (`seoname`)');
sql_index_exists('storage_pages', 'seoname_documents_id', '!ALTER TABLE `storage_pages` ADD UNIQUE `seoname_documents_id` (`seoname`, `documents_id`)');
?>