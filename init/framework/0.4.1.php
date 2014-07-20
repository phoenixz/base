<?php
	sql_query("CREATE TABLE IF NOT EXISTS `password_reset` (`id`             INT(10)     UNSIGNED NOT NULL AUTO_INCREMENT,
															`users_id`       INT(11)              NOT NULL,
															`code`           VARCHAR(255) DEFAULT     NULL,
															`date_requested` INT(11)      DEFAULT '0',
															`date_used`      INT(11)      DEFAULT '0',
															`ip`             VARCHAR(32)  DEFAULT     NULL,

															 PRIMARY KEY (`id`),
															 UNIQUE  KEY `code` (`code`)

														   ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8");
?>
