<?php
/*
 * This file should be copied to any website that is on a server with multiple ips, this file should not load any external libraries.
 */
require_once(dirname(__FILE__).'/libs/startup.php');

$apikey               = isset_get($_GET['apikey']);
$params['url']        = isset_get($_GET['url']);
$params['contains']   = isset_get($_GET['contains']);
$params['getheaders'] = isset_get($_GET['getheaders'], false);

load_libs('curl,json');

if(empty($apikey) or ($apikey != $_CONFIG['curl']['proxy']['apikey'])){
	//http_response_code('401');
	//die('Unauthorized');
}

if(empty($params['url'])){
	http_response_code('400');
	die('Bad request');
}

$params['url'] = strtolower(urldecode(trim($params['url'])));

if((substr($params['url'], 0, 7) !== 'http://') and (substr($params['url'], 0, 8) !== 'https://')) {
	http_response_code('400');
	die('Bad request');
}

try{
	$start = microtime(true);
	$data  = curl_get($params);

}catch(Exception $e){
	$data = array('data'   => null,
				  'status' => array('http'    => str_replace('HTTP', '', $e->getCode())),
				  'error'  => array('code'    => $e->getCode(),
                                    'message' => $e->getMessage()));
}

$data['proxy'] = array('host' => $_SERVER['SERVER_NAME'],
					   'time' => microtime(true) - $start);

die('PROXY_RESULT'.base64_encode(json_encode_custom($data)));
?>
