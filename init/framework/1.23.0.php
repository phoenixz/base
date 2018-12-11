<?php

sql_column_exists('users', 'cities_id',    '!ALTER TABLE `users` ADD COLUMN `cities_id`    INT NULL AFTER `fake`;');
sql_column_exists('users', 'states_id',    '!ALTER TABLE `users` ADD COLUMN `states_id`    INT NULL AFTER `cities_id`');
sql_column_exists('users', 'countries_id', '!ALTER TABLE `users` ADD COLUMN `countries_id` INT NULL AFTER `states_id`');


sql_foreignkey_exists('users', 'fk_users_cities_id',    '!ALTER TABLE `users` ADD CONSTRAINT `fk_users_cities_id` FOREIGN KEY (`cities_id`)    REFERENCES `geo_cities`    (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
sql_foreignkey_exists('users', 'fk_users_states_id',    '!ALTER TABLE `users` ADD CONSTRAINT `fk_users_states_id` FOREIGN KEY (`states_id`)    REFERENCES `geo_states`    (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
sql_foreignkey_exists('users', 'fk_users_countries_id', '!ALTER TABLE `users` ADD CONSTRAINT `fk_users_countries` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
?>