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
html_load_css('style,ie,ie6', 'print');

echo html_header();
echo '<h1>THIS IS A CSS TEST FILE</h1>';
echo htmlentities('Please check the source code to see if all CSS <link> tags have been added correcty');
echo html_footer();
?>
