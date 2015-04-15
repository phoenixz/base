<?php
$_CONFIG['email'] = array('hostname'        => '{imap.gmail.com:993/imap/ssl}INBOX',

                          'users'           => null,

                          'conversations'   => array('size'             => 5,
                                                     'message_dates'    => '<span class="emaildate">%datetime%</span> - '),

                          'display'         => array('auto_reload'      => 60),

                          'polling'         => array('interval'         => 60));
?>
