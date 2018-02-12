libraries_access<?php
/*
 * New statistics library
 */
sql_query('DROP TABLE IF EXISTS `drivers_options`');
sql_query('DROP TABLE IF EXISTS `drivers_devices`');



sql_query('CREATE TABLE `drivers_devices` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                           `createdon`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `createdby`    INT(11)          NULL,
                                           `meta_id`      INT(11)          NULL,
                                           `status`       VARCHAR(16)      NULL,
                                           `type`         VARCHAR(32)      NULL,
                                           `manufacturer` VARCHAR(32)      NULL,
                                           `model`        VARCHAR(32)      NULL,
                                           `vendor`       VARCHAR(32)      NULL,
                                           `product`      VARCHAR(32)      NULL,
                                           `libusb`       VARCHAR(8)       NULL,
                                           `bus`          VARCHAR(8)       NULL,
                                           `device`       VARCHAR(8)       NULL,
                                           `string`       VARCHAR(32)      NULL,
                                           `default`      TINYINT(1)   NOT NULL,
                                           `description`  VARCHAR(255)     NULL,

                                           PRIMARY KEY `id`           (`id`),
                                                   KEY `createdon`    (`createdon`),
                                                   KEY `createdby`    (`createdby`),
                                                   KEY `type`         (`type`),
                                                   KEY `meta_id`      (`meta_id`),
                                                   KEY `manufacturer` (`manufacturer`),
                                                   KEY `model`        (`model`),
                                                   KEY `vendor`       (`vendor`),
                                                   KEY `product`      (`product`),
                                           UNIQUE  KEY `libusb`       (`libusb`),
                                                   KEY `bus`          (`bus`),
                                                   KEY `device`       (`device`),
                                                   KEY `default`      (`default`),
                                           UNIQUE  KEY `string`       (`string`),
                                           UNIQUE  KEY `default_type` (`default`, `type`),

                                           CONSTRAINT `fk_drivers_devices_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_drivers_devices_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `drivers_options` (`id`         INT(11)     NOT NULL AUTO_INCREMENT,
                                           `devices_id` INT(11)     NOT NULL,
                                           `key`        VARCHAR(32)     NULL,
                                           `value`      VARCHAR(32)     NULL,
                                           `default`    TINYINT(1)      NULL,

                                           PRIMARY KEY `id`         (`id`),
                                                   KEY `devices_id` (`devices_id`),
                                                   KEY `key`        (`key`),
                                                   KEY `default`    (`default`),

                                           CONSTRAINT `fk_drivers_options_id` FOREIGN KEY (`devices_id`) REFERENCES `drivers_devices` (`id`) ON DELETE CASCADE

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>