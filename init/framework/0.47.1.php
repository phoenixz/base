<?php
/*
 * Fixed users apikey uniqueness
 */
sql_index_exists('users', 'apikey', '!ALTER TABLE `users` DROP KEY `apikey`');
sql_query('ALTER TABLE `users` ADD UNIQUE KEY `url` (`url`)');
?>
