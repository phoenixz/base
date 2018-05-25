<?php
/*
 * Add required table for forwards library
 */
sql_query('DROP TABLE IF EXISTS `forwards`');



/*
 * Create  forwardings table
 */
sql_query('CREATE TABLE `forwards` (`id`          INT(11)      NOT NULL AUTO_INCREMENT,
                                    `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `createdby`   INT(11)          NULL,
                                    `modifiedon`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `modifiedby`  INT(11)          NULL,
                                    `servers_id`  INT(11)      NOT NULL,
                                    `source_id`   INT(11)      NOT NULL,
                                    `source_ip`   VARCHAR(15)  NOT NULL,
                                    `source_port` INT(11)      NOT NULL,
                                    `target_id`   INT(11)          NULL,
                                    `target_ip`   VARCHAR(15)      NULL,
                                    `target_port` INT(11)          NULL,
                                    `protocol`    ENUM("ssh", "http", "https", "smtp", "imap") NOT NULL,
                                    `description` VARCHAR(155)     NULL,
                                    `status`      VARCHAR(16)      NULL,

                                    PRIMARY KEY `id`         (`id`),
                                            KEY `servers_id` (`servers_id`),
                                            KEY `source_id`  (`source_id`),
                                            KEY `target_id`  (`source_id`),
                                            KEY `createdon`  (`createdon`),
                                            KEY `createdby`  (`createdby`),
                                            KEY `status`     (`status`),
                                         UNIQUE `forward`    (`servers_id`, `source_ip`, `source_port`, `target_ip`, `target_port`),

                                    CONSTRAINT `fk_forwards_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_forwards_source_id`  FOREIGN KEY (`source_id`)  REFERENCES `servers` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_forwards_target_id`  FOREIGN KEY (`target_id`)  REFERENCES `servers` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_forwards_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>