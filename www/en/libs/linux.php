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

    }catch(Exception $e){
        throw new bException('linux_library_init(): Failed', $e);
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
 * @param
 */
function linux_enable_ssh_tcp_forwarding($server, $background = false, $function = null){
    try{
        $commands = 'sudo cp -a /etc/ssh/sshd_config /etc/ssh/sshd_config~'.date_convert(null, 'Ymd-His').' && sudo sed -i "s/AllowTcpForwarding \+no/AllowTcpForwarding yes/" /etc/ssh/sshd_config && sudo service ssh restart';
        servers_exec($server, $commands, $background, $function);

    }catch(Exception $e){
        throw new bException('linux_enable_ssh_tcp_forwarding(): Failed', $e);
    }
}
?>
