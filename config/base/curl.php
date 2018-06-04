<?php
/*
 * Basic BASE configuration file. DO NOT MODIFY THIS FILE! This file contains default values
 * that may be overwritten when you perform a system update!
 *
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE

 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>, Johan Geuze
 */

// Curl library configuration
$_CONFIG['curl']                                                                = array('proxies'          => array(),
                                                                                        'connect_timeout'  => 30,
                                                                                        'timeout'          => 30,
                                                                                        'log'              => false,
                                                                                        'user_agents'      => array('Mozilla/5.0 (Windows NT 5.1; rv:10.0.2) Gecko/20100101 Firefox/10.0.2',
                                                                                                                    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36'));
?>
