<?php
/*
 * Add extra standard rights
 */
$p  = sql_prepare('INSERT INTO `rights` (`name`, `description`)
                   VALUES               (:name , :description)

                   ON DUPLICATE KEY UPDATE `name`        = :name,
                                           `description` = :description;');

$rights = array('profile'        => 'This right will allow the user access to their profile information',
                'profile_update' => 'This right will allow the user to update their profile information',
                'password'       => 'This right will allow the user access to the user passwords update page');

foreach($rights as $name => $description){
    $p->execute(array(':name'        => $name,
                      ':description' => $description));
}
?>
