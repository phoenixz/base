<?php
/*
 * This table will store the questionarie group answers
 */
sql_query('CREATE TABLE `questionaries_answer_sets` (`id`               INT(11)      NOT NULL AUTO_INCREMENT,
                                                     `createdon`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                     `createdby`        INT(11)          NULL,
                                                     `meta_id`          INT(11)      NOT NULL,
                                                     `status`           VARCHAR(16)      NULL,
                                                     `questionaries_id` INT(11)          NULL,

                                                     PRIMARY KEY `id`        (`id`),
                                                             KEY `meta_id`   (`meta_id`),
                                                             KEY `createdon` (`createdon`),
                                                             KEY `createdby` (`createdby`),
                                                             KEY `status`    (`status`),

                                                     CONSTRAINT `fk_questionaries_answer_sets_meta_id`          FOREIGN KEY (`meta_id`)          REFERENCES `meta`                    (`id`) ON DELETE RESTRICT,
                                                     CONSTRAINT `fk_questionaries_answer_sets_createdby`        FOREIGN KEY (`createdby`)        REFERENCES `users`                   (`id`) ON DELETE RESTRICT,
                                                     CONSTRAINT `fk_questionaries_answer_sets_questionaries_id` FOREIGN KEY (`questionaries_id`) REFERENCES `questionaries`           (`id`) ON DELETE RESTRICT

                                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_foreignkey_exists('questionaries_answers', 'fk_questionaries_answers_answer_sets_id', 'ALTER  TABLE `questionaries_answers` DROP FOREIGN KEY `fk_questionaries_answers_answer_sets_id`');
sql_column_exists    ('questionaries_answers', 'answer_sets_id'                         , '!ALTER TABLE `questionaries_answers` ADD  COLUMN      `answer_sets_id` INT(11) NULL');
sql_foreignkey_exists('questionaries_answers', 'fk_questionaries_answers_answer_sets_id', '!ALTER TABLE `questionaries_answers` ADD  CONSTRAINT  `fk_questionaries_answers_answer_sets_id` FOREIGN KEY (`answer_sets_id`) REFERENCES `questionaries_answer_sets` (`id`) ON DELETE RESTRICT;');
?>