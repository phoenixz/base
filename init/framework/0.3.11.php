<?php
foreach(array('gp', 'ms', 'tw', 'yh') as $provider){
	if(!sql_index_exists('users', $provider.'_id')){
		sql_query('ALTER TABLE `users` ADD UNIQUE (`'.$provider.'_id`);');
	}
}
?>
