<?php
/*
 * email-servers library
 *
 * This library contains functions to manage email servers through SQL queries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return void
 */
function email_servers_library_init(){
    try{

    }catch(Exception $e){
        throw new bException('email_servers_library_init(): Failed', $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_init($server, $connector = 'mail'){
    try{
        if(!isset($_CONFIG['db'][$connector])){
            throw new bException(tr('email_servers_init(): The specified connector ":connector" does not exist', array(':connector' => $connector)), 'not-exist');
        }

        $config   = $_CONFIG['db'][$connector];
        $database = $config['database'];

        /*
         * Create mail database with tables
         */
        sql_query('DROP TABLE IF EXISTS `aliases`' , null, $connector);
        sql_query('DROP TABLE IF EXISTS `accounts`', null, $connector);
        sql_query('DROP TABLE IF EXISTS `domains`' , null, $connector);

        sql_query('CREATE TABLE `domains` (`id`           INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                           `status`       VARCHAR(16)     NULL,
                                           `max_accounts` INT(11)     NOT NULL DEFAULT 0,
                                           `name`         VARCHAR(50)     NULL,

                                           KEY `status` (`status`),
                                           UNIQUE KEY `name`   (`name`)

                                          ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";', null, $connector);

        sql_query('CREATE TABLE `aliases` (`id`         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                                                        `status`     VARCHAR(16)      NULL,
                                                                        `domains_id` INT(11)      NOT NULL,
                                                                        `source`     VARCHAR(120)     NULL,
                                                                        `target`     VARCHAR(120)     NULL,

                                                                                KEY `status`        (`status`),
                                                                                KEY `domains_id`    (`domains_id`),
                                                                                KEY `source`        (`source`),
                                                                                KEY `target`        (`target`),
                                                                         UNIQUE KEY `source_target` (`source`,`target`),

                                                                         CONSTRAINT `fk_aliases_domains_id`  FOREIGN KEY (`domains_id`)  REFERENCES `domains` (`id`) ON DELETE RESTRICT

                                                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";', null, null, 'mail');

        sql_exec($server, 'CREATE TABLE `accounts` (`id`         INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                                                         `status`     VARCHAR(16)      NULL,
                                                                         `domains_id` INT(11)      NOT NULL,
                                                                         `password`   VARCHAR(106)     NULL,
                                                                         `email`      VARCHAR(120)     NULL,

                                                                                 KEY `status`     (`status`),
                                                                                 KEY `domains_id` (`domains_id`),
                                                                          UNIQUE KEY `email`      (`email`),

                                                                          CONSTRAINT `fk_accounts_domains_id`  FOREIGN KEY (`domains_id`)  REFERENCES `domains` (`id`) ON DELETE RESTRICT

                                                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";', null, null, 'mail');

    }catch(Exception $e){
        throw new bException('email_servers_init(): Failed', $e);
    }
}



/*
 * Clear all domains, all aliases and accounts from the email database
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_clear_all($server, $database = 'mail'){
    try{
        $count = array();

        $counts['accounts'] = sql_exec($server, 'DELETE FROM `accounts`');
        $counts['aliases']  = sql_exec($server, 'DELETE FROM `aliases`');
        $counts['domains']  = sql_exec($server, 'DELETE FROM `domains`');

        return $count;

    }catch(Exception $e){
        throw new bException('email_servers_clear_all(): Failed', $e);
    }
}



/*
 * Create new email domains
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_create_domain($domain){
    try{
        $domain = email_servers_validate_domain($domain);
        $exists = sql_exec('SELECT `id` FROM `domains` WHERE `name` = "'.cfm($domain).'"');

        if($exists){
            return false;
        }

        sql_exec('INSERT INTO `domains` (`name`)
                  VALUES                ("'.cfm($domain).'")');

        return true;

    }catch(Exception $e){
        throw new bException('email_servers_create_domain(): Failed', $e);
    }
}



/*
 * Create new email accounts
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_create_accounts($accounts){
    try{
        /*
         * Validate all accounts
         */
        foreach($accounts as $account){
            if(empty($account)) continue;

            $account = email_servers_validate_account($account);

            /*
             * Domain exists?
             */
            $domain = str_from(cfm($account['email']), '@');

            if(empty($domains[$domain])){
                $domains_id = sql_exec('SELECT `id` FROM `domains` WHERE `name` = "'.cfm($domain).'"');

                if(!$domains_id){
                    $v->setError(tr('Domain ":domain" for specified email ":email" does not exist', array(':domain' => $domain, ':email' => $account['email'])));
                }

                /*
                 * Cache domains_id so that we can use it with the INSERT later
                 */
                $domains[$domain] = $domains_id;
            }
        }

        $counts = array('created' => 0,
                        'existed' => 0);

        foreach($accounts as $account){
            if(empty($account)) continue;

            /*
             * Email account already exists?
             */
            $exists = sql_exec('SELECT `id` FROM `accounts` WHERE `email` = "'.cfm($account['email']).'"');

            if($exists){
                $counts['existed']++;

            }else{
                $counts['created']++;
                sql_exec('INSERT INTO `accounts` (`domains_id`                                  , `email`                                                             , `password`                                 )
                          VALUES                 ('.$domains[str_from($account['email'], '@')].', "'.cfm($account['email']).', ENCRYPT("'.cfm($account['password']).'", CONCAT("$6$", SUBSTRING(SHA(RAND()), -16))))');
            }
        }

        return $counts;

    }catch(Exception $e){
        throw new bException('email_servers_create_accounts(): Failed', $e);
    }
}



/*
 * Create new email aliases
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_create_aliases($aliases){
    try{
        /*
         * Validate all aliases
         */
        foreach($aliases as $source => $target){
            email_servers_validate_email($source);
            email_servers_validate_email($target);

            $source_domain = str_from($source, '@');
            $target_domain = str_from($target, '@');

            $domains[$source_domain] = email_servers_email_domain_exists($source_domain);
            $domains[$target_domain] = email_servers_email_domain_exists($target_domain);
        }

        /*
         * Create all forwards
         */
        $count = array('created' => 0,
                       'existed' => 0);

        foreach($_POST['aliases'] as $source => $target){
            /*
             * Email aliase already exists?
             */
            $exists = sql_exec('SELECT `id` FROM `aliases` WHERE `source` = "'.cfm($source).'" AND `target` = "'.cfm($target).'"');

            if($exists){
                $count['existed']++;

            }else{
                $count['created']++;
                sql_exec('INSERT INTO `aliases` (`domains_id`                        ,        `source`    ,        `target`    )
                          VALUES                ('.$domains[str_from($source, '@')].', "'.cfm($source).'" , "'.cfm($target).'" )');
            }
        }

    }catch(Exception $e){
        throw new bException('email_servers_create_aliases(): Failed', $e);
    }
}



/*
 * Delete new email domains
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_delete_domains($domains){
    try{
        foreach($domains as $domain){
            email_servers_validate_domain($domain);
        }

        $count = 0;

        foreach($domains as $domain){
            if(empty($domain)) continue;
            $domains_id = sql_exec('SELECT `id` FROM `domains` WHERE `name` = :name', 'id', array(':name' => cfm($domain)), 'mail');

            if($domains_id){
                /*
                 * First ensure that all accounts and aliases for this domain are
                 * deleted
                 */
                sql_exec('DELETE FROM `accounts` WHERE `domains_id` = '.$domains_id);
                sql_exec('DELETE FROM `aliases`  WHERE `domains_id` = '.$domains_id);
                $count++;
            }
        }

    }catch(Exception $e){
        throw new bException('email_servers_delete_domains(): Failed', $e);
    }
}



/*
 * Delete new email accounts
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_delete_accounts($accounts){
    try{
        /*
         * Validate all accounts
         */
        foreach($accounts as $account){
            if(empty($account)) continue;
            email_servers_validate_email($account);
        }

        $count = 0;

        foreach($accounts as $account){
            if(empty($account)) continue;

            $accounts_id = sql_get('SELECT `id` FROM `accounts` WHERE `email` = "'.$account.'"');

            if($accounts_id){
                $count++;
                $p = sql_prepare('DELETE FROM `accounts` WHERE `id` = '.cfi($accounts_id));
            }
        }

    }catch(Exception $e){
        throw new bException('email_servers_delete_accounts(): Failed', $e);
    }
}



