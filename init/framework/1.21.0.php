<?php
/*
 * Add support for ssh_fingerprint storage (knownhosts)
 */
sql_query('DROP TABLE IF EXISTS `ssh_fingerprints`');



sql_query('CREATE TABLE `ssh_fingerprints` (`id`          INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                            `createdon`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby`   INT(11)           NULL DEFAULT NULL,
                                            `meta_id`     INT(11)       NOT NULL,
                                            `status`      VARCHAR(16)       NULL DEFAULT NULL,
                                            `servers_id`  INT(11)           NULL,
                                            `hostname`    VARCHAR(64)   NOT NULL,
                                            `seohostname` VARCHAR(64)   NOT NULL,
                                            `port`        INT(11)       NOT NULL DEFAULT "22",
                                            `fingerprint` VARCHAR(4088) NOT NULL DEFAULT "",
                                            `algorithm`   VARCHAR(24)   NOT NULL DEFAULT "",

                                                   KEY `createdon`     (`createdon`),
                                                   KEY `createdby`     (`createdby`),
                                                   KEY `meta_id`       (`meta_id`),
                                                   KEY `status`        (`status`),
                                                   KEY `hostname`      (`hostname`),
                                            UNIQUE KEY `hostname_port` (`hostname`, `port`, `algorithm`),
                                            UNIQUE KEY `seohostname`   (`seohostname`),
                                                   KEY `port`          (`port`),
                                                   KEY `fingerprint`   (`fingerprint` (64)),

                                            CONSTRAINT `fk_ssh_fingerprints_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_ssh_fingerprints_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_ssh_fingerprints_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>