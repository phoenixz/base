<?php
// Disabled because it made the new notification system crash

///*
// * Create the default error and developers
// */
//sql_query('ALTER TABLE `notifications_classes` CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
//sql_query('ALTER TABLE `notifications_members` CHANGE COLUMN `createdby` `createdby` INT(11) NULL');
//
//if(!sql_get('SELECT `id` FROM `notifications_classes` WHERE `name` = "error"', 'id')){
//    script_exec('base/notifications/classes', 'create name error methods email,sms description "All error messages will go to this class"');
//    script_exec('base/notifications/members', 'add error 1');
//}
//
//if(!sql_get('SELECT `id` FROM `notifications_classes` WHERE `name` = "developers"', 'id')){
//    script_exec('base/notifications/classes', 'create name developers methods email,sms description "All developer messages will go to this class"');
//    script_exec('base/notifications/members', 'add developers 1');
//}
?>