/*
 * Delete new email aliases
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_delete_aliases($aliases){
    try{
        /*
         * Validate all aliases
         */
        foreach($aliases as $source => $target){
            email_servers_validate_email($source);
            email_servers_validate_email($target);

            $source_domain = str_from($source, '@');
            $target_domain = str_from($target, '@');

            $domains[$source_domain] = email_servers_email_domain_exists($source_domain);
            $domains[$target_domain] = email_servers_email_domain_exists($target_domain);
        }

        $count = 0;

        foreach($aliases as $alias){
            if(empty($alias)) continue;
            $alias_id = sql_get('SELECT `id` FROM `aliases` WHERE `source` = "'.cfm($source).'" AND `target` = "'.cfm($target).'"');

            if($alias_id){
                $count++;
                sql_exec('DELETE FROM `aliases` WHERE `id` = '.cfi($alias_id));
            }
        }

        return $count;

    }catch(Exception $e){
        throw new bException('email_servers_delete_aliases(): Failed', $e);
    }
}



/*
 * List new email domains
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_list_domains($domains){
    try{
        /*
         * Validate remote data
         */
        if(!empty($domains)){
            foreach($domains as $domain){
                if(empty($domain_name)) continue;
                email_servers_validate_domain($domain);
            }
        }

        $query = 'SELECT `id`, `name` FROM `domains` ';

        if(!empty($domains)){
            $query .= ' WHERE `name` IN ('.sql_in_columns($execute).') ';
        }

        $domains = sql_exec($query.' LIMIT 5000');
        return $domains;

    }catch(Exception $e){
        throw new bException('email_servers_list_domains(): Failed', $e);
    }
}



