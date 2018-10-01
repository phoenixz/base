<?php
/*
 * Adding support for multiple hostnames per server
 */
sql_query('DROP TABLE IF EXISTS `servers_hostnames`');



/*
 *
 */
sql_query('CREATE TABLE `servers_hostnames` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                             `createdon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                             `createdby`   INT(11)         NULL DEFAULT NULL,
                                             `meta_id`     INT(11)     NOT NULL,
                                             `status`      VARCHAR(16)     NULL DEFAULT NULL,
                                             `servers_id`  INT(11)     NOT NULL,
                                             `hostname`    VARCHAR(64) NOT NULL DEFAULT "",

                                                    KEY `createdon`  (`createdon`),
                                                    KEY `createdby`  (`createdby`),
                                                    KEY `meta_id`    (`meta_id`),
                                                    KEY `status`     (`status`),
                                                    KEY `servers_id` (`servers_id`),
                                             UNIQUE KEY `hostname`   (`hostname`),

                                             CONSTRAINT `fk_servers_hostnames_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_servers_hostnames_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_servers_hostnames_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * Add all current hostnames to server_hostnames list
 */
$servers = sql_query('SELECT `id`, `hostname` FROM `servers` WHERE `status` IS NULL');
$insert  = sql_prepare('INSERT INTO `servers_hostnames` (`meta_id`, `servers_id`, `hostname`)
                        VALUES                          (:meta_id , :servers_id , :hostname )');

while($server = sql_fetch($servers)){
    $insert->execute(array(':meta_id'    => meta_action(),
                           ':servers_id' => $server['id'],
                           ':hostname'   => $server['hostname']));
}



/*
 * Upgrade servers table
 */
sql_foreignkey_exists('servers', 'fk_servers_modifiedby', 'ALTER TABLE `servers` DROP FOREIGN KEY `fk_servers_modifiedby`;');

sql_index_exists ('servers', 'modifiedby', 'ALTER TABLE `servers` DROP KEY    `modifiedby`');
sql_column_exists('servers', 'modifiedby', 'ALTER TABLE `servers` DROP COLUMN `modifiedby`');

sql_index_exists ('servers', 'modifiedon', 'ALTER TABLE `servers` DROP KEY    `modifiedon`');
sql_column_exists('servers', 'modifiedon', 'ALTER TABLE `servers` DROP COLUMN `modifiedon`');

sql_column_exists('servers', 'meta_id', '!ALTER TABLE `servers` ADD COLUMN `meta_id` INT(11) NULL DEFAULT NULL AFTER `createdby`');
sql_index_exists ('servers', 'meta_id', '!ALTER TABLE `servers` ADD KEY    `meta_id` (`meta_id`)');

sql_foreignkey_exists('servers', 'fk_servers_meta_id', '!ALTER TABLE `servers` ADD CONSTRAINT `fk_servers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');



/*
 * Upgrade email_servers table
 */
sql_foreignkey_exists('email_servers', 'fk_email_servers_modifiedby', 'ALTER TABLE `email_servers` DROP FOREIGN KEY `fk_email_servers_modifiedby`;');

sql_index_exists ('email_servers', 'modifiedby', 'ALTER TABLE `email_servers` DROP KEY    `modifiedby`');
sql_column_exists('email_servers', 'modifiedby', 'ALTER TABLE `email_servers` DROP COLUMN `modifiedby`');

sql_index_exists ('email_servers', 'modifiedon', 'ALTER TABLE `email_servers` DROP KEY    `modifiedon`');
sql_column_exists('email_servers', 'modifiedon', 'ALTER TABLE `email_servers` DROP COLUMN `modifiedon`');

sql_column_exists('email_servers', 'meta_id', '!ALTER TABLE `email_servers` ADD COLUMN `meta_id` INT(11) NULL DEFAULT NULL AFTER `createdby`');
sql_index_exists ('email_servers', 'meta_id', '!ALTER TABLE `email_servers` ADD KEY    `meta_id` (`meta_id`)');

sql_foreignkey_exists('email_servers', 'fk_email_servers_meta_id', '!ALTER TABLE `email_servers` ADD CONSTRAINT `fk_email_servers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');
?>