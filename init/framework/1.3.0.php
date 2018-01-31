libraries_access<?php
/*
 * New meta library system!
 * New libraries library system!
 *
 * Add the following tables:
 *
 * meta
 * meta_history
 *
 * libraries : This used to be the blogs table
 * libraries_categories  hiarchical structure under which
 * libraries_documents : These are the main documents
 * libraries_pages : These are the pages from the documents containing the actual texts. All pages should have the same content, just in a different language
 * libraries_comments : These are the comments made on libraries_texts
 * libraries_keywords : Blog documents can have multiple keywords, stored here
 * libraries_key_values : Blog texts can have multiple key_value pairs. Stored here, per text
 * libraries_key_values_definitions : The definitions of the available key_value pairs, per libraries
 * libraries_files : The files linked to each document. If file_types_id is NULL, then the file can be of any type. This is why this table will have its independant type, mime1, and mime2 columns
 * libraries_file_types :
 *
 */
sql_query('DROP TABLE IF EXISTS `storage_ratings`');
sql_query('DROP TABLE IF EXISTS `storage_access`');
sql_query('DROP TABLE IF EXISTS `storage_resources`');
sql_query('DROP TABLE IF EXISTS `storage_page_resources`');
sql_query('DROP TABLE IF EXISTS `storage_files`');
sql_query('DROP TABLE IF EXISTS `storage_file_types`');
sql_query('DROP TABLE IF EXISTS `storage_key_values`');
sql_query('DROP TABLE IF EXISTS `storage_keywords`');
sql_query('DROP TABLE IF EXISTS `storage_comments`');
sql_query('DROP TABLE IF EXISTS `storage_pages`');
sql_query('DROP TABLE IF EXISTS `storage_documents`');
sql_query('DROP TABLE IF EXISTS `storage_categories`');
sql_query('DROP TABLE IF EXISTS `storage`');

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



