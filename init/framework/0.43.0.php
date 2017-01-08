<?php
/*
 * Add API call registry table
 */

sql_query('DROP TABLE IF EXISTS `api_calls`');
sql_query('DROP TABLE IF EXISTS `api_sessions`');

sql_query('CREATE TABLE `api_sessions` (`id`        INT(11)     NOT NULL AUTO_INCREMENT,
                                        `createdon` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `createdby` INT(11)         NULL DEFAULT NULL,
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
?>
