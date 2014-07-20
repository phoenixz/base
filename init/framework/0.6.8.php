<?php
sql_column_exists('users', 'mailings', '!ALTER TABLE `users` ADD COLUMN `mailings` INT(1) DEFAULT 1 AFTER `date_validated`');
?>