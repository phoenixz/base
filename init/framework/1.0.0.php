<?php
/*
 * Tasks library tables
 *
 * tasks is a library that can execute generic tasks in the background, and store results
 */
sql_query('DROP TABLE IF EXISTS `tasks`');

sql_query('CREATE TABLE `tasks` (`id`          INT(11)                                              NOT NULL AUTO_INCREMENT,
                                 `createdon`   TIMESTAMP                                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 `executedon`  DATETIME                                                 NULL,
                                 `createdby`   INT(11)                                                  NULL,
                                 `meta_id`     INT(11)                                                  NULL,
                                 `after`       DATETIME                                                 NULL,
                                 `method`      ENUM("background", "internal", "normal", "function")     NULL,
                                 `status`      VARCHAR(16)                                              NULL,
                                 `parents_id`  INT(11)                                                  NULL,
                                 `time_limit`  INT(11)                                                  NULL,
                                 `time_spent`  DOUBLE(6, 6)                                             NULL,
                                 `command`     VARCHAR(32)                                              NULL,
                                 `executed`    VARCHAR(255)                                             NULL,
                                 `description` VARCHAR(2047)                                            NULL,
                                 `data`        BLOB                                                     NULL,
                                 `results`     BLOB                                                     NULL,

                                 PRIMARY KEY `id`         (`id`),
                                         KEY `createdon`  (`createdon`),
                                         KEY `executedon` (`executedon`),
                                         KEY `meta_id`    (`meta_id`),
                                         KEY `createdby`  (`createdby`),
                                         KEY `status`     (`status`),
                                         KEY `command`    (`command`),
                                         KEY `method`     (`method`),
                                         KEY `time_spent` (`time_spent`),
                                         KEY `parents_id` (`parents_id`),

                                 CONSTRAINT `fk_tasks_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                 CONSTRAINT `fk_tasks_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                 CONSTRAINT `fk_tasks_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE

                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('ALTER TABLE `passwords` MODIFY COLUMN `createdby` INT(11) NULL');
?>
