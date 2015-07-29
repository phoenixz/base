<?php
/*
 * Track SMS conversations
 */
sql_query('DROP TABLE IF EXISTS `twilio_messages`');
sql_query('DROP TABLE IF EXISTS `twilio_conversations`');



sql_query('CREATE TABLE `twilio_conversations` (`id`            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                                `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                `modifiedon`    DATETIME          NULL,
                                                `status`        VARCHAR(16)       NULL,
                                                `replied`       DATETIME          NULL,
                                                `phone_local`   VARCHAR(16)   NOT NULL,
                                                `phone_remote`  VARCHAR(16)   NOT NULL,
                                                `last_messages` VARCHAR(1024) NOT NULL,

                                                INDEX (`createdon`),
                                                INDEX (`modifiedon`),
                                                INDEX (`replied`),
                                                INDEX (`phone_local`),
                                                INDEX (`phone_remote`),
                                                INDEX (`status`),
                                                UNIQUE(`phone_local`, `phone_remote`)

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `twilio_messages` (`id`                      INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                           `createdon`               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `status`                  VARCHAR(16)      NULL,
                                           `direction`               VARCHAR(8)       NULL,
                                           `twilio_conversations_id` INT(11)          NULL,
                                           `reply_to_id`             INT(11)          NULL,

                                           `api_version`             VARCHAR(12)  NOT NULL,
                                           `message_sid`             VARCHAR(64)  NOT NULL,
                                           `account_sid`             VARCHAR(64)  NOT NULL,
                                           `sms_status`              VARCHAR(16)  NOT NULL,
                                           `sms_id`                  VARCHAR(64)  NOT NULL,
                                           `sms_message_sid`         VARCHAR(64)  NOT NULL,
                                           `num_media`               INT(11)      NOT NULL,
                                           `num_segments`            INT(11)          NULL,

                                           `from_country`            VARCHAR(32)  NOT NULL,
                                           `from_state`              VARCHAR(32)  NOT NULL,
                                           `from_city`               VARCHAR(32)  NOT NULL,
                                           `from_zip`                VARCHAR(7)   NOT NULL,
                                           `from_phone`              VARCHAR(16)  NOT NULL,

                                           `to_country`              VARCHAR(32)  NOT NULL,
                                           `to_state`                VARCHAR(32)  NOT NULL,
                                           `to_city`                 VARCHAR(32)  NOT NULL,
                                           `to_zip`                  VARCHAR(7)   NOT NULL,
                                           `to_phone`                VARCHAR(16)  NOT NULL,

                                           `body`                    VARCHAR(255) NOT NULL,

                                           INDEX (`createdon`),
                                           INDEX (`from_phone`),
                                           INDEX (`sms_id`),
                                           INDEX (`sms_message_sid`),
                                           INDEX (`direction`),
                                           INDEX (`status`),
                                           INDEX (`reply_to_id`),
                                           INDEX (`twilio_conversations_id`),

                                           CONSTRAINT `fk_twilio_messages_reply_to_id`             FOREIGN KEY (`reply_to_id`)             REFERENCES `twilio_messages`      (`id`) ON DELETE CASCADE,
                                           CONSTRAINT `fk_twilio_messages_twilio_conversations_id` FOREIGN KEY (`twilio_conversations_id`) REFERENCES `twilio_conversations` (`id`) ON DELETE CASCADE

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>