/*
 * List new email accounts
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_list_accounts($domain){
    try{
        /*
         * Validate remote data
         */
        if(!empty($_POST['domains'])){
            email_servers_validate_domain($domain);
        }

        $query = 'SELECT `id`, `email` FROM `accounts` ';

        if(!empty($domain)){
            $query .= ' WHERE `email` IN ('.$domain.') ';
        }

        $retval = sql_exec($server, $query);
        return $retval;

    }catch(Exception $e){
        throw new bException('email_servers_list_accounts(): Failed', $e);
    }
}



/*
 * List new email aliases
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_list_aliases($domain){
    try{
        if(!empty($_POST['domains'])){
            email_servers_validate_domain($domain);
        }

        /*
         *
         */
        $query = 'SELECT `id`, `source`, `target` FROM `aliases` ';

        /*
         * Check if we should filter for domains
         */
        if(!empty($domain)){
            $query .= ' WHERE `domains_id` IN ('.implode(',', $domain).') ';
        }

        $aliases = sql_exec($query);
        return $aliases;

    }catch(Exception $e){
        throw new bException('email_servers_list_aliases(): Failed', $e);
    }
}



/*
 *
 *
 * @author Marcos Prudencio <marcos@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param array $servers
 * @return void
 */
function email_servers_update_password($account, $password){
    try{
        /*
         * Validate all accounts
         */
        foreach($_POST['accounts'] as $account){
            email_servers_validate_email($account);
        }

        sql_exec('UPDATE `accounts` SET `password` = ENCRYPT(:password, CONCAT("$6$", SUBSTRING(SHA(RAND()), -16))) WHERE `email` = "'.$account.'"');

    }catch(Exception $e){
        throw new bException('email_servers_update_password(): Failed', $e);
    }
}



