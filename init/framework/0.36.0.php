<?php
/*
 *
 */
sql_query('DROP TABLE IF EXISTS `domains_groups`');
sql_query('DROP TABLE IF EXISTS `domains`');
sql_query('DROP TABLE IF EXISTS `servers`');
sql_query('DROP TABLE IF EXISTS `hosting_providers`');
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

sql_query('CREATE TABLE `hosting_providers` (`id`           INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
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

                                            CONSTRAINT `fk_hosting_providers_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`),
                                            CONSTRAINT `fk_hosting_providers_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`)

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

                                   CONSTRAINT `fk_servers_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`),
                                   CONSTRAINT `fk_servers_modifiedby`   FOREIGN KEY (`modifiedby`)   REFERENCES `users`     (`id`),
                                   CONSTRAINT `fk_servers_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`),
                                   CONSTRAINT `fk_servers_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`)

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `domains` (`id`           INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                                   `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   `createdby`    INT(11)       NOT NULL,
                                   `modifiedon`   DATETIME          NULL DEFAULT NULL,
                                   `modifiedby`   INT(11)           NULL DEFAULT NULL,
                                   `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                   `domain`       VARCHAR(64)   NOT NULL,
                                   `seodomain`    VARCHAR(64)   NOT NULL,
                                   `ssh_hostname` VARCHAR(64)       NULL DEFAULT NULL,
                                   `ssh_port`     INT(11)           NULL DEFAULT NULL,
                                   `ssh_user`     VARCHAR(32)       NULL DEFAULT NULL,
                                   `ssh_path`     VARCHAR(255)  NOT NULL,
                                   `db_host`      VARCHAR(64)   NOT NULL,
                                   `db_name`      VARCHAR(64)   NOT NULL,
                                   `db_user`      VARCHAR(64)   NOT NULL,
                                   `db_pass`      VARCHAR(64)   NOT NULL,
                                   `description`  VARCHAR(2047)     NULL DEFAULT NULL,
                                   `users_id`     INT(11)           NULL DEFAULT NULL,
                                   `servers_id`   INT(11)           NULL DEFAULT NULL,

                                   KEY `createdon`  (`createdon`),
                                   KEY `createdby`  (`createdby`),
                                   KEY `modifiedon` (`modifiedon`),
                                   KEY `modifiedby` (`modifiedby`),
                                   KEY `status`     (`status`),
                                   KEY `users_id`   (`users_id`),
                                   KEY `servers_id` (`servers_id`),

                                   CONSTRAINT `fk_domains_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`),
                                   CONSTRAINT `fk_domains_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`),
                                   CONSTRAINT `fk_domains_users_id`   FOREIGN KEY (`users_id`)   REFERENCES `users` (`id`)

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
?>
