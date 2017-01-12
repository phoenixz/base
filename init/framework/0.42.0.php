<?php
/*
 * Add link forwarder tables
 *
 * Add advertisement manager tables
 */

sql_query('DROP TABLE IF EXISTS `ads_views`');
sql_query('DROP TABLE IF EXISTS `ads_images`');
sql_query('DROP TABLE IF EXISTS `ads_campaigns`');

sql_query('DROP TABLE IF EXISTS `forwarder_clicks`');
sql_query('DROP TABLE IF EXISTS `forwarder_links`');
sql_query('DROP TABLE IF EXISTS `forwarder_clusters`');



 /*
  * Create link forwarder tables
  */
sql_query('CREATE TABLE `forwarder_clusters` (`id`          INT(11) NOT NULL AUTO_INCREMENT,
                                              `createdon`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `createdby`   INT(11) DEFAULT NULL,
                                              `modifiedon`  DATETIME DEFAULT NULL,
                                              `modifiedby`  INT(11) DEFAULT NULL,
                                              `status`      VARCHAR(16) DEFAULT NULL,
                                              `assigned_to` INT(11) DEFAULT NULL,
                                              `name`        VARCHAR(32) DEFAULT NULL,
                                              `seoname`     VARCHAR(32) DEFAULT NULL,
                                              `keyword`     VARCHAR(32) DEFAULT NULL,

                                              PRIMARY KEY (`id`),
                                              UNIQUE KEY `seoname` (`seoname`),
                                              UNIQUE KEY `name` (`name`),
                                              UNIQUE KEY `keyword` (`keyword`),
                                              KEY `createdon` (`createdon`),
                                              KEY `createdby` (`createdby`),
                                              KEY `modifiedon` (`modifiedon`),
                                              KEY `modifiedby` (`modifiedby`),
                                              KEY `assigned_to` (`assigned_to`),

                                              CONSTRAINT `fk_forwarder_clusters_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
                                              CONSTRAINT `fk_forwarder_clusters_createdby`   FOREIGN KEY (`createdby`) REFERENCES `users` (`id`),
                                              CONSTRAINT `fk_forwarder_clusters_modifiedby`  FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`)

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `forwarder_links` (`id`                     INT(11) NOT NULL AUTO_INCREMENT,
                                              `createdon`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `createdby`           INT(11) DEFAULT NULL,
                                              `modifiedon`          DATETIME DEFAULT NULL,
                                              `modifiedby`          INT(11) DEFAULT NULL,
                                              `status`              VARCHAR(16) DEFAULT NULL,
                                              `clusters_id`         INT(11) NOT NULL,
                                              `name`                VARCHAR(32) DEFAULT NULL,
                                              `seoname`             VARCHAR(32) DEFAULT NULL,
                                              `priority`            INT(11) DEFAULT NULL,
                                              `total_count`         INT(11) NOT NULL,
                                              `available_count`     INT(11) NOT NULL,
                                              `url`                 VARCHAR(255) DEFAULT NULL,
                                              `required_browser`    VARCHAR(16) DEFAULT NULL,
                                              `denied_browser`      VARCHAR(16) DEFAULT NULL,
                                              `mobile_restrictions` ENUM("android","iphone","windowws","") NOT NULL,

                                              PRIMARY KEY (`id`),
                                              KEY `createdon`        (`createdon`),
                                              KEY `createdby`        (`createdby`),
                                              KEY `modifiedon`       (`modifiedon`),
                                              KEY `modifiedby`       (`modifiedby`),
                                              KEY `status`           (`status`),
                                              KEY `available_count`  (`available_count`),
                                              KEY `required_browser` (`required_browser`),
                                              KEY `denied_browser`   (`denied_browser`),
                                              KEY `total_count`      (`total_count`),
                                              KEY `clusters_id`      (`clusters_id`),
                                              KEY `name`             (`clusters_id`,`name`),
                                              KEY `seoname`          (`clusters_id`,`seoname`),

                                              CONSTRAINT `fk_forwarder_links_clusters_id` FOREIGN KEY (`clusters_id`) REFERENCES `forwarder_clusters` (`id`),
                                              CONSTRAINT `fk_forwarder_links_createdby`   FOREIGN KEY (`createdby`)   REFERENCES `users`              (`id`),
                                              CONSTRAINT `fk_forwarder_links_modifiedby`  FOREIGN KEY (`modifiedby`)  REFERENCES `users`              (`id`)

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `forwarder_clicks` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                            `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby`    INT(11)           NULL,
                                            `emails_id`    INT(11)           NULL DEFAULT NULL,
                                            `clusters_id`  INT(11)           NULL DEFAULT NULL,
                                            `links_id`     INT(11)           NULL DEFAULT NULL,
                                            `email`        VARCHAR(128)      NULL DEFAULT NULL,
                                            `ip`           VARCHAR(64)   NOT NULL,
                                            `reverse_host` VARCHAR(255)      NULL DEFAULT NULL,
                                            `latitude`     decimal(10,7)     NULL DEFAULT NULL,
                                            `longitude`    decimal(10,7)     NULL DEFAULT NULL,
                                            `referrer`     VARCHAR(255)      NULL DEFAULT NULL,
                                            `user_agent`   VARCHAR(255)      NULL DEFAULT NULL,
                                            `browser`      VARCHAR(12)   NOT NULL,

                                            PRIMARY KEY           (`id`),
                                            KEY `createdon`       (`createdon`),
                                            KEY `emails_id`       (`emails_id`),
                                            KEY `email`           (`email`),
                                            KEY `ip`              (`ip`),
                                            KEY `links_id`        (`links_id`),
                                            KEY `clusters_id`     (`clusters_id`),

                                            CONSTRAINT `fk_forwarder_clicks_clusters_id` FOREIGN KEY (`clusters_id`) REFERENCES `forwarder_clusters` (`id`),
                                            CONSTRAINT `fk_forwarder_clicks_links_id`    FOREIGN KEY (`links_id`)    REFERENCES `forwarder_links`    (`id`),
                                            CONSTRAINT `fk_forwarder_clicks_createdby`   FOREIGN KEY (`createdby`)   REFERENCES `users`              (`id`)

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Create ad management tables
 */
sql_query('CREATE TABLE `ads_campaigns` (`id`          INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                         `createdon`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby`   INT(11)       NOT NULL,
                                         `modifiedon`  DATETIME          NULL DEFAULT NULL,
                                         `modifiedby`  INT(11)           NULL DEFAULT NULL,
                                         `status`      VARCHAR(16)       NULL DEFAULT NULL,
                                         `from`        DATETIME          NULL DEFAULT NULL,
                                         `until`       DATETIME          NULL DEFAULT NULL,
                                         `clusters_id` INT(11)       NOT NULL,
                                         `name`        VARCHAR(64)   NOT NULL,
                                         `seoname`     VARCHAR(64)   NOT NULL,
                                         `description` VARCHAR(2047)     NULL DEFAULT NULL,

                                         KEY        `createdon`   (`createdon`),
                                         KEY        `createdby`   (`createdby`),
                                         KEY        `modifiedon`  (`modifiedon`),
                                         KEY        `modifiedby`  (`modifiedby`),
                                         KEY        `status`      (`status`),
                                         KEY        `from`        (`from`),
                                         KEY        `until`       (`until`),
                                         KEY        `clusters_id` (`clusters_id`),
                                         UNIQUE KEY `seoname`     (`seoname`),

                                         CONSTRAINT `fk_ads_campaigns_createdby`   FOREIGN KEY (`createdby`)   REFERENCES `users`              (`id`),
                                         CONSTRAINT `fk_ads_campaigns_modifiedby`  FOREIGN KEY (`modifiedby`)  REFERENCES `users`              (`id`),
                                         CONSTRAINT `fk_ads_campaigns_clusters_id` FOREIGN KEY (`clusters_id`) REFERENCES `forwarder_clusters` (`id`)

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `ads_images` (`id`           INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                      `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `createdby`    INT(11)       NOT NULL,
                                      `campaigns_id` INT(11)       NOT NULL,
                                      `file`         VARCHAR(128)  NOT NULL,
                                      `description`  VARCHAR(2047)     NULL DEFAULT NULL,

                                      KEY        `createdon`    (`createdon`),
                                      KEY        `createdby`    (`createdby`),
                                      UNIQUE KEY `file`         (`file`),
                                      KEY        `campaigns_id` (`campaigns_id`),

                                      CONSTRAINT `fk_ads_images_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`         (`id`),
                                      CONSTRAINT `fk_ads_images_campaigns_id` FOREIGN KEY (`campaigns_id`) REFERENCES `ads_campaigns` (`id`)

                                     ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `ads_views` (`id`           INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                     `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `createdby`    INT(11)           NULL,
                                     `campaigns_id` INT(11)           NULL DEFAULT NULL,
                                     `images_id`    INT(11)           NULL DEFAULT NULL,
                                     `ip`           VARCHAR(64)   NOT NULL,
                                     `reverse_host` VARCHAR(255)      NULL DEFAULT NULL,
                                     `latitude`     DECIMAL(10,7)     NULL DEFAULT NULL,
                                     `longitude`    DECIMAL(10,7)     NULL DEFAULT NULL,
                                     `referrer`     VARCHAR(255)      NULL DEFAULT NULL,
                                     `user_agent`   VARCHAR(255)      NULL DEFAULT NULL,
                                     `browser`      VARCHAR(12)   NOT NULL,

                                     KEY `createdon`    (`createdon`),
                                     KEY `createdby`    (`createdby`),
                                     KEY `campaigns_id` (`campaigns_id`),
                                     KEY `images_id`    (`images_id`),
                                     KEY `ip`           (`ip`),

                                     CONSTRAINT `fk_ads_views_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`         (`id`),
                                     CONSTRAINT `fk_ads_views_campaigns_id` FOREIGN KEY (`campaigns_id`) REFERENCES `ads_campaigns` (`id`),
                                     CONSTRAINT `fk_ads_views_images_id`    FOREIGN KEY (`images_id`)    REFERENCES `ads_images`    (`id`)

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
