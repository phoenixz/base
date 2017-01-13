<?php
$_CONFIG['email'] = array('imap'            => '{imap.gmail.com:993/imap/ssl}INBOX',

                          'imap_cache'      => 10,

                          'smtp'            => array('host'   => 'smtp.google.com',
                                                     'port'   => 587,
                                                     'auth'   => true,
                                                     'secure' => 'tls'),

                          'sql'             => array(),

                          'forward_option'  => 'source',

                          'users'           => null,

                          'encryption_key'  => '',

                          'conversations'   => array('size'             => 5,
                                                     'message_dates'    => '<span class="emaildate">%datetime%</span> - '),

                          'delayed'         => array('auto_start'       => true),

                          'display'         => array('auto_reload'      => 60),

                          'polling'         => array('interval'         => 60),

                          'from'            => 'support@email.com',

                          'subject'         => 'Default subject',

                          'templates'       => array('default' => array('name' => 'Default template',
                                                                        'file' => 'emails/template')));

?>
