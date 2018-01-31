<?php
/*
 * Add the buks library table, the Base version of the linux LUKS system
 *
 * Add fingerprint support to users table
 */
sql_query('DROP TABLE IF EXISTS `buks`;');

sql_query('CREATE TABLE `buks` (`id`       INT(11)       NOT NULL AUTO_INCREMENT,
                                `meta_id`  INT(11)           NULL,
                                `status`   VARCHAR(16)       NULL,
                                `users_id` INT(11)           NULL,
                                `name`     VARCHAR(16)       NULL,
                                `key`      VARCHAR(8912)     NULL,

                                PRIMARY KEY `id`       (`id`),
                                        KEY `meta_id`  (`meta_id`),
                                        KEY `users_id` (`users_id`),
                                        KEY `name`     (`name`),

                                CONSTRAINT `fk_buks_meta_id`    FOREIGN KEY (`meta_id`)  REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                CONSTRAINT `fk_buks_users_id`   FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_column_exists('users', 'fingerprint', '!ALTER TABLE `users` ADD COLUMN `fingerprint` DATETIME NULL AFTER `password`');
sql_index_exists ('users', 'fingerprint', '!ALTER TABLE `users` ADD KEY    `fingerprint` (`fingerprint`)');
?>