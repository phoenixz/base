<?php
/*
 * Add required table for proxies modifications, allow several proxies for one server
 */
sql_query('DROP TABLE IF EXISTS `servers_ssh_proxies`');

sql_query('CREATE TABLE `servers_ssh_proxies` (`id`         INT(11)     NOT NULL AUTO_INCREMENT,
                                               `createdon`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `createdby`  INT(11)         NULL,
                                               `meta_id`    INT(11)         NULL,
                                               `status`     VARCHAR(16)     NULL,
                                               `servers_id` INT(11)     NOT NULL,
                                               `proxies_id` INT(11)     NOT NULL,

                                               PRIMARY KEY `id`         (`id`),
                                                       KEY `meta_id`    (`meta_id`),
                                                       KEY `servers_id` (`servers_id`),
                                                       KEY `proxies_id` (`proxies_id`),
                                                       KEY `status`     (`status`),

                                               CONSTRAINT `fk_servers_ssh_proxies_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`    (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_servers_ssh_proxies_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_servers_ssh_proxies_proxies_id` FOREIGN KEY (`proxies_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT,
                                               CONSTRAINT `fk_servers_ssh_proxies_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`   (`id`) ON DELETE RESTRICT

                                             ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');


/*
 * Inserting proxy relation on new table
 */
if(sql_column_exists('servers', 'ssh_proxies_id')){
    $servers = sql_query('SELECT `id`, `ssh_proxies_id` FROM `servers` WHERE ssh_proxies_id IS NOT NULL');

    while($server = sql_fetch($servers)){
        sql_query('INSERT INTO `proxy_servers` (`meta_id`, `servers_id`, `proxies_id`)
                   VALUES                      (:meta_id , :servers_id , :proxies_id )',

                   array(':meta_id'    => meta_action(),
                         ':servers_id' => $server['id'],
                         ':proxies_id' => $server['ssh_proxies_id']));
    }

    /*
     * Removing ssh_proxies_id from servers table
     */
    sql_foreignkey_exists ('servers', 'fk_servers_ssh_proxies_id', 'ALTER TABLE `servers` DROP FOREIGN KEY `fk_servers_ssh_proxies_id`');
    sql_index_exists      ('servers', 'ssh_proxies_id'           , 'ALTER TABLE `servers` DROP INDEX  `ssh_proxies_id`');
    sql_column_exists     ('servers', 'ssh_proxies_id'           , 'ALTER TABLE `servers` DROP COLUMN `ssh_proxies_id`');
}
?>