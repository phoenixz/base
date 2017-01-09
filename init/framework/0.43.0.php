<?php
/*
 * Add API call registry table
 */

sql_query('DROP TABLE IF EXISTS `api_calls`');
sql_query('DROP TABLE IF EXISTS `api_sessions`');
sql_query('DROP TABLE IF EXISTS `email_client_accounts`');
sql_query('DROP TABLE IF EXISTS `email_client_domains`');

sql_query('CREATE TABLE `api_sessions` (`id`        INT(11)     NOT NULL AUTO_INCREMENT,
                                        `createdon` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `createdby` INT(11)         NULL DEFAULT NULL,
                                        `closedon`  DATETIME        NULL DEFAULT NULL,
                                        `ip`        INT(11)         NULL DEFAULT NULL,
                                        `apikey`    VARCHAR(64)     NULL DEFAULT NULL,

                                        PRIMARY KEY `id`           (`id`),
                                                KEY `createdon`    (`createdon`),
                                                KEY `createdby`    (`createdby`),
                                                KEY `ip`           (`ip`),

                                        CONSTRAINT `fk_api_sessions_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`)

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `api_calls` (`id`          INT(11)     NOT NULL AUTO_INCREMENT,
                                     `createdon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `sessions_id` INT(11)         NULL DEFAULT NULL,
                                     `time`        FLOAT(10.2)     NULL DEFAULT NULL,
                                     `call`        VARCHAR(32)     NULL DEFAULT NULL,
                                     `result`      VARCHAR(16)     NULL DEFAULT NULL,

                                     PRIMARY KEY `id`                    (`id`),
                                             KEY `sessions_id`           (`sessions_id`),
                                             KEY `time`                  (`time`),
                                             KEY `call`                  (`call`),
                                             KEY `result`                (`result`),
                                             KEY `sessions_id_call_time` (`sessions_id`, `call`, `time`),

                                     CONSTRAINT `fk_api_calls_sessions_id` FOREIGN KEY (`sessions_id`) REFERENCES `api_sessions` (`id`)

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 *
 */
sql_query('CREATE TABLE `email_client_domains` (`id`                INT(11)      NOT NULL AUTO_INCREMENT,
                                                `createdon`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                                `createdby`         INT(11)          NULL DEFAULT NULL,
                                                `modifiedon`        DATETIME         NULL DEFAULT NULL,
                                                `modifiedby`        INT(11)          NULL DEFAULT NULL,
                                                `status`            VARCHAR(16)      NULL DEFAULT NULL,
                                                `server_domains_id` INT(11)          NULL,
                                                `name`              VARCHAR(96)      NULL DEFAULT NULL,
                                                `seoname`           VARCHAR(96)      NULL DEFAULT NULL,
                                                `smtp_host`         VARCHAR(128)     NULL DEFAULT NULL,
                                                `smtp_port`         INT(11)      NOT NULL,
                                                `imap`              VARCHAR(160) NOT NULL,
                                                `poll_interval`     INT(11)          NULL DEFAULT NULL,
                                                `header`            TEXT,
                                                `footer`            TEXT,
                                                `description`       TEXT,

                                                PRIMARY KEY                     (`id`),
                                                        KEY `createdon`         (`createdon`),
                                                        KEY `createdby`         (`createdby`),
                                                        KEY `modifiedon`        (`modifiedon`),
                                                        KEY `modifiedby`        (`modifiedby`),
                                                        KEY `status`            (`status`),
                                                        KEY `seoname`           (`seoname`),
                                                        KEY `server_domains_id` (`server_domains_id`),


                                                CONSTRAINT `fk_email_client_domains_server_domains_id` FOREIGN KEY (`server_domains_id`) REFERENCES `email_domains` (`id`),
                                                CONSTRAINT `fk_email_client_domains_createdby`         FOREIGN KEY (`createdby`)         REFERENCES `users`         (`id`),
                                                CONSTRAINT `fk_email_client_domains_modifiedby`        FOREIGN KEY (`modifiedby`)        REFERENCES `users`         (`id`)

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `email_client_accounts` (`id`            INT(11)      NOT NULL AUTO_INCREMENT,
                                                 `createdon`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                                 `createdby`     INT(11)          NULL DEFAULT NULL,
                                                 `modifiedon`    DATETIME         NULL DEFAULT NULL,
                                                 `modifiedby`    INT(11)          NULL DEFAULT NULL,
                                                 `status`        VARCHAR(16)      NULL DEFAULT NULL,
                                                 `domains_id`    INT(11)          NULL DEFAULT NULL,
                                                 `users_id`      INT(11)          NULL DEFAULT NULL,
                                                 `poll_interval` INT(11)      NOT NULL,
                                                 `last_poll`     DATETIME         NULL DEFAULT NULL,
                                                 `email`         VARCHAR(128) NOT NULL,
                                                 `seoemail`      VARCHAR(128) NOT NULL,
                                                 `name`          VARCHAR(32)  NOT NULL,
                                                 `password`      VARCHAR(128)     NULL DEFAULT NULL,
                                                 `header`        TEXT,
                                                 `footer`        TEXT,
                                                 `description`   TEXT,

                                                 PRIMARY KEY `id`            (`id`),
                                                         KEY `createdon`     (`createdon`),
                                                         KEY `createdby`     (`createdby`),
                                                         KEY `modifiedon`    (`modifiedon`),
                                                         KEY `modifiedby`    (`modifiedby`),
                                                         KEY `status`        (`status`),
                                                         KEY `name`          (`name`),
                                                         KEY `email`         (`email`),
                                                         KEY `seoemail`      (`seoemail`),
                                                         KEY `domains_id`    (`domains_id`),
                                                         KEY `poll_interval` (`poll_interval`),
                                                         KEY `last_poll`     (`last_poll`),

                                                 CONSTRAINT `fk_email_client_accounts_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`                (`id`),
                                                 CONSTRAINT `fk_email_client_accounts_domains_id` FOREIGN KEY (`domains_id`) REFERENCES `email_client_domains` (`id`),
                                                 CONSTRAINT `fk_email_client_accounts_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users`                (`id`)

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
