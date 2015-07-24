<?php
/*
 * Add GEO tables
 */
sql_query('DROP TABLE IF EXISTS `extended_sessions`;');

sql_query('CREATE TABLE `extended_sessions` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                             `addedon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                             `users_id`    INT(11)     NOT NULL,
                                             `session_key` VARCHAR(64) NOT NULL,
                                             `ip`          INT(11)     NOT NULL,

                                              INDEX (`addedon`),
                                              INDEX (`users_id`),
                                              UNIQUE(`session_key`),
                                              INDEX (`ip`),

                                              CONSTRAINT `fk_extended_logins_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>