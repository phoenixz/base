<?php
/*
 * Email administration library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sve
 *
 * virtual_domains
 *
 * +-------+-------------+------+-----+---------+----------------+
 * | Field | Type        | Null | Key | Default | Extra          |
 * +-------+-------------+------+-----+---------+----------------+
 * | id    | int(11)     | NO   | PRI | NULL    | auto_increment |
 * | name  | varchar(50) | NO   |     | NULL    |                |
 * +-------+-------------+------+-----+---------+----------------+
 *
 * virtual_users;
 * +-----------+--------------+------+-----+---------+----------------+
 * | Field     | Type         | Null | Key | Default | Extra          |
 * +-----------+--------------+------+-----+---------+----------------+
 * | id        | int(11)      | NO   | PRI | NULL    | auto_increment |
 * | domain_id | int(11)      | NO   | MUL | NULL    |                |
 * | password  | varchar(106) | NO   |     | NULL    |                |
 * | email     | varchar(120) | NO   | UNI | NULL    |                |
 * +-----------+--------------+------+-----+---------+----------------+
 *
 * virtual_aliases;
 *
 * +-------------+--------------+------+-----+---------+----------------+
 * | Field       | Type         | Null | Key | Default | Extra          |
 * +-------------+--------------+------+-----+---------+----------------+
 * | id          | int(11)      | NO   | PRI | NULL    | auto_increment |
 * | domain_id   | int(11)      | NO   | MUL | NULL    |                |
 * | source      | varchar(100) | NO   |     | NULL    |                |
 * | destination | varchar(100) | NO   |     | NULL    |                |
 * +-------------+--------------+------+-----+---------+----------------+
 */



/*
 * Return all domain that are processed by this email server
 */
function emailadmin_query(){
    global $emailsql;

    try{
        if(empty($emailsql)){
            $emailsql = sql_connect($_CONFIG['email']['db']);
        }

        $emailsql->query($query);

    }catch(Exception $e){
        throw new bException('emailadmin_query(): Failed', $e);
    }
}



/*
 * Execute query and return only the first row
 */
function emailadmin_get($query, $column = null, $execute = null, $sql = 'sql') {
    try{
        if(is_array($column)){
            /*
             * Argument shift, no columns were specified.
             */
            $tmp     = $execute;
            $execute = $column;
            $column  = $tmp;
            unset($tmp);
        }

        return sql_fetch(emailadmin_query($query, $execute, true, $sql), $column);

    }catch(Exception $e){
        if(strtolower(substr(trim($query), 0, 6)) != 'select'){
            throw new bException('emailadmin_get(): Query "'.str_log($query, 4096).'" is not a select query and as such cannot return results', $e);
        }

        throw new bException('emailadmin_get(): Failed', $e);
    }
}



/*
 * Execute query and return only the first row
 */
function emailadmin_list($query, $column = null, $execute = null, $sql = 'sql') {
    try{
        if(is_array($column)){
            /*
             * Argument shift, no columns were specified.
             */
            $tmp     = $execute;
            $execute = $column;
            $column  = $tmp;
            unset($tmp);
        }

        $r      = emailadmin_query($query, $execute, true, $sql);
        $retval = array();

        while($row = sql_fetch($r, $column)){
            if(is_scalar($row)){
                $retval[] = $row;

            }else{
                switch(count($row)){
                    case 1:
                        $retval[] = array_shift($row);
                        break;

                    case 2:
                        $retval[array_shift($row)] = array_shift($row);
                        break;

                    default:
                        $retval[array_shift($row)] = $row;
                }
            }
        }

        return $retval;

    }catch(Exception $e){
        if(strtolower(substr(trim($query), 0, 6)) != 'select'){
            throw new bException('emailadmin_list(): Query "'.str_log($query, 4096).'" is not a select query and as such cannot return results', $e);
        }

        throw new bException('emailadmin_list(): Failed', $e);
    }
}



/*
 * Return all domain that are processed by this email server
 */
function emailadmin_get_domain($domain){
    try{
        if(is_numeric($domain)){
            return emailadmin_get('SELECT `name` FROM `virtual_domains` WHERE `id`   = :id'  , 'name', array(':id'   => $domain));

        }elseif(is_string($domain)){
            return emailadmin_get('SELECT `id`   FROM `virtual_domains` WHERE `name` = :name', 'id'  , array(':name' => $domain));

        }else{
            throw new bException(tr('emailadmin_get_domain(): Invalid domain name or id type ":value" specified, please specify either string (domain name) or integer (domain id)', array(':value' => gettype($domain))), 'invalid');
        }

    }catch(Exception $e){
        throw new bException('emailadmin_get_domain(): Failed', $e);
    }
}



/*
 * Return all domain that are processed by this email server
 */
