<?php
sql_query('DROP TABLE IF EXISTS `users_rights`');



/*
 * This table keeps track of what users have what rights. The `name` column
 * is added extra here as this means we do not have to do a join when loading
 * these user rights
 */
sql_query('CREATE TABLE `users_rights` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `addedon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `addedby`     INT(11)     NOT NULL,
                                        `users_id`    INT(11)     NOT NULL,
                                        `rights_id`   INT(11)     NOT NULL,
                                        `name`        VARCHAR(32)     NULL,

                                        INDEX (`addedon`),
                                        INDEX (`addedby`),
                                        INDEX (`users_id`),
                                        INDEX (`rights_id`),
                                        INDEX (`name`),
                                        UNIQUE(`users_id`,`rights_id`),

                                        CONSTRAINT `fk_users_rights_addedby`   FOREIGN KEY (`addedby`)   REFERENCES `users`  (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_users_rights_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users`  (`id`) ON DELETE CASCADE,
                                        CONSTRAINT `fk_users_rights_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `rights` (`id`) ON DELETE CASCADE

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
