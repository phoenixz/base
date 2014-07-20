<?php
$columns   = array('token',
				   'token_access',
				   'token_authentication');

//$providers = array('fb',
//				   'gp',
//				   'tw',
//				   'ms',
//				   'yh');

$providers = array('ms');

/*
 * Drop all token columns for all providers
 */
foreach($providers as $provider){
	foreach($columns as $column){
		$column = $provider.'_'.$column;

		if(sql_column_exists('users', $column)){
			sql_query('ALTER TABLE `users` DROP COLUMN `'.$column.'`;');
		}
	}
}

/*
 * Add the new token columns for all providers
 */
foreach($providers as $provider){
	sql_query('ALTER TABLE `users` ADD COLUMN `'.$provider.'_token_authentication` VARCHAR(255) NULL AFTER `'.$provider.'_id`');
	sql_query('ALTER TABLE `users` ADD COLUMN `'.$provider.'_token_access`         VARCHAR(255) NULL AFTER `'.$provider.'_token_authentication`');
}
?>
