<?php
/*
 * CDN command storage table
 */
sql_query('DROP TABLE IF EXISTS `cdn_commands`');



sql_query('CREATE TABLE `cdn_commands` (`id`             INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `createdon`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `status`         VARCHAR(16)       NULL,
                                        `command`        VARCHAR(16)   NOT NULL,
                                        `data`           VARCHAR(255)  NOT NULL,

                                        INDEX (`createdon`),
                                        INDEX (`command`),
                                        INDEX (`status`)

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
