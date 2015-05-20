<?php
/*
 * Add "sent" column, so we can support delayed sending
 */
sql_column_exists('email_messages', 'sent', '!ALTER TABLE `email_messages` ADD COLUMN `sent` DATETIME NULL AFTER `direction`');
?>