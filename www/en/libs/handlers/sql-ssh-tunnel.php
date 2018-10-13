<?php
/*
 * Apply default configuration
 */
load_libs('ssh');

$connector['ssh_tunnel'] = array_merge_null(array('target_hostname' => $_CONFIG['ssh']['tunnel']['target_hostname'],
                                                  'target_port'     => 3306), $connector['ssh_tunnel']);

/*
 * Assign port dynamically?
 */
if(empty($connector['port']) or empty($connector['ssh_tunnel']['source_port'])){
    if(empty($connector['port']) and !empty($connector['ssh_tunnel']['source_port'])){
        throw new bException(tr('sql_connect(): Connector requires an SSH tunnel with source_port ":port", but the connector has an empty port value specified. If dynamic port allocation is required, please ensure that both the connector port and the SSH tunnel port are null', array(':port' => $connector['ssh_tunnel']['source_port'])), 'invalid');
    }

    if(!empty($connector['port']) and empty($connector['ssh_tunnel']['source_port'])){
        throw new bException(tr('sql_connect(): Connector requires an SSH tunnel with a dynamic port assignment, but the connector has port ":port" hard specified. If dynamic port allocation is required, please ensure that both the connector port and the SSH tunnel port are null', array(':port' => $connector['port'])), 'invalid');
    }

    /*
     * Assign a dymanic port
     */
// :TODO: IP 127.0.0.1 is still hardcoded, this should be made dynamic as well
    load_libs('inet');
    $connector['port']                      = inet_get_available_port('127.0.0.1');
    $connector['ssh_tunnel']['source_port'] = $connector['port'];

    log_console(tr('Dynamically assigned port ":port" for SSH tunnel', array(':port' => $connector['port'])), 'VERBOSE');
}

$tunnel = ssh_tunnel($connector['ssh_tunnel']);

/*
 * The SSH tunnel MAY force a different port, so be sure to force the connector
 * port to the SSH source port
 */
$connector['ssh_tunnel']['pid'] = $tunnel['pid'];
$connector['port']              = $tunnel['source_port'];

usleep(isset_get($connector['ssh_tunnel']['usleep'], 10000));
?>