<?php
/*
 * Manage email conversations
 */
sql_query('DROP TABLE IF EXISTS `email_messages`');
sql_query('DROP TABLE IF EXISTS `email_conversations`');
sql_query('DROP TABLE IF EXISTS `email_users`');



sql_query('CREATE TABLE `email_users` (`id`             INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                       `createdon`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `createdby`      INT(11)       NOT NULL,
                                       `status`         VARCHAR(16)       NULL,
                                       `users_id`       INT(11)       NOT NULL,
                                       `email`          VARCHAR(64)   NOT NULL,
                                       `password`       VARCHAR(64)   NOT NULL,

                                       INDEX (`createdon`),
                                       INDEX (`createdby`),
                                       INDEX (`email`),
                                       INDEX (`users_id`),

                                       CONSTRAINT `fk_email_users_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users` (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `fk_email_users_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



sql_query('CREATE TABLE `email_conversations` (`id`             INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                               `createdon`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `modifiedon`     DATETIME          NULL,
                                               `status`         VARCHAR(16)       NULL,
                                               `from`           VARCHAR(64)   NOT NULL,
                                               `to`             VARCHAR(64)   NOT NULL,
                                               `users_id`       INT(11)           NULL,
                                               `last_reply`     DATETIME          NULL,
                                               `last_direction` VARCHAR(8)    NOT NULL,
                                               `subject`        VARCHAR(255)  NOT NULL,
                                               `direction`      VARCHAR(8)        NULL,
                                               `repliedon`      DATETIME          NULL,
                                               `last_messages`  VARCHAR(2048) NOT NULL,

                                               INDEX (`createdon`),
                                               INDEX (`modifiedon`),
                                               INDEX (`last_reply`),
                                               INDEX (`last_direction`),
                                               INDEX (`to`),
                                               INDEX (`from`),
                                               INDEX (`direction`),
                                               INDEX (`subject`),
                                               INDEX (`status`),
                                               INDEX (`users_id`),
                                               UNIQUE(`from`, `to`),

                                               CONSTRAINT `fk_email_conversations_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

                                              ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



sql_query('CREATE TABLE `email_messages` (`id`               INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `createdon`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `status`           VARCHAR(16)      NULL,
                                          `direction`        VARCHAR(8)       NULL,
                                          `conversations_id` INT(11)          NULL,
                                          `reply_to_id`      INT(11)          NULL,

                                          `from`             VARCHAR(64)  NOT NULL,
                                          `to`               VARCHAR(64)  NOT NULL,
                                          `users_id`         INT(11)          NULL,

                                          `date`             DATETIME     NOT NULL,
                                          `message_id`       VARCHAR(128)     NULL,
                                          `size`             INT(11)      NOT NULL,
                                          `uid`              INT(11)          NULL,
                                          `msgno`            INT(11)          NULL,
                                          `recent`           INT(11)          NULL,
                                          `flagged`          INT(11)          NULL,
                                          `answered`         INT(11)          NULL,
                                          `deleted`          INT(11)          NULL,
                                          `seen`             INT(11)          NULL,
                                          `draft`            INT(11)          NULL,
                                          `udate`            INT(11)          NULL,

                                          `subject`          VARCHAR(255) NOT NULL,
                                          `text`             TEXT         NOT NULL,
                                          `html`             TEXT         NOT NULL,

                                           INDEX (`createdon`),
                                           INDEX (`from`),
                                           INDEX (`to`),
                                           INDEX (`direction`),
                                           INDEX (`date`),
                                           INDEX (`size`),
                                           INDEX (`status`),
                                           INDEX (`reply_to_id`),
                                           INDEX (`conversations_id`),

                                           CONSTRAINT `fk_email_messages_users_id`         FOREIGN KEY (`users_id`)         REFERENCES `users`               (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `fk_email_messages_reply_to_id`      FOREIGN KEY (`reply_to_id`)      REFERENCES `email_messages`      (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `fk_email_messages_conversations_id` FOREIGN KEY (`conversations_id`) REFERENCES `email_conversations` (`id`) ON DELETE CASCADE

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');
?>