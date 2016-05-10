<?php
/*
 * Add basic scraper tool tables
 */
sql_query('DROP TABLE IF EXISTS `scraper_urls`');



/*
 * URL's that have to be scraped, or already have been scraped (cache)
 */
sql_query('CREATE TABLE `scraper_urls` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `createdon`   TIMESTAMP    NOT NULL,
                                        `createdby`   INT(11)          NULL,
                                        `modifiedon`  DATETIME         NULL,
                                        `modifiedby`  INT(11)          NULL,
                                        `expires`     DATETIME         NULL,
                                        `status`      VARCHAR(16)      NULL,
                                        `priority`    INT(11)          NULL,
                                        `url`         VARCHAR(1023)    NULL,
                                        `http_code`   INT(11)          NULL,
                                        `data`        MEDIUMTEXT       NULL,

                                        INDEX (`createdon`),
                                        INDEX (`createdby`),
                                        INDEX (`modifiedon`),
                                        INDEX (`modifiedby`),
                                        INDEX (`expires`),
                                        INDEX (`status`),
                                        INDEX (`url` (128)),
                                        INDEX (`priority`),
                                        INDEX (`http_code`),

                                        CONSTRAINT `fk_scraper_urls_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_scraper_urls_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
