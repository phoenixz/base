<?php
/*
 * Add timer library tables
 */
sql_query('DROP TABLE IF EXISTS `timers`');

sql_query('CREATE TABLE `timers` (`id`            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                  `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  `createdby`     INT(11)       NOT NULL,
                                  `start`         DATETIME          NULL,
                                  `stop`          DATETIME          NULL,
                                  `time`          INT(11)           NULL,
                                  `process`       VARCHAR(32)       NULL,

                                  INDEX (`createdon`),
                                  INDEX (`createdby`),
                                  INDEX (`start`),
                                  INDEX (`time`),
                                  INDEX (`process`)

                                 ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>