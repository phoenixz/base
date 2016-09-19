<?php
// Deploy target environments configuration

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

                                         'modes'               => array('dirs'  => array('/'             => 'a-rwx,ug+rx,g+s',
                                                                                         '/data'         => 'ug+w',
                                                                                         '/data/tmp'     => 'ug+w',
                                                                                         '/data/content' => 'ug+w'),

                                                                        'files' => array('/'             => 'a-rwx,ug+r',
                                                                                         '/data/tmp'     => 'ug+w',
                                                                                         '/data/content' => 'ug+w',
                                                                                         '/scripts'      => 'a-rw,ug+x')));

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

                                         'modes'               => array('dirs'  => array('/'             => 'a-rwx,ug+rx,g+s',
                                                                                         '/data'         => 'ug+w',
                                                                                         '/data/tmp'     => 'ug+w',
                                                                                         '/data/content' => 'u+w'),

                                                                        'files' => array('/'             => 'a-rwx,ug+r',
                                                                                         '/data/tmp'     => 'ug+w',
                                                                                         '/data/content' => 'u+w',
                                                                                         '/scripts'      => 'a-rw,ug+x')));
?>
