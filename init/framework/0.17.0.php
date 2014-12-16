<?php
/*
 * Improve blog key_values store
 */
sql_query('DROP TABLE IF EXISTS `blogs_key_value_descriptions`');



sql_query('CREATE TABLE `blogs_key_value_descriptions` (`id`           INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                                        `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                        `createdby`    INT(11)       NOT NULL,
                                                        `blogs_id`     INT(11)       NOT NULL,
                                                        `language`     VARCHAR(2)        NULL,
                                                        `key`          VARCHAR(16)       NULL,
                                                        `seovalue`     VARCHAR(128)      NULL,
                                                        `description1` VARCHAR(2048)     NULL,
                                                        `description2` VARCHAR(2048)     NULL,

                                                        INDEX (`blogs_id`),
                                                        INDEX (`language`),
                                                        INDEX (`key`),
                                                        INDEX (`seovalue`),
                                                        UNIQUE(`key`, `seovalue`),

                                                        CONSTRAINT `fk_blogs_key_value_descriptions_blogs_id` FOREIGN KEY (`blogs_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE

                                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



/*
 * Add language support for the entire blog system
 */
sql_column_exists('blogs'           , 'language', '!ALTER TABLE `blogs`            ADD COLUMN `language` VARCHAR(2) NOT NULL AFTER `status`');
sql_column_exists('blogs_categories', 'language', '!ALTER TABLE `blogs_categories` ADD COLUMN `language` VARCHAR(2) NOT NULL AFTER `status`');
sql_column_exists('blogs_posts'     , 'language', '!ALTER TABLE `blogs_posts`      ADD COLUMN `language` VARCHAR(2) NOT NULL AFTER `status`');

sql_index_exists ('blogs'           , 'language', '!ALTER TABLE `blogs`            ADD INDEX (`language`)');
sql_index_exists ('blogs_categories', 'language', '!ALTER TABLE `blogs_categories` ADD INDEX (`language`)');
sql_index_exists ('blogs_posts'     , 'language', '!ALTER TABLE `blogs_posts`      ADD INDEX (`language`)');
?>