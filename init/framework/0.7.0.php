<?php
/*
 * Setup the tables for the notification system
 */
sql_query('DROP TABLE IF EXISTS `notifications`;');
sql_query('DROP TABLE IF EXISTS `notifications_members`;');
sql_query('DROP TABLE IF EXISTS `notifications_classes`;');



/*
 * This table keeps track of classes. Notifications sent to a specific class will automatically use
 * the specified notification methods (notify by email, prowl, etc), and all members of that class
 * will receive the notification by all specified methods
 */
sql_query('CREATE TABLE `notifications_classes` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                                 `addedon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                 `addedby`     INT(11)     NOT NULL,
                                                 `updatedon`   DATETIME        NULL,
                                                 `updatedby`   INT(11)         NULL,
                                                 `status`      VARCHAR(16)     NULL,
                                                 `name`        VARCHAR(32)     NULL,
                                                 `description` TEXT            NULL,
                                                 `methods`     VARCHAR(255)    NULL,

                                                 INDEX (`addedon`),
                                                 INDEX (`addedby`),
                                                 INDEX (`updatedon`),
                                                 INDEX (`updatedby`),
                                                 INDEX (`status`),
                                                 INDEX (`name`),
                                                 UNIQUE(`addedby`,`name`),

                                                 CONSTRAINT `fk_notifications_classes_addedby`   FOREIGN KEY (`addedby`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
                                                 CONSTRAINT `fk_notifications_classes_updatedby` FOREIGN KEY (`updatedby`) REFERENCES `users` (`id`) ON DELETE SET NULL

                                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * This table keeps track of what users are members of what classes
 */
sql_query('CREATE TABLE `notifications_members` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                                 `addedon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                 `addedby`     INT(11)     NOT NULL,
                                                 `updatedon`   DATETIME        NULL,
                                                 `updatedby`   INT(11)         NULL,
                                                 `status`      VARCHAR(16)     NULL,
                                                 `classes_id`  INT(11)         NULL,
                                                 `users_id`    INT(11)         NULL,

                                                  INDEX (`addedon`),
                                                  INDEX (`addedby`),
                                                  INDEX (`updatedon`),
                                                  INDEX (`updatedby`),
                                                  INDEX (`status`),
                                                  INDEX (`classes_id`),
                                                  INDEX (`users_id`),
                                                  UNIQUE(`classes_id`,`users_id`),

                                                  CONSTRAINT `fk_notifications_members_addedby`    FOREIGN KEY (`addedby`)    REFERENCES `users`                 (`id`) ON DELETE CASCADE,
                                                  CONSTRAINT `fk_notifications_members_updatedby`  FOREIGN KEY (`updatedby`)  REFERENCES `users`                 (`id`) ON DELETE SET NULL,
                                                  CONSTRAINT `fk_notifications_members_classes_id` FOREIGN KEY (`classes_id`) REFERENCES `notifications_classes` (`id`) ON DELETE CASCADE,
                                                  CONSTRAINT `fk_notifications_members_users_id`   FOREIGN KEY (`users_id`)   REFERENCES `users`                 (`id`) ON DELETE CASCADE

                                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * This is a log of all sent notifications
 */
sql_query('CREATE TABLE `notifications` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `addedon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `addedby`     INT(11)     NOT NULL,
                                         `updatedon`   DATETIME        NULL,
                                         `updatedby`   INT(11)         NULL,
                                         `classes_id`  INT(11)         NULL,
                                         `users_id`    INT(11)         NULL,
                                         `subject`     INT(11)         NULL,

                                         INDEX (`addedon`),
                                         INDEX (`addedby`),
                                         INDEX (`updatedon`),
                                         INDEX (`updatedby`),
                                         INDEX (`classes_id`),
                                         INDEX (`users_id`),

                                         CONSTRAINT `fk_notifications_addedby`    FOREIGN KEY (`addedby`)    REFERENCES `users`                 (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_notifications_updatedby`  FOREIGN KEY (`updatedby`)  REFERENCES `users`                 (`id`) ON DELETE SET NULL,
                                         CONSTRAINT `fk_notifications_classes_id` FOREIGN KEY (`classes_id`) REFERENCES `notifications_classes` (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_notifications_users_id`   FOREIGN KEY (`users_id`)   REFERENCES `users`                 (`id`) ON DELETE CASCADE

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>