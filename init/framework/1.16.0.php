<?php
/*
 * Add tables for new categories library
 * Add tables for new projects library
 * Add tables for new progress library
 *
 * Update storage_documents table to have process / process steps capabilities
 * Update customers table to use meta_id, drop modifiedon / modifiedby
 * Update customers table add support for phone, email
 */
sql_foreignkey_exists('customers'        , 'fk_customers_categories_id'       , 'ALTER TABLE `customers`         DROP FOREIGN KEY `fk_customers_categories_id`');
sql_foreignkey_exists('storage_documents', 'fk_storage_documents_processes_id', 'ALTER TABLE `storage_documents` DROP FOREIGN KEY `fk_storage_documents_processes_id`');
sql_foreignkey_exists('storage_documents', 'fk_storage_documents_steps_id'    , 'ALTER TABLE `storage_documents` DROP FOREIGN KEY `fk_storage_documents_steps_id`');

sql_query('DROP TABLE IF EXISTS `projects`');
sql_query('DROP TABLE IF EXISTS `progress_steps`');
sql_query('DROP TABLE IF EXISTS `progress_processes`');
sql_query('DROP TABLE IF EXISTS `categories`');



sql_query('CREATE TABLE `categories` (`id`          INT(11)       NOT NULL AUTO_INCREMENT,
                                      `createdon`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `createdby`   INT(11)           NULL,
                                      `meta_id`     INT(11)       NOT NULL,
                                      `status`      VARCHAR(16)       NULL,
                                      `parents_id`  INT(11)           NULL,
                                      `name`        VARCHAR(64)       NULL,
                                      `seoname`     VARCHAR(64)       NULL,
                                      `description` VARCHAR(2047)     NULL,

                                      PRIMARY KEY `id`          (`id`),
                                              KEY `meta_id`     (`meta_id`),
                                              KEY `parents_id`  (`parents_id`),
                                              KEY `createdon`   (`createdon`),
                                              KEY `createdby`   (`createdby`),
                                              KEY `status`      (`status`),
                                      UNIQUE  KEY `seoname`     (`seoname`),
                                      UNIQUE  KEY `parent_name` (`parents_id`, `name`),

                                      CONSTRAINT `fk_categories_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`       (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_categories_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_categories_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`      (`id`) ON DELETE RESTRICT

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `progress_processes` (`id`            INT(11)       NOT NULL AUTO_INCREMENT,
                                              `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `createdby`     INT(11)           NULL,
                                              `meta_id`       INT(11)       NOT NULL,
                                              `status`        VARCHAR(16)       NULL,
                                              `categories_id` INT(11)           NULL,
                                              `name`          VARCHAR(64)       NULL,
                                              `seoname`       VARCHAR(64)       NULL,
                                              `description`   VARCHAR(2047)     NULL,

                                              PRIMARY KEY `id`               (`id`),
                                                      KEY `meta_id`          (`meta_id`),
                                                      KEY `categories_id`    (`categories_id`),
                                                      KEY `createdon`        (`createdon`),
                                                      KEY `createdby`        (`createdby`),
                                                      KEY `status`           (`status`),
                                              UNIQUE  KEY `category_seoname` (`categories_id`, `seoname`),

                                              CONSTRAINT `fk_progress_processes_meta_id`       FOREIGN KEY (`meta_id`)       REFERENCES `meta`       (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_progress_processes_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_progress_processes_createdby`     FOREIGN KEY (`createdby`)     REFERENCES `users`      (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `progress_steps` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                          `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`    INT(11)           NULL,
                                          `meta_id`      INT(11)       NOT NULL,
                                          `status`       VARCHAR(16)       NULL,
                                          `processes_id` INT(11)       NOT NULL,
                                          `parents_id`   INT(11)           NULL,
                                          `name`         VARCHAR(64)       NULL,
                                          `seoname`      VARCHAR(64)       NULL,
                                          `url`          VARCHAR(255)      NULL,
                                          `description`  VARCHAR(2047)     NULL,

                                          PRIMARY KEY `id`              (`id`),
                                                  KEY `meta_id`         (`meta_id`),
                                                  KEY `createdon`       (`createdon`),
                                                  KEY `createdby`       (`createdby`),
                                                  KEY `status`          (`status`),
                                                  KEY `processes_id`    (`processes_id`),
                                                  KEY `parents_id`      (`parents_id`),
                                          UNIQUE  KEY `process_seoname` (`processes_id`, `seoname`),

                                          CONSTRAINT `fk_progress_steps_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`               (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_progress_steps_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`              (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_progress_steps_processes_id` FOREIGN KEY (`processes_id`) REFERENCES `progress_processes` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_progress_steps_parents_id`   FOREIGN KEY (`parents_id`)   REFERENCES `progress_steps`     (`id`) ON DELETE CASCADE

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `projects` (`id`            INT(11)       NOT NULL AUTO_INCREMENT,
                                    `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `createdby`     INT(11)           NULL,
                                    `meta_id`       INT(11)       NOT NULL,
                                    `status`        VARCHAR(16)       NULL,
                                    `categories_id` INT(11)           NULL,
                                    `customers_id`  INT(11)           NULL,
                                    `processes_id`  INT(11)           NULL,
                                    `steps_id`      INT(11)           NULL,
                                    `documents_id`  INT(11)           NULL,
                                    `name`          VARCHAR(64)       NULL,
                                    `seoname`       VARCHAR(64)       NULL,
                                    `code`          VARCHAR(32)       NULL,
                                    `api_key`       VARCHAR(64)       NULL,
                                    `last_login`    TIMESTAMP         NULL,
                                    `description`   VARCHAR(2047)     NULL,

                                    PRIMARY KEY `id`            (`id`),
                                            KEY `meta_id`       (`meta_id`),
                                            KEY `createdon`     (`createdon`),
                                            KEY `createdby`     (`createdby`),
                                            KEY `status`        (`status`),
                                            KEY `categories_id` (`categories_id`),
                                            KEY `customers_id`  (`customers_id`),
                                            KEY `documents_id`  (`documents_id`),
                                            KEY `processes_id`  (`processes_id`),
                                            KEY `steps_id`      (`steps_id`),
                                    UNIQUE  KEY `seoname`       (`seoname`),
                                    UNIQUE  KEY `code`          (`code`),
                                    UNIQUE  KEY `api_key`       (`api_key`),

                                    CONSTRAINT `fk_projects_meta_id`       FOREIGN KEY (`meta_id`)       REFERENCES `meta`               (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_createdby`     FOREIGN KEY (`createdby`)     REFERENCES `users`              (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories`         (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_customers_id`  FOREIGN KEY (`customers_id`)  REFERENCES `customers`          (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_documents_id`  FOREIGN KEY (`documents_id`)  REFERENCES `storage_documents`  (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_processes_id`  FOREIGN KEY (`processes_id`)  REFERENCES `progress_processes` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_steps_id`      FOREIGN KEY (`steps_id`)      REFERENCES `progress_steps`     (`id`) ON DELETE CASCADE

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('ALTER TABLE `customers` MODIFY `name`    VARCHAR(64) NULL DEFAULT NULL');
sql_query('ALTER TABLE `customers` MODIFY `seoname` VARCHAR(64) NULL DEFAULT NULL');

sql_column_exists    ('customers', 'documents_id'             , '!ALTER TABLE `customers` ADD COLUMN     `documents_id` INT(11) NULL DEFAULT NULL AFTER `phones`');
sql_index_exists     ('customers', 'documents_id'             , '!ALTER TABLE `customers` ADD KEY        `documents_id` (`documents_id`)');
sql_foreignkey_exists('customers', 'fk_customers_documents_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;');

sql_column_exists    ('customers', 'categories_id'             , '!ALTER TABLE `customers` ADD COLUMN     `categories_id` INT(11) NULL DEFAULT NULL AFTER `documents_id`');
sql_index_exists     ('customers', 'categories_id'             , '!ALTER TABLE `customers` ADD KEY        `categories_id` (`categories_id`)');
sql_foreignkey_exists('customers', 'fk_customers_categories_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;');

sql_column_exists    ('storage_documents', 'processes_id'                     , '!ALTER TABLE `storage_documents` ADD COLUMN     `processes_id` INT(11) NULL DEFAULT NULL AFTER `customers_id`');
sql_index_exists     ('storage_documents', 'processes_id'                     , '!ALTER TABLE `storage_documents` ADD KEY        `processes_id` (`processes_id`)');
sql_foreignkey_exists('storage_documents', 'fk_storage_documents_processes_id', '!ALTER TABLE `storage_documents` ADD CONSTRAINT `fk_storage_documents_processes_id` FOREIGN KEY (`processes_id`) REFERENCES `progress_processes` (`id`) ON DELETE RESTRICT;');

sql_column_exists    ('storage_documents', 'steps_id'                     , '!ALTER TABLE `storage_documents` ADD COLUMN     `steps_id` INT(11) NULL DEFAULT NULL AFTER `processes_id`');
sql_index_exists     ('storage_documents', 'steps_id'                     , '!ALTER TABLE `storage_documents` ADD KEY        `steps_id` (`steps_id`)');
sql_foreignkey_exists('storage_documents', 'fk_storage_documents_steps_id', '!ALTER TABLE `storage_documents` ADD CONSTRAINT `fk_storage_documents_steps_id` FOREIGN KEY (`steps_id`) REFERENCES `progress_steps` (`id`) ON DELETE RESTRICT;');

sql_column_exists    ('customers', 'meta_id'             , '!ALTER TABLE `customers` ADD COLUMN     `meta_id` INT(11) NULL DEFAULT NULL AFTER `createdby`');
sql_index_exists     ('customers', 'meta_id'             , '!ALTER TABLE `customers` ADD KEY        `meta_id` (`meta_id`)');
sql_foreignkey_exists('customers', 'fk_customers_meta_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');

sql_index_exists ('customers', 'modifiedon', 'ALTER TABLE `customers` DROP KEY    `modifiedon`');
sql_column_exists('customers', 'modifiedon', 'ALTER TABLE `customers` DROP COLUMN `modifiedon`');

sql_foreignkey_exists('customers', 'fk_customers_modifiedby', 'ALTER TABLE `customers` DROP FOREIGN KEY `fk_customers_modifiedby`');
sql_index_exists ('customers', 'modifiedby', 'ALTER TABLE `customers` DROP KEY    `modifiedby`');
sql_column_exists('customers', 'modifiedby', 'ALTER TABLE `customers` DROP COLUMN `modifiedby`');

sql_column_exists('customers', 'email' , '!ALTER TABLE `customers` ADD COLUMN `email` VARCHAR(96) NULL DEFAULT NULL AFTER `company`');
sql_index_exists ('customers', 'email' , '!ALTER TABLE `customers` ADD KEY    `email` (`email`)');

sql_column_exists('customers', 'phones', '!ALTER TABLE `customers` ADD COLUMN `phones` VARCHAR(36) NULL DEFAULT NULL AFTER `email`');
sql_index_exists ('customers', 'phones', '!ALTER TABLE `customers` ADD KEY    `phones` (`phones`)');
?>