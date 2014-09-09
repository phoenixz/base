<?php
/*
 * First BASE framework init file
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <so.oostenbrink@gmail.com>, Johan Geuze
 */

/*
 * Clear data store
 */
file_delete_tree(ROOT.'data/pub');

mkdir(ROOT.'data/pub', $_CONFIG['fs']['dir_mode'], true);



/*
 * Setup custom.php and custom_admin.php
 */
if(!file_exists(ROOT.'libs/custom.php')){
    copy(ROOT.'libs/_custom.php'      , ROOT.'libs/custom.php');
    log_console('Created custom.php file', 'created', 'green');
}

if(!file_exists(ROOT.'libs/custom_admin.php')){
    copy(ROOT.'libs/_custom_admin.php', ROOT.'libs/custom_admin.php');
    log_console('Created custom_admin.php file', 'created', 'green');
}



/*
 * Create database tables
 */
sql_query('DROP TABLE IF EXISTS `log`;');
sql_query('DROP TABLE IF EXISTS `users`;');
sql_query('DROP TABLE IF EXISTS `versions`;');



sql_query('CREATE TABLE `versions` (`id`        INT(11)    NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                    `added`     TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `framework` VARCHAR(8) NOT NULL,
                                    `project`   VARCHAR(8) NOT NULL,

                                    INDEX (`added`),
                                    INDEX (`framework`),
                                    INDEX (`project`),
                                    UNIQUE(`framework`, `project`)

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');


sql_query('CREATE TABLE `log` (`id`       INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                               `added`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                               `users_id` INT(11)           NULL,
                               `type`     VARCHAR(32)   NOT NULL,
                               `message`  VARCHAR(1024)     NULL,

                               INDEX(`added`),
                               INDEX(`type`),
                               INDEX(`users_id`)

                              ) ENGINE=MyISAM AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');


log_database('Started system initialization', 'init', 0);


sql_query('CREATE TABLE `users` (`id`           INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                 `date_added`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 `username`     VARCHAR(255) NOT NULL,
                                 `password`     VARCHAR(64)  NOT NULL,
                                 `name`         VARCHAR(255) NOT NULL,
                                 `avatar`       VARCHAR(255)     NULL,
                                 `email`        VARCHAR(255) NOT NULL,
                                 `validated`   VARCHAR(128)     NULL,
                                 `fb_id`        BIGINT(20)       NULL,
                                 `fb_token`     VARCHAR(255)     NULL,
                                 `language`     CHAR(2)          NULL,
                                 `gender`       INT(11)          NULL,
                                 `bd_day`       INT(11)          NULL,
                                 `bd_month`     INT(11)          NULL,
                                 `bd_year`      INT(11)          NULL,
                                 `country`      VARCHAR(64)      NULL,

                                 UNIQUE(`name`),
                                 UNIQUE(`email`),
                                 INDEX (`validated`),
                                 INDEX (`language`),
                                 INDEX (`country`),
                                 UNIQUE(`fb_id`)

                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');
?>
