<?php
/*
 * Since emails (and possibly sms) can be full UTF now, ensure more storage space
 */
sql_query('ALTER TABLE `sms_conversations`   CHANGE COLUMN `last_messages` `last_messages` VARCHAR(4096);');
sql_query('ALTER TABLE `email_conversations` CHANGE COLUMN `last_messages` `last_messages` VARCHAR(8192);');
?>
