<?php
/*
 * Adding support for domain keyword scanner
 */
sql_query('DROP TABLE IF EXISTS `domains_keywords`');



/*
 *
 */
sql_query('CREATE TABLE `domains_keywords` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                            `createdon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby`   INT(11)         NULL DEFAULT NULL,
                                            `meta_id`     INT(11)         NULL DEFAULT NULL,
                                            `status`      VARCHAR(16)     NULL DEFAULT NULL,
                                            `keyword`     VARCHAR(64) NOT NULL DEFAULT "",
                                            `seokeyword`  VARCHAR(64) NOT NULL DEFAULT "",
                                            `tld`         VARCHAR(64)     NULL,

                                                   KEY `createdon`  (`createdon`),
                                                   KEY `createdby`  (`createdby`),
                                                   KEY `meta_id`    (`meta_id`),
                                                   KEY `status`     (`status`),
                                                   KEY `tld`        (`tld`),
                                            UNIQUE KEY `keyword`    (`keyword`),
                                            UNIQUE KEY `seokeyword` (`seokeyword`),

                                            CONSTRAINT `fk_domains_keywords_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_domains_keywords_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>