/*
 * Get server for specified account
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_get_servers($email){
    try{
        /*
         * Domain exists?
         */
        load_libs('servers');
        $domain = str_from(cfm($email), '@');

        /*
         * Ensure that all MX servers are registered
         */
        $results = getmxrr($domain, $hostnames);

        if(!$results){
            throw new bException(tr('email_servers_get_servers(): Failed to get MX records for domain ":domain" for specified email ":email"', array(':domain' => $domain, ':email' => $email)));
        }

        if(!$hostnames){
            throw new bException(tr('email_servers_get_servers(): Domain ":domain" for specified email ":email" has no MX records registered', array(':domain' => $domain, ':email' => $email)));
        }

        foreach($hostnames as $hostname){
            $servers_id = servers_get($hostname);

            if(!$servers_id){
                throw new bException(tr('email_servers_get_servers(): MX record ":hostname" for domain ":domain" for specified email ":email" has no MX records registered', array(':hostname' => $hostname, ':domain' => $domain, ':email' => $email)));
            }

            $servers[] = $servers_id;
        }

        return $servers;

    }catch(Exception $e){
        throw new bException('email_servers_get_servers(): Failed', $e);
    }
}



/*
 * Validate specified server
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_validate_server($server){
    try{
        $v = new validate_form();
        $v->isRegex($server, '/\w{1,240}/', tr('The specified server ":server" is not valid', array(':server' => $server)));
        $v->isValid();

        return $server;

    }catch(Exception $e){
        throw new bException('email_servers_validate_server(): Failed', $e);
    }
}



/*
 * Validate specified server
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_validate_database($database){
    try{
        $v = new validate_form();
        $v->isRegex($database, '/\w{1,32}\/', tr('The specified server ":server" is not valid', array(':server' => $database)));
        $v->isValid();

        return $database;

    }catch(Exception $e){
        throw new bException('email_servers_validate_database(): Failed', $e);
    }
}



/*
 * Validate specified domain
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_validate_domain($domain){
    try{
        $v = new validate_form($domain);
        $v->isRegex($domain, '/\w{1,128}/', tr('The specified email domain ":domain" is not valid', array(':domain' => $domain)));
        $v->isValid();

        return $domain;

    }catch(Exception $e){
        throw new bException('email_servers_validate_domain(): Failed', $e);
    }
}



/*
 * Validate specified domain
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_email_domain_exists($email){
    try{
        /*
         * Domain exists?
         */
        load_libs('servers');

        /*
         * Ensure that the specified domain is registered
         */
        $domain     = str_from(cfm($email), '@');
        $domains_id = sql_get('SELECT `id` FROM `domains` WHERE `name` = :name', 'id', array(':name' => cfm($domain)), 'mail');

        if(!$domains_id){
            throw new bException(tr('Domain ":domain" for specified email ":email" does not exist', array(':domain' => $domain, ':email' => $source['email'])));
        }

        email_servers_get_servers($email);

        return $domains_id;

    }catch(Exception $e){
        throw new bException('email_servers_email_domain_exists(): Failed', $e);
    }
}



/*
 * Validate specified domain
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_validate_email($email){
    try{
        $v = new validate_form($email);
        $v->isEmail($email, tr('The specified email email ":email" is not valid', array(':email' => $email)));
        $v->isValid();

        return $email;

    }catch(Exception $e){
        throw new bException('email_servers_validate_email(): Failed', $e);
    }
}



/*
 * Validate specified account
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @return array
 */
function email_servers_validate_account($account){
    try{
        $v = new validate_form($account, 'email,password');
        $v->isEmail($email['email'], tr('The specified email email ":email" is not valid', array(':email' => $account['email'])));
        $v->isValid();

        return $email;

    }catch(Exception $e){
        throw new bException('email_servers_validate_email(): Failed', $e);
    }
}
?>
