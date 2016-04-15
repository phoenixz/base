<?php
/*
 * Fix blog posts status column, add very needed email_subscriptions
 */
sql_query('DROP TABLE IF EXISTS `email_subscriptions`');



sql_query('CREATE TABLE `email_subscriptions` (`id`        INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                               `createdon` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `status`    VARCHAR(16)       NULL,
                                               `ip`        VARCHAR(15)   NOT NULL,
                                               `email`     VARCHAR(255)  NOT NULL,

                                               INDEX (`createdon`),
                                               INDEX (`ip`),
                                               INDEX (`status`)

                                              ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');


sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `status` `status` VARCHAR(16) NULL;');
?>
