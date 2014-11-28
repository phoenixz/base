<?php
/*
 * Add user role links, drop admin column
 */
sql_column_exists('users', 'role'    , '!ALTER TABLE `users` ADD  COLUMN `role`     VARCHAR(32) AFTER `admin`');
sql_column_exists('users', 'roles_id', '!ALTER TABLE `users` ADD  COLUMN `roles_id` INT(11)     AFTER `role`');
sql_column_exists('users', 'admin'   ,  'ALTER TABLE `users` DROP COLUMN `admin`');



/*
 * Update rights table to use "createdon" and "modifiedon"
 */
sql_index_exists ('rights', 'addedby'   ,  'ALTER TABLE `rights` DROP INDEX `addedby`');
sql_index_exists ('rights', 'addedon'   ,  'ALTER TABLE `rights` DROP INDEX `addedon`');

sql_column_exists('rights', 'createdby' , '!ALTER TABLE `rights` CHANGE COLUMN `addedby` `createdby` INT(11)');
sql_column_exists('rights', 'createdon' , '!ALTER TABLE `rights` CHANGE COLUMN `addedon` `createdon` TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

sql_column_exists('rights', 'modifiedby', '!ALTER TABLE `rights` ADD COLUMN `modifiedby` INT(11)   AFTER `createdon`');
sql_column_exists('rights', 'modifiedon', '!ALTER TABLE `rights` ADD COLUMN `modifiedon` DATETIME  AFTER `modifiedby`');
sql_column_exists('rights', 'status'    , '!ALTER TABLE `rights` ADD COLUMN `status` VARCHAR(16)   AFTER `modifiedon`');

sql_index_exists ('rights', 'createdby' , '!ALTER TABLE `rights` ADD  INDEX (`createdby`)');
sql_index_exists ('rights', 'createdon' , '!ALTER TABLE `rights` ADD  INDEX (`createdon`)');
sql_index_exists ('rights', 'modifiedby', '!ALTER TABLE `rights` ADD  INDEX (`modifiedby`)');
sql_index_exists ('rights', 'modifiedon', '!ALTER TABLE `rights` ADD  INDEX (`modifiedon`)');
sql_index_exists ('rights', 'status'    , '!ALTER TABLE `rights` ADD  INDEX (`status`)');



/*
 * Update rights table to use "createdon", "createdby", "modifiedby" and "modifiedon"
 */
sql_column_exists('users', 'createdby' , '!ALTER TABLE `users` ADD COLUMN `createdby`  INT(11)       NULL AFTER `id`'); // HAS TO BE NULL SINCE ITS SELF REFERENCING AND HAS A FOREIGN KEY CONSTRAINT!

sql_column_exists('users', 'date_added',  'ALTER TABLE `users` CHANGE COLUMN `date_added` `createdon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `createdby`');

sql_column_exists('users', 'modifiedby', '!ALTER TABLE `users` ADD COLUMN `modifiedby` INT(11)       NULL AFTER `createdon`');
sql_column_exists('users', 'modifiedon', '!ALTER TABLE `users` ADD COLUMN `modifiedon` DATETIME      NULL AFTER `modifiedby`');

sql_index_exists ('users', 'date_added',  'ALTER TABLE `users` DROP INDEX  `date_added`');

sql_index_exists ('users', 'createdby' , '!ALTER TABLE `users` ADD  INDEX (`createdby`)');
sql_index_exists ('users', 'createdon' , '!ALTER TABLE `users` ADD  INDEX (`createdon`)');
sql_index_exists ('users', 'modifiedby', '!ALTER TABLE `users` ADD  INDEX (`modifiedby`)');
sql_index_exists ('users', 'modifiedon', '!ALTER TABLE `users` ADD  INDEX (`modifiedon`)');

sql_foreignkey_exists ('users_rights', 'fk_users_rights_addedby',  'ALTER TABLE `users_rights` DROP FOREIGN KEY `fk_users_rights_addedby`');

sql_index_exists      ('users_rights', 'addedby'  ,  'ALTER TABLE `users_rights` DROP INDEX  `addedby`');
sql_index_exists      ('users_rights', 'addedon'  ,  'ALTER TABLE `users_rights` DROP INDEX  `addedon`');

sql_column_exists     ('users_rights', 'addedby'  ,  'ALTER TABLE `users_rights` DROP COLUMN `addedby`');
sql_column_exists     ('users_rights', 'addedon'  ,  'ALTER TABLE `users_rights` DROP COLUMN `addedon`');



/*
 * Add roles tables
 */
sql_query('DROP TABLE IF EXISTS `roles_rights`');
sql_query('DROP TABLE IF EXISTS `roles`');



sql_query('CREATE TABLE `roles` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                 `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 `createdby`   INT(11)          NULL,
                                 `modifiedon`  DATETIME         NULL,
                                 `modifiedby`  INT(11)          NULL,
                                 `status`      VARCHAR(16)      NULL,
                                 `name`        VARCHAR(32)      NULL,
                                 `description` VARCHAR(2047)    NULL,

                                 INDEX (`createdon`),
                                 INDEX (`createdby`),
                                 INDEX (`modifiedon`),
                                 INDEX (`modifiedby`),
                                 INDEX (`status`),
                                 INDEX (`name`),

                                 CONSTRAINT `fk_roles_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                 CONSTRAINT `fk_roles_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



sql_query('CREATE TABLE `roles_rights` (`id`        INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `roles_id`  INT(11) NOT NULL,
                                        `rights_id` INT(11) NOT NULL,

                                        INDEX (`roles_id`),
                                        INDEX (`rights_id`),
                                        UNIQUE(`roles_id`, `rights_id`),

                                        CONSTRAINT `fk_roles_rights_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `rights` (`id`) ON DELETE CASCADE,
                                        CONSTRAINT `fk_roles_rights_roles_id`  FOREIGN KEY (`roles_id`)  REFERENCES `roles`  (`id`) ON DELETE CASCADE

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



/*
 * Add extra standard rights
 */
$p  = sql_prepare('INSERT INTO `rights` (`name`, `description`)
                   VALUES               (:name , :description)

                   ON DUPLICATE KEY UPDATE `name`        = :name,
                                           `description` = :description;');

$rights = array('rights'        => 'This right allows the user to modify user rights',
                'roles'         => 'This right allows the user to manage user roles',
                'setpassword'   => 'This right allows the user to modify passwords from all users',
                'modify'        => 'This right is a generic "allow to modify" right that can be used in combination with other rights like "users", which will allow the user to modify users',
                'statistics'    => 'This right allows access to statistics',
                'configuration' => 'This right allows access to configuration');

foreach($rights as $name => $description){
    $p->execute(array(':name'        => $name,
                      ':description' => $description));
}



/*
 * Create standard roles, with rights
 */
$p  = sql_prepare('INSERT INTO `roles` (`name`, `description`)
                   VALUES              (:name , :description)

                   ON DUPLICATE KEY UPDATE `name`        = :name,
                                           `description` = :description;');

$q  = sql_prepare('DELETE FROM `roles_rights` WHERE `roles_id` = :roles_id');

$r  = sql_prepare('INSERT INTO `roles_rights` (`roles_id`, `rights_id`)
                   VALUES                     (:roles_id , :rights_id )

                   ON DUPLICATE KEY UPDATE `id` = `id`');

$roles = array('god'       => array('description' => 'This role is for the most powerful user',
                                    'rights'      => 'god'),

               'devil'     => array('description' => 'This role is for users that should have all rights denied',
                                    'rights'      => 'devil'),

               'admin'     => array('description' => 'This role is for a standard administrator with ',
                                    'rights'      => 'admin,users,modify,rights,configuration,setpassword,statistics'),

               'manager'   => array('description' => 'This role is for a standard administrative manager with little extra rights',
                                    'rights'      => 'admin,users,statistics'),

               'moderator' => array('description' => 'This role is for a standard moderator with no extra rights',
                                    'rights'      => 'admin'));

foreach($roles as $name => $data){
    $p->execute(array(':name'        => $name,
                      ':description' => $data['description']));

    $roles_id = sql_insert_id();

    $q->execute(array(':roles_id' => $roles_id));

    foreach(array_force($data['rights']) as $right){
        $rights_id = sql_get('SELECT `id` FROM `rights` WHERE `name` = :name', 'id', array(':name' => $right));

        $r->execute(array(':roles_id'  => $roles_id,
                          ':rights_id' => $rights_id));
    }
}
?>
