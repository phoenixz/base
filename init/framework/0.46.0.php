<?php
/*
 * Upgraded CDN system support
 */
sql_foreignkey_exists('cdn_files', 'fk_cdn_files_servers_id', 'ALTER TABLE `cdn_files` DROP FOREIGN KEY `fk_cdn_files_servers_id`');

sql_query('DROP TABLE IF EXISTS `cdn_storage`');
sql_query('DROP TABLE IF EXISTS `cdn_objects`');
sql_query('DROP TABLE IF EXISTS `cdn_servers`');
sql_query('DROP TABLE IF EXISTS `cdn_projects`');



/*
 * Create tables
 */
sql_query('CREATE TABLE `cdn_projects` (`id`          INT(11)       NOT NULL AUTO_INCREMENT,
                                       `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `createdby`    INT(11)           NULL,
                                       `modifiedon`   DATETIME          NULL DEFAULT NULL,
                                       `modifiedby`   INT(11)           NULL DEFAULT NULL,
                                       `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                       `customers_id` INT(11)       NOT NULL,
                                       `users_id`     INT(11)       NOT NULL,
                                       `name`         VARCHAR(32)   NOT NULL,
                                       `seoname`      VARCHAR(32)   NOT NULL,
                                       `description`  VARCHAR(2047) NOT NULL,

                                       PRIMARY KEY `id`           (`id`),
                                               KEY `createdon`    (`createdon`),
                                               KEY `createdby`    (`createdby`),
                                               KEY `modifiedon`   (`modifiedon`),
                                               KEY `status`       (`status`),
                                               KEY `customers_id` (`customers_id`),
                                               KEY `users_id`     (`users_id`),
                                       UNIQUE  KEY `seoname`      (`seoname`),

                                       CONSTRAINT `fk_cdn_projects_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_cdn_projects_modifiedby`   FOREIGN KEY (`modifiedby`)   REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_cdn_projects_users_id`     FOREIGN KEY (`users_id`)     REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_cdn_projects_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `cdn_servers` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                       `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `createdby`    INT(11)           NULL,
                                       `modifiedon`   DATETIME          NULL DEFAULT NULL,
                                       `modifiedby`   INT(11)           NULL DEFAULT NULL,
                                       `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                       `domain`       VARCHAR(64)   NOT NULL,
                                       `root`         VARCHAR(64)   NOT NULL,
                                       `description`  VARCHAR(2047) NOT NULL,

                                       PRIMARY KEY `id`         (`id`),
                                               KEY `createdon`  (`createdon`),
                                               KEY `createdby`  (`createdby`),
                                               KEY `modifiedon` (`modifiedon`),
                                               KEY `status`     (`status`),
                                       UNIQUE  KEY `domain`     (`domain`),

                                       CONSTRAINT `fk_cdn_servers_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_cdn_servers_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `cdn_objects` (`id`          INT(11)      NOT NULL AUTO_INCREMENT,
                                       `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `projects_id` INT(11)      NOT NULL,
                                       `filesize`    INT(11)      NOT NULL,
                                       `url`         VARCHAR(127) NOT NULL,

                                       PRIMARY KEY `id`          (`id`),
                                               KEY `createdon`   (`createdon`),
                                               KEY `projects_id` (`projects_id`),
                                       UNIQUE  KEY `url`         (`url`),

                                       CONSTRAINT `fk_cdn_objects_projects_id` FOREIGN KEY (`projects_id`) REFERENCES `cdn_projects` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `cdn_storage` (`id`         INT(11)       NOT NULL AUTO_INCREMENT,
                                       `createdon`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `objects_id` INT(11)       NOT NULL,
                                       `servers_id` INT(11)       NOT NULL,

                                       PRIMARY KEY `id`           (`id`),
                                               KEY `createdon`    (`createdon`),
                                               KEY `objects_id`   (`objects_id`),
                                               KEY `servers_id`   (`servers_id`),
                                       UNIQUE  KEY `entry`        (`objects_id`,`servers_id`),

                                       CONSTRAINT `fk_cdn_storage_objects_id` FOREIGN KEY (`objects_id`) REFERENCES `cdn_objects` (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_cdn_storage_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `cdn_servers` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * MySQL 5.7 has a native DISTANCE() function, so our "DISTANCE()" function will
 * cause interference
 */
sql_function_exists('DISTANCE'     , 'DROP FUNCTION DISTANCE');
sql_function_exists('BASE_DISTANCE', 'DROP FUNCTION BASE_DISTANCE');

/*
 * This stored procedure was kindly provided by http://derickrethans.nl/spatial-indexes-mysql.html
 *
 * Basically it will return the distance for target lat / long from the specified source lat / long
 */
sql_query('CREATE FUNCTION BASE_DISTANCE (latA DOUBLE, lonA DOUBLE, latB DOUBLE, LonB DOUBLE)
               RETURNS DOUBLE DETERMINISTIC
           BEGIN
               SET @RlatA = radians(latA);
               SET @RlonA = radians(lonA);
               SET @RlatB = radians(latB);
               SET @RlonB = radians(LonB);
               SET @deltaLat = @RlatA - @RlatB;
               SET @deltaLon = @RlonA - @RlonB;
               SET @d = SIN(@deltaLat / 2) * SIN(@deltaLat / 2) +
                   COS(@RlatA) * COS(@RlatB) * SIN(@deltaLon / 2) * SIN(@deltaLon / 2);
               RETURN 2 * ASIN(SQRT(@d)) * 6371.01;
           END');
?>
