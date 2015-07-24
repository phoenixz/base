<?php
/*
 * Add GEO-IP support
 */
sql_query('DROP TABLE IF EXISTS `geoip_location`');
sql_query('DROP TABLE IF EXISTS `geoip_blocks`');



sql_query('CREATE TABLE `geoip_blocks` (`startIpNum` INT(11) UNSIGNED NOT NULL,
                                        `endIpNum`   INT(11) UNSIGNED NOT NULL PRIMARY KEY,
                                        `locId`      INT(11) UNSIGNED NOT NULL,

                                        INDEX (`startIpNum`),
                                        INDEX (`locId`)

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geoip_location` (`locId`      INT(11)     NOT NULL PRIMARY KEY,
                                          `country`    VARCHAR(2)  NOT NULL,
                                          `region`     VARCHAR(2)  NOT NULL,
                                          `city`       VARCHAR(50)     NULL,
                                          `postalCode` VARCHAR(5)  NOT NULL,
                                          `latitude`   FLOAT           NULL,
                                          `longitude`  FLOAT           NULL,
                                          `dmaCode`    INT(11)         NULL,
                                          `areaCode`   INT(11)         NULL,

                                          INDEX (`country`),
                                          INDEX (`region`),
                                          INDEX (`city`),
                                          INDEX (`latitude`),
                                          INDEX (`longitude`)

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
