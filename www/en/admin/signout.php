<?php
include_once(dirname(__FILE__).'/../libs/startup.php');
log_database('Logout : "'.isset_get($_SESSION['user']['name']).'"', 'LOGOUT_ADMIN');

load_libs('user');
user_signout();
redirect();
?>