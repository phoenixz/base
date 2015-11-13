<?php
/*
 * Add emails table
 */
sql_query('DROP TABLE IF EXISTS `emails`');

sql_query('CREATE TABLE `emails` (`id`         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                  `createdon`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  `createdby`  INT(11)          NULL,
                                  `modifiedon` DATETIME         NULL,
                                  `modifiedby` INT(11)          NULL,
                                  `status`     VARCHAR(16)      NULL,
                                  `template`   VARCHAR(32)      NULL,
                                  `users_id`   INT(11)          NULL,
                                  `senton`     DATETIME         NULL,
                                  `subject`    VARCHAR(64)  NOT NULL,
                                  `from`       VARCHAR(64)  NOT NULL,
                                  `to`         VARCHAR(64)  NOT NULL,
                                  `body`       TEXT         NOT NULL,
                                  `format`     ENUM("html", "text") NOT NULL,

                                  INDEX (`createdon`),
                                  INDEX (`createdby`),
                                  INDEX (`modifiedon`),
                                  INDEX (`modifiedby`),
                                  INDEX (`senton`),
                                  INDEX (`status`),

                                  CONSTRAINT `fk_emails_users_id`   FOREIGN KEY (`users_id`)   REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                  CONSTRAINT `fk_emails_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                  CONSTRAINT `fk_emails_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                 ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
