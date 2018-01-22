<?php
/*
 * Add tables for the files library (generic files storage system)
 * Update storage library to use the generic files management library
 * Fix missing columns and indices in storage tables
 */
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