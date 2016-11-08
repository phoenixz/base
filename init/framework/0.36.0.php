<?php
/*
 *
 */
sql_foreignkey_exists('email_domains', 'fk_email_domains_servers_id', 'ALTER TABLE `email_domains` DROP FOREIGN KEY `fk_email_domains_servers_id`');


sql_query('DROP TABLE IF EXISTS `email_servers`');
sql_query('DROP TABLE IF EXISTS `domains_groups`');
sql_query('DROP TABLE IF EXISTS `domains_servers_links`');
sql_query('DROP TABLE IF EXISTS `domains`');
sql_query('DROP TABLE IF EXISTS `servers`');
sql_query('DROP TABLE IF EXISTS `providers`');
sql_query('DROP TABLE IF EXISTS `customers`');



sql_query('CREATE TABLE `customers` (`id`           INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                     `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `createdby`    INT(11)       NOT NULL,
                                     `modifiedon`   DATETIME          NULL DEFAULT NULL,
                                     `modifiedby`   INT(11)           NULL DEFAULT NULL,
                                     `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                     `name`         VARCHAR(32)   NOT NULL,
                                     `seoname`      VARCHAR(32)   NOT NULL,
                                     `url`          VARCHAR(255)  NOT NULL,
                                     `description`  VARCHAR(2047) NOT NULL,

                                     KEY        `createdon`  (`createdon`),
                                     KEY        `createdby`  (`createdby`),
                                     KEY        `modifiedon` (`modifiedon`),
                                     KEY        `modifiedby` (`modifiedby`),
                                     KEY        `status`     (`status`),
                                     UNIQUE KEY `seoname`    (`seoname`),
                                     KEY        `name`       (`name`),

                                     CONSTRAINT `fk_customers_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`),
                                     CONSTRAINT `fk_customers_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`)

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `providers` (`id`           INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                     `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `createdby`    INT(11)       NOT NULL,
                                     `modifiedon`   DATETIME          NULL DEFAULT NULL,
                                     `modifiedby`   INT(11)           NULL DEFAULT NULL,
                                     `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                     `name`         VARCHAR(32)   NOT NULL,
                                     `seoname`      VARCHAR(32)   NOT NULL,
                                     `url`          VARCHAR(255)  NOT NULL,
                                     `description`  VARCHAR(2047) NOT NULL,

                                     KEY        `createdon`    (`createdon`),
                                     KEY        `createdby`    (`createdby`),
                                     KEY        `modifiedon`   (`modifiedon`),
                                     KEY        `modifiedby`   (`modifiedby`),
                                     KEY        `status`       (`status`),
                                     UNIQUE KEY `seoname`      (`seoname`),
                                     KEY        `name`         (`name`),

                                     CONSTRAINT `fk_providers_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`),
                                     CONSTRAINT `fk_providers_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`)

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `servers` (`id`           INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                   `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   `createdby`    INT(11)       NOT NULL,
                                   `modifiedon`   DATETIME          NULL DEFAULT NULL,
                                   `modifiedby`   INT(11)           NULL DEFAULT NULL,
                                   `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                   `hostname`     VARCHAR(64)   NOT NULL,
                                   `seohostname`  VARCHAR(64)   NOT NULL,
                                   `user`         VARCHAR(64)   NOT NULL,
                                   `port`         INT(11)       NOT NULL,
                                   `cost`         DOUBLE(15,5)      NULL,
                                   `bill_duedate` DATETIME          NULL,
                                   `interval`     ENUM ("hourly", "daily", "weekly", "monthly", "bimonthly", "quarterly", "semiannual", "anually") NULL,
                                   `providers_id` INT(11)           NULL,
                                   `customers_id` INT(11)           NULL,
                                   `description`  VARCHAR(2047) NOT NULL,
                                   `web`          TINYINT       NOT NULL,
                                   `mail`         TINYINT       NOT NULL,
                                   `database`     TINYINT       NOT NULL,

                                   UNIQUE KEY `hostname`     (`hostname`),
                                   UNIQUE KEY `seohostname`  (`seohostname`),
                                   KEY        `createdon`    (`createdon`),
                                   KEY        `createdby`    (`createdby`),
                                   KEY        `modifiedon`   (`modifiedon`),
                                   KEY        `modifiedby`   (`modifiedby`),
                                   KEY        `status`       (`status`),
                                   KEY        `providers_id` (`providers_id`),
                                   KEY        `customers_id` (`customers_id`),
                                   KEY        `bill_duedate` (`bill_duedate`),
                                   KEY        `web`          (`web`),
                                   KEY        `mail`         (`mail`),
                                   KEY        `database`     (`database`),

                                   CONSTRAINT `fk_servers_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`),
                                   CONSTRAINT `fk_servers_modifiedby`   FOREIGN KEY (`modifiedby`)   REFERENCES `users`     (`id`),
                                   CONSTRAINT `fk_servers_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`),
                                   CONSTRAINT `fk_servers_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`)

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `domains` (`id`            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                   `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   `createdby`     INT(11)       NOT NULL,
                                   `modifiedon`    DATETIME          NULL DEFAULT NULL,
                                   `modifiedby`    INT(11)           NULL DEFAULT NULL,
                                   `status`        VARCHAR(16)       NULL DEFAULT NULL,
                                   `customers_id`  INT(11)           NULL DEFAULT NULL,
                                   `mx_domains_id` INT(11)           NULL,
                                   `domain`        VARCHAR(64)   NOT NULL,
                                   `seodomain`     VARCHAR(64)   NOT NULL,
                                   `ssh_hostname`  VARCHAR(64)       NULL DEFAULT NULL,
                                   `ssh_port`      INT(11)       NOT NULL DEFAULT 22,
                                   `ssh_user`      VARCHAR(32)       NULL DEFAULT NULL,
                                   `ssh_path`      VARCHAR(255)  NOT NULL,
                                   `description`   VARCHAR(2047)     NULL DEFAULT NULL,
                                   `web`           TINYINT       NOT NULL,
                                   `mail`          TINYINT       NOT NULL,

                                   KEY `createdon`    (`createdon`),
                                   KEY `createdby`    (`createdby`),
                                   KEY `modifiedon`   (`modifiedon`),
                                   KEY `modifiedby`   (`modifiedby`),
                                   KEY `status`       (`status`),
                                   KEY `customers_id` (`customers_id`),
                                   KEY `web`          (`web`),
                                   KEY `mail`         (`mail`),

                                   CONSTRAINT `fk_domains_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`),
                                   CONSTRAINT `fk_domains_modifiedby`   FOREIGN KEY (`modifiedby`)   REFERENCES `users`     (`id`),
                                   CONSTRAINT `fk_domains_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`)

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `domains_servers_links` (`servers_id`   INT(11) NOT NULL,
                                                 `customers_id` INT(11) NOT NULL,

                                                 PRIMARY KEY (`servers_id`, `customers_id`),

                                                 KEY `customers_id` (`customers_id`),
                                                 KEY `servers_id`   (`servers_id`),

                                                 CONSTRAINT `fk_domains_servers_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`),
                                                 CONSTRAINT `fk_domains_servers_servers_id`   FOREIGN KEY (`servers_id`)   REFERENCES `servers`   (`id`)

                                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `domains_groups` (`id`         INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `createdon`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`  INT(11)     NOT NULL,
                                          `modifiedon` DATETIME        NULL DEFAULT NULL,
                                          `modifiedby` INT(11)         NULL DEFAULT NULL,
                                          `name`       VARCHAR(64) NOT NULL,
                                          `seoname`    VARCHAR(64) NOT NULL,
                                          `status`     VARCHAR(16)     NULL DEFAULT NULL,

                                          UNIQUE KEY `seoname`   (`seoname`),
                                          KEY        `createdon` (`createdon`),
                                          KEY        `createdby` (`createdby`),

                                          CONSTRAINT `fk_domains_groups_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`)

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `email_servers` (`id`            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `createdon`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                         `createdby`     INT(11)          NULL DEFAULT NULL,
                                         `modifiedon`    DATETIME         NULL DEFAULT NULL,
                                         `modifiedby`    INT(11)          NULL DEFAULT NULL,
                                         `status`        VARCHAR(16)      NULL DEFAULT NULL,
                                         `servers_id`    INT(11)          NULL DEFAULT NULL,
                                         `domains_id`    INT(11)          NULL DEFAULT NULL,
                                         `domain`        VARCHAR(64)      NULL DEFAULT NULL,
                                         `smtp_port`     INT(11)      NOT NULL,
                                         `imap`          VARCHAR(160) NOT NULL,
                                         `poll_interval` INT(11)          NULL DEFAULT NULL,
                                         `header`        TEXT         NOT NULL,
                                         `footer`        TEXT         NOT NULL,
                                         `description`   TEXT         NOT NULL,

                                         KEY `createdon`  (`createdon`),
                                         KEY `createdby`  (`createdby`),
                                         KEY `modifiedon` (`modifiedon`),
                                         KEY `modifiedby` (`modifiedby`),
                                         KEY `status`     (`status`),
                                         KEY `domain`     (`domain`),
                                         KEY `servers_id` (`servers_id`),

                                         CONSTRAINT `fk_email_servers_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`),
                                         CONSTRAINT `fk_email_servers_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users`   (`id`),
                                         CONSTRAINT `fk_email_servers_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`)

                                        ) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4');

sql_column_exists('email_domains', 'servers_id', '!ALTER TABLE `email_domains` ADD COLUMN `servers_id` INT(11) AFTER `status`');
sql_index_exists('email_domains', 'servers_id', '!ALTER TABLE `email_domains` ADD INDEX (`servers_id`)');
sql_foreignkey_exists('email_domains', 'fk_email_domains_servers_id', '!ALTER TABLE `email_domains` ADD CONSTRAINT `fk_email_domains_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');
?>
