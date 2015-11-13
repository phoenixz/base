<?php
$_CONFIG['email'] = array('imap'            => '{imap.gmail.com:993/imap/ssl}INBOX',

                          'smtp'            => array('host'   => 'smtp.google.com',
                                                     'port'   => 587,
                                                     'auth'   => true,
                                                     'secure' => 'tls'),

                          'sql'             => array(),

                          'users'           => null,

                          'conversations'   => array('size'             => 5,
                                                     'message_dates'    => '<span class="emaildate">%datetime%</span> - '),

                          'display'         => array('auto_reload'      => 60),

                          'polling'         => array('interval'         => 60),

                          'from'            => 'support@email.com',

                          'subject'         => 'Default subject',

                          'templates'       => array('default' => array('name' => 'Default template',
                                                                        'file' => 'emails/template')));

?>
