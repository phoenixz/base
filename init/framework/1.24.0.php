<?php
/*
 * Add support for coupons library
 */
sql_query('DROP TABLE IF EXISTS `coupons_used`');
sql_query('DROP TABLE IF EXISTS `coupons`');



sql_query('CREATE TABLE `coupons` (`id`            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                   `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   `createdby`     INT(11)           NULL DEFAULT NULL,
                                   `meta_id`       INT(11)       NOT NULL,
                                   `status`        VARCHAR(16)       NULL DEFAULT NULL,
                                   `categories_id` INT(11)       NOT NULL,
                                   `code`          VARCHAR(16)   NOT NULL,
                                   `seocode`       VARCHAR(16)   NOT NULL,
                                   `reward`        VARCHAR(8)    NOT NULL,
                                   `description`   VARCHAR(2040) NOT NULL,

                                          KEY `createdon`     (`createdon`),
                                          KEY `createdby`     (`createdby`),
                                          KEY `meta_id`       (`meta_id`),
                                          KEY `status`        (`status`),
                                          KEY `categories_id` (`categories_id`),
                                          KEY `code`          (`code`),
                                   UNIQUE KEY `seocode`       (`seocode`),

                                   CONSTRAINT `fk_coupons_createdby`     FOREIGN KEY (`createdby`) REFERENCES `users`      (`id`) ON DELETE RESTRICT,
                                   CONSTRAINT `fk_coupons_meta_id`       FOREIGN KEY (`meta_id`)   REFERENCES `meta`       (`id`) ON DELETE RESTRICT,
                                   CONSTRAINT `fk_coupons_categories_id` FOREIGN KEY (`meta_id`)   REFERENCES `categories` (`id`) ON DELETE RESTRICT

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `coupons_used` (`id`         INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `createdon`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `createdby`  INT(11)         NULL DEFAULT NULL,
                                        `meta_id`    INT(11)     NOT NULL,
                                        `status`     VARCHAR(16)     NULL DEFAULT NULL,
                                        `coupons_id` INT(11)     NOT NULL,

                                               KEY `createdon`            (`createdon`),
                                               KEY `createdby`            (`createdby`),
                                               KEY `meta_id`              (`meta_id`),
                                               KEY `status`               (`status`),
                                               KEY `coupons_id`           (`coupons_id`),
                                        UNIQUE KEY `coupons_id_createdby` (`coupons_id`, `createdby`),

                                        CONSTRAINT `fk_coupons_used_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_coupons_used_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_coupons_used_coupons_id` FOREIGN KEY (`coupons_id`) REFERENCES `coupons` (`id`) ON DELETE RESTRICT

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>