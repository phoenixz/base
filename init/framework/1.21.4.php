<?php
/*
 * Find double hostnames and remove them
 * Fix servers hostname index
 */
log_console('Removing double hostnames from the `servers` table');

$doubles = sql_query('SELECT   COUNT(`servers`.`id`) AS `count`,
                               `servers`.`hostname`

                      FROM     `servers`

                      JOIN     `servers` AS `double`
                      ON       `servers`.`hostname` = `double`.`hostname`
                      AND      `servers`.`id`      != `double`.`id`

                      GROUP BY `servers`.`hostname`');

if($doubles->rowCount()){
    while($double = sql_fetch($doubles)){
        $servers = sql_query(' SELECT `id` FROM `servers` WHERE `hostname` = :hostname', array(':hostname' => $double['hostname']));

        while($servers_id = sql_fetch($servers, true)){
            cli_dot(1);

            sql_query(' DELETE FROM `servers_hostnames` WHERE `servers_id` = :servers_id', array(':servers_id' => $servers_id));
            sql_query(' DELETE FROM `servers`           WHERE `id`         = :id'        , array(':id'         => $servers_id));

            if(--$double['count'] >= 1){
                break;
            }
        }
    }
}

cli_dot(false);

sql_index_exists('servers', 'hostname_ssh_accounts_id',  'ALTER TABLE `servers` DROP INDEX `hostname_ssh_accounts_id`');
sql_index_exists('servers', 'hostname'                ,  'ALTER TABLE `servers` DROP INDEX `hostname`');
sql_index_exists('servers', 'hostname'                , '!ALTER TABLE `servers` ADD  UNIQUE KEY `hostname` (`hostname`)');

sql_index_exists('servers_hostnames', 'hostname', 'ALTER TABLE `servers_hostnames` DROP INDEX `hostname`');
sql_query('ALTER TABLE `servers_hostnames` ADD UNIQUE KEY `hostname_servers_id` (`hostname`, `servers_id`)');

sql_column_exists('api_accounts', 'security_type', 'ALTER TABLE `api_accounts` ADD COLUMN `security_type` ENUM ("none", "api_key", "sessions") NOT NULL DEFAULT "sessions"');
sql_column_exists('api_accounts', 'version'      , 'ALTER TABLE `version`      ADD COLUMN `version`       VARCHAR(8) NOT NULL');
?>