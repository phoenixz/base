<?php
/*
 * Add mailer tables
 */
sql_table_exists('mailer_groups', 'DROP TABLE mailer');
sql_table_exists('mailer_groups', 'DROP TABLE mailer_groups');

sql_query('CREATE TABLE `mailer_mailings` (`id`            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                           `status`        VARCHAR(16)      NULL DEFAULT NULL,
                                           `addedon`       TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
                                           `updatedon`     DATETIME         NULL,
                                           `starton`       DATETIME         NULL,
                                           `startedon`     DATETIME         NULL,
                                           `finishedon`    DATETIME         NULL,
                                           `title`         VARCHAR(32)  NOT NULL,
                                           `header`        VARCHAR(255) NOT NULL,
                                           `template_file` VARCHAR(255) NOT NULL,
                                           `content_file`  VARCHAR(255) NOT NULL,
                                           `from`          TEXT         NOT NULL,
                                           `to`            TEXT         NOT NULL,

                                           INDEX(`status`),
                                           INDEX(`addedon`),
                                           INDEX(`startedon`),
                                           INDEX(`finishedon`),
                                           INDEX(`header`),
                                           INDEX(`title`)

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `mailer_recipients` (`id`            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                             `status`        VARCHAR(16)      NULL DEFAULT NULL,
                                             `addedon`       TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
                                             `updatedon`     DATETIME         NULL,
                                             `senton`        DATETIME         NULL,
                                             `mailings_id`   INT(11)      NOT NULL,
                                             `users_id`      INT(11)      NOT NULL,
                                             `code`          VARCHAR(32)  NOT NULL,
                                             `image`         VARCHAR(255) NOT NULL,

                                             INDEX(`status`),
                                             INDEX(`addedon`),
                                             INDEX(`updatedon`),
                                             INDEX(`mailings_id`),
                                             INDEX(`users_id`),

                                             CONSTRAINT `fk_mailer_users_id`    FOREIGN KEY (`users_id`)    REFERENCES `users`           (`id`) ON DELETE CASCADE,
                                             CONSTRAINT `fk_mailer_mailings_id` FOREIGN KEY (`mailings_id`) REFERENCES `mailer_mailings` (`id`) ON DELETE CASCADE

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `mailer_viewed` (`id`            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `addedon`       TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
                                         `recipients_id` INT(11)          NULL,
                                         `ip`            INT(11)          NULL,
                                         `host`          VARCHAR(255)     NULL,
                                         `referrer`      VARCHAR(255)     NULL,

                                         INDEX(`addedon`),
                                         INDEX(`recipients_id`),
                                         INDEX(`ip`),
                                         INDEX(`referrer`),

                                         CONSTRAINT `fk_mailer_access_recipients_id` FOREIGN KEY (`recipients_id`) REFERENCES `mailer_recipients` (`id`) ON DELETE CASCADE

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>