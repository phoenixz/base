<?php
/*
 * Password field is too small for SHA256 and definitely for sSHA512
 */
sql_query('ALTER TABLE `users` CHANGE COLUMN `password` `password` VARCHAR(140) NOT NULL');
?>
