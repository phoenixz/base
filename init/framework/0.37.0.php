<?php
/*
 * Manage email conversations
 */
sql_query('DROP TABLE IF EXISTS `twilio_numbers`');
sql_query('DROP TABLE IF EXISTS `twilio_groups`');
sql_query('DROP TABLE IF EXISTS `twilio_accounts`');



sql_query('CREATE TABLE `twilio_accounts` (`id`             INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                           `createdon`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `createdby`      INT(11)          NULL,
                                           `modifiedon`     DATETIME         NULL,
                                           `modifiedby`     INT(11)          NULL,
                                           `status`         VARCHAR(16)      NULL,
                                           `email`          VARCHAR(128)     NULL,
                                           `accounts_id`    VARCHAR(40)      NULL,
                                           `accounts_token` VARCHAR(40)      NULL,

                                           INDEX (`createdon`),
                                           INDEX (`createdby`),
                                           INDEX (`modifiedon`),
                                           INDEX (`modifiedby`),
                                           INDEX (`status`),
                                           INDEX (`email`),
                                           INDEX (`accounts_id`),

                                           CONSTRAINT `fk_twilio_accounts_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                           CONSTRAINT `fk_twilio_accounts_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `twilio_groups` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `createdby`   INT(11)          NULL,
                                         `modifiedon`  DATETIME         NULL,
                                         `modifiedby`  INT(11)          NULL,
                                         `status`      VARCHAR(16)      NULL,
                                         `name`        VARCHAR(64)  NOT NULL,
                                         `description` VARCHAR(128) NOT NULL,

                                          INDEX (`createdon`),
                                          INDEX (`createdby`),
                                          INDEX (`modifiedon`),
                                          INDEX (`modifiedby`),
                                          UNIQUE(`name`),

                                          CONSTRAINT `fk_twilio_groups_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_twilio_groups_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_query('CREATE TABLE `twilio_numbers` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`   INT(11)          NULL,
                                          `modifiedon`  DATETIME         NULL,
                                          `modifiedby`  INT(11)          NULL,
                                          `status`      VARCHAR(16)      NULL,
                                          `accounts_id` INT(11)      NOT NULL,
                                          `groups_id`   INT(11)          NULL,
                                          `name`        VARCHAR(64)  NOT NULL,
                                          `number`      VARCHAR(12)  NOT NULL,

                                          INDEX (`createdon`),
                                          INDEX (`createdby`),
                                          INDEX (`modifiedon`),
                                          INDEX (`modifiedby`),
                                          INDEX (`status`),
                                          INDEX (`accounts_id`),
                                          INDEX (`groups_id`),
                                          INDEX (`number`),

                                          CONSTRAINT `fk_twilio_numbers_createdby`   FOREIGN KEY (`createdby`)   REFERENCES `users`           (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_twilio_numbers_modifiedby`  FOREIGN KEY (`modifiedby`)  REFERENCES `users`           (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_twilio_numbers_accounts_id` FOREIGN KEY (`accounts_id`) REFERENCES `twilio_accounts` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_twilio_numbers_groups_id`   FOREIGN KEY (`groups_id`)   REFERENCES `twilio_groups`   (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

if(!empty($_CONFIG['twilio']['accounts'])){
    load_config('twilio');
    cli_log(tr('Copying twilio configuration...'), 'white');

    $r_account = sql_prepare('INSERT INTO `twilio_accounts` (`email`, `accounts_id`, `accounts_token`)
                              VALUES                        (:email , :accounts_id , :accounts_token )');

    $r_number = sql_prepare('INSERT INTO `twilio_numbers` (`accounts_id`, `number`, `name`)
                             VALUES                       (:accounts_id , :number , :name )');

    foreach($_CONFIG['twilio']['accounts'] as $email => $data){
        cli_dot(1);
        $r_account->execute(array(':email'          => $email,
                                  ':accounts_id'    => $data['accounts_id'],
                                  ':accounts_token' => $data['accounts_token']));

        $accounts_id = sql_insert_id();

        foreach($data['sources'] as $number => $name){
            cli_dot(1);
            $r_number->execute(array(':accounts_id' => $accounts_id,
                                     ':number'      => $number,
                                     ':name'        => $name));
        }
    }

    cli_dot(false);
}
?>
