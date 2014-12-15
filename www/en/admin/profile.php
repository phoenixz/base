<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

rights_or_redirect('admin');

$profile      = true;
$_GET['user'] = $_SESSION['user']['username'];
include('user.php');
?>