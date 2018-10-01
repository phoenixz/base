<?php
/*
 * OS library
 *
 * This is the Operating System library. This library contains functions to execute operating system functions on (as many as possible) operating systems.
 * This library is a front end to other libraries that have specific implementations for the required functions on their specific operating systems
 * Examples of these other libraries are ubuntu, ubuntu1604, redhad, fedora, fedora25, etc
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
 * @package os
 *
 * @return void
 */
function os_library_init(){
    try{
        load_libs('servers');

    }catch(Exception $e){
        throw new bException('os_library_init(): Failed', $e);
    }
}



/*
 * Execute the specified command(s) on the specified hostname using the correct commands for each different operating system.
 * This function will check what operating system the specified $hostname runs, and load the required library for that operating system, and have that library execute the required commands to execute the specified function
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package os
 *
 * @param mixed $hostname
 * @param mixed commands
 * @return mixed
 */
function os_execute_command($hostname, $command = null){
    try{
        if(is_array($hostname)){
            /*
             * Server data has been specified by calling function
             */
            $server = $hostname;

        }else{
            /*
             * Load server data from database
             */
            $server = servers_get($hostname);

            if(!$server){
                throw new bException(tr('os_execute_command(): Specified hostname ":hostname" does not exist', array(':hostname' => $hostname)), 'not-exist');
            }
        }

        /*
         * Ensure we know what function to exexute
         */
        if($command === null){
            $command = current_function(-1);
showdie($command);
        }

        /*
         * Depending on the OS type, load the required library and continue
         * there
         */
        switch($server['os_type']){
            case 'linux':
                load_libs('linux');
                return 'linux_'.$command($server, $command);

            case 'mac':
                // FALLTHROUGH
            case 'windows':
                // FALLTHROUGH
            case 'freebsd':
                // FALLTHROUGH
            case 'openbsd':
                /*
                 * These operating systems are currently not supported
                 */
                not_supported();

            default:
                throw new bException(tr('os_execute_command(): Unknown operating system type ":type" found for hostname ":hostname"', array(':hostname' => $hostname, ':type' => $server['type'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('os_execute_command(): Failed', $e);
    }
}



/*
 * Enable SSH server TCP forwarding on the specified hostname
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package os
 *
 * @param mixed $hostname
 * return mixed
 */
function os_enable_ssh_tcp_forwarding($hostname){
    try{
        return os_execute_command($hostname);

    }catch(Exception $e){
        throw new bException('os_enable_ssh_tcp_forwarding(): Failed', $e);
    }
}
?>
