<?php
/*
 * Basic BASE configuration file. DO NOT MODIFY THIS FILE! This file contains default values
 * that may be overwritten when you perform a system update!
 *
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE

 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
 */
$_CONFIG['blogs']               = array('enabled'         => false,

                                        'images'          => array('resize'         => array('thumbs'        => array('x' => false,
                                                                                                                      'y' => false)),

                                                                                             'images'        => array('x' => false,
                                                                                                                      'y' => false)),
                                        'url'             => '/%category%/%date%/%seoname%.html');
?>