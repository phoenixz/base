<?php
include_once(dirname(__FILE__).'/libs/startup.php');
load_libs('user');
user_signout();
redirect();
?>
