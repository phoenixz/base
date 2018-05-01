<?php
/*
 * Add SMS number blocking table
 */
sql_query('DROP TABLE IF EXISTS `sms_blocks`');

sql_query('CREATE TABLE `sms_blocks` (`id`        INT(11)      NOT NULL AUTO_INCREMENT,
                                      `createdon` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `createdby` INT(11)          NULL,
                                      `meta_id`   INT(11)      NOT NULL,
                                      `status`    VARCHAR(16)      NULL,
                                      `number`    VARCHAR(16)      NULL,

                                      PRIMARY KEY `id`        (`id`),
                                              KEY `meta_id`   (`meta_id`),
                                              KEY `createdon` (`createdon`),
                                              KEY `createdby` (`createdby`),
                                              KEY `status`    (`status`),
                                              KEY `number`    (`number`),

                                      CONSTRAINT `fk_sms_numbers_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_sms_numbers_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>