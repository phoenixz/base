<?php
/*
 * Change the entire "twilio" storage to "sms" storage
 */
if(sql_table_exists('twilio_messages')){
    sql_table_exists('sms_messages', 'DROP TABLE `sms_messages`');
    sql_foreignkey_exists('twilio_messages', 'fk_twilio_messages_twilio_conversations_id', 'ALTER TABLE `twilio_messages` DROP FOREIGN KEY `fk_twilio_messages_twilio_conversations_id`');
    sql_foreignkey_exists('twilio_messages', 'fk_twilio_messages_reply_to_id'            , 'ALTER TABLE `twilio_messages` DROP FOREIGN KEY `fk_twilio_messages_reply_to_id`');

    sql_index_exists ('twilio_messages', 'twilio_conversations_id',  'ALTER TABLE `twilio_messages` DROP INDEX  `twilio_conversations_id`');
    sql_column_exists('twilio_messages', 'twilio_conversations_id',  'ALTER TABLE `twilio_messages` CHANGE COLUMN `twilio_conversations_id` `conversations_id` INT(11) NULL');
    sql_index_exists ('twilio_messages', 'twilio_conversations_id', '!ALTER TABLE `twilio_messages` ADD INDEX  (`conversations_id`)');

    sql_query('RENAME TABLE `twilio_messages` TO `sms_messages`');
}

if(sql_table_exists('twilio_conversations')){
    sql_table_exists('sms_conversations', 'DROP TABLE `sms_conversations`');
    sql_query('RENAME TABLE `twilio_conversations` TO `sms_conversations`');
}

sql_foreignkey_exists('sms_messages', 'fk_sms_messages_conversations_id', '!ALTER TABLE `sms_messages` ADD CONSTRAINT `fk_sms_messages_conversations_id` FOREIGN KEY (`conversations_id`) REFERENCES `sms_conversations` (`id`) ON DELETE CASCADE;');
sql_foreignkey_exists('sms_messages', 'fk_sms_messages_reply_to_id'     , '!ALTER TABLE `sms_messages` ADD CONSTRAINT `fk_sms_messages_reply_to_id`      FOREIGN KEY (`reply_to_id`)      REFERENCES `sms_messages`      (`id`) ON DELETE RESTRICT;');

sql_column_exists('sms_messages', 'provider', '!ALTER TABLE `sms_messages` ADD COLUMN `provider` ENUM("twilio", "crmtext") NOT NULL DEFAULT "twilio" AFTER `createdon`');
sql_index_exists ('sms_messages', 'provider', '!ALTER TABLE `sms_messages` ADD  INDEX (`provider`)');

sql_column_exists('sms_messages', 'store_id'          , '!ALTER TABLE `sms_messages` ADD COLUMN `store_id`           INT(11)     NULL AFTER `direction`');
sql_column_exists('sms_messages', 'store_keyword'     , '!ALTER TABLE `sms_messages` ADD COLUMN `store_keyword`      VARCHAR(16) NULL AFTER `store_id`');
sql_column_exists('sms_messages', 'optin_status'      , '!ALTER TABLE `sms_messages` ADD COLUMN `optin_status`       VARCHAR(7)  NULL AFTER `store_keyword`');
sql_column_exists('sms_messages', 'customer_name'     , '!ALTER TABLE `sms_messages` ADD COLUMN `customer_name`      VARCHAR(16) NULL AFTER `optin_status`');
sql_column_exists('sms_messages', 'provider_timestamp', '!ALTER TABLE `sms_messages` ADD COLUMN `provider_timestamp` DATETIME    NULL AFTER `customer_name`');
?>