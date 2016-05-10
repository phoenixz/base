<?php
/*
 * Add loads of new email functionalities
 */
sql_query('DROP TABLE IF EXISTS `email_saved_data`');
sql_query('DROP TABLE IF EXISTS `email_saved`');
sql_query('DROP TABLE IF EXISTS `email_keywords`');
sql_query('DROP TABLE IF EXISTS `email_templates`');
sql_query('DROP TABLE IF EXISTS `email_aliases`');
sql_query('DROP TABLE IF EXISTS `email_users`');
sql_query('DROP TABLE IF EXISTS `email_domains`');



/*
 * Domains that are supported to receive and send emails from
 * Contains configuration for each domain
 */
sql_query('CREATE TABLE `email_domains` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `createdon`   TIMESTAMP    NOT NULL,
                                         `createdby`   INT(11)          NULL,
                                         `modifiedon`  DATETIME         NULL,
                                         `modifiedby`  INT(11)          NULL,
                                         `status`      VARCHAR(16)      NULL,
                                         `domain`      VARCHAR(96)      NULL,
                                         `smtp_host`   VARCHAR(128)     NULL,
                                         `smtp_port`   INT(11)      NOT NULL,
                                         `imap`        VARCHAR(160) NOT NULL,
                                         `header`      TEXT             NULL,
                                         `footer`      TEXT             NULL,
                                         `description` TEXT             NULL,

                                         INDEX (`createdon`),
                                         INDEX (`createdby`),
                                         INDEX (`modifiedon`),
                                         INDEX (`modifiedby`),
                                         INDEX (`status`),
                                         INDEX (`domain`),

                                         CONSTRAINT `fk_email_domains_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_email_domains_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Email users @ email_domains, contains user, what domain, password, etc.
 */
sql_query('CREATE TABLE `email_users` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                       `createdon`   TIMESTAMP    NOT NULL,
                                       `createdby`   INT(11)          NULL,
                                       `modifiedon`  DATETIME         NULL,
                                       `modifiedby`  INT(11)          NULL,
                                       `status`      VARCHAR(16)      NULL,
                                       `domains_id`  INT(11)          NULL,
                                       `email`       VARCHAR(128) NOT NULL,
                                       `name`        VARCHAR(32)  NOT NULL,
                                       `seoname`     VARCHAR(32)  NOT NULL,
                                       `realname`    VARCHAR(64)      NULL,
                                       `password`    VARCHAR(128)     NULL,
                                       `header`      TEXT             NULL,
                                       `footer`      TEXT             NULL,
                                       `description` TEXT             NULL,

                                       INDEX (`createdon`),
                                       INDEX (`createdby`),
                                       INDEX (`modifiedon`),
                                       INDEX (`modifiedby`),
                                       INDEX (`status`),
                                       INDEX (`seoname`),
                                       INDEX (`name`),

                                       CONSTRAINT `fk_email_users_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`         (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_email_users_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users`         (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_email_users_domains_id` FOREIGN KEY (`domains_id`) REFERENCES `email_domains` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Registration of what mails are actually forwards
 */
sql_query('CREATE TABLE `email_aliases` (`id`              INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `createdon`       TIMESTAMP    NOT NULL,
                                         `createdby`       INT(11)          NULL,
                                         `modifiedon`      DATETIME         NULL,
                                         `modifiedby`      INT(11)          NULL,
                                         `status`          VARCHAR(16)      NULL,
                                         `from_domains_id` INT(11)      NOT NULL,
                                         `to_domains_id`   INT(11)      NOT NULL,
                                         `from`            VARCHAR(128) NOT NULL,
                                         `to`              VARCHAR(128) NOT NULL,
                                         `description`     TEXT             NULL,

                                         INDEX (`createdon`) ,
                                         INDEX (`createdby`),
                                         INDEX (`modifiedon`),
                                         INDEX (`modifiedby`),
                                         INDEX (`status`),
                                         INDEX (`from_domains_id`),
                                         INDEX (`to_domains_id`),
                                         INDEX (`from`),
                                         INDEX (`to`),
                                         UNIQUE(`from`,`to`),

                                         CONSTRAINT `fk_email_aliases_createdby`       FOREIGN KEY (`createdby`)       REFERENCES `users`         (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_email_aliases_modifiedby`      FOREIGN KEY (`modifiedby`)      REFERENCES `users`         (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_email_aliases_from_domains_id` FOREIGN KEY (`from_domains_id`) REFERENCES `email_domains` (`id`) ON DELETE RESTRICT,
                                         CONSTRAINT `fk_email_aliases_to_domains_id`   FOREIGN KEY (`to_domains_id`)   REFERENCES `email_domains` (`id`) ON DELETE RESTRICT

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Email templates management
 */
sql_query('CREATE TABLE `email_templates` (`id`         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                           `createdon`  TIMESTAMP    NOT NULL,
                                           `createdby`  INT(11)          NULL,
                                           `modifiedon` DATETIME         NULL,
                                           `modifiedby` INT(11)          NULL,
                                           `status`     VARCHAR(16)      NULL,
                                           `domains_id` INT(11)          NULL,
                                           `name`       VARCHAR(32)  NOT NULL,
                                           `seoname`    VARCHAR(32)  NOT NULL,
                                           `template`   TEXT         NOT NULL,

                                           INDEX (`createdon`) ,
                                           INDEX (`createdby`),
                                           INDEX (`modifiedon`),
                                           INDEX (`modifiedby`),
                                           INDEX (`status`),
                                           INDEX (`domains_id`),
                                           UNIQUE(`name`),
                                           UNIQUE(`seoname`),

                                           CONSTRAINT `fk_email_templates_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`         (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_email_templates_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users`         (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_email_templates_domains_id` FOREIGN KEY (`domains_id`) REFERENCES `email_domains` (`id`) ON DELETE RESTRICT

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * What possible keywords and values are available to search / replace email templates
 */
sql_query('CREATE TABLE `email_keywords` (`id`           INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`    INT(11)     NOT NULL,
                                          `modifiedon`   DATETIME        NULL,
                                          `modifiedby`   INT(11)         NULL,
                                          `status`       VARCHAR(16)     NULL,
                                          `templates_id` INT(11)         NULL,
                                          `name`         VARCHAR(32)     NULL,
                                          `value`        VARCHAR(255)    NULL,

                                          INDEX (`createdon`),
                                          INDEX (`createdby`),
                                          INDEX (`modifiedon`),
                                          INDEX (`modifiedby`),
                                          INDEX (`templates_id`),
                                          INDEX (`status`),
                                          UNIQUE(`name`),

                                          CONSTRAINT `fk_email_keywords_templates_id`  FOREIGN KEY (`templates_id`) REFERENCES `email_templates` (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `fk_email_keywords_createdby`     FOREIGN KEY (`createdby`)    REFERENCES `users`           (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_email_keywords_modifiedby`    FOREIGN KEY (`modifiedby`)   REFERENCES `users`           (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Emails from templates that have been saved with key / value replacements.
 */
sql_query('CREATE TABLE `email_saved` (`id`           INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                       `createdon`    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `createdby`    INT(11)     NOT NULL,
                                       `modifiedon`   DATETIME        NULL,
                                       `modifiedby`   INT(11)         NULL,
                                       `status`       VARCHAR(16)     NULL,
                                       `templates_id` INT(11)         NULL,
                                       `name`         VARCHAR(32)     NULL,
                                       `html`         TEXT            NULL,

                                       INDEX (`createdon`),
                                       INDEX (`createdby`),
                                       INDEX (`modifiedon`),
                                       INDEX (`modifiedby`),
                                       INDEX (`templates_id`),
                                       INDEX (`status`),
                                       UNIQUE(`name`),

                                       CONSTRAINT `fk_email_saved_templates_id`  FOREIGN KEY (`templates_id`) REFERENCES `email_templates` (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_email_saved_createdby`     FOREIGN KEY (`createdby`)    REFERENCES `users`           (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_email_saved_modifiedby`    FOREIGN KEY (`modifiedby`)   REFERENCES `users`           (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Emails from templates that have been saved with key / value replacements.
 */
sql_query('CREATE TABLE `email_saved_data` (`id`             INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                            `email_saved_id` INT(11)     NOT NULL,
                                            `name`           VARCHAR(32)     NULL,
                                            `value`          VARCHAR(255)    NULL,

                                            INDEX (`email_saved_id`),

                                            CONSTRAINT `email_saved_data_email_saved_id` FOREIGN KEY (`email_saved_id`) REFERENCES `email_saved` (`id`) ON DELETE CASCADE

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Auto populate domains and users from (old) configuration
 */
load_libs('seo,crypt');
load_config('email');


$p = sql_prepare('INSERT INTO `email_domains` (`domain`, `smtp_host`, `smtp_port`, `imap`, `header`, `footer`)
                  VALUES                      (:domain , :smtp_host , :smtp_port , :imap , :header , :footer)');

$q = sql_prepare('INSERT INTO `email_users` (`domains_id`, `email`, `name`, `seoname`, `realname`, `password`)
                  VALUES                    (:domains_id , :email , :name , :seoname , :realname , :password )');

/*
 * Add gmail as an example
 */
$p->execute(array(':domain'    => 'gmail.com',
                  ':smtp_host' => 'smtp.google.com',
                  ':smtp_port' => 587,
                  ':imap'      => '{imap.gmail.com:993/imap/ssl}INBOX',
                  ':header'    => '',
                  ':footer'    => ''));

/*
 * Add current configured domain
 */
$p->execute(array(':domain'    => $_CONFIG['domain'],
                  ':smtp_host' => $_CONFIG['email']['smtp']['host'],
                  ':smtp_port' => $_CONFIG['email']['smtp']['port'],
                  ':imap'      => $_CONFIG['email']['imap'],
                  ':header'    => isset_get($_CONFIG['email']['header']),
                  ':footer'    => isset_get($_CONFIG['email']['footer'])));

$domains_id = sql_insert_id();


if(!empty($_CONFIG['email']['users'])){
    foreach($_CONFIG['email']['users'] as $email => $userdata){
        $name = str_until($email, '@');

        $q->execute(array('domains_id' => $domains_id,
                          'email'      => $email,
                          'name'       => $name,
                          'seoname'    => seo_unique($name, 'email_users'),
                          'realname'   => $userdata['name'],
                          'password'   => encrypt($userdata['pass'], $_CONFIG['email']['encryption_key'])));
    }
}
?>
