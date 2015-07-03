<?php
/*
 * This is a test script. It will NOT work in production environments!
 */
require_once(dirname(__FILE__).'/../libs/startup.php');

if(ENVIRONMENT == 'production'){
	page_show(404);
}

html_only();

/*
 * DO NOT MODIFY THE LINES ABOVE!
 * FROM HERE BE TESTS!
 */
page_show(404);
?>
