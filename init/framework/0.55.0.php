<?php
/*
 * Update the users table
 *
 * Currently there are 2 fields for api keys "api_key" and "apikey"
 * The one that is being used is "apikey" so we must delete the field "api_key"
 */
sql_query('UPDATE `users` SET `apikey` = `api_key` WHERE `apikey` IS NULL AND `api_key` IS NOT NULL');
sql_query('ALTER TABLE `users` DROP COLUMN `api_key`');
?>
