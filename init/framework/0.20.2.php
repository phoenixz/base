<?php
/*
 * Change the entire "twilio" storage to "sms" storage
 */
sql_foreignkey_exists('twilio_messages', 'fk_twilio_messages_twilio_conversations_id', 'ALTER TABLE `twilio_messages` DROP FOREIGN KEY `fk_twilio_messages_twilio_conversations_id`');
sql_foreignkey_exists('twilio_messages', 'fk_twilio_messages_clubs_id'               , 'ALTER TABLE `twilio_messages` DROP FOREIGN KEY `fk_twilio_messages_clubs_id`');
sql_foreignkey_exists('twilio_messages', 'fk_twilio_messages_referrers_id'           , 'ALTER TABLE `twilio_messages` DROP FOREIGN KEY `fk_twilio_messages_referrers_id`');
sql_foreignkey_exists('twilio_messages', 'fk_twilio_messages_reply_to_id'            , 'ALTER TABLE `twilio_messages` DROP FOREIGN KEY `fk_twilio_messages_reply_to_id`');

sql_foreignkey_exists('twilio_conversations', 'fk_twilio_conversations_clubs_id'         , 'ALTER TABLE `twilio_conversations` DROP FOREIGN KEY `fk_twilio_conversations_clubs_id`');
sql_foreignkey_exists('twilio_conversations', 'fk_twilio_conversations_last_referrals_id', 'ALTER TABLE `twilio_conversations` DROP FOREIGN KEY `fk_twilio_conversations_last_referrals_id`');
sql_foreignkey_exists('twilio_conversations', 'fk_twilio_conversations_referrers_id'     , 'ALTER TABLE `twilio_conversations` DROP FOREIGN KEY `fk_twilio_conversations_referrers_id`');

if(sql_table_exists('twilio_messages')){
    sql_index_exists ('twilio_messages', 'twilio_conversations_id',  'ALTER TABLE `twilio_messages` DROP INDEX  `twilio_conversations_id`');
    sql_column_exists('twilio_messages', 'twilio_conversations_id',  'ALTER TABLE `twilio_messages` CHANGE COLUMN `twilio_conversations_id` `conversations_id` INT(11) NULL');
    sql_index_exists ('twilio_messages', 'twilio_conversations_id', '!ALTER TABLE `twilio_messages` ADD INDEX  (`conversations_id`)');

    sql_query('RENAME TABLE `twilio_messages` TO `sms_messages`');
}

sql_table_exists('twilio_conversations', 'RENAME TABLE `twilio_conversations` TO `sms_conversations`');

sql_foreignkey_exists('sms_messages', 'fk_sms_messages_conversations_id', '!ALTER TABLE `sms_messages` ADD CONSTRAINT `fk_sms_messages_conversations_id` FOREIGN KEY (`conversations_id`) REFERENCES `sms_conversations` (`id`) ON DELETE CASCADE;');
sql_foreignkey_exists('sms_messages', 'fk_sms_messages_clubs_id'        , '!ALTER TABLE `sms_messages` ADD CONSTRAINT `fk_sms_messages_clubs_id`         FOREIGN KEY (`clubs_id`)         REFERENCES `clubs`             (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('sms_messages', 'fk_sms_messages_referrers_id'    , '!ALTER TABLE `sms_messages` ADD CONSTRAINT `fk_sms_messages_referrers_id`     FOREIGN KEY (`referrers_id`)     REFERENCES `users`             (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('sms_messages', 'fk_sms_messages_reply_to_id'     , '!ALTER TABLE `sms_messages` ADD CONSTRAINT `fk_sms_messages_reply_to_id`      FOREIGN KEY (`reply_to_id`)      REFERENCES `sms_messages`      (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('sms_conversations', 'fk_sms_conversations_clubs_id'         , '!ALTER TABLE `sms_conversations` ADD CONSTRAINT `fk_sms_conversations_clubs_id`          FOREIGN KEY (`clubs_id`)          REFERENCES `clubs`     (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('sms_conversations', 'fk_sms_conversations_last_referrals_id', '!ALTER TABLE `sms_conversations` ADD CONSTRAINT `fk_sms_conversations_last_referrals_id` FOREIGN KEY (`last_referrals_id`) REFERENCES `referrals` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('sms_conversations', 'fk_sms_conversations_referrers_id'     , '!ALTER TABLE `sms_conversations` ADD CONSTRAINT `fk_sms_conversations_referrers_id`      FOREIGN KEY (`referrers_id`)      REFERENCES `users`     (`id`) ON DELETE RESTRICT;');

sql_column_exists('sms_messages', 'provider', '!ALTER TABLE `sms_messages` ADD COLUMN `provider` ENUM("twilio", "crmtext") NOT NULL DEFAULT "twilio" AFTER `createdon`');
sql_index_exists ('sms_messages', 'provider', '!ALTER TABLE `sms_messages` ADD  INDEX (`provider`)');
?>