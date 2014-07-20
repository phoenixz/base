<?php
require_once(dirname(__FILE__).'/../libs/startup.php');

load_libs('admin');

if(isset($_GET['logout'])) {
	log_database('Logout : "'.$_SESSION['admin']['name'].'"', 'ADMIN');
	unset($_SESSION['admin']);
	redirect('login.php');
}

if(isset($_POST['dosubmit'])) {
	if(empty($_POST['username']) or empty($_POST['password'])){
		log_database('Login failed : NO OR PARTIAL CREDENDIALS','ADMIN');
		$flash = tr('Please specify a user and password');

	}elseif(empty($_CONFIG['admins'][$_POST['username']]) or ($_CONFIG['admins'][$_POST['username']]['password'] != $_POST['password'])) {
		log_database('Login failed : User "'.$_POST['username'].'"','ADMIN');
		$flash = tr('Invalid credentials');

	}else{
		//login!
		$_SESSION['admin'] = array('name'  => $_POST['username'],
								   'admin' => true,
								   'id'    => -1);

		log_database('Login success : User "'.$_POST['username'].'"','ADMIN');

		redirect('/admin/index.php');
	}
}

$html = html_flash(isset_get($flash), 'error').'<form method="post" action="'.$_SERVER['REQUEST_URI'].'">
	<label>'.tr('Username').'</label><br>
	<input name="username" type="text" value="'.isset_get($_POST['username'], '').'" placeholder="'.tr('Your username').'"><br>
	<label>'.tr('Password').'</label><br>
	<input name="password" type="password" placeholder="'.tr('Your password').'"><br>
	<input type="submit" name="dosubmit" value="'.tr('Login').'">
</form>';

echo admin_start(tr('Login')).
	 $html.
	 admin_end();
?>