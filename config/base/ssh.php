<?php
/*
 * SSH CONFIGURATION FILE
 *
 * This configuration file is used by the SSH library
 *
 * @author Sven Oostenbrink <support@capmega.com>,
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Configuration
 * @package ssh
 */
$_CONFIG['ssh']                                                                 = array('options' => array('connect_timeout'       => '15',
                                                                                                           'check_host_ip'         => false,
                                                                                                           'strict_host_checking'  => true, // Will check if the host signature matches. WARNING: Putting this to false will leave you open to forgery attacks
                                                                                                           'user_known_hosts_file' => null,
                                                                                                           'port'                  => null));
?>
