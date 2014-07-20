<?php
include_once(dirname(__FILE__).'/../libs/startup.php');

load_libs('base');

html_load_css('style,ie,ie6', 'print');

echo html_header();
echo tr('works').'<br>';
echo tr("also works");
echo html_footer();
?>
