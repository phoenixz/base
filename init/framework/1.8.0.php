<?php
/*
 * Drop foreign keys and tables
 */
sql_foreignkey_exists('questionaries_questions', 'fk_questionaries_questions_correct_option', 'ALTER TABLE `questionaries_questions` DROP FOREIGN KEY `fk_questionaries_questions_correct_option`');
sql_query('DROP TABLE IF EXISTS `questionaries_answers`');
sql_query('DROP TABLE IF EXISTS `questionaries_options`');
sql_query('DROP TABLE IF EXISTS `questionaries_related_questions`');
sql_query('DROP TABLE IF EXISTS `questionaries_questions`');
sql_query('DROP TABLE IF EXISTS `questionaries`');

/*
 * Create questionaries table
 */
sql_query('CREATE TABLE `questionaries` (`id`        INT(11)      NOT NULL AUTO_INCREMENT,
                                         `createdon` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby` INT(11)          NULL,
                                         `meta_id`   INT(11)      NOT NULL,
                                         `status`    VARCHAR(16)      NULL,
                                         `name`      VARCHAR(255)     NULL,

                                         PRIMARY KEY `id`        (`id`),
                                                 KEY `meta_id`   (`meta_id`),
                                                 KEY `createdon` (`createdon`),
                                                 KEY `createdby` (`createdby`),
                                                 KEY `status`    (`status`),

                                         CONSTRAINT `fk_questionaries_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`       (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_questionaries_createdby` FOREIGN KEY (`createdby`) REFERENCES `users`      (`id`) ON DELETE RESTRICT

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * Create questions table
 */
sql_query('CREATE TABLE `questionaries_questions` (`id`        INT(11)     NOT NULL AUTO_INCREMENT,
                                                   `createdon` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                   `createdby` INT(11)         NULL,
                                                   `meta_id`   INT(11)     NOT NULL,
                                                   `status`    VARCHAR(16)     NULL,
                                                   `type`      ENUM("multiplechoice", "open")              NOT NULL,
                                                   `inputtype` ENUM("text", "number", "email", "textarea") NOT NULL,
                                                   `question`  VARCHAR(255)    NULL,

                                                   PRIMARY KEY `id`        (`id`),
                                                           KEY `meta_id`   (`meta_id`),
                                                           KEY `createdon` (`createdon`),
                                                           KEY `createdby` (`createdby`),
                                                           KEY `status`    (`status`),

                                                   CONSTRAINT `fk_questionaries_questions_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                                   CONSTRAINT `fk_questionaries_questions_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * Create questionaries_related_questions table
 * This table will join both tables
 */
sql_query('CREATE TABLE `questionaries_related_questions` (`id`               INT(11)        NOT NULL AUTO_INCREMENT,
                                                           `createdon`        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                           `createdby`        INT(11)            NULL,
                                                           `meta_id`          INT(11)        NOT NULL,
                                                           `status`           VARCHAR(16)        NULL,
                                                           `title`            VARCHAR(255)       NULL,
                                                           `questions_id`     INT(11)            NULL,
                                                           `questionaries_id` INT(11)            NULL,

                                                           PRIMARY KEY `id`        (`id`),
                                                                   KEY `meta_id`   (`meta_id`),
                                                                   KEY `createdon` (`createdon`),
                                                                   KEY `createdby` (`createdby`),
                                                                   KEY `status`    (`status`),

                                                           CONSTRAINT `fk_questionaries_related_questions_meta_id`          FOREIGN KEY (`meta_id`)          REFERENCES `meta`                    (`id`) ON DELETE RESTRICT,
                                                           CONSTRAINT `fk_questionaries_related_questions_createdby`        FOREIGN KEY (`createdby`)        REFERENCES `users`                   (`id`) ON DELETE RESTRICT,
                                                           CONSTRAINT `fk_questionaries_related_questions_questions_id`     FOREIGN KEY (`questions_id`)     REFERENCES `questionaries_questions` (`id`) ON DELETE RESTRICT,
                                                           CONSTRAINT `fk_questionaries_related_questions_questionaries_id` FOREIGN KEY (`questionaries_id`) REFERENCES `questionaries`           (`id`) ON DELETE RESTRICT

                                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * This table will store posible options for each question
 */
sql_query('CREATE TABLE `questionaries_options` (`id`           INT(11)      NOT NULL AUTO_INCREMENT,
                                                 `createdon`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                 `createdby`    INT(11)          NULL,
                                                 `meta_id`      INT(11)      NOT NULL,
                                                 `status`       VARCHAR(16)      NULL,
                                                 `title`        VARCHAR(255)     NULL,
                                                 `questions_id` INT(11)          NULL,

                                                 PRIMARY KEY `id`        (`id`),
                                                         KEY `meta_id`   (`meta_id`),
                                                         KEY `createdon` (`createdon`),
                                                         KEY `createdby` (`createdby`),
                                                         KEY `status`    (`status`),

                                                 CONSTRAINT `fk_questionaries_options_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`                    (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_questionaries_options_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`                   (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_questionaries_options_questions_id` FOREIGN KEY (`questions_id`) REFERENCES `questionaries_questions` (`id`) ON DELETE RESTRICT

                                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * This table will store the answers of the users
 */
sql_query('CREATE TABLE `questionaries_answers` (`id`               INT(11)      NOT NULL AUTO_INCREMENT,
                                                 `createdon`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                 `createdby`        INT(11)          NULL,
                                                 `meta_id`          INT(11)      NOT NULL,
                                                 `status`           VARCHAR(16)      NULL,
                                                 `answer`           VARCHAR(255)     NULL,
                                                 `options_id`       INT(11)          NULL,
                                                 `questions_id`     INT(11)          NULL,
                                                 `questionaries_id` INT(11)          NULL,

                                                 PRIMARY KEY `id`        (`id`),
                                                         KEY `meta_id`   (`meta_id`),
                                                         KEY `createdon` (`createdon`),
                                                         KEY `createdby` (`createdby`),
                                                         KEY `status`    (`status`),

                                                 CONSTRAINT `fk_questionaries_answers_meta_id`          FOREIGN KEY (`meta_id`)          REFERENCES `meta`                    (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_questionaries_answers_createdby`        FOREIGN KEY (`createdby`)        REFERENCES `users`                   (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_questionaries_answers_options_id`       FOREIGN KEY (`options_id`)       REFERENCES `questionaries_options`   (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_questionaries_answers_questions_id`     FOREIGN KEY (`questions_id`)     REFERENCES `questionaries_questions` (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_questionaries_answers_questionaries_id` FOREIGN KEY (`questionaries_id`) REFERENCES `questionaries`           (`id`) ON DELETE RESTRICT

                                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * ADD FOREIGN KEY OF CORRECT ANSWER ON QUESTIONS
 */
sql_column_exists    ('questionaries_questions', 'correct_option'                           , '!ALTER TABLE `questionaries_questions` ADD COLUMN `correct_option` INT (11) NULL');
sql_index_exists     ('questionaries_questions', 'correct_option'                           , '!ALTER TABLE `questionaries_questions` ADD INDEX (`correct_option`)');
sql_foreignkey_exists('questionaries_questions', 'fk_questionaries_questions_correct_option', '!ALTER TABLE `questionaries_questions` ADD CONSTRAINT `fk_questionaries_questions_correct_option` FOREIGN KEY (`correct_option`) REFERENCES `questionaries_options` (`id`) ON DELETE RESTRICT;');
?>