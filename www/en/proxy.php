<?php
/*
 * This file should be copied to any website that is on a server with multiple ips, this file should not load any external libraries.
 */
require_once(dirname(__FILE__).'/libs/startup.php');

$url        = isset_get($_GET['url']);
$contains   = isset_get($_GET['contains']);
$apikey     = isset_get($_GET['apikey']);
$getheaders = isset_get($_GET['getheaders'], false);

load_libs('curl');

if(empty($_GET['apikey']) or ($_GET['apikey'] != $_CONFIG['curl']['proxy']['apikey'])){
	http_response_code('401');
	die('Unauthorized');
}

if(empty($_GET['url'])){
	http_response_code('400');
	die('Bad request');
}

$_GET['url'] = urldecode(trim($_GET['url']));

if((strtolower(substr($_GET['url'], 0, 4)) !== 'http')) {
	http_response_code('400');
	die('Bad request');
}

try{
	$start = microtime(true);
	$data  = curl_get(array('url'        => $_GET['url'],
							'getheaders' => $getheaders,
							'contains'   => $contains));

}catch(Exception $e){
	$data = array('data'   => null,
				  'status' => array('http'    => str_replace('HTTP', $e->getCode())),
				  'error'  => array('code'    => $e->getCode(),
                                    'message' => $e->getMessage()));
}

$data['proxy'] = array('host' => $_SERVER['SERVER_NAME'],
					   'time' => microtime(true) - $start);

die('PROXY_RESULT'.base64_encode($data));
?>
