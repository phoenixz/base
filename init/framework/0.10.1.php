<?php
sql_column_exists('blogs_posts', 'views'   , '!ALTER TABLE `blogs_posts` ADD COLUMN `views`    INT(11) NOT NULL AFTER `keywords`');
sql_column_exists('blogs_posts', 'rating'  , '!ALTER TABLE `blogs_posts` ADD COLUMN `rating`   INT(11) NOT NULL AFTER `views`');
sql_column_exists('blogs_posts', 'comments', '!ALTER TABLE `blogs_posts` ADD COLUMN `comments` INT(11) NOT NULL AFTER `rating`');

sql_index_exists('blogs_posts', 'views'   , '!ALTER TABLE `blogs_posts` ADD INDEX (`views`)');
sql_index_exists('blogs_posts', 'rating'  , '!ALTER TABLE `blogs_posts` ADD INDEX (`rating`)');
sql_index_exists('blogs_posts', 'comments', '!ALTER TABLE `blogs_posts` ADD INDEX (`comments`)');



/*
 * Add table to add images to a blog
 */
sql_query('DROP TABLE IF EXISTS `blogs_images`');
sql_query('DROP TABLE IF EXISTS `blogs_comments`');



sql_query('CREATE TABLE `blogs_images` (`id`             INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `createdon`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `createdby`      INT(11)     NOT NULL,
                                        `blogs_posts_id` INT(11)     NOT NULL,
                                        `url`            VARCHAR(64)     NULL,
                                        `description`    VARCHAR(64)     NULL,

                                        INDEX (`createdon`),
                                        INDEX (`createdby`),
                                        INDEX (`blogs_posts_id`),

                                        CONSTRAINT `fk_blogs_images_createdby`      FOREIGN KEY (`createdby`)      REFERENCES `users`       (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_blogs_images_blogs_posts_id` FOREIGN KEY (`blogs_posts_id`) REFERENCES `blogs_posts` (`id`) ON DELETE CASCADE

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `blogs_comments` (`id`             INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `createdon`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`      INT(11)         NULL,
                                          `blogs_posts_id` INT(11)     NOT NULL,
                                          `status`         VARCHAR(16)     NULL,
                                          `comment`        TEXT            NULL,

                                          INDEX (`createdon`),
                                          INDEX (`createdby`),
                                          INDEX (`blogs_posts_id`),
                                          INDEX (`status`),

                                          CONSTRAINT `fk_blogs_comments_createdby`      FOREIGN KEY (`createdby`)      REFERENCES `users`       (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_blogs_comments_blogs_posts_id` FOREIGN KEY (`blogs_posts_id`) REFERENCES `blogs_posts` (`id`) ON DELETE CASCADE

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