function emailadmin_list_domains(){
    try{
        return emailadmin_list('SELECT `id`, `name` FROM `virtual_domains` ORDER BY `id` DESC');

    }catch(Exception $e){
        throw new bException('emailadmin_list_domains(): Failed', $e);
    }
}



/*
 * Add a new domain
 */
function emailadmin_add_domain($domain){
    try{
        if(!$domain){
            throw new bException(tr('emailadmin_add_domain(): No domain name specified'), 'not-specified');
        }

// :TODO: Add check for valid domain
        //if(!$domain){
        //    throw new bException(tr('emailadmin_add_domain(): No domain name specified'), 'not-specified');
        //}

        emailadmin_query('INSERT INTO `virtual_domains` (`name`)
                          VALUES                        (:name )',

                          array(':name' => $domain));

        return $core->register['emailsql']->lastInsertId();

    }catch(Exception $e){
        throw new bException('emailadmin_add_domain(): Failed', $e);
    }
}



/*
 * Remove the specified domain
 */
function emailadmin_remove_domains($domain){
    try{
        if(is_numeric($domain)){
            emailadmin_query('DELETE FROM `virtual_domains`
                              WHERE       `id` = :id',

                              array(':id' => $domain));

        }elseif(is_string($domain)){
            emailadmin_query('DELETE FROM `virtual_domains`
                              WHERE       `name` = :name',

                              array(':name' => $domain));

        }elseif(is_array($domain)){
            $in = sql_in($domain);

            emailadmin_query('DELETE FROM `virtual_domains`

                              WHERE       `name` IN ('.implode(',', array_keys($in)).')

                              OR          `id`   IN ('.implode(',', array_keys($in)).')',

                              $in);

        }else{
            throw new bException(tr('emailadmin_remove_domains(): Invalid domain name or id type ":value" specified, please specify either string (domain name) or integer (domain id) or an array with both mixed', array(':value' => gettype($domain))), 'invalid');
        }

        return $core->register['emailsql']->rowCount();

    }catch(Exception $e){
        throw new bException('emailadmin_remove_domains(): Failed', $e);
    }
}



/*
 * Return requested user data
 */
function emailadmin_get_user($user){
    try{
        if(is_numeric($user)){
            return emailadmin_get('SELECT `email` FROM `virtual_users` WHERE `id`    = :id'   , 'name', array(':id'    => $user));

        }elseif(is_string($user)){
            return emailadmin_get('SELECT `id`    FROM `virtual_users` WHERE `email` = :email', 'id'  , array(':email' => $user));

        }else{
            throw new bException(tr('emailadmin_get_user(): Invalid user name or id type ":value" specified, please specify either string (user name) or integer (user id)', array(':value' => gettype($user))), 'invalid');
        }

    }catch(Exception $e){
        throw new bException('emailadmin_get_user(): Failed', $e);
    }
}



/*
 * Return all user that are processed by this email server
 */
function emailadmin_list_users($domain){
    try{
        if(!$domain){
            return emailadmin_list('SELECT `id`, `email`, `domains_id` FROM `virtual_users` ORDER BY `domains_id` DESC, `id` DESC');
        }

        if(!is_numeric($domain)){
            $domain = emailadmin_get_domain($domain);
        }

        return emailadmin_list('SELECT   `id`,
                                         `email`,
                                         `domains_id`

                                FROM     `virtual_users`

                                WHERE    `domains_id` = :domains_id

                                ORDER BY `domains_id` DESC, `id` DESC',

                               array(':domains_id' => $domain));

    }catch(Exception $e){
        throw new bException('emailadmin_list_users(): Failed', $e);
    }
}



/*
 * Add a new user
 */
function emailadmin_add_user($email, $password){
    try{
        if(!$email){
            throw new bException(tr('emailadmin_add_user(): No email specified'), 'not-specified');
        }

// :TODO: Add check for valid user
        //if(!$user){
        //    throw new bException(tr('emailadmin_add_user(): No user name specified'), 'not-specified');
        //}

        if(!$domains_id = emailadmin_get_domain(str_from($email, '@'))){
            throw new bException(tr('emailadmin_add_user(): Specified domain "%domain%" is not managed', array('%domain%' => str_from($email, '@'))), 'notfound');
        }

        emailadmin_query('INSERT INTO `virtual_users` (`domains_id`, `email`, `password`)
                          VALUES                      (:domains_id , :email , ENCRYPT(:password , CONCAT("$6$", SUBSTRING(SHA(RAND()), -16))))',

                          array(':email'      => $email,
                                ':domains_id' => $domains_id,
                                ':password'   => $password));

        return $core->register['emailsql']->lastInsertId();

    }catch(Exception $e){
        throw new bException('emailadmin_add_user(): Failed', $e);
    }
}



/*
 * Remove the specified user
 */
