<?php
/*
 * Add badges table
 */
sql_query('CREATE TABLE `badges`     (`id`             INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                      `createdon`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `createdby`      INT(11)         NULL,
                                      `status`         VARCHAR(16)     NULL,
                                      `user_id`        INT(11)     NOT NULL,

                                      `en`             VARCHAR(16)     NULL,
                                      `en_seo`         VARCHAR(16)     NULL,
                                      `en_description` TEXT            NULL,

                                      `es`             VARCHAR(16)     NULL,
                                      `es_seo`         VARCHAR(16)     NULL,
                                      `es_description` TEXT            NULL,

                                      `nl`             VARCHAR(16)     NULL,
                                      `nl_seo`         VARCHAR(16)     NULL,
                                      `nl_description` TEXT            NULL,

                                      UNIQUE(`user_id`, `en`),
                                      UNIQUE(`user_id`, `es`),
                                      UNIQUE(`user_id`, `nl`),
                                      INDEX (`createdon`),
                                      INDEX (`createdby`),
                                      INDEX (`status`),
                                      INDEX (`user_id`),
                                      INDEX (`en_seo`),
                                      INDEX (`es_seo`),
                                      INDEX (`nl_seo`),

                                      CONSTRAINT `fk_badges_user_id`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_badges_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                     ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * Add column badge on users table
 */
sql_column_exists('users', 'badges', '!ALTER TABLE `users` ADD COLUMN `badges` VARCHAR(1024) NOT NULL');
?>
