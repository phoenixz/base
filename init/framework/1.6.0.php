<?php
/*
 * Return retarded "libraries" name back to storage, thank you
 * Extend meta library with meta_data table for larger meta data blobs
 * Update user rights storage with where the right came from
 *
 */
sql_query('DROP TABLE IF EXISTS `libraries_ratings`');
sql_query('DROP TABLE IF EXISTS `libraries_access`');
sql_query('DROP TABLE IF EXISTS `libraries_resources`');
sql_query('DROP TABLE IF EXISTS `libraries_page_resources`');
sql_query('DROP TABLE IF EXISTS `libraries_files`');
sql_query('DROP TABLE IF EXISTS `libraries_file_types`');
sql_query('DROP TABLE IF EXISTS `libraries_key_values`');
sql_query('DROP TABLE IF EXISTS `libraries_keywords`');
sql_query('DROP TABLE IF EXISTS `libraries_comments`');
sql_query('DROP TABLE IF EXISTS `libraries_pages`');
sql_query('DROP TABLE IF EXISTS `libraries_documents`');
sql_query('DROP TABLE IF EXISTS `libraries_categories`');
sql_query('DROP TABLE IF EXISTS `libraries`');

sql_query('DROP TABLE IF EXISTS `storage_ratings`');
sql_query('DROP TABLE IF EXISTS `storage_resources`');
sql_query('DROP TABLE IF EXISTS `storage_page_resources`');
sql_query('DROP TABLE IF EXISTS `storage_files`');
sql_query('DROP TABLE IF EXISTS `storage_file_restrictions`');
sql_query('DROP TABLE IF EXISTS `storage_file_types`');
sql_query('DROP TABLE IF EXISTS `storage_key_values`');
sql_query('DROP TABLE IF EXISTS `storage_keywords`');
sql_query('DROP TABLE IF EXISTS `storage_comments`');
sql_query('DROP TABLE IF EXISTS `storage_pages`');
sql_query('DROP TABLE IF EXISTS `storage_documents`');
sql_query('DROP TABLE IF EXISTS `storage_categories`');
sql_query('DROP TABLE IF EXISTS `storage_sections`');

sql_query('DROP TABLE IF EXISTS `meta_data`');



