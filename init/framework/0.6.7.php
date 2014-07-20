<?php
sql_index_exists ('mailer_mailings'  , 'header' ,  'ALTER TABLE `mailer_mailings` DROP INDEX `header`  ');
sql_index_exists ('mailer_mailings'  , 'subject', '!ALTER TABLE `mailer_mailings` ADD  INDEX(`subject`)');

/*
 * Remove all double `mailings_id`,`users_id` entries to ensure init wont fail, then apply constraint
 */
sql_query('DELETE `n1`

           FROM   `mailer_recipients` `n1`,
                  `mailer_recipients` `n2`

           WHERE  `n1`.`mailings_id` = `n2`.`mailings_id`
           AND    `n1`.`users_id`    = `n2`.`users_id`
           AND    `n1`.`id`          > `n2`.`id`');

/*
 * Each user can only receive the same mailing once!
 */
sql_index_exists ('mailer_recipients', 'mailings_id,users_id', '!ALTER TABLE `mailer_recipients` ADD UNIQUE(`mailings_id`,`users_id`)');
?>