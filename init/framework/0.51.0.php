<?php
/*
 *
 */
sql_foreignkey_exists ('users_social', 'users_social_users_id'   ,  'ALTER TABLE `users_social` DROP FOREIGN KEY `users_social_users_id`');
sql_foreignkey_exists ('users_social', 'fk_users_social_users_id',  'ALTER TABLE `users_social` DROP FOREIGN KEY `fk_users_social_users_id`');

sql_table_exists('users_sso', '!RENAME TABLE `users_social` TO `users_sso`');

sql_foreignkey_exists('users_sso', 'fk_users_sso_users_id', '!ALTER TABLE `users_sso` ADD CONSTRAINT `fk_users_sso_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');

sql_query('CREATE TABLE `users_social` (`id`         INT(11)      NOT NULL AUTO_INCREMENT,
                                        `createdon`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `modifiedon` DATETIME         NULL DEFAULT NULL,
                                        `users_id`   INT(11)          NULL DEFAULT NULL,
                                        `provider`   ENUM ("facebook", "google", "twitter", "linkedin", "yahoo", "openid", "windows", "foursquare", "github", "instagram", "tumblr", "stripe", "steam", "yandex", "vimeo", "pixnet", "500px", "lastfm", "reddit") NOT NULL,
                                        `name`       VARCHAR(32)      NULL,
                                        `url`        VARCHAR(255)     NULL,

                                        PRIMARY KEY `id`                (`id`),
                                                KEY `createdon`         (`createdon`),
                                                KEY `modifiedon`        (`modifiedon`),
                                                KEY `users_id`          (`users_id`),
                                                KEY `provider`          (`provider`),
                                        UNIQUE  KEY `users_id_provider` (`users_id`, `provider`),

                                        CONSTRAINT `fk_users_social_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
