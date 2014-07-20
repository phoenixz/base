<?php
/*
 * All blog post statusses must have a NOT NULL value from now on.
 */
sql_query('UPDATE `blogs_posts` SET `status` = "unpublished" WHERE `status` IS NULL');
sql_query('ALTER TABLE `blogs_posts` CHANGE `status` `status` VARCHAR(16) NOT NULL');
?>
