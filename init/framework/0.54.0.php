<?php
/*
 * New meta library system!
 * New storage library system!
 *
 * Add the following tables:
 *
 * meta
 * meta_history
 *
 * storage : This used to be the blogs table
 * storage_categories  hiarchical structure under which
 * storage_documents : These are the main documents
 * storage_pages : These are the pages from the documents containing the actual texts. All pages should have the same content, just in a different language
 * storage_comments : These are the comments made on storage_texts
 * storage_keywords : Blog documents can have multiple keywords, stored here
 * storage_key_values : Blog texts can have multiple key_value pairs. Stored here, per text
 * storage_key_values_definitions : The definitions of the available key_value pairs, per storage
 * storage_files : The files linked to each document. If file_types_id is NULL, then the file can be of any type. This is why this table will have its independant type, mime1, and mime2 columns
 * storage_file_types :
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

sql_query('DROP TABLE IF EXISTS `meta_history`');
sql_query('DROP TABLE IF EXISTS `meta`');



sql_query('CREATE TABLE `meta` (`id` INT(11) NOT NULL AUTO_INCREMENT,

                                PRIMARY KEY `id` (`id`)

                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `meta_history` (`id`         INT(11)       NOT NULL AUTO_INCREMENT,
                                        `createdon`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `createdby`  INT(11)           NULL,
                                        `meta_id`    INT(11)           NULL,
                                        `action`     VARCHAR(16)       NULL,
                                        `data`       VARCHAR(1023) NOT NULL,

                                        PRIMARY KEY `id`        (`id`),
                                                KEY `createdon` (`createdon`),
                                                KEY `createdby` (`createdby`),
                                                KEY `action`    (`action`),

                                        CONSTRAINT `fk_meta_history_id`        FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE CASCADE,
                                        CONSTRAINT `fk_meta_history_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE CASCADE

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage` (`id`                  INT(11)     NOT NULL AUTO_INCREMENT,
                                   `meta_id`             INT(11)     NOT NULL,
                                   `status`              VARCHAR(16)     NULL,
                                   `restrict_file_types` TINYINT(1)  NOT NULL,

                                   PRIMARY KEY `id`      (`id`),
                                           KEY `meta_id` (`meta_id`),
                                           KEY `status`  (`status`),

                                   CONSTRAINT `fk_storage_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_categories` (`id`         INT(11)     NOT NULL AUTO_INCREMENT,
                                              `meta_id`    INT(11)     NOT NULL,
                                              `storage_id` INT(11)     NOT NULL,
                                              `status`     VARCHAR(16)     NULL,

                                              PRIMARY KEY `id`         (`id`),
                                                      KEY `meta_id`    (`meta_id`),
                                                      KEY `storage_id` (`storage_id`),

                                              CONSTRAINT `fk_storage_categories_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_storage_categories_storage_id` FOREIGN KEY (`storage_id`) REFERENCES `storage` (`id`) ON DELETE CASCADE

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_documents` (`id`             INT(11)     NOT NULL AUTO_INCREMENT,
                                             `meta_id`        INT(11)     NOT NULL,
                                             `storage_id`     INT(11)     NOT NULL,
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
                                                     KEY `storage_id`     (`storage_id`),
                                                     KEY `masters_id`     (`masters_id`),
                                                     KEY `parents_id`     (`parents_id`),
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
                                             CONSTRAINT `fk_storage_documents_storage_id`     FOREIGN KEY (`storage_id`)     REFERENCES `storage`           (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_masters_id`     FOREIGN KEY (`masters_id`)     REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_parents_id`     FOREIGN KEY (`parents_id`)     REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_documents_assigned_to_id` FOREIGN KEY (`assigned_to_id`) REFERENCES `users`             (`id`) ON DELETE CASCADE

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_pages` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                         `meta_id`      INT(11)      NOT NULL,
                                         `storage_id`   INT(11)      NOT NULL,
                                         `documents_id` INT(11)      NOT NULL,
                                         `status`       VARCHAR(16)      NULL,
                                         `language`     VARCHAR(2)       NULL,
                                         `name`         VARCHAR(64)      NULL,
                                         `seoname`      VARCHAR(64)      NULL,
                                         `description`  VARCHAR(255)     NULL,
                                         `body`         MEDIUMTEXT       NULL,

                                          PRIMARY KEY `id`           (`id`),
                                                  KEY `meta_id`      (`meta_id`),
                                                  KEY `storage_id`   (`storage_id`),
                                                  KEY `documents_id` (`documents_id`),

                                          CONSTRAINT `fk_storage_pages_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_storage_pages_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `fk_storage_pages_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_comments` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                            `meta_id`      INT(11)       NOT NULL,
                                            `storage_id`   INT(11)       NOT NULL,
                                            `documents_id` INT(11)       NOT NULL,
                                            `pages_id`     INT(11)       NOT NULL,
                                            `status`       VARCHAR(16)       NULL,
                                            `body`         VARCHAR(2044)     NULL,

                                             PRIMARY KEY `id`           (`id`),
                                                     KEY `meta_id`      (`meta_id`),
                                                     KEY `storage_id`   (`storage_id`),
                                                     KEY `documents_id` (`documents_id`),
                                                     KEY `pages_id`     (`pages_id`),

                                             CONSTRAINT `fk_storage_comments_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_comments_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `fk_storage_comments_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `fk_storage_comments_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE CASCADE

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_keywords` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                            `meta_id`      INT(11)     NOT NULL,
                                            `storage_id`   INT(11)     NOT NULL,
                                            `documents_id` INT(11)     NOT NULL,
                                            `pages_id`     INT(11)     NOT NULL,
                                            `keyword`      VARCHAR(32)     NULL,

                                            PRIMARY KEY `id`           (`id`),
                                                    KEY `meta_id`      (`meta_id`),
                                                    KEY `storage_id`   (`storage_id`),
                                                    KEY `documents_id` (`documents_id`),
                                                    KEY `pages_id`     (`pages_id`),

                                            CONSTRAINT `fk_storage_keywords_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_storage_keywords_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `fk_storage_keywords_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `fk_storage_keywords_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE CASCADE

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_key_values` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                              `meta_id`      INT(11)      NOT NULL,
                                              `storage_id`   INT(11)      NOT NULL,
                                              `documents_id` INT(11)      NOT NULL,
                                              `pages_id`     INT(11)      NOT NULL,
                                              `parent`       VARCHAR(32)      NULL,
                                              `key`          VARCHAR(32)  NOT NULL,
                                              `value`        VARCHAR(32)  NOT NULL,
                                              `seokey`       VARCHAR(128) NOT NULL,
                                              `seovalue`     VARCHAR(128) NOT NULL,

                                              PRIMARY KEY `id`            (`id`),
                                                      KEY `meta_id`       (`meta_id`),
                                                      KEY `storage_id`    (`storage_id`),
                                                      KEY `documents_id`  (`documents_id`),
                                                      KEY `pages_id`      (`pages_id`),
                                                      KEY `seokey`        (`seokey`),
                                                      KEY `seovalue`      (`seovalue`),
                                                      KEY `pages_id_key`  (`pages_id`, `key`),

                                              CONSTRAINT `fk_storage_key_values_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_storage_key_values_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                              CONSTRAINT `fk_storage_key_values_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE,
                                              CONSTRAINT `fk_storage_key_values_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE CASCADE

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_file_types` (`id`         INT(11)     NOT NULL AUTO_INCREMENT,
                                              `meta_id`    INT(11)     NOT NULL,
                                              `storage_id` INT(11)     NOT NULL,
                                              `required`   TINYINT(1)  NOT NULL,
                                              `type`       VARCHAR(16) NOT NULL,
                                              `mime1`      VARCHAR(8)  NOT NULL,
                                              `mime2`      VARCHAR(8)  NOT NULL,

                                               PRIMARY KEY `id`         (`id`),
                                                       KEY `meta_id`    (`meta_id`),
                                                       KEY `storage_id` (`storage_id`),
                                                       KEY `type`       (`type`),
                                                       KEY `mime1`      (`mime1`),
                                                       KEY `mime2`      (`mime2`),

                                               CONSTRAINT `fk_storage_file_types_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_storage_file_types_storage_id` FOREIGN KEY (`storage_id`) REFERENCES `storage` (`id`) ON DELETE CASCADE

                                              ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_files` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                         `meta_id`      INT(11)      NOT NULL,
                                         `storage_id`   INT(11)      NOT NULL,
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
                                                 KEY `storage_id`   (`storage_id`),
                                                 KEY `documents_id` (`documents_id`),
                                                 KEY `pages_id`     (`pages_id`),
                                                 KEY `status`       (`status`),
                                                 KEY `type`         (`type`),
                                                 KEY `mime1`        (`mime1`),
                                                 KEY `mime2`        (`mime2`),
                                                 KEY `hash`         (`hash`),
                                                 KEY `priority`     (`priority`),

                                         CONSTRAINT `fk_storage_file_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_storage_file_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_storage_file_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_storage_file_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE CASCADE

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_resources` (`id`         INT(11)       NOT NULL AUTO_INCREMENT,
                                             `meta_id`    INT(11)       NOT NULL,
                                             `storage_id` INT(11)       NOT NULL,
                                             `status`     VARCHAR(16)       NULL,
                                             `language`   VARCHAR(2)        NULL,
                                             `query`      VARCHAR(2045)     NULL,

                                             PRIMARY KEY `id`         (`id`),
                                                     KEY `meta_id`    (`meta_id`),
                                                     KEY `storage_id` (`storage_id`),
                                                     KEY `status`     (`status`),
                                                     KEY `language`   (`language`),

                                             CONSTRAINT `fk_storage_resources_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_storage_resources_storage_id` FOREIGN KEY (`storage_id`) REFERENCES `storage` (`id`) ON DELETE CASCADE

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_page_resources` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                                  `meta_id`      INT(11)     NOT NULL,
                                                  `storage_id`   INT(11)     NOT NULL,
                                                  `documents_id` INT(11)     NOT NULL,
                                                  `pages_id`     INT(11)         NULL,
                                                  `status`       VARCHAR(16)     NULL,
                                                  `language`     VARCHAR(2)      NULL,

                                                  PRIMARY KEY `id`           (`id`),
                                                          KEY `meta_id`      (`meta_id`),
                                                          KEY `storage_id`   (`storage_id`),
                                                          KEY `documents_id` (`documents_id`),
                                                          KEY `pages_id`     (`pages_id`),
                                                          KEY `status`       (`status`),
                                                          KEY `language`     (`language`),

                                                  CONSTRAINT `fk_storage_page_resources_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                                  CONSTRAINT `fk_storage_page_resources_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                                  CONSTRAINT `fk_storage_page_resources_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE,
                                                  CONSTRAINT `fk_storage_page_resources_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE CASCADE

                                                 ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_access` (`id`           INT(11) NOT NULL AUTO_INCREMENT,
                                          `meta_id`      INT(11) NOT NULL,
                                          `storage_id`   INT(11) NOT NULL,
                                          `documents_id` INT(11) NOT NULL,
                                          `pages_id`     INT(11)     NULL,
                                          `users_id`     INT(11)     NULL,

                                          PRIMARY KEY `id`           (`id`),
                                                  KEY `meta_id`      (`meta_id`),
                                                  KEY `storage_id`   (`storage_id`),
                                                  KEY `documents_id` (`documents_id`),
                                                  KEY `pages_id`     (`pages_id`),
                                                  KEY `users_id`     (`users_id`),

                                          CONSTRAINT `fk_storage_access_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_storage_access_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `fk_storage_access_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `fk_storage_access_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `fk_storage_access_users_id`     FOREIGN KEY (`users_id`)     REFERENCES `users`             (`id`) ON DELETE RESTRICT

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `storage_ratings` (`id`           INT(11)     NOT NULL AUTO_INCREMENT,
                                           `meta_id`      INT(11)     NOT NULL,
                                           `storage_id`   INT(11)     NOT NULL,
                                           `documents_id` INT(11)     NOT NULL,
                                           `pages_id`     INT(11)         NULL,
                                           `status`       VARCHAR(16)     NULL,
                                           `rating`       INT(2)          NULL,

                                           PRIMARY KEY `id`           (`id`),
                                                   KEY `meta_id`      (`meta_id`),
                                                   KEY `storage_id`   (`storage_id`),
                                                   KEY `documents_id` (`documents_id`),
                                                   KEY `pages_id`     (`pages_id`),
                                                   KEY `status`       (`status`),

                                           CONSTRAINT `fk_storage_ratings_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_storage_ratings_storage_id`   FOREIGN KEY (`storage_id`)   REFERENCES `storage`           (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `fk_storage_ratings_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `storage_documents` (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `fk_storage_ratings_pages_id`     FOREIGN KEY (`pages_id`)     REFERENCES `storage_pages`     (`id`) ON DELETE CASCADE

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
