<?php
/*
 * Add white label domain support
 */
sql_query('DROP TABLE IF EXISTS `domains`;');

sql_query('CREATE TABLE `domains` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                   `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   `createdby`   INT(11)          NULL,
                                   `modifiedon`  DATETIME         NULL,
                                   `modifiedby`  INT(11)          NULL,
                                   `status`      VARCHAR(16)      NULL,
                                   `users_id`    INT(11)      NOT NULL,
                                   `name`        VARCHAR(64)  NOT NULL,
                                   `domain`      VARCHAR(128) NOT NULL,

                                   INDEX (`createdon`),
                                   INDEX (`createdby`),
                                   INDEX (`modifiedon`),
                                   INDEX (`modifiedby`),
                                   INDEX (`status`),
                                   UNIQUE(`name`),
                                   UNIQUE(`domain`),
                                   UNIQUE(`users_id`),

                                   CONSTRAINT `fk_domains_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                   CONSTRAINT `fk_domains_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                   CONSTRAINT `fk_domains_users_id`   FOREIGN KEY (`users_id`)   REFERENCES `users` (`id`) ON DELETE RESTRICT

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
