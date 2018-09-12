<?php
/*
 * Fix missing index on sms_messages that causes SMS operations to be slow
 */
sql_index_exists ('sms_messages', 'to_phone', '!ALTER TABLE `sms_messages` ADD KEY `to_phone` (`to_phone`);');
?>