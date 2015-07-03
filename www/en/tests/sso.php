<?php
/*
 * This is a generic test script for whatever test needed. It will NOT work in production environments!
 */
require_once(dirname(__FILE__).'/../libs/startup.php');

if(ENVIRONMENT == 'production'){
	page_show(404);
}

/*
 * FROM HERE BE TESTS!
 */
if(empty($_GET['provider'])){
?>
<ul>
	<li>
		V <a href='sso.php?provider=google'>Google+</a>
	</li>
	<li>
		X <a href='sso.php?provider=facebook'>Facebook</a>
	</li>
	<li>
		V <a href='sso.php?provider=microsoft'>Microsoft</a>
	</li>
	<li>
		X <a href='sso.php?provider=twitter'>Twitter</a>
	</li>
	<li>
		V <a href='sso.php?provider=paypal'>PayPal</a>
	</li>
	<li>
		X <a href='sso.php?provider=linkedin'>LinkedIn</a>
	</li>
	<li>
		X <a href='sso.php?provider=reddit'>Reddit (To test this API)</a>
	</li>
	<li>
		X <a href='sso.php?provider=yandex'>Yandex (Another, just to test)</a>
	</li>
</ul>
<?php

}else{
	try{
		load_libs('sso');
		$result = sso($_GET['provider'], true);

		echo '<h1>Successful loging with provider "'.$_GET['provider'].'"</h1><ul>';

		if($_GET['provider'] == 'paypal'){
			echo 'NOTE: paypal does not seem to return a token';
		}

		show($result);

	}catch(Exception $e){
		echo '<h1>Login with provider "'.$_GET['provider'].'" failed</h1><ul>';

		foreach($e->getMessages() as $message){
			echo '<li>'.$message.'<li>';
		}

		echo '<ul>';
	}

	?>
	<ul>
		<li>
			<a href="sso.php?provider=<?php echo $_GET['provider'] ?>">Retry</a>
		</li>
		<li>
			<a href="sso.php">Back</a>
		</li>
	</ul>
	<?php
}
?>