sql_query('CREATE TABLE `meta_data` (`id`              INT(11)    NOT NULL AUTO_INCREMENT,
                                     `meta_history_id` INT(11)        NULL,
                                     `data`            MEDIUMBLOB NOT NULL,

                                     PRIMARY KEY `id`              (`id`),
                                     UNIQUE  KEY `meta_history_id` (`meta_history_id`),

                                     CONSTRAINT `fk_meta_data_history_id` FOREIGN KEY (`meta_history_id`) REFERENCES `meta`  (`id`) ON DELETE CASCADE

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_sections` (`id`                  INT(11)      NOT NULL AUTO_INCREMENT,
                                            `createdon`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby`           INT(11)          NULL,
                                            `meta_id`             INT(11)      NOT NULL,
                                            `status`              VARCHAR(16)      NULL,
                                            `restrict_file_types` TINYINT(1)       NULL,
                                            `name`                VARCHAR(32)      NULL,
                                            `seoname`             VARCHAR(32)      NULL,
                                            `slogan`              VARCHAR(255)     NULL,
                                            `url_template`        VARCHAR(255)     NULL,
                                            `description`         TEXT             NULL,

                                            PRIMARY KEY `id`        (`id`),
                                                    KEY `meta_id`   (`meta_id`),
                                                    KEY `createdon` (`createdon`),
                                                    KEY `createdby` (`createdby`),
                                                    KEY `status`    (`status`),
                                                    KEY `seoname`   (`seoname`),

                                            CONSTRAINT `fk_storage_sections_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_storage_sections_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_categories` (`id`          INT(11)     NOT NULL AUTO_INCREMENT,
                                              `createdon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `createdby`   INT(11)         NULL,
                                              `meta_id`     INT(11)     NOT NULL,
                                              `sections_id` INT(11)     NOT NULL,
                                              `status`      VARCHAR(16)     NULL,

                                              PRIMARY KEY `id`          (`id`),
                                                      KEY `meta_id`     (`meta_id`),
                                                      KEY `createdon`   (`createdon`),
                                                      KEY `createdby`   (`createdby`),
                                                      KEY `sections_id` (`sections_id`),

                                              CONSTRAINT `fk_storage_categories_meta_id`     FOREIGN KEY (`meta_id`)     REFERENCES `meta`             (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_storage_categories_sections_id` FOREIGN KEY (`sections_id`) REFERENCES `storage_sections` (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_storage_categories_createdby`   FOREIGN KEY (`createdby`)   REFERENCES `users`            (`id`) ON DELETE RESTRICT

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_documents` (`id`             INT(11)     NOT NULL AUTO_INCREMENT,
                                             `createdon`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                             `createdby`      INT(11)         NULL,
                                             `meta_id`        INT(11)     NOT NULL,
                                             `sections_id`    INT(11)     NOT NULL,
                                             `masters_id`     INT(11)         NULL,
                                             `parents_id`     INT(11)         NULL,
                                             `rights_id`      INT(11)         NULL,
                                             `assigned_to_id` INT(11)         NULL,
                                             `status`         VARCHAR(16)     NULL,
                                             `featured_until` DATETIME        NULL,
                                             `category1`      INT(11)         NULL,
                                             `category2`      INT(11)         NULL,
                                             `category3`      INT(11)         NULL,
                                             `upvotes`        INT(11)     NOT NULL,
                                             `downvotes`      INT(11)     NOT NULL,
                                             `priority`       INT(11)     NOT NULL,
                                             `level`          INT(11)     NOT NULL,
                                             `views`          INT(11)     NOT NULL,
                                             `rating`         INT(11)     NOT NULL,
                                             `comments`       INT(11)     NOT NULL,

                                             PRIMARY KEY `id`             (`id`),
                                                     KEY `createdon`      (`createdon`),
                                                     KEY `createdby`      (`createdby`),
                                                     KEY `meta_id`        (`meta_id`),
                                                     KEY `sections_id`    (`sections_id`),
                                                     KEY `masters_id`     (`masters_id`),
                                                     KEY `parents_id`     (`parents_id`),
                                                     KEY `rights_id`      (`rights_id`),
                                                     KEY `assigned_to_id` (`assigned_to_id`),
                                                     KEY `status`         (`status`),
                                                     KEY `featured_until` (`featured_until`),
                                                     KEY `category1`      (`category1`),
                                                     KEY `category2`      (`category2`),
                                                     KEY `category3`      (`category3`),
                                                     KEY `priority`       (`priority`),
                                                     KEY `views`          (`views`),
                                                     KEY `rating`         (`rating`),

                                             CONSTRAINT `fk_storage_documents_meta_id`        FOREIGN KEY (`meta_id`)        REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_rights_id`      FOREIGN KEY (`rights_id`)      REFERENCES `rights`            (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_sections_id`    FOREIGN KEY (`sections_id`)    REFERENCES `storage_sections`  (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_masters_id`     FOREIGN KEY (`masters_id`)     REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_parents_id`     FOREIGN KEY (`parents_id`)     REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_assigned_to_id` FOREIGN KEY (`assigned_to_id`) REFERENCES `users`             (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_createdby`      FOREIGN KEY (`createdby`)      REFERENCES `users`             (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_pages` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                         `createdon`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby`    INT(11)          NULL,
                                         `meta_id`      INT(11)      NOT NULL,
                                         `sections_id`  INT(11)      NOT NULL,
                                         `documents_id` INT(11)      NOT NULL,
                                         `status`       VARCHAR(16)      NULL,
                                         `language`     VARCHAR(2)       NULL,
                                         `name`         VARCHAR(64)      NULL,
                                         `seoname`      VARCHAR(64)      NULL,
                                         `description`  VARCHAR(255)     NULL,
                                         `body`         MEDIUMTEXT       NULL,

                                          PRIMARY KEY `id`           (`id`),
                                                  KEY `createdon`    (`createdon`),
                                                  KEY `createdby`    (`createdby`),
                                                  KEY `meta_id`      (`meta_id`),
                                                  KEY `sections_id`  (`sections_id`),
                                                  KEY `documents_id` (`documents_id`),

                                          CONSTRAINT `fk_storage_pages_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_storage_pages_sections_id`  FOREIGN KEY (`sections_id`)  REFERENCES `storage_sections`  (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_storage_pages_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_storage_pages_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`             (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_comments` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                            `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby`    INT(11)           NULL,
                                            `meta_id`      INT(11)       NOT NULL,
                                            `sections_id`  INT(11)       NOT NULL,
                                            `documents_id` INT(11)       NOT NULL,
                                            `pages_id`     INT(11)       NOT NULL,
                                            `status`       VARCHAR(16)       NULL,
                                            `body`         VARCHAR(2044)     NULL,

                                             PRIMARY KEY `id`           (`id`),
                                                     KEY `createdon`    (`createdon`),
                                                     KEY `createdby`    (`createdby`),
                                                     KEY `meta_id`      (`meta_id`),
                                                     KEY `sections_id`  (`sections_id`),
                                                     KEY `documents_id` (`documents_id`),
                                                     KEY `pages_id`     (`pages_id`),

                                             CONSTRAINT `fk_storage_comments_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_comments_sections_id`  FOREIGN KEY (`sections_id`)  REFERENCES `storage_sections`  (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_comments_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_comments_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_comments_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`             (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_keywords` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                            `meta_id`      INT(11)     NOT NULL,
                                            `sections_id`  INT(11)    NOT NULL,
                                            `documents_id` INT(11)     NOT NULL,
                                            `pages_id`     INT(11)     NOT NULL,
                                            `keyword`      VARCHAR(32)     NULL,

                                            PRIMARY KEY `id`           (`id`),
                                                    KEY `meta_id`      (`meta_id`),
                                                    KEY `sections_id`  (`sections_id`),
                                                    KEY `documents_id` (`documents_id`),
                                                    KEY `pages_id`     (`pages_id`),

                                            CONSTRAINT `fk_storage_keywords_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_storage_keywords_sections_id`  FOREIGN KEY (`sections_id`)  REFERENCES `storage_sections`  (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_storage_keywords_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_storage_keywords_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_key_values` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                              `meta_id`      INT(11)      NOT NULL,
                                              `sections_id`  INT(11)      NOT NULL,
                                              `documents_id` INT(11)      NOT NULL,
                                              `pages_id`     INT(11)      NOT NULL,
                                              `parent`       VARCHAR(32)      NULL,
                                              `key`          VARCHAR(32)  NOT NULL,
                                              `value`        VARCHAR(32)  NOT NULL,
                                              `seokey`       VARCHAR(128) NOT NULL,
                                              `seovalue`     VARCHAR(128) NOT NULL,

                                              PRIMARY KEY `id`            (`id`),
                                                      KEY `meta_id`       (`meta_id`),
                                                      KEY `sections_id`   (`sections_id`),
                                                      KEY `documents_id`  (`documents_id`),
                                                      KEY `pages_id`      (`pages_id`),
                                                      KEY `seokey`        (`seokey`),
                                                      KEY `seovalue`      (`seovalue`),
                                                      KEY `pages_id_key`  (`pages_id`, `key`),

                                              CONSTRAINT `fk_storage_key_values_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_storage_key_values_sections_id`  FOREIGN KEY (`sections_id`)  REFERENCES `storage_sections`  (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_storage_key_values_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_storage_key_values_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE RESTRICT

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_file_types` (`id`          INT(11)     NOT NULL AUTO_INCREMENT,
                                              `createdon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `createdby`   INT(11)         NULL,
                                              `meta_id`     INT(11)     NOT NULL,
                                              `sections_id` INT(11)     NOT NULL,
                                              `required`    TINYINT(1)  NOT NULL,
                                              `type`        VARCHAR(16) NOT NULL,
                                              `mime1`       VARCHAR(8)  NOT NULL,
                                              `mime2`       VARCHAR(8)  NOT NULL,

                                               PRIMARY KEY `id`          (`id`),
                                                       KEY `createdon`   (`createdon`),
                                                       KEY `createdby`   (`createdby`),
                                                       KEY `meta_id`     (`meta_id`),
                                                       KEY `sections_id` (`sections_id`),
                                                       KEY `type`        (`type`),
                                                       KEY `mime1`       (`mime1`),
                                                       KEY `mime2`       (`mime2`),

                                               CONSTRAINT `fk_storage_file_types_meta_id`     FOREIGN KEY (`meta_id`)     REFERENCES `meta`             (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_storage_file_types_sections_id` FOREIGN KEY (`sections_id`) REFERENCES `storage_sections` (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_storage_file_types_createdby`   FOREIGN KEY (`createdby`)   REFERENCES `users`            (`id`) ON DELETE RESTRICT

                                              ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_file_restrictions` (`id`            INT(11)     NOT NULL AUTO_INCREMENT,
                                                     `meta_id`       INT(11)     NOT NULL,
                                                     `file_types_id` INT(11)     NOT NULL,
                                                     `required`      TINYINT(1)  NOT NULL,
                                                     `key`           VARCHAR(16) NOT NULL,
                                                     `value`         VARCHAR(16) NOT NULL,

                                                      PRIMARY KEY `id`            (`id`),
                                                              KEY `meta_id`       (`meta_id`),
                                                              KEY `file_types_id` (`file_types_id`),
                                                              KEY `key`           (`key`),

                                                      CONSTRAINT `fk_storage_file_restrictions_meta_id`       FOREIGN KEY (`meta_id`)       REFERENCES `meta`                      (`id`) ON DELETE RESTRICT,
                                                      CONSTRAINT `fk_storage_file_restrictions_file_types_id` FOREIGN KEY (`file_types_id`) REFERENCES `storage_file_restrictions` (`id`) ON DELETE RESTRICT

                                                     ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_files` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                         `createdon`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby`    INT(11)          NULL,
                                         `meta_id`      INT(11)      NOT NULL,
                                         `sections_id`  INT(11)      NOT NULL,
                                         `documents_id` INT(11)      NOT NULL,
                                         `pages_id`     INT(11)          NULL,
                                         `types_id`     INT(11)          NULL,
                                         `files_id`     INT(11)          NULL,

                                         PRIMARY KEY `id`           (`id`),
                                                 KEY `createdon`    (`createdon`),
                                                 KEY `createdby`    (`createdby`),
                                                 KEY `meta_id`      (`meta_id`),
                                                 KEY `sections_id`  (`sections_id`),
                                                 KEY `documents_id` (`documents_id`),
                                                 KEY `pages_id`     (`pages_id`),
                                                 KEY `types_id`     (`types_id`),
                                                 KEY `files_id`     (`files_id`),

                                         CONSTRAINT `fk_storage_files_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`               (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_storage_files_sections_id`  FOREIGN KEY (`sections_id`)  REFERENCES `storage_sections`   (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_storage_files_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents`  (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_storage_files_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`      (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_storage_files_types_id`     FOREIGN KEY (`types_id`)     REFERENCES `storage_file_types` (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_storage_files_files_id`     FOREIGN KEY (`files_id`)     REFERENCES `files`              (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_storage_files_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`              (`id`) ON DELETE RESTRICT

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_resources` (`id`          INT(11)       NOT NULL AUTO_INCREMENT,
                                             `createdon`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                             `createdby`   INT(11)           NULL,
                                             `meta_id`     INT(11)       NOT NULL,
                                             `sections_id` INT(11)       NOT NULL,
                                             `status`      VARCHAR(16)       NULL,
                                             `language`    VARCHAR(2)        NULL,
                                             `query`       VARCHAR(2045)     NULL,

                                             PRIMARY KEY `id`          (`id`),
                                                     KEY `createdon`   (`createdon`),
                                                     KEY `createdby`   (`createdby`),
                                                     KEY `meta_id`     (`meta_id`),
                                                     KEY `sections_id` (`sections_id`),
                                                     KEY `status`      (`status`),
                                                     KEY `language`    (`language`),

                                             CONSTRAINT `fk_storage_resources_meta_id`     FOREIGN KEY (`meta_id`)     REFERENCES `meta`             (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_resources_sections_id` FOREIGN KEY (`sections_id`) REFERENCES `storage_sections` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_resources_createdby`   FOREIGN KEY (`createdby`)   REFERENCES `users`            (`id`) ON DELETE RESTRICT

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_ratings` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                           `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `createdby`    INT(11)         NULL,
                                           `meta_id`      INT(11)     NOT NULL,
                                           `sections_id`  INT(11)     NOT NULL,
                                           `documents_id` INT(11)     NOT NULL,
                                           `pages_id`     INT(11)         NULL,
                                           `status`       VARCHAR(16)     NULL,
                                           `rating`       INT(2)          NULL,

                                           PRIMARY KEY `id`           (`id`),
                                                   KEY `createdon`    (`createdon`),
                                                   KEY `createdby`    (`createdby`),
                                                   KEY `meta_id`      (`meta_id`),
                                                   KEY `sections_id`  (`sections_id`),
                                                   KEY `documents_id` (`documents_id`),
                                                   KEY `pages_id`     (`pages_id`),
                                                   KEY `status`       (`status`),

                                           CONSTRAINT `fk_storage_ratings_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_storage_ratings_sections_id`  FOREIGN KEY (`sections_id`)  REFERENCES `storage_sections`  (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_storage_ratings_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_storage_ratings_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_storage_ratings_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`             (`id`) ON DELETE RESTRICT

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
