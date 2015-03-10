<?php
/*
 * Add direction column to twilio conversations
 */
sql_column_exists('twilio_conversations', 'direction', '!ALTER TABLE `twilio_conversations` ADD COLUMN  `direction` ENUM("sent", "received") NOT NULL AFTER `status`');
sql_index_exists ('twilio_conversations', 'direction', '!ALTER TABLE `twilio_conversations` ADD INDEX  (`direction`)');

sql_query('ALTER TABLE `twilio_messages` CHANGE COLUMN `direction` `direction` ENUM("sent", "received")');

sql_index_exists ('twilio_conversations', 'replied'  ,  'ALTER TABLE `twilio_conversations` DROP INDEX `replied`');
sql_column_exists('twilio_conversations', 'replied'  ,  'ALTER TABLE `twilio_conversations` CHANGE COLUMN  `replied` `repliedon` DATETIME NULL');
sql_index_exists ('twilio_conversations', 'repliedon', '!ALTER TABLE `twilio_conversations` ADD INDEX  (`repliedon`)');
?>