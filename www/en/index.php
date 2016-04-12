<?php
require_once(dirname(__FILE__).'/libs/startup.php');

load_libs('base');

html_load_css('style,ie,ie6', 'print');

$html = html_flash().'<br>'.tr('works').'<br>'.tr("also works");

$params = array('title'       => 'Welcome to base');
$meta   = array('description' => 'base',
                'keywords'    => 'base');

echo c_page($html, $params, $meta);
?>
