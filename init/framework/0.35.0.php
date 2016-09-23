<?php
/*
 *
 */
sql_query('DROP TABLE IF EXISTS `unsubscribe`');



sql_query('CREATE TABLE `unsubscribe` (`id`         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                       `createdon`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `modifiedon` DATETIME         NULL,
                                       `modifiedby` INT(11)          NULL,
                                       `status`     VARCHAR(16)      NULL,
                                       `email`      VARCHAR(255) NOT NULL,

                                       INDEX (`createdon`),
                                       INDEX (`modifiedon`),
                                       INDEX (`modifiedby`),
                                       INDEX (`email`),

                                       CONSTRAINT `fk_cl_unsubscribe_mail_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT


                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
