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
if(isset($_POST['submit'])){
	load_libs('upload');
	upload_check_files();
}

echo html_header();
echo '<h1>THIS IS AN UPLOAD TESTER</h1>
<form action="upload.php" method="post" enctype="multipart/form-data">
	<ul>
		<li><input name="test" type="text" value="test text"/></li>
		<li><input name="test1" type="file" /></li>
		<li><input name="test2" type="file" /></li>
		<li><input name="test[]" type="file" /></li>
		<li><input name="test[]" type="file" /></li>
		<li><input name="submit" type="submit" /></li>
	</ul>
</form>';
echo html_footer();
?>
