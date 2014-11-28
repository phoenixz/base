<?php
include_once(dirname(__FILE__).'/../libs/startup.php');
log_database('Logout : "'.isset_get($_SESSION['user']['name']).'"', 'LOGOUT_ADMIN');

user_signout();
redirect();
?>