<?php
/*
 * Add cURL cache table
 */
sql_query('DROP TABLE IF EXISTS `curl_cache`;');

sql_query('CREATE TABLE `curl_cache` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                      `addedon`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `users_id`    INT(11)          NULL,
                                      `url`         VARCHAR(255) NOT NULL,
                                      `data`        MEDIUMTEXT   NOT NULL,

                                      INDEX (`addedon`),
                                      INDEX (`users_id`),
                                      UNIQUE(`url`(32)),

                                      CONSTRAINT `fk_curl_cache_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
