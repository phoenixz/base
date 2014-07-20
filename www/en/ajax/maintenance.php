<?php
    require_once(dirname(__FILE__).'/../libs/startup.php');

	load_libs('json');
	json_reply(tr('The website is under maintenance'), 'MAINTENANCE');
?>