sql_query('CREATE TABLE `libraries` (`id`                  INT(11)     NOT NULL AUTO_INCREMENT,
                                     `createdon`           TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `createdby`           INT(11)         NULL,
                                     `meta_id`             INT(11)     NOT NULL,
                                     `status`              VARCHAR(16)     NULL,
                                     `restrict_file_types` TINYINT(1)  NOT NULL,

                                     PRIMARY KEY `id`        (`id`),
                                             KEY `meta_id`   (`meta_id`),
                                             KEY `createdon` (`createdon`),
                                             KEY `createdby` (`createdby`),
                                             KEY `status`    (`status`),

                                     CONSTRAINT `fk_libraries_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_libraries_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_categories` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                                `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                `createdby`    INT(11)         NULL,
                                                `meta_id`      INT(11)     NOT NULL,
                                                `libraries_id` INT(11)     NOT NULL,
                                                `status`       VARCHAR(16)     NULL,

                                                PRIMARY KEY `id`           (`id`),
                                                        KEY `meta_id`      (`meta_id`),
                                                        KEY `createdon`    (`createdon`),
                                                        KEY `createdby`    (`createdby`),
                                                        KEY `status`       (`status`),
                                                        KEY `libraries_id` (`libraries_id`),

                                                CONSTRAINT `fk_libraries_categories_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`      (`id`) ON DELETE RESTRICT,
                                                CONSTRAINT `fk_libraries_categories_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                                CONSTRAINT `fk_libraries_categories_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries` (`id`) ON DELETE CASCADE

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_documents` (`id`             INT(11)     NOT NULL AUTO_INCREMENT,
                                               `createdon`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `createdby`      INT(11)         NULL,
                                               `meta_id`        INT(11)     NOT NULL,
                                               `libraries_id`   INT(11)     NOT NULL,
                                               `masters_id`     INT(11)         NULL,
                                               `parents_id`     INT(11)         NULL,
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
                                                       KEY `meta_id`        (`meta_id`),
                                                       KEY `libraries_id`   (`libraries_id`),
                                                       KEY `masters_id`     (`masters_id`),
                                                       KEY `parents_id`     (`parents_id`),
                                                       KEY `assigned_to_id` (`assigned_to_id`),
                                                       KEY `createdon`      (`createdon`),
                                                       KEY `createdby`      (`createdby`),
                                                       KEY `status`         (`status`),
                                                       KEY `featured_until` (`featured_until`),
                                                       KEY `category1`      (`category1`),
                                                       KEY `category2`      (`category2`),
                                                       KEY `category3`      (`category3`),
                                                       KEY `priority`       (`priority`),
                                                       KEY `views`          (`views`),
                                                       KEY `rating`         (`rating`),

                                               CONSTRAINT `fk_libraries_documents_meta_id`        FOREIGN KEY (`meta_id`)        REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_documents_createdby`      FOREIGN KEY (`createdby`)      REFERENCES `users`               (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_documents_libraries_id`   FOREIGN KEY (`libraries_id`)   REFERENCES `libraries`           (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_documents_masters_id`     FOREIGN KEY (`masters_id`)     REFERENCES `libraries_documents` (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_documents_parents_id`     FOREIGN KEY (`parents_id`)     REFERENCES `libraries_documents` (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_documents_assigned_to_id` FOREIGN KEY (`assigned_to_id`) REFERENCES `users`               (`id`) ON DELETE CASCADE

                                              ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_pages` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                           `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `createdby`    INT(11)         NULL,
                                           `meta_id`      INT(11)      NOT NULL,
                                           `libraries_id` INT(11)      NOT NULL,
                                           `documents_id` INT(11)      NOT NULL,
                                           `status`       VARCHAR(16)      NULL,
                                           `language`     VARCHAR(2)       NULL,
                                           `name`         VARCHAR(64)      NULL,
                                           `seoname`      VARCHAR(64)      NULL,
                                           `description`  VARCHAR(255)     NULL,
                                           `body`         MEDIUMTEXT       NULL,

                                            PRIMARY KEY `id`           (`id`),
                                                    KEY `meta_id`      (`meta_id`),
                                                    KEY `createdon`    (`createdon`),
                                                    KEY `createdby`    (`createdby`),
                                                    KEY `status`       (`status`),
                                                    KEY `libraries_id` (`libraries_id`),
                                                    KEY `documents_id` (`documents_id`),

                                            CONSTRAINT `fk_libraries_pages_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_libraries_pages_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`               (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_libraries_pages_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries`           (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `fk_libraries_pages_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `libraries_documents` (`id`) ON DELETE CASCADE

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_comments` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                              `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `createdby`    INT(11)           NULL,
                                              `meta_id`      INT(11)       NOT NULL,
                                              `libraries_id` INT(11)       NOT NULL,
                                              `documents_id` INT(11)       NOT NULL,
                                              `pages_id`     INT(11)       NOT NULL,
                                              `status`       VARCHAR(16)       NULL,
                                              `body`         VARCHAR(2044)     NULL,

                                               PRIMARY KEY `id`           (`id`),
                                                       KEY `meta_id`      (`meta_id`),
                                                       KEY `status`       (`status`),
                                                       KEY `createdon`    (`createdon`),
                                                       KEY `createdby`    (`createdby`),
                                                       KEY `libraries_id` (`libraries_id`),
                                                       KEY `documents_id` (`documents_id`),
                                                       KEY `pages_id`     (`pages_id`),

                                               CONSTRAINT `fk_libraries_comments_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_comments_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`               (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_comments_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries`           (`id`) ON DELETE CASCADE,
                                               CONSTRAINT `fk_libraries_comments_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `libraries_documents` (`id`) ON DELETE CASCADE,
                                               CONSTRAINT `fk_libraries_comments_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `libraries_pages`     (`id`) ON DELETE CASCADE

                                              ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_keywords` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                              `meta_id`      INT(11)     NOT NULL,
                                              `libraries_id` INT(11)     NOT NULL,
                                              `documents_id` INT(11)     NOT NULL,
                                              `pages_id`     INT(11)     NOT NULL,
                                              `keyword`      VARCHAR(32)     NULL,

                                              PRIMARY KEY `id`           (`id`),
                                                      KEY `meta_id`      (`meta_id`),
                                                      KEY `libraries_id` (`libraries_id`),
                                                      KEY `documents_id` (`documents_id`),
                                                      KEY `pages_id`     (`pages_id`),

                                              CONSTRAINT `fk_libraries_keywords_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_libraries_keywords_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries`           (`id`) ON DELETE CASCADE,
                                              CONSTRAINT `fk_libraries_keywords_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `libraries_documents` (`id`) ON DELETE CASCADE,
                                              CONSTRAINT `fk_libraries_keywords_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `libraries_pages`     (`id`) ON DELETE CASCADE

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_key_values` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                                `meta_id`      INT(11)      NOT NULL,
                                                `libraries_id` INT(11)      NOT NULL,
                                                `documents_id` INT(11)      NOT NULL,
                                                `pages_id`     INT(11)      NOT NULL,
                                                `parent`       VARCHAR(32)      NULL,
                                                `key`          VARCHAR(32)  NOT NULL,
                                                `value`        VARCHAR(32)  NOT NULL,
                                                `seokey`       VARCHAR(128) NOT NULL,
                                                `seovalue`     VARCHAR(128) NOT NULL,

                                                PRIMARY KEY `id`            (`id`),
                                                        KEY `meta_id`       (`meta_id`),
                                                        KEY `libraries_id`  (`libraries_id`),
                                                        KEY `documents_id`  (`documents_id`),
                                                        KEY `pages_id`      (`pages_id`),
                                                        KEY `seokey`        (`seokey`),
                                                        KEY `seovalue`      (`seovalue`),
                                                        KEY `pages_id_key`  (`pages_id`, `key`),

                                                CONSTRAINT `fk_libraries_key_values_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                                CONSTRAINT `fk_libraries_key_values_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries`           (`id`) ON DELETE CASCADE,
                                                CONSTRAINT `fk_libraries_key_values_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `libraries_documents` (`id`) ON DELETE CASCADE,
                                                CONSTRAINT `fk_libraries_key_values_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `libraries_pages`     (`id`) ON DELETE CASCADE

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_file_types` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                                `meta_id`      INT(11)     NOT NULL,
                                                `status`       VARCHAR(16)     NULL,
                                                `libraries_id` INT(11)     NOT NULL,
                                                `required`     TINYINT(1)  NOT NULL,
                                                `type`         VARCHAR(16) NOT NULL,
                                                `mime1`        VARCHAR(8)  NOT NULL,
                                                `mime2`        VARCHAR(8)  NOT NULL,

                                                 PRIMARY KEY `id`           (`id`),
                                                         KEY `meta_id`      (`meta_id`),
                                                         KEY `status`       (`status`),
                                                         KEY `libraries_id` (`libraries_id`),
                                                         KEY `type`         (`type`),
                                                         KEY `mime1`        (`mime1`),
                                                         KEY `mime2`        (`mime2`),

                                                 CONSTRAINT `fk_libraries_file_types_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`      (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_libraries_file_types_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries` (`id`) ON DELETE CASCADE

                                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_files` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                           `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `createdby`    INT(11)         NULL,
                                           `meta_id`      INT(11)      NOT NULL,
                                           `libraries_id` INT(11)      NOT NULL,
                                           `documents_id` INT(11)      NOT NULL,
                                           `pages_id`     INT(11)          NULL,
                                           `types_id`     INT(11)          NULL,
                                           `status`       VARCHAR(16)      NULL,
                                           `file`         VARCHAR(2)   NOT NULL,
                                           `original`     VARCHAR(2)   NOT NULL,
                                           `hash`         VARCHAR(64)  NOT NULL,
                                           `description`  VARCHAR(511)     NULL,
                                           `priority`     INT(11)      NOT NULL,
                                           `type`         VARCHAR(16)  NOT NULL,
                                           `mime1`        VARCHAR(8)   NOT NULL,
                                           `mime2`        VARCHAR(8)   NOT NULL,

                                           PRIMARY KEY `id`           (`id`),
                                                   KEY `meta_id`      (`meta_id`),
                                                   KEY `libraries_id` (`libraries_id`),
                                                   KEY `documents_id` (`documents_id`),
                                                   KEY `pages_id`     (`pages_id`),
                                                   KEY `status`       (`status`),
                                                   KEY `createdon`    (`createdon`),
                                                   KEY `createdby`    (`createdby`),
                                                   KEY `type`         (`type`),
                                                   KEY `mime1`        (`mime1`),
                                                   KEY `mime2`        (`mime2`),
                                                   KEY `hash`         (`hash`),
                                                   KEY `priority`     (`priority`),

                                           CONSTRAINT `fk_libraries_file_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_libraries_file_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`               (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_libraries_file_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries`           (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `fk_libraries_file_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `libraries_documents` (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `fk_libraries_file_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `libraries_pages`     (`id`) ON DELETE CASCADE

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_resources` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                               `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `createdby`    INT(11)           NULL,
                                               `meta_id`      INT(11)       NOT NULL,
                                               `libraries_id` INT(11)       NOT NULL,
                                               `status`       VARCHAR(16)       NULL,
                                               `language`     VARCHAR(2)        NULL,
                                               `query`        VARCHAR(2045)     NULL,

                                               PRIMARY KEY `id`           (`id`),
                                                       KEY `meta_id`      (`meta_id`),
                                                       KEY `libraries_id` (`libraries_id`),
                                                       KEY `createdon`    (`createdon`),
                                                       KEY `createdby`    (`createdby`),
                                                       KEY `status`       (`status`),
                                                       KEY `language`     (`language`),

                                               CONSTRAINT `fk_libraries_resources_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`      (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_resources_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_libraries_resources_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries` (`id`) ON DELETE CASCADE

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_page_resources` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                                    `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                    `createdby`    INT(11)         NULL,
                                                    `meta_id`      INT(11)     NOT NULL,
                                                    `libraries_id` INT(11)     NOT NULL,
                                                    `documents_id` INT(11)     NOT NULL,
                                                    `pages_id`     INT(11)         NULL,
                                                    `status`       VARCHAR(16)     NULL,
                                                    `language`     VARCHAR(2)      NULL,

                                                    PRIMARY KEY `id`           (`id`),
                                                            KEY `meta_id`      (`meta_id`),
                                                            KEY `libraries_id` (`libraries_id`),
                                                            KEY `documents_id` (`documents_id`),
                                                            KEY `pages_id`     (`pages_id`),
                                                            KEY `status`       (`status`),
                                                            KEY `createdon`    (`createdon`),
                                                            KEY `createdby`    (`createdby`),
                                                            KEY `language`     (`language`),

                                                    CONSTRAINT `fk_libraries_page_resources_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                                    CONSTRAINT `fk_libraries_page_resources_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`               (`id`) ON DELETE RESTRICT,
                                                    CONSTRAINT `fk_libraries_page_resources_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries`           (`id`) ON DELETE CASCADE,
                                                    CONSTRAINT `fk_libraries_page_resources_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `libraries_documents` (`id`) ON DELETE CASCADE,
                                                    CONSTRAINT `fk_libraries_page_resources_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `libraries_pages`     (`id`) ON DELETE CASCADE

                                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_access` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                            `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby`    INT(11)         NULL,
                                            `meta_id`      INT(11)     NOT NULL,
                                            `status`       VARCHAR(16)     NULL,
                                            `libraries_id` INT(11)     NOT NULL,
                                            `documents_id` INT(11)     NOT NULL,
                                            `pages_id`     INT(11)         NULL,
                                            `users_id`     INT(11)         NULL,

                                            PRIMARY KEY `id`           (`id`),
                                                    KEY `meta_id`      (`meta_id`),
                                                    KEY `createdon`    (`createdon`),
                                                    KEY `createdby`    (`createdby`),
                                                    KEY `status`       (`status`),
                                                    KEY `libraries_id` (`libraries_id`),
                                                    KEY `documents_id` (`documents_id`),
                                                    KEY `pages_id`     (`pages_id`),
                                                    KEY `users_id`     (`users_id`),

                                            CONSTRAINT `fk_libraries_access_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_libraries_access_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`               (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_libraries_access_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries`           (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `fk_libraries_access_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `libraries_documents` (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `fk_libraries_access_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `libraries_pages`     (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `fk_libraries_access_users_id`     FOREIGN KEY (`users_id`)     REFERENCES `users`               (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `libraries_ratings` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                             `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                             `createdby`    INT(11)         NULL,
                                             `meta_id`      INT(11)     NOT NULL,
                                             `libraries_id` INT(11)     NOT NULL,
                                             `documents_id` INT(11)     NOT NULL,
                                             `pages_id`     INT(11)         NULL,
                                             `status`       VARCHAR(16)     NULL,
                                             `rating`       INT(2)          NULL,

                                             PRIMARY KEY `id`           (`id`),
                                                     KEY `meta_id`      (`meta_id`),
                                                     KEY `libraries_id` (`libraries_id`),
                                                     KEY `documents_id` (`documents_id`),
                                                     KEY `pages_id`     (`pages_id`),
                                                     KEY `createdon`    (`createdon`),
                                                     KEY `createdby`    (`createdby`),
                                                     KEY `status`       (`status`),

                                             CONSTRAINT `fk_libraries_ratings_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_libraries_ratings_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`               (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_libraries_ratings_libraries_id` FOREIGN KEY (`libraries_id`) REFERENCES `libraries`           (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `fk_libraries_ratings_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `libraries_documents` (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `fk_libraries_ratings_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `libraries_pages`     (`id`) ON DELETE CASCADE

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
