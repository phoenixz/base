<?php
/*
 * Add authentication registration support
 *
 * Add users messages support
 */
sql_query('DROP TABLE IF EXISTS `authentications`');
sql_query('DROP TABLE IF EXISTS `users_messages`');

sql_query('CREATE TABLE `users_messages` (`id`               INT(11)      NOT NULL AUTO_INCREMENT,
                                          `createdon`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`        INT(11)      NOT NULL,
                                          `modifiedon`       DATETIME         NULL DEFAULT NULL,
                                          `modifiedby`       INT(11)          NULL DEFAULT NULL,
                                          `status`           VARCHAR(16)      NULL DEFAULT NULL,
                                          `users_id`         INT(11)      NOT NULL,
                                          `priority`         ENUM("low", "medium", "high") NOT NULL,
                                          `subject`          VARCHAR(255) NULL DEFAULT NULL,
                                          `message`          TEXT         NULL DEFAULT NULL,

                                          PRIMARY KEY `id`           (`id`),
                                                  KEY `createdon`    (`createdon`),
                                                  KEY `createdby`    (`createdby`),
                                                  KEY `modifiedon`   (`modifiedon`),
                                                  KEY `status`       (`status`),
                                                  KEY `priority`     (`priority`),
                                                  KEY `users_id`     (`users_id`),
                                                  KEY `subject`      (`subject`),

                                          CONSTRAINT `fk_users_messages_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_users_messages_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_users_messages_users_id`   FOREIGN KEY (`users_id`)   REFERENCES `users` (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `authentications` (`id`            INT(11)     NOT NULL AUTO_INCREMENT,
                                           `createdon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `createdby`     INT(11)         NULL,
                                           `status`        VARCHAR(16)     NULL DEFAULT NULL,
                                           `with_captcha`  INT(1)      NOT NULL DEFAULT NULL,
                                           `failed_reason` VARCHAR(64)     NULL DEFAULT NULL,
                                           `users_id`      INT(11)     NOT NULL,
                                           `ip`            VARCHAR(46),

                                           PRIMARY KEY `id`        (`id`),
                                                   KEY `createdon` (`createdon`),
                                                   KEY `createdby` (`createdby`),
                                                   KEY `status`    (`status`),
                                                   KEY `users_id`  (`users_id`),
                                                   KEY `ip`        (`ip`),

                                           CONSTRAINT `fk_authentications_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_authentications_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users` (`id`) ON DELETE RESTRICT

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
