<?php
/*
 * Create email_files table
 */
sql_query('DROP TABLE IF EXISTS `email_files`');

sql_query('CREATE TABLE `email_files` (`id`                INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                       `email_messages_id` INT(11)         NULL,
                                       `file_cid`          VARCHAR(64)     NULL,
                                       `file`              VARCHAR(64) NOT NULL,

                                       INDEX(`email_messages_id`),

                                       CONSTRAINT `fk_email_files_email_messages_id` FOREIGN KEY (`email_messages_id`) REFERENCES `email_messages` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
