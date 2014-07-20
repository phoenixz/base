<?php
sql_column_exists('mailer_mailings', 'seotitle', '!ALTER TABLE `mailer_mailings` ADD COLUMN `seotitle` VARCHAR(32) NOT NULL AFTER `title`;');
sql_index_exists ('mailer_mailings', 'seotitle', '!ALTER TABLE `mailer_mailings` ADD INDEX (`seotitle`);');
?>