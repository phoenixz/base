<?php
/*
 * Fix various possible blog DB issues
 */
sql_query('DELETE      `blogs_posts`
           FROM        `blogs_posts`

           LEFT JOIN   `blogs_posts` AS `siblings`
           ON          `siblings`.`masters_id` = `blogs_posts`.`id`

           WHERE       `blogs_posts`.`masters_id` IS NOT NULL
           AND         `siblings`.`masters_id`    IS NULL');

sql_query('DELETE FROM `blogs_posts` WHERE `status` = "_new" AND `masters_id` IS NULL');
sql_query('UPDATE `blogs_posts` SET `masters_id` = `id` WHERE `masters_id` IS NULL');
?>
