<?php
/*
 * Fix notifications table structures
 */

/*
 * Cut the garbage from the notifications_members table
 */
sql_foreignkey_exists ('notifications_members', 'fk_notifications_members_updatedby'    ,  'ALTER TABLE `notifications_members` DROP FOREIGN KEY `fk_notifications_members_updatedby`');
sql_index_exists      ('notifications_members', 'updatedby'                             ,  'ALTER TABLE `notifications_members` DROP INDEX  `updatedby`');
sql_column_exists     ('notifications_members', 'updatedby'                             ,  'ALTER TABLE `notifications_members` DROP COLUMN `updatedby`');
sql_index_exists      ('notifications_members', 'updatedon'                             ,  'ALTER TABLE `notifications_members` DROP INDEX  `updatedon`');
sql_column_exists     ('notifications_members', 'updatedon'                             ,  'ALTER TABLE `notifications_members` DROP COLUMN `updatedon`');
sql_index_exists      ('notifications_members', 'status'                                ,  'ALTER TABLE `notifications_members` DROP INDEX  `status`');
sql_column_exists     ('notifications_members', 'status'                                ,  'ALTER TABLE `notifications_members` DROP COLUMN `status`');

sql_foreignkey_exists ('notifications_members', 'fk_notifications_members_addedby'      ,  'ALTER TABLE `notifications_members` DROP FOREIGN KEY `fk_notifications_members_addedby`');
sql_index_exists      ('notifications_members', 'addedby'                               ,  'ALTER TABLE `notifications_members` DROP INDEX `addedby`');
sql_column_exists     ('notifications_members', 'addedby'                               ,  'ALTER TABLE `notifications_members` CHANGE COLUMN `addedby` `createdby` INT(11) NOT NULL');
sql_index_exists      ('notifications_members', 'createdby'                             , '!ALTER TABLE `notifications_members` ADD  INDEX (`createdby`)');
sql_foreignkey_exists ('notifications_members', 'fk_notifications_members_createdby'    , '!ALTER TABLE `notifications_members` ADD CONSTRAINT `fk_notifications_members_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE CASCADE;');

