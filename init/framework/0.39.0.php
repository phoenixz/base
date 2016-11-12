<?php
/*
 * Manage ssh-keys to servers
 */
sql_query('DROP TABLE IF EXISTS `ssh_keys`');

sql_query('CREATE TABLE `ssh_keys` (`id`             INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                    `createdon`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `createdby`      INT(11)           NULL,
                                    `modifiedon`     DATETIME          NULL,
                                    `modifiedby`     INT(11)           NULL,
                                    `status`         VARCHAR(16)       NULL,
                                    `name`           VARCHAR(128)      NULL,
                                    `seoname`        VARCHAR(128)      NULL,
                                    `ssh_key`        VARCHAR(2047)     NULL,
                                    `description`    VARCHAR(2047)     NULL,

                                    INDEX (`createdon`),
                                    INDEX (`createdby`),
                                    INDEX (`modifiedon`),
                                    INDEX (`modifiedby`),
                                    INDEX (`status`),
                                    INDEX (`name`),
                                    INDEX (`seoname`)

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
