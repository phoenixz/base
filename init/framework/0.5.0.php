<?php
/*
 * Add "status" column
 */
sql_query('DROP TABLE IF EXISTS `rights`;');

sql_query('CREATE TABLE `rights` (`id`          INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                  `addedby`     TIMESTAMP         NULL,
                                  `addedon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  `name`        VARCHAR(16)   NOT NULL,
                                  `description` VARCHAR(2048) NOT NULL,

                                  INDEX (`addedby`),
                                  INDEX (`addedon`),
                                  UNIQUE(`name`)

                                 ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
