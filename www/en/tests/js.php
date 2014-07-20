<?php
/*
 * This is a test script. It will NOT work in production environments!
 */
require_once(dirname(__FILE__).'/../libs/startup.php');

if(ENVIRONMENT == 'production'){
	page_404();
}

html_only();

/*
 * DO NOT MODIFY THE LINES ABOVE!
 * FROM HERE BE TESTS!
 */
html_load_js('jquery,base/popup,base/jquery.validate');

echo html_header();
echo '<h1>THIS IS A JAVASCRIPT TEST FILE</h1>';
echo htmlentities('Please check the source code to see if all JS <script> tags have been added correcty');
echo html_footer();
?>
