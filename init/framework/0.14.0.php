<?php
/*
 * Fix missing seocategory column
 */
sql_column_exists('blogs_posts', 'seocategory', '!ALTER TABLE `blogs_posts` ADD COLUMN `seocategory` VARCHAR(64) NULL AFTER `categories_id`');
sql_index_exists ('blogs_posts', 'seocategory', '!ALTER TABLE `blogs_posts` ADD INDEX(`seocategory`)');



/*
 * This table is a key-value store for the blog pages
 */
sql_query('DROP TABLE IF EXISTS `blogs_key_value`');



sql_query('CREATE TABLE `blogs_key_value` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                           `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `blogs_id`    INT(11)          NULL,
                                           `key`         VARCHAR(16)      NULL,
                                           `value`       VARCHAR(128)     NULL,

                                           INDEX (`createdon`),
                                           INDEX (`blogs_id`),
                                           INDEX (`key`),

                                           CONSTRAINT `fk_blogs_key_value_blogs_id`  FOREIGN KEY (`blogs_id`)  REFERENCES `blogs` (`id`) ON DELETE CASCADE

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');
?>
