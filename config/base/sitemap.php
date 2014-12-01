<?php
$_CONFIG['sitemap'] = array('ondeploy'         => true,                         // true | false. If set true, will always regenerate the sitemap on each deploy
                            'change_frequency' => 'never',                      // ENUM("always", "hourly", "daily", "weekly", "monthly", "yearly", "never")
                            'languages'        => null,                         // null (all) or array with required languages
                            'priority'         => 0,                            // Priority level, can usually be left at 0
                            'modified'         => null,                         // File modified. Either "auto", "current" or a static date. If "auto", the file last modified date will be used. If "current", the current date will be used. If a static date, then that will be used

                            'ignore'           => array('404.php',              // List of files that should be ignored. The files in these list should near always be ignored, so when overwriting this list, be sure to add these files again!
                                                        'go.php',
                                                        'google.php',
                                                        'microsoft.php',
                                                        'facebook.php',
                                                        'maintenance.php',
                                                        'gotonormal.php',
                                                        'gotomobile.php',
                                                        'robots.txt',
                                                        'signout.php',
                                                        'unsubscribe.php',
                                                        'api',
                                                        'ajax',
                                                        'css',
                                                        'img',
                                                        'image',
                                                        'libs',
                                                        'js',
                                                        'javascript',
                                                        'mobile',
                                                        'popups',
                                                        'pub',
                                                        'tests'),

                            'filetypes'        => array('php',                  // Filetypes to process. Default are .php and .html, add any file like image or video or music if required
                                                        'html',
                                                        'jpg',
                                                        'jpeg',
                                                        'gif',
                                                        'png'),

                            'force_html'       => true,                         // If set to true, .php files will be displayed as .html
                            'recursive'        => false,                        // Recurse into sub directories or not.
                            'rename'           => array('index.html' => '',     // Rename specified files
                                                        'index.php'  => ''),
                            'xsl'              => '');                          // XSL file, in case the XML file should be displayed nicely
