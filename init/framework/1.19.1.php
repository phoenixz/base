<?php
/*
 * Fix servers_hostnames table
 */
sql_column_exists('servers_hostnames', 'seohostname', '!ALTER TABLE `servers_hostnames` ADD COLUMN `seohostname` VARCHAR(64) NOT NULL DEFAULT "" AFTER `hostname`');
sql_index_exists ('servers_hostnames', 'seohostname', '!ALTER TABLE `servers_hostnames` ADD KEY    `seohostname` (`seohostname`)');

$servers = sql_query('SELECT `id`, `hostname`, `ipv4` FROM `servers`');
$insert  = sql_prepare('INSERT INTO `servers_hostnames` (`meta_id`, `servers_id`, `hostname`, `seohostname`)
                        VALUES                          (:meta_id , :servers_id , :hostname , :seohostname )');

load_libs('seo');
log_console(tr('Updating server IPv4\'s and server hostnames in multi hostnames list'));

sql_query('TRUNCATE `servers_hostnames`');

while($server = sql_fetch($servers)){
    if(!$server['ipv4']){
        $server['ipv4'] = gethostbynamel($server['hostname']);

        if(!$server['ipv4']){
            $server['ipv4'] = null;
            log_console(tr('No IPv4 found for hostname ":hostname"', array(':ip' => $server['ipv4'], ':hostname' => $server['hostname'])), 'yellow');

        }else{
            if(count($server['ipv4']) == 1){
                $server['ipv4'] = array_shift($server['ipv4']);
                log_console(tr('Set IPv4 ":ip" for hostname ":hostname"', array(':ip' => $server['ipv4'], ':hostname' => $server['hostname'])));

            }else{
                log_console(tr('Found multiple IPv4 entries for hostname ":hostname", not automatically updating', array(':hostname' => $server['hostname'])), 'yellow');
            }
        }

        sql_query('UPDATE `servers` SET `ipv4` = :ipv4 WHERE `id` = :id', array(':id' => $server['id'], ':ipv4' => $server['ipv4']));
    }

    $servers_id = sql_get('SELECT `servers_id` FROM `servers_hostnames` WHERE `hostname` = :hostname', true, array('hostname' => $server['hostname']));

    if($servers_id){
        /*
         * Hostname is registered, $servers_id should match $server[id]
         */
        if($servers_id != $server['id']){
            log_console(tr('Failed to register main hostname ":hostname", it was already registered for servers_id ":id"', array(':hostname' => $server['hostname'], ':servers_id' => $servers_id)), 'yellow');
        }

    }else{
        log_console(tr('Adding hostname ":hostname" to servers hostnames table', array(':hostname' => $server['hostname'])));
        $insert->execute(array(':meta_id'     => meta_action(),
                               ':servers_id'  => $server['id'],
                               ':hostname'    => $server['hostname'],
                               ':seohostname' => seo_unique($server['hostname'], 'servers_hostnames', null, 'seohostname')));
    }
}

cli_dot(false);
?>