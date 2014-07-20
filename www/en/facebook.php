<?php
require_once(dirname(__FILE__).'/libs/startup.php');

try{
	load_libs('sso,custom');
	sso('facebook');

}catch(Exception $e){
	sso_fail(tr('Facebook login failed. Please try again later'), 'signin.php');
}
?>
