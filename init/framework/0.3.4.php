<?php
sql_query('DROP TABLE IF EXISTS `extended_logins`;');

sql_query('CREATE TABLE IF NOT EXISTS `statistics` (`id`       INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
													`statdate` DATETIME     NOT NULL,
													`code`     VARCHAR(128) NOT NULL,
													`count`    INT(11)      NOT NULL,

													INDEX  (`statdate`),
													UNIQUE (`code`, `statdate`)

													) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');


if(sql_column_exists('users', 'last_login')){
	sql_query('ALTER TABLE `users` DROP COLUMN `last_login`;');
}

sql_query('ALTER TABLE `users` ADD COLUMN `last_login` DATETIME NULL AFTER `date_added`');
?>