sql_index_exists      ('notifications_members', 'addedon'                               ,  'ALTER TABLE `notifications_members` DROP INDEX  `addedon`');
sql_column_exists     ('notifications_members', 'addedon'                               ,  'ALTER TABLE `notifications_members` CHANGE COLUMN `addedon` `createdon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
sql_index_exists      ('notifications_members', 'createdon'                             , '!ALTER TABLE `notifications_members` ADD  INDEX (`createdon`)');



/*
 * Update the notificatoins_classes table to conform with the current common table design
 */
sql_foreignkey_exists ('notifications_classes', 'fk_notifications_classes_addedby'      ,  'ALTER TABLE `notifications_classes` DROP FOREIGN KEY `fk_notifications_classes_addedby`');
sql_index_exists      ('notifications_classes', 'addedby'                               ,  'ALTER TABLE `notifications_classes` DROP INDEX `addedby`');
sql_column_exists     ('notifications_classes', 'addedby'                               ,  'ALTER TABLE `notifications_classes` CHANGE COLUMN `addedby` `createdby` INT(11) NOT NULL');
sql_index_exists      ('notifications_classes', 'createdby'                             , '!ALTER TABLE `notifications_classes` ADD INDEX (`createdby`)');
sql_foreignkey_exists ('notifications_classes', 'fk_notifications_classes_createdby'    , '!ALTER TABLE `notifications_classes` ADD CONSTRAINT `fk_notifications_classes_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE CASCADE;');

sql_foreignkey_exists ('notifications_classes', 'fk_notifications_classes_updatedby'    ,  'ALTER TABLE `notifications_classes` DROP FOREIGN KEY `fk_notifications_classes_updatedby`');
sql_index_exists      ('notifications_classes', 'updatedby'                             ,  'ALTER TABLE `notifications_classes` DROP INDEX `updatedby`');
sql_column_exists     ('notifications_classes', 'updatedby'                             ,  'ALTER TABLE `notifications_classes` CHANGE COLUMN `updatedby` `modifiedby` INT(11) NULL');
sql_index_exists      ('notifications_classes', 'modifiedby'                            , '!ALTER TABLE `notifications_classes` ADD INDEX (`modifiedby`)');
sql_foreignkey_exists ('notifications_classes', 'fk_notifications_classes_modifiedby'   , '!ALTER TABLE `notifications_classes` ADD CONSTRAINT `fk_notifications_classes_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE CASCADE;');

sql_index_exists      ('notifications_classes', 'addedon'                               ,  'ALTER TABLE `notifications_classes` DROP INDEX `addedon`');
sql_column_exists     ('notifications_classes', 'addedon'                               ,  'ALTER TABLE `notifications_classes` CHANGE COLUMN `addedon` `createdon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
sql_index_exists      ('notifications_classes', 'createdon'                             , '!ALTER TABLE `notifications_classes` ADD INDEX (`createdon`)');

sql_index_exists      ('notifications_classes', 'updatedon'                             ,  'ALTER TABLE `notifications_classes` DROP INDEX `updatedon`');
sql_column_exists     ('notifications_classes', 'updatedon'                             ,  'ALTER TABLE `notifications_classes` CHANGE COLUMN `updatedon` `modifiedon` DATETIME NULL');
sql_index_exists      ('notifications_classes', 'modifiedon'                            , '!ALTER TABLE `notifications_classes` ADD INDEX (`modifiedon`)');

sql_column_exists     ('notifications_classes', 'description'                           ,  'ALTER TABLE `notifications` CHANGE COLUMN `description` `description` VARCHAR(255) NULL');

sql_index_exists      ('notifications_classes', 'addedby_2'                             ,  'ALTER TABLE `notifications_classes` DROP INDEX  `addedby_2`');
sql_index_exists      ('notifications_classes', 'name'                                  ,  'ALTER TABLE `notifications_classes` DROP INDEX  `name`');
sql_index_exists      ('notifications_classes', 'name'                                  , '!ALTER TABLE `notifications_classes` ADD UNIQUE (`name`)');



/*
 * Update the notificatoins table to conform with the current common table design
 */
sql_foreignkey_exists ('notifications', 'fk_notifications_addedby'              ,  'ALTER TABLE `notifications` DROP FOREIGN KEY `fk_notifications_addedby`');
sql_index_exists      ('notifications', 'addedby'                               ,  'ALTER TABLE `notifications` DROP INDEX `addedby`');
sql_column_exists     ('notifications', 'addedby'                               ,  'ALTER TABLE `notifications` CHANGE COLUMN `addedby` `createdby` INT(11) NOT NULL');
sql_index_exists      ('notifications', 'createdby'                             , '!ALTER TABLE `notifications` ADD  INDEX (`createdby`)');
sql_foreignkey_exists ('notifications', 'fk_notifications_createdby'            , '!ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE CASCADE;');

sql_foreignkey_exists ('notifications', 'fk_notifications_updatedby'            ,  'ALTER TABLE `notifications` DROP FOREIGN KEY `fk_notifications_updatedby`');
sql_index_exists      ('notifications', 'updatedby'                             ,  'ALTER TABLE `notifications` DROP INDEX `updatedby`');
sql_column_exists     ('notifications', 'updatedby'                             ,  'ALTER TABLE `notifications` DROP COLUMN `updatedby`');

sql_index_exists      ('notifications', 'addedon'                               ,  'ALTER TABLE `notifications` DROP INDEX  `addedon`');
sql_column_exists     ('notifications', 'addedon'                               ,  'ALTER TABLE `notifications` CHANGE COLUMN `addedon` `createdon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
sql_index_exists      ('notifications', 'createdon'                             , '!ALTER TABLE `notifications` ADD  INDEX (`createdon`)');

sql_index_exists      ('notifications', 'updatedon'                             ,  'ALTER TABLE `notifications` DROP INDEX  `updatedon`');
sql_column_exists     ('notifications', 'updatedon'                             ,  'ALTER TABLE `notifications` DROP COLUMN `updatedon`');

sql_column_exists     ('notifications', 'subject'                               ,  'ALTER TABLE `notifications` CHANGE COLUMN `subject` `event` VARCHAR(255) NOT NULL');
sql_column_exists     ('notifications', 'description'                           , '!ALTER TABLE `notifications` ADD COLUMN `description` TEXT NOT NULL');
?>