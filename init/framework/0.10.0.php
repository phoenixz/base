<?php
/*
 * Create basic admin user from current shell user, signin as that user
 * and then upgrade that user to admin
 *
 * If the current user already is registerd, then just ensure
 */
if(!$user = sql_get('SELECT `id`, `name`, `username` FROM `users` WHERE `username` = :username', array(':username' => $_SERVER['USER']))){
    $user = array('username' => $_SERVER['USER'],
                  'name'     => $_SERVER['USER'],
                  'email'    => 'unknown@localhost',
                  'password' => 'admin');

    sql_query('INSERT INTO `users` (`name`, `email`, `username`, `password`)
               VALUES              (:name , :email , :username , :password )',

               array(':name'      => $user['name'],
                     ':email'     => $user['email'],
                     ':username'  => $user['username'],
                     ':password'  => password($user['password'])));

    $user = sql_get('SELECT `id`,
                            `name`,
                            `email`,
                            `username`

                     FROM   `users`

                     WHERE  `username` = :username',

                     array(':username' => $user['username']));

    if(!$user){
        /*
         * Erw, something went wrong?
         */
        throw new bException('init/framework/0.10.0(): Failed to create user "'.$_SERVER['USER'].'"', 'user_create_failed');
    }
}



/*
 * Ensure that this user is admin and has god rights
 */
load_libs('rights');
rights_give($user['id'], 'admin,god');
log_console('Created admin user "'.$_SERVER['USER'].'" with god rights', 'created', 'green');



/*
 * Ensure that the user is signed in from here on out
 */
if(empty($_SESSION['user']['id'])){
    load_libs('user');
    user_signin($user);
}



/*
 * Setup blogging tables
 */
sql_query('DROP TABLE IF EXISTS `blogs_keywords`');
sql_query('DROP TABLE IF EXISTS `blogs_posts`');

sql_foreignkey_exists('blogs_categories', 'fk_blogs_categories_parents_id', 'ALTER TABLE `blogs_categories` DROP FOREIGN KEY `fk_blogs_categories_parents_id`');
sql_query('DROP TABLE IF EXISTS `blogs_categories`');

sql_query('DROP TABLE IF EXISTS `blogs`');



/*
 * This table keeps track of what users have blogs
 */
sql_query('CREATE TABLE `blogs` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                 `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 `createdby`   INT(11)      NOT NULL,
                                 `modifiedon`  DATETIME         NULL,
                                 `modifiedby`  INT(11)          NULL,
                                 `status`      VARCHAR(16)      NULL,
                                 `rights_id`   INT(11)          NULL,
                                 `name`        VARCHAR(64)      NULL,
                                 `seoname`     VARCHAR(64)      NULL,
                                 `slogan`      VARCHAR(255)     NULL,
                                 `description` TEXT             NULL,

                                 INDEX (`createdon`),
                                 INDEX (`createdby`),
                                 INDEX (`modifiedon`),
                                 INDEX (`modifiedby`),
                                 INDEX (`rights_id`),
                                 INDEX (`status`),
                                 UNIQUE(`name`),
                                 UNIQUE(`seoname`),

                                 CONSTRAINT `fk_blogs_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`  (`id`) ON DELETE RESTRICT,
                                 CONSTRAINT `fk_blogs_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users`  (`id`) ON DELETE RESTRICT,
                                 CONSTRAINT `fk_blogs_rights_id`  FOREIGN KEY (`rights_id`)  REFERENCES `rights` (`id`) ON DELETE RESTRICT

                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



/*
 * This table keeps track of posts for each blog
 */
sql_query('CREATE TABLE `blogs_categories` (`id`            INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                            `createdon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `createdby`     INT(11)     NOT NULL,
                                            `modifiedon`    DATETIME        NULL,
                                            `modifiedby`    INT(11)         NULL,
                                            `status`        VARCHAR(16)     NULL,
                                            `blogs_id`      INT(11)     NOT NULL,
                                            `parents_id`    INT(11)         NULL,
                                            `name`          VARCHAR(64)     NULL,
                                            `seoname`       VARCHAR(64)     NULL,

                                            INDEX (`createdon`),
                                            INDEX (`createdby`),
                                            INDEX (`modifiedon`),
                                            INDEX (`modifiedby`),
                                            INDEX (`blogs_id`),
                                            INDEX (`parents_id`),
                                            INDEX (`status`),
                                            UNIQUE(`name`),
                                            UNIQUE(`seoname`),

                                            CONSTRAINT `fk_blogs_categories_createdby`  FOREIGN KEY (`createdby`)  REFERENCES `users`            (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_blogs_categories_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users`            (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_blogs_categories_blogs_id`   FOREIGN KEY (`blogs_id`)   REFERENCES `blogs`            (`id`) ON DELETE CASCADE,
                                            CONSTRAINT `fk_blogs_categories_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `blogs_categories` (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



/*
 * This table keeps track of posts for each blog
 */
sql_query('CREATE TABLE `blogs_posts` (`id`            INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                       `createdon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `createdby`     INT(11)     NOT NULL,
                                       `modifiedon`    DATETIME        NULL,
                                       `modifiedby`    INT(11)         NULL,
                                       `status`        VARCHAR(16)     NULL,
                                       `blogs_id`      INT(11)     NOT NULL,
                                       `categories_id` INT(11)     NOT NULL,
                                       `category`      VARCHAR(64)     NULL,
                                       `keywords`      VARCHAR(255)    NULL,
                                       `url`           VARCHAR(255)    NULL,
                                       `name`          VARCHAR(64)     NULL,
                                       `seoname`       VARCHAR(64)     NULL,
                                       `body`          MEDIUMTEXT      NULL,

                                       INDEX (`createdon`),
                                       INDEX (`createdby`),
                                       INDEX (`modifiedon`),
                                       INDEX (`modifiedby`),
                                       INDEX (`blogs_id`),
                                       INDEX (`categories_id`),
                                       INDEX (`category`),
                                       INDEX (`keywords`),
                                       INDEX (`status`),
                                       UNIQUE(`url`),
                                       UNIQUE(`name`),
                                       UNIQUE(`seoname`),

                                       CONSTRAINT `fk_blogs_posts_createdby`     FOREIGN KEY (`createdby`)     REFERENCES `users`            (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_blogs_posts_modifiedby`    FOREIGN KEY (`modifiedby`)    REFERENCES `users`            (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_blogs_posts_blogs_id`      FOREIGN KEY (`blogs_id`)      REFERENCES `blogs`            (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `fk_blogs_posts_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `blogs_categories` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');



/*
 * This table keeps track of posts for each blog
 */
sql_query('CREATE TABLE `blogs_keywords` (`id`             INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `createdon`      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`      INT(11)     NOT NULL,
                                          `blogs_posts_id` INT(11)     NOT NULL,
                                          `name`           VARCHAR(64)     NULL,
                                          `seoname`        VARCHAR(64)     NULL,

                                          INDEX (`createdon`),
                                          INDEX (`createdby`),
                                          INDEX (`blogs_posts_id`),
                                          INDEX (`name`),
                                          INDEX (`seoname`),
                                          UNIQUE(`blogs_posts_id`, `seoname`),
                                          UNIQUE(`blogs_posts_id`, `name`),

                                          CONSTRAINT `fk_blogs_keywords_createdby`      FOREIGN KEY (`createdby`)      REFERENCES `users`       (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_blogs_keywords_blogs_posts_id` FOREIGN KEY (`blogs_posts_id`) REFERENCES `blogs_posts` (`id`) ON DELETE CASCADE

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['charset'].'" COLLATE="'.$_CONFIG['db']['collate'].'";');
?>
