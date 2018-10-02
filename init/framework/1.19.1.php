<?php
/*
 * Fix servers_hostnames table
 */
sql_column_exists('servers_hostnames', 'seohostname', '!ALTER TABLE `servers_hostnames` ADD COLUMN `seohostname` VARCHAR(64) NOT NULL DEFAULT "" AFTER `hostname`');
sql_index_exists ('servers_hostnames', 'seohostname', '!ALTER TABLE `servers_hostnames` ADD KEY    `seohostname` (`seohostname`)');

$servers = sql_query('SELECT `id`, `hostname` FROM `servers`');
$insert  = sql_prepare('INSERT INTO `servers_hostnames` (`meta_id`, `servers_id`, `hostname`, `seohostname`)
                        VALUES                          (:meta_id , :servers_id , :hostname , :seohostname )');

load_libs('seo');
log_console(tr('Updating server hostnames in multi hostnames list'));

sql_query('TRUNCATE `servers_hostnames`');

while($server = sql_fetch($servers)){
    $servers_id = sql_get('SELECT `servers_id` FROM `servers_hostnames` WHERE `hostname` = :hostname', true, array('hostname' => $server['hostname']));

    if($servers_id){
        /*
         * Hostname is registered, $servers_id should match $server[id]
         */
        if($servers_id != $server['id']){
            log_console(tr('Failed to register main hostname ":hostname", it was already registered for servers_id ":id"', array(':hostname' => $server['hostname'], ':servers_id' => $servers_id)), 'yellow');
        }

    }else{
        cli_dot(1);
        $insert->execute(array(':meta_id'     => meta_action(),
                               ':servers_id'  => $server['id'],
                               ':hostname'    => $server['hostname'],
                               ':seohostname' => seo_unique($server['hostname'], 'servers_hostnames', null, 'seohostname')));
    }
}

cli_dot(false);
?>