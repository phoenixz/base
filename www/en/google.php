<?php
require_once(dirname(__FILE__).'/libs/startup.php');

try{
	load_libs('sso');
	sso('google');

}catch(Exception $e){
	sso_fail(tr('Google login failed. Please try again later'), 'signin.php');
}
?>
