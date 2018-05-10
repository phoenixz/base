<?php
/*
 * Basic BASE configuration file. DO NOT MODIFY THIS FILE! This file contains default values
 * that may be overwritten when you perform a system update!
 *
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY

 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>, Johan Geuze
 */
$_CONFIG['sitemap'] = array('enabled'          => true,                                     // true | false, if disabled, the sitemap script will refuse to work

                            'change_frequency' => 'never',                                  // ENUM("always", "hourly", "daily", "weekly", "monthly", "yearly", "never")

                            'disallow'         => array('/\/google.html/i'      => null,    // List of REGEX paths that should always show as "Decline"
                                                        '/\/microsoft.html/i'   => null,
                                                        '/\/facebook.html/i'    => null,
                                                        '/\/gotonormal.html/i'  => null,
                                                        '/\/gotomobile.html/i'  => null,
                                                        '/\/signout.html/i'     => null,
                                                        '/\/unsubscribe.html/i' => null),

                            'filetypes'        => array('php',                              // Filetypes to process. Default are .php and .html, add any file like image or video or music if required
                                                        'html',
                                                        'jpg',
                                                        'jpeg',
                                                        'gif',
                                                        'png'),

                            'ignore'           => array('/\/go.html/i',                     // List of files (or paths) in regex format that should be ignored. The files in these list should near always be ignored, so when overwriting this list, be sure to add these files again!
                                                        '/\/robots.txt/i',
                                                        '/\/api\/.*/i',
                                                        '/\/ajax\/.*/i',
                                                        '/\/css\/.*/i',
                                                        '/\/img\/.*/i',
                                                        '/\/image\/.*/i',
                                                        '/\/libs\/.*/i',
                                                        '/\/js\/.*/i',
                                                        '/\/javascript\/.*/i',
                                                        '/\/mobile\/.*/i',
                                                        '/\/popups\/.*/i',
                                                        '/\/pub\/.*/i',
                                                        '/\/tests\/.*/i'),

                            'languages'        => null,                                     // null (all) or array with required languages
                            'modified'         => 'auto',                                   // File modified date. Either "auto", "current" or a static date. If "auto", the file last modified date will be used. If "current", the current date will be used. If a static date, then that will be used
                            'ondeploy'         => true,                                     // true | false. If set true, will always regenerate the sitemap on each deploy
                            'priority'         => 0,                                        // Priority level, can usually be left at 0
                            'scan'             => array('/index.html'),                     // Files that should be scanned. If contains %language% then these files willl be scanned for each language. Links from there will be scanned as well as long as they are within the same domain and not in the ignore list.

                            'show_changefreq'  => true,                                     // Show change frequency
                            'show_priority'    => true,                                     // Show priority

                            'xsl'              => '/sitemap.xsl');                          // XSL file, in case the XML file should be displayed nicely
?>