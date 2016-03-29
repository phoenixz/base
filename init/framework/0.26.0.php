<?php
/*
 * Add html_img info cache table
 */
sql_query('DROP TABLE IF EXISTS `html_img`');

/*
 * Create "groups" table
 */
sql_query('CREATE TABLE `html_img` (`id`         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                    `createdon`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `modifiedon` TIMESTAMP    NOT NULL,
                                    `status`     VARCHAR(16)      NULL,
                                    `url`        VARCHAR(255)     NULL,
                                    `height`     INT(11)      NOT NULL,
                                    `width`      INT(11)      NOT NULL,

                                     INDEX (`createdon`),
                                     UNIQUE(`url`(32))

                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
