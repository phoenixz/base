<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

right_or_redirect('admin');
load_libs('admin');

$html = '';

echo admin_start(tr('Admin Dashboard')).
	 $html.
	 admin_end();
?>