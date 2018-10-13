<?php
/*
 * Linux library
 *
 * This is the Linux library. This library contains functions to execute operating system functions on as many as possible Linux distributions
 * This library is a front end to other libraries that have specific implementations for the required functions on their specific operating systems
 * Examples of these other libraries are ubuntu, ubuntu1604, redhad, fedora, fedora25, etc
 *
 * NOTE: These functions should NOT be called directly, they should be called by functions from the "os" library!
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @return void
 */
function linux_library_init(){
    try{
        load_libs('servers');

    }catch(Exception $e){
        throw new bException('linux_library_init(): Failed', $e);
    }
}



/*
 * Gets and returns SSH server AllowTcpForwarding configuration for the specified server
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @return boolean True if AllowTcpForwarding is configured, False if not
 */
function linux_get_ssh_tcp_forwarding($server){
    try{
        $server   = servers_get($server);
        $commands = 'sudo sshd -T 2> /dev/null | grep allowtcpforwarding';
        $results  = servers_exec($server, $commands);
        $result   = array_shift($results);
        $result   = strtolower(trim($result));
        $result   = str_cut($result, ' ', ' ');

        switch($result){
            case 'yes';
                return true;

            case 'no';
                return false;

            default:
                throw new bException(tr('linux_get_ssh_tcp_forwarding(): Unknown result ":result" received from SSHD configuration on server ":server"', array(':server' => $server, ':result' => $result)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('linux_get_ssh_tcp_forwarding(): Failed', $e);
    }
}



/*
 * Enable SSH TCP forwarding on the specified linux server. The function makes a backup of the current SSH daemon configuration file, and update the current file to enable TCP forwarding/
 * For the moment, this function assumes that every linux distribution uses /etc/ssh/sshd_config for SSH daemon configuration, and that all use "AllowTcpForwarding no" or "AllowTcpForwarding yes"
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param mixed $server
 * @param boolean $enable
 * @return array
 */
function linux_set_ssh_tcp_forwarding($server, $enable, $force = false){
    try{
        $server = servers_get($server);

        if(!$server['allow_sshd_modification'] and !$force){
            throw new bException(tr('linux_set_ssh_tcp_forwarding(): The specified server ":server" does not allow SSHD modifications', array(':server' => $server['hostname'])), 'not-allowed');
        }

        $enable   = ($enable ? 'yes' : 'no');
        $commands = 'sudo cp -a /etc/ssh/sshd_config /etc/ssh/sshd_config~'.date_convert(null, 'Ymd-His').' && sudo sed -iE \'s/AllowTcpForwarding \+\(yes\|no\)/AllowTcpForwarding '.$enable.'/gI\' /etc/ssh/sshd_config && sudo service ssh restart';
        $results  = servers_exec($server, $commands);

        return $enable;

    }catch(Exception $e){
        throw new bException('linux_enable_ssh_tcp_forwarding(): Failed', $e);
    }
}
?>
