<?php
// Notification configuration
$_CONFIG['notifications']      = array('force'            => false,                                                     // Always send notifications, even if we are not in production environment

                                       'methods'          => array('email'         => array('enabled' => true),
                                                                   'prowl'         => array('enabled' => false)));
?>
