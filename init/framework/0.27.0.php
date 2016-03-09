<?php
/*
 * Add SMS images support
 */
sql_query('DROP TABLE IF EXISTS `sms_images`');



sql_query('CREATE TABLE `sms_images` (`id`              INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                      `sms_messages_id` INT(11)      NOT NULL,
                                      `downloaded`      DATETIME         NULL,
                                      `url`             VARCHAR(255) NOT NULL,
                                      `file`            VARCHAR(255)     NULL,

                                      INDEX (`sms_messages_id`),
                                      INDEX (`downloaded`),

                                      CONSTRAINT `fk_sms_images_messages_id` FOREIGN KEY (`sms_messages_id`) REFERENCES `sms_messages` (`id`) ON DELETE CASCADE

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
