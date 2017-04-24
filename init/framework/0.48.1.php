<?php
/*
 * Fix various possible blog DB issues
 */
sql_query('DELETE FROM `blogs_posts` WHERE `status` = "_new"');
sql_query('UPDATE `blogs_posts` SET `masters_id` = `id` WHERE `masters_id` IS NULL');
?>
