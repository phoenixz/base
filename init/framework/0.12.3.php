<?php
/*
 * Fix invalid priorities, status, etc.
 */
sql_query('UPDATE `blogs_posts` SET `priority` = 1 WHERE `priority` IS NULL OR `priority` = 0;');



/*
 * Fix blogs posts name sizes
 */
sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `name`    `name`    VARCHAR(255) NOT NULL;');
sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `seoname` `seoname` VARCHAR(255) NOT NULL;');



/*
 * Add new columns "groups_id" and "group" to blogs_posts
 */
sql_column_exists('blogs_posts', 'groups_id', '!ALTER TABLE `blogs_posts` ADD COLUMN `groups_id` INT(11)     NULL AFTER category;');
sql_column_exists('blogs_posts', 'group'    , '!ALTER TABLE `blogs_posts` ADD COLUMN `group`     VARCHAR(64) NULL AFTER groups_id;');

sql_index_exists('blogs_posts', 'groups_id', '!ALTER TABLE `blogs_posts` ADD INDEX(`groups_id`);');
sql_index_exists('blogs_posts', 'group'    , '!ALTER TABLE `blogs_posts` ADD INDEX(`group`    );');

sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_groups_id', '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_groups_id` FOREIGN KEY (`groups_id`) REFERENCES `blogs_categories` (`id`) ON DELETE RESTRICT;');



/*
 * Add table to keep track of blog updates
 */
sql_query('DROP TABLE IF EXISTS `blogs_updates`');

sql_query('CREATE TABLE `blogs_updates` (`id`             INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `createdon`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby`      INT(11)          NULL,
                                         `blogs_posts_id` INT(11)          NULL,
                                         `action`         VARCHAR(16)  NOT NULL,

                                         INDEX (`createdon`),
                                         INDEX (`createdby`),
                                         INDEX (`action`),
                                         INDEX (`blogs_posts_id`),

                                         CONSTRAINT `fk_blogs_updates_createdby` FOREIGN KEY (`createdby`)       REFERENCES `users`       (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_blogs_updates_blogs_id`  FOREIGN KEY (`blogs_posts_id`)  REFERENCES `blogs_posts` (`id`) ON DELETE CASCADE

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



?>
