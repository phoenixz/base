<?php
/*
 * Update email_conversations table
 */
sql_column_exists('email_conversations', 'from', 'ALTER TABLE `email_conversations` CHANGE COLUMN `from` `them` VARCHAR(64) NOT NULL');
sql_column_exists('email_conversations', 'to'  , 'ALTER TABLE `email_conversations` CHANGE COLUMN `to`   `us`   VARCHAR(64) NOT NULL');
?>