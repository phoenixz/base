<?php
require_once(dirname(__FILE__).'/libs/startup.php');

if(substr(isset_get($_SERVER['REQUEST_URI']), 0, 7) == '/admin/'){
/*
 * This 404 is for the admin page
 */
return include('admin/404.php');
}

header('HTTP/1.0 404 Not Found');
echo '<h1><strong>404</strong>  - '.tr('Not Found').'</h1>';
echo tr('The page that you have requested could not be found.');
?>
