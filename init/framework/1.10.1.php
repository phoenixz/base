<?php
sql_query('TRUNCATE `sms_blocks`');

sql_index_exists('sms_blocks', 'number',  'ALTER TABLE `sms_blocks` DROP INDEX       `number`');
sql_index_exists('sms_blocks', 'number', '!ALTER TABLE `sms_blocks` ADD  UNIQUE KEY  `number` (`number`)');
?>
