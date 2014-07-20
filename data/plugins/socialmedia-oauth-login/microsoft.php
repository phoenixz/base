<?php
/*
	@author		:	Giriraj Namachivayam
	@date 		:	Mar 20, 2013
	@demourl	:	http://ngiriraj.com/socialMedia/oauthlogin/msn.php
	@document	:	http://ngiriraj.com/work/hotmail-connect-by-using-oauth/
	@license	: 	Free to use,
	@History	:	V1.0 - Released oauth 2.0 service providers login access
	@oauth2		:	Support following oauth2 login
					Bitly
					Wordpress
					Paypal
					Facebook
					Google
					Microsoft(MSN,Live,Hotmail)
					Foursquare
					Box
					Reddit
					Yammer
					Yandex

*/

include(dirname(__FILE__).'/socialmedia_oauth_connect.php');

$config               = $_CONFIG['sso']['microsoft'];
$oauth                = new socialmedia_oauth_connect();

$oauth->provider      = 'Microsoft';
$oauth->client_id     = $config['appid'];
$oauth->client_secret = $config['secret'];
$oauth->scope         = $config['scope'];
$oauth->redirect_uri  = $config['redirect'];

$oauth->Initialize();

$code = (!empty($_REQUEST["code"])) ?  ($_REQUEST["code"]) : "";

if(empty($code)) {
	$oauth->Authorize();

}else{
	$oauth->code = $code;
#	print $oauth->getAccessToken();
	$getData = json_decode($oauth->getUserProfile());
	$oauth->debugJson($getData);
}


?>
