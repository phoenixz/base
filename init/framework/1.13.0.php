<?php

sql_query('DROP TABLE IF EXISTS `forwardings`');

/*
 * Create  forwardings table
 */
sql_query('CREATE TABLE `forwardings` (  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                         `createdon`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby`    INT(11)      NULL,
                                         `modifiedon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `modifiedby`   INT(11)      NULL,
                                         `source_ip`    VARCHAR(16)  NOT NULL,
                                         `source_port`  INT(11)      NOT NULL,
                                         `source_id`    INT(11)      NOT NULL,
                                         `target_ip`    VARCHAR(16)  NULL,
                                         `target_port`  INT(11)      NULL,
                                         `target_id`    INT(11)      NULL,
                                         `protocol`     ENUM("ssh", "http", "https", "smtp", "imap") NOT NULL,
                                         `description`  VARCHAR(155) NULL,
                                         `status`       VARCHAR(16)  NULL,

                                         PRIMARY KEY `id`        (`id`),
                                                 KEY `source_id` (`source_id`),
                                                 KEY `target_id` (`source_id`),
                                                 KEY `createdon` (`createdon`),
                                                 KEY `createdby` (`createdby`),
                                                 KEY `status`    (`status`),

                                         CONSTRAINT `fk_forwardings_source_id` FOREIGN KEY (`source_id`)   REFERENCES `servers`  (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_forwardings_target_id` FOREIGN KEY (`target_id`)   REFERENCES `servers`  (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_forwardings_createdby` FOREIGN KEY (`createdby`) REFERENCES `users`      (`id`) ON DELETE RESTRICT

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>