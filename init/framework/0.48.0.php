<?php
/*
 * Add api_accounts support
 */
sql_query('DROP TABLE IF EXISTS `users_social`');

sql_query('CREATE TABLE `users_social` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                        `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `modifiedon`   DATETIME          NULL DEFAULT NULL,
                                        `users_id`     INT(11)       NULL DEFAULT NULL,
                                        `provider`     ENUM ("facebook", "google", "twitter", "linkedin", "yahoo", "openid", "windows", "foursquare", "github", "instagram", "tumblr", "stripe", "steam", "yandex", "vimeo", "pixnet", "500px", "lastfm", "reddit") NOT NULL,
                                        `identifier`   INT(20)       NOT NULL,
                                        `email`        VARCHAR(128)      NULL,
                                        `phones`       VARCHAR(64)       NULL,
                                        `avatar_url`   VARCHAR(255)      NULL,
                                        `profile_url`  VARCHAR(32)       NULL,
                                        `website_url`  VARCHAR(32)       NULL,
                                        `display_name` VARCHAR(64)       NULL,
                                        `description`  VARCHAR(2047)     NULL,
                                        `first_name`   VARCHAR(64)       NULL,
                                        `last_name`    VARCHAR(64)       NULL,
                                        `gender`       VARCHAR(8)        NULL,
                                        `language`     VARCHAR(5)        NULL,
                                        `age`          INT(11)           NULL,
                                        `birthday`     DATETIME          NULL,
                                        `country`      VARCHAR(64)       NULL,
                                        `region`       VARCHAR(64)       NULL,
                                        `city`         VARCHAR(64)       NULL,
                                        `zip`          VARCHAR(6)        NULL,
                                        `job`          VARCHAR(64)       NULL,
                                        `organization` VARCHAR(64)       NULL,


                                        PRIMARY KEY `id`                  (`id`),
                                                KEY `createdon`           (`createdon`),
                                                KEY `modifiedon`          (`modifiedon`),
                                                KEY `users_id`            (`users_id`),
                                                KEY `provider`            (`provider`),
                                                KEY `email`               (`email`),
                                                KEY `identifier`          (`identifier`),
                                        UNIQUE  KEY `provider_identifier` (`provider`, `identifier`),
                                        UNIQUE  KEY `provider_email`      (`provider`, `email`),

                                        CONSTRAINT `users_social_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_column_exists('users', 'locale', 'ALTER TABLE `users` ADD COLUMN `locale` VARCHAR(6)');
?>
