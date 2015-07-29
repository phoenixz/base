<?php
sql_query('DROP TABLE IF EXISTS `rip_settings`');

sql_query('CREATE TABLE `rip_settings` (`id`            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `addedon`       TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
                                        `updatedon`     DATETIME         NULL,
                                        `status`        VARCHAR(16)      NULL DEFAULT NULL,
                                        `accept_timout` INT(11)      NOT NULL,
                                        `submit_timout` INT(11)      NOT NULL

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
