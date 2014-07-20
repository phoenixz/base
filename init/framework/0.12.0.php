<?php
/*
 * Add contactus table
 */
sql_query('DROP TABLE IF EXISTS `persons_names`');
sql_query('DROP TABLE IF EXISTS `sitemap_data`');
sql_query('DROP TABLE IF EXISTS `sitemap_builds`');



sql_query('CREATE TABLE `sitemap_builds` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`   INT(11)          NULL,
                                          `status`      VARCHAR(16)      NULL,
                                          `build_time`  INT(11)          NULL,
                                          `language`    VARCHAR(2)   NOT NULL,

                                          INDEX (`createdon`),
                                          INDEX (`createdby`),
                                          INDEX (`status`),
                                          INDEX (`build_time`),
                                          INDEX (`language`),

                                          CONSTRAINT `fk_sitemap_builds_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



sql_query('CREATE TABLE `sitemap_data` (`id`            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `builds_id`     INT(11)      NOT NULL,
                                        `status`        VARCHAR(16)      NULL,
                                        `file`          VARCHAR(64)  NOT NULL,
                                        `file_original` VARCHAR(64)      NULL,
                                        `type`          VARCHAR(6)   NOT NULL,
                                        `modified`      DATETIME     NOT NULL,
                                        `priority`      INT(11)      NOT NULL,
                                        `changefreq`    ENUM("always", "hourly", "daily", "weekly", "monthly", "yearly", "never") NOT NULL,
                                        `description`   VARCHAR(155) NOT NULL,

                                        INDEX (`builds_id`),
                                        INDEX (`status`),
                                        INDEX (`file`),
                                        INDEX (`file_original`),
                                        INDEX (`type`),
                                        INDEX (`modified`),
                                        INDEX (`priority`),
                                        INDEX (`changefreq`),
                                        INDEX (`builds_id`, `file`),

                                        CONSTRAINT `fk_sitemap_data_builds_id`  FOREIGN KEY (`builds_id`)  REFERENCES `sitemap_builds` (`id`) ON DELETE CASCADE

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



sql_query('CREATE TABLE `persons_names` (`id`              INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `language`        VARCHAR(2)  NOT NULL,
                                         `male`            VARCHAR(16)     NULL,
                                         `female`          VARCHAR(16)     NULL,
                                         `last`            VARCHAR(16)     NULL,
                                         `male_priority`   INT(11)         NULL,
                                         `female_priority` INT(11)         NULL,
                                         `last_priority`   INT(11)         NULL,

                                         INDEX (`language`),
                                         INDEX (`male`),
                                         INDEX (`female`),
                                         INDEX (`last`),
                                         INDEX (`male_priority`),
                                         INDEX (`female_priority`),
                                         INDEX (`last_priority`)

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');
?>
