<?php
/*
 * Fix domains table for domains management
 */
sql_query('DROP TABLE IF EXISTS `domains`;');

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
?>
