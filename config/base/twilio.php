<?php
$_CONFIG['twilio'] = array('accounts' => array('test' => array('accounts_id'    => null,
                                                               'accounts_token' => null,
                                                               'sources'        => array('number' => 'name'))),

                           'conversations'  => array('size'             => 3,
                                                     'message_dates'    => false),

                           'display'        => array('auto_reload'      => 60),

                           'defaults'       => array('country_code'     => 1));
?>
