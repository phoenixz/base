<?php
sql_query('DROP TABLE IF EXISTS `synonyms`');



/*
 * This table keeps track of what users have what rights. The `name` column
 * is added extra here as this means we do not have to do a join when loading
 * these user rights
 */
sql_query('CREATE TABLE `synonyms` (`id`       INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                    `language` CHAR(2)     NOT NULL,
                                    `word`     VARCHAR(64) NOT NULL,
                                    `type`     VARCHAR(16) NOT NULL,
                                    `synonyms` TEXT        NOT NULL,

                                    INDEX (`language`),
                                    INDEX (`word`),
                                    INDEX (`language`,`word`),
                                    INDEX (`type`)

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
