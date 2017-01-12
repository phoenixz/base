<?php
// Deploy target environments configuration

$_CONFIG['deploy']['local']     = array('sudo'            => true,
                                        'user'            => 'www-data',
                                        'group'           => 'www-data',

                                        'modes'           => array('dirs'  => array('/'             => 'a-rwx,ug+rwx,g+s'),

                                                                   'files' => array('/'             => 'a-rwx,ug+rw',
                                                                                    '/scripts'      => 'a-w,ug+rx')));

$_CONFIG['deploy']['trial']      = array('target_user'         => '',
                                         'target_server'       => '?website-server-domain-name?',
                                         'target_port'         => 22,
                                         'target_dir'          => '/var/www/html/trial/?website-domain-name?/',
                                         'categories'          => 'trial',
                                         'sudo'                => true,
                                         'rsync_parrallel'     => false,
                                         'user'                => 'www-data',
                                         'group'               => 'www-data',

                                         'languages'           => array('es'),

                                         'exclude_dirs'        => array('.git',
                                                                        'www/avatars/*',
                                                                        'www/logos/*',
                                                                        'www/photos/*',
                                                                        'www/headers/*',
                                                                        'www/streetview_cache/*',
                                                                        'www/tmp_photos/*',
                                                                        'data/xapian/*',
                                                                        'data/tmp',
                                                                        'data/content',
                                                                        'data/cache',
                                                                        'data/backups'),

                                         'modes'               => array());

$_CONFIG['deploy']['production'] = array('target_user'         => '',
                                         'target_server'       => '?website-server-domain-name?',
                                         'target_port'         => 22,
                                         'target_dir'          => '/var/www/html/?website-domain-name?/',
                                         'categories'          => 'production',
                                         'sudo'                => true,
                                         'rsync_parrallel'     => true,
                                         'user'                => 'www-data',
                                         'group'               => 'www-data',

                                         'languages'           => array('es'),

                                         'exclude_dirs'        => array('.git',
                                                                        'www/avatars/*',
                                                                        'www/logos/*',
                                                                        'www/photos/*',
                                                                        'www/headers/*',
                                                                        'www/streetview_cache/*',
                                                                        'www/tmp_photos/*',
                                                                        'data/xapian/*',
                                                                        'data/tmp',
                                                                        'data/content',
                                                                        'data/cache',
                                                                        'data/backups'),

                                         'modes'               => array());
?>
