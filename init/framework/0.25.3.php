<?php
/*
 * Add "groups" and "users_groups" tables!
 */
sql_query('DROP TABLE IF EXISTS `users_groups`');
sql_query('DROP TABLE IF EXISTS `groups`');

/*
 * Create "groups" table
 */
sql_query('CREATE TABLE `groups` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                  `createdon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  `createdby`   INT(11)     NOT NULL,
                                  `modifiedon`  DATETIME        NULL,
                                  `modifiedby`  INT(11)         NULL,
                                  `status`      VARCHAR(16)     NULL,
                                  `name`        VARCHAR(64)     NULL,
                                  `seoname`     VARCHAR(64)     NULL,
                                  `description` VARCHAR(255)    NULL,

                                  INDEX (`createdon`),
                                  INDEX (`createdby`),
                                  INDEX (`modifiedon`),
                                  INDEX (`modifiedby`),
                                  INDEX (`status`),
                                  UNIQUE(`name`),
                                  UNIQUE(`seoname`),

                                  CONSTRAINT `fk_groups_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                  CONSTRAINT `fk_groups_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * Create table users_groups
 */
sql_query('CREATE TABLE `users_groups` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `users_id`    INT(11)     NOT NULL,
                                        `groups_id`   INT(11)     NOT NULL,
                                        `name`        VARCHAR(64)     NULL,
                                        `seoname`     VARCHAR(64)     NULL,

                                        INDEX (`users_id`),
                                        INDEX (`groups_id`),
                                        UNIQUE(`users_id`, `groups_id`),

                                        CONSTRAINT `fk_users_groups_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users`  (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_users_groups_groups_id` FOREIGN KEY (`groups_id`) REFERENCES `groups` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
