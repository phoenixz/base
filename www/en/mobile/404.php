<?php
include_once(dirname(__FILE__).'/../libs/startup.php');

header('HTTP/1.0 404 Not Found');
echo '<h1>404 '.tr('Not Found').'</h1>';
echo tr('The page that you have requested could not be found.');
?>
