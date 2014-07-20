<?php
include_once(dirname(__FILE__).'/../libs/startup.php');

header('HTTP/1.0 503 Maintenance');
echo '<h1>'.tr('Maintenance').'</h1>';
echo tr('The site currently is in maintenance, we will be right back!.');
?>
