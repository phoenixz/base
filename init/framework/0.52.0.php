<?php
/*
 *
 */
sql_query('DROP TABLE IF EXISTS `ratings_votes`');
sql_query('DROP TABLE IF EXISTS `ratings`');

sql_query('CREATE TABLE `ratings` (`id`         INT(11)   NOT NULL AUTO_INCREMENT,
                                   `createdon`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   `modifiedon` DATETIME      NULL,
                                   `rating`     INT(11)   NOT NULL,

                                   PRIMARY KEY `id`         (`id`),
                                           KEY `createdon`  (`createdon`),
                                           KEY `modifiedon` (`modifiedon`)

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `ratings_votes` (`id`         INT(11)   NOT NULL AUTO_INCREMENT,
                                         `createdon`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby`  INT(11)   NOT NULL,
                                         `modifiedon` DATETIME      NULL DEFAULT NULL,
                                         `modifiedby` INT(11)       NULL DEFAULT NULL,
                                         `ratings_id` INT(11)   NOT NULL,
                                         `rating`     INT(11)   NULL DEFAULT NULL,

                                         PRIMARY KEY `id`         (`id`),
                                                 KEY `ratings_id` (`ratings_id`),
                                                 KEY `createdby`  (`createdby`),
                                                 KEY `modifiedby` (`modifiedby`),

                                         CONSTRAINT `fk_ratings_votes_ratings_id` FOREIGN KEY (`ratings_id`) REFERENCES `ratings` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_ratings_votes_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_ratings_votes_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users`   (`id`) ON DELETE RESTRICT

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
