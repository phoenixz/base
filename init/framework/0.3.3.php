<?php
sql_query('DROP TABLE IF EXISTS `extended_logins`;');

sql_query('CREATE TABLE `extended_logins` (`users_id` INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                           `added`    DATETIME     NOT NULL,
                                           `ip`       VARCHAR(15)  NOT NULL,
                                           `code`     VARCHAR(255) NOT NULL,

                                           INDEX (`users_id`),
                                           INDEX (`added`),
                                           INDEX (`ip`),
                                           UNIQUE(`code`(32))

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
