<?php
/*
 * Adding support for sql connector data storage in main database
 */
sql_query('DROP TABLE IF EXISTS `sql_connectors`');



/*
 *
 */
sql_query('CREATE TABLE `sql_connectors` (`id`                     INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `createdon`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`              INT(11)          NULL DEFAULT NULL,
                                          `meta_id`                INT(11)      NOT NULL,
                                          `status`                 VARCHAR(16)      NULL DEFAULT NULL,
                                          `name`                   VARCHAR(32)  NOT NULL,
                                          `seoname`                VARCHAR(32)  NOT NULL,
                                          `servers_id`             INT(11)          NULL,
                                          `hostname`               VARCHAR(64)  NOT NULL,
                                          `driver`                 VARCHAR(8)   NOT NULL,
                                          `database`               VARCHAR(32)  NOT NULL DEFAULT "",
                                          `user`                   VARCHAR(32)  NOT NULL DEFAULT "",
                                          `password`               VARCHAR(32)  NOT NULL DEFAULT "",
                                          `autoincrement`          INT(11)      NOT NULL DEFAULT 0,
                                          `buffered`               TINYINT(1)   NOT NULL DEFAULT 0,
                                          `charset`                VARCHAR(12)  NOT NULL DEFAULT "",
                                          `collate`                VARCHAR(32)  NOT NULL DEFAULT "",
                                          `limit_max`              INT(11)      NOT NULL DEFAULT 0,
                                          `mode`                   VARCHAR(255) NOT NULL DEFAULT "",
                                          `ssh_tunnel_required`    TINYINT(1)   NOT NULL DEFAULT 0,
                                          `ssh_tunnel_source_port` INT(11)      NOT NULL DEFAULT 0,
                                          `ssh_tunnel_hostname`    VARCHAR(253) NOT NULL DEFAULT "",
                                          `usleep`                 INT(11)      NOT NULL DEFAULT 0,
                                          `pdo_attributes`         VARCHAR(511) NOT NULL DEFAULT "",
                                          `timezone`               VARCHAR(64)  NOT NULL DEFAULT "",
                                          `version`                VARCHAR(11)  NOT NULL DEFAULT "",

                                                 KEY `createdon` (`createdon`),
                                                 KEY `createdby` (`createdby`),
                                                 KEY `meta_id`   (`meta_id`),
                                                 KEY `status`    (`status`),
                                                 KEY `name`      (`name`),
                                          UNIQUE KEY `seoname`   (`seoname`),

                                          CONSTRAINT `fk_sql_connectors_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_sql_connectors_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_sql_connectors_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>