function emailadmin_remove_users($user){
    try{
        if(is_numeric($user)){
            emailadmin_query('DELETE FROM `virtual_users`
                              WHERE       `id` = :id',

                              array(':id' => $user));

        }elseif(is_string($user)){
            emailadmin_query('DELETE FROM `virtual_users`
                              WHERE       `email` = :email',

                              array(':name' => $user));

        }elseif(is_array($domain)){
            $in = sql_in($domain);

            emailadmin_query('DELETE FROM `virtual_users`

                              WHERE       `name` IN ('.implode(',', array_keys($in)).')

                              OR          `id`   IN ('.implode(',', array_keys($in)).')',

                              $in);

        }else{
            throw new bException(tr('emailadmin_remove_users(): Invalid user name or id type ":value" specified, please specify either string (user name) or integer (user id) or an array with both mixed', array(':value' => gettype($user))), 'invalid');
        }

        return $core->register['emailsql']->rowCount();

    }catch(Exception $e){
        throw new bException('emailadmin_remove_users(): Failed', $e);
    }
}



/*
 * Return requested alias data
 */
function emailadmin_get_alias($alias){
    try{
        if(is_numeric($alias)){
            return emailadmin_get('SELECT `email` FROM `virtual_aliases` WHERE `id`    = :id'   , 'name', array(':id'    => $alias));

        }elseif(is_string($alias)){
            return emailadmin_get('SELECT `id`    FROM `virtual_aliases` WHERE `email` = :email', 'id'  , array(':email' => $alias));

        }else{
            throw new bException(tr('emailadmin_get_alias(): Invalid alias name or id type ":value" specified, please specify either string (alias name) or integer (alias id)', array(':value' => gettype($alias))), 'invalid');
        }

    }catch(Exception $e){
        throw new bException('emailadmin_get_alias(): Failed', $e);
    }
}



/*
 * Return all alias that are processed by this email server
 */
function emailadmin_list_aliases($domain){
    try{
        if(!$domain){
            return emailadmin_list('SELECT `id`, `email`, `domains_id` FROM `virtual_aliases` ORDER BY `domains_id` DESC, `id` DESC');
        }

        if(!is_numeric($domain)){
            $domain = emailadmin_get_domain($domain);
        }

        return emailadmin_list('SELECT   `id`,
                                         `email`,
                                         `domains_id`

                                FROM     `virtual_aliases`

                                WHERE    `domains_id` = :domains_id

                                ORDER BY `domains_id` DESC, `id` DESC',

                               array(':domains_id' => $domain));

    }catch(Exception $e){
        throw new bException('emailadmin_list_aliases(): Failed', $e);
    }
}



/*
 * Add a new alias
 */
function emailadmin_add_alias($source, $destination){
    try{
        if(!$email){
            throw new bException(tr('emailadmin_add_alias(): No email specified'), 'not-specified');
        }

// :TODO: Add check for valid alias
        //if(!$alias){
        //    throw new bException(tr('emailadmin_add_alias(): No alias name specified'), 'not-specified');
        //}

        if(!$domains_id = emailadmin_get_domain(str_from($email, '@'))){
            throw new bException(tr('emailadmin_add_alias(): Specified domain "%domain%" is not managed', array('%domain%' => str_from($email, '@'))), 'notfound');
        }

        emailadmin_query('INSERT INTO `virtual_aliases` (`domains_id`, `source`, `destination`)
                          VALUES                        (:domains_id , :source , :destination )',

                          array(':domains_id'  => $domains_id,
                                ':source'      => $source,
                                ':destination' => $destination));

        return $core->register['emailsql']->lastInsertId();

    }catch(Exception $e){
        throw new bException('emailadmin_add_alias(): Failed', $e);
    }
}



/*
 * Remove the specified alias
 */
function emailadmin_remove_aliases($alias){
    try{
        if(is_numeric($alias)){
            emailadmin_query('DELETE FROM `virtual_aliases`
                              WHERE       `id` = :id',

                              array(':id' => $alias));

        }elseif(is_string($alias)){
            emailadmin_query('DELETE FROM `virtual_aliases`

                              WHERE       `source`      = :email
                              OR          `destination` = :email',

                              array(':name' => $alias));

        }elseif(is_array($domain)){
            $in = sql_in($domain);

            emailadmin_query('DELETE FROM `virtual_aliases`

                              WHERE       `source`      IN ('.implode(',', array_keys($in)).')
                              OR          `destination` IN ('.implode(',', array_keys($in)).')
                              OR          `id`          IN ('.implode(',', array_keys($in)).')',

                              $in);

        }else{
            throw new bException(tr('emailadmin_remove_aliases(): Invalid alias name or id type ":value" specified, please specify either string (alias name) or integer (alias id) or an array with both mixed', array(':value' => gettype($alias))), 'invalid');
        }

        return $core->register['emailsql']->rowCount();

    }catch(Exception $e){
        throw new bException('emailadmin_remove_aliass(): Failed', $e);
    }
}
?>
