<?php
$p = sql_prepare('INSERT INTO `rights` (`name`, `description`)
                  VALUES               (:name , :description)

                  ON DUPLICATE KEY UPDATE `name` = :name;');

$rights = array('admin' => 'This right allows the user to view admin only pages',
                'god'   => 'This right allows the user to view ALL rights managed pages',
                'devil' => 'This right allows the user to view NO rights managed pages at all (This right takes precedence over the "god" right)',
                'users' => 'This right allows the user to access the user management area');

foreach($rights as $name => $description){
    $p->execute(array(':name'        => $name,
                      ':description' => $description));
}

sql_column_exists('users', 'phones', '!ALTER TABLE `users` ADD COLUMN `phones` VARCHAR(64) NULL AFTER `email`');
?>