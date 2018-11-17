<?php
/*
 * Fix ssh_accounts name, seoname, and username indices, all should be unique
 * First scan for duplicates and remove if required
 */
$delete     = sql_prepare('DELETE FROM `ssh_accounts` WHERE `id` = :id');
$duplicates = sql_query('SELECT   `ssh_accounts_duplicates`.`name`,
                                  `ssh_accounts_duplicates`.`username`

                         FROM     `ssh_accounts`

                         JOIN     `ssh_accounts` AS `ssh_accounts_duplicates`
                         ON       `ssh_accounts`.`id`      != `ssh_accounts_duplicates`.`id`
                         AND     (`ssh_accounts`.`username` = `ssh_accounts_duplicates`.`username`
                         OR       `ssh_accounts`.`name`     = `ssh_accounts_duplicates`.`name`)

                         GROUP BY `ssh_accounts_duplicates`.`username`');

while($duplicate = sql_fetch($duplicates)){
    $count = 2;

    while($count > 1){
        $id = sql_get('SELECT `id`

                       FROM   `ssh_accounts`

                       WHERE  `name`     = :name
                       OR     `username` = :username

                       ORDER BY `createdon` DESC

                       LIMIT 1',

                       true, array(':name'     => $duplicate['name'],
                                   ':username' => $duplicate['username']));

        $delete->execute(array(':id' => $id));

        $count = sql_get('SELECT COUNT(`id`) AS `count`

                          FROM   `ssh_accounts`

                          WHERE  `name`     = :name
                          OR     `username` = :username',

                          true, array(':name'     => $duplicate['name'],
                                      ':username' => $duplicate['username']));
    }
}

sql_index_exists ('ssh_accounts',     'name',  'ALTER TABLE `ssh_accounts` DROP KEY    `name`');
sql_index_exists ('ssh_accounts',  'seoname',  'ALTER TABLE `ssh_accounts` DROP KEY `seoname`');

sql_index_exists ('ssh_accounts',     'name', '!ALTER TABLE `ssh_accounts` ADD UNIQUE KEY     `name`     (`name`)');
sql_index_exists ('ssh_accounts',  'seoname', '!ALTER TABLE `ssh_accounts` ADD UNIQUE KEY  `seoname`  (`seoname`)');
sql_index_exists ('ssh_accounts', 'username', '!ALTER TABLE `ssh_accounts` ADD UNIQUE KEY `username` (`username`)');
?>