<?php
/*
 * Add API call registry table
 */

sql_query('DROP TABLE IF EXISTS `sitemap_data`');
sql_query('DROP TABLE IF EXISTS `sitemap_scans`');

sql_query('DROP TABLE IF EXISTS `sitemaps_data`');
sql_query('DROP TABLE IF EXISTS `sitemaps_generated`');

sql_query('CREATE TABLE `sitemaps_data` (`id`               INT(11)      NOT NULL AUTO_INCREMENT,
                                         `createdon`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby`        INT(11)          NULL DEFAULT NULL,
                                         `modifiedon`       DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
                                         `modifiedby`       INT(11)          NULL DEFAULT NULL,
                                         `status`           VARCHAR(16)      NULL DEFAULT NULL,
                                         `url`              VARCHAR(255)     NULL DEFAULT NULL,
                                         `priority`         FLOAT(2,2)       NULL DEFAULT NULL,
                                         `page_modifiedon`  DATETIME     NOT NULL,
                                         `change_frequency` ENUM ("always", "hourly", "daily", "weekly", "monthly", "yearly", "never") NOT NULL,
                                         `language`         VARCHAR(2)       NULL DEFAULT NULL,
                                         `group`            VARCHAR(16)      NULL DEFAULT NULL,
                                         `file`             VARCHAR(16)      NULL DEFAULT NULL,

                                         PRIMARY KEY `id`           (`id`),
                                                 KEY `createdon`    (`createdon`),
                                                 KEY `createdby`    (`createdby`),
                                                 KEY `modifiedon`   (`modifiedon`),
                                                 KEY `status`       (`status`),
                                                 KEY `priority`     (`priority`),

                                         CONSTRAINT `fk_sitemaps_data_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`),
                                         CONSTRAINT `fk_sitemaps_data_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`)

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `sitemaps_generated` (`id`        INT(11)      NOT NULL AUTO_INCREMENT,
                                              `createdon` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `createdby` INT(11)          NULL DEFAULT NULL,
                                              `language`  VARCHAR(255)     NULL DEFAULT NULL,
                                              `file`      VARCHAR(255)     NULL DEFAULT NULL,

                                              PRIMARY KEY `id`        (`id`),
                                                      KEY `createdon` (`createdon`),
                                                      KEY `createdby` (`createdby`),
                                                      KEY `language`  (`language`),
                                                      KEY `file`      (`file`),

                                              CONSTRAINT `fk_sitemaps_generated_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`)

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



?>
