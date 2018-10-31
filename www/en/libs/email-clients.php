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
 * Validate an email server
 *
 * This function will validate all relevant fields in the specified $email_server array
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available categories
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param params $params The parameters required
 * @params natural id The database table id for the specified email server, if not new
 * @params string server_seodomain
 * @params
 * @params
 * @params
 * @return The specified email server, validated
 */
function email_servers_validate($email_server){
    try{
        load_libs('validate,seo,domains,servers');

        $v = new validate_form($email_server, 'id,server_seodomain,domain,smtp_port,imap,poll_interval,header,footer,description');
        $v->isNotEmpty($email_server['server_seodomain'], tr('Please specify a server'));
        $v->isNotEmpty($email_server['domain'], tr('Please specify a domain'));

        /*
         * Validate the server
         */
        $server = servers_get($email_server['server_seodomain'], false, true, true);

        if(!$server){
            $v->setError(tr('The specified server ":server" does not exist', array(':server' => $email_server['seodomain'])));
        }

        $email_server['servers_id'] = $server['id'];

        /*
         * Validate the domain
         */
        $domain = domains_get($email_domain['domain_seodomain'], false, true, true);

        if(!$domain){
            $v->setError(tr('The specified domain ":domain" does not exist', array(':domain' => $email_domain['seodomain'])));
        }

        $email_domain['domains_id'] = $domain['id'];

        /*
         * Validate the rest
         */
        if($email_server['smtp_port']){
            $v->isBetween($email_server['smtp_port'], 1, 65535, tr('Please specify a valid SMTP port'));

        }else{
            $email_server['smtp_port'] = 0;
        }

        if($email_server['imap']){
            $v->hasMaxChars($email_server['imap'], 160, tr('Please specify a valid IMAP string'));
//            $v->isAlphaNumeric($email_server['imap'], tr('Please specify a valid IMAP string'));

        }else{
            $email_server['imap'] = '';
        }

        $email_server['header']      = '';
        $email_server['footer']      = '';
        $email_server['description'] = '';
// :IMPLEMENT:

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `email_servers` WHERE `domain` = :domain AND `id` != :id LIMIT 1', true, array(':domain' => $email_server['domain'], ':id' => isset_get($email_server['id'], 0)));

        if($exists){
            $v->setError(tr('The domain ":domain" is already registered', array(':domain' => $email_server['domain'])));
        }

        $email_server['seodomain'] = seo_unique($email_server['domain'], 'email_servers', $email_server['id'], 'seodomain');

        $v->isValid();

        return $email_server;

    }catch(Exception $e){
        throw new bException(tr('email_servers_validate(): Failed'), $e);
    }
}



/*
 * Validate an email domain
 *
 * This function will validate all relevant fields in the specified $domain array
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available categories
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package categories
 *
 * @param array $params The parameters required
 * @paramkey $params name
 * @paramkey $params class
 * @paramkey $params extra
 * @paramkey $params tabindex
 * @paramkey $params empty
 * @paramkey $params none
 * @paramkey $params selected
 * @paramkey $params parents_id
 * @paramkey $params status
 * @paramkey $params orderby
 * @paramkey $params resource
 * @return string HTML for a categories select box within the specified parameters
 */
function email_servers_validate_domain($domain){
    try{
        load_libs('validate,seo,customers');

        $v = new validate_form($domain, 'id,name,seocustomer,description');
        $v->isNotEmpty($domain['name'], tr('Please specify a domain name'));
        $v->hasMaxChars($domain['name'], 64, tr('Please specify a domain of less than 64 characters'));
        $v->isFilter($domain['name'], FILTER_VALIDATE_DOMAIN, tr('Please specify a valid domain'));

        if($domain['seocustomer']){
            $domain['customer'] = customers_get($domain['seocustomer'], 'seoname');

        }else{
            $domain['customer'] = null;
        }

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `domains` WHERE `name` = :name LIMIT 1', true, array(':name' => $domain['name']));

        if($exists){
            $v->setError(tr('The domain ":name" is already registered on this email server'. array(':name' => $domain['name'])));
        }

        $domain['seoname'] = seo_unique($domain['name'], 'domains', $domain['id']);

        $v->isValid();

        return $domain;

    }catch(Exception $e){
        if($e->getCode() == '1049'){
            load_libs('servers');

            $servers  = servers_list_domains($domain['server']);
            $server   = servers_get($domain['server']);
            $domain = not_empty($servers[$domain['server']], $domain['server']);

            throw new bException(tr('email_servers_validate_domain(): Specified email server ":server" (server domain ":domain") does not have a "mail" database', array(':server' => $domain, ':domain' => $server['domain'])), 'not-exist');
        }

        throw new bException(tr('email_servers_validate_domain(): Failed'), $e);
    }
}



/*
 * Return HTML for a email server select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available email servers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email_servers
 *
 * @param array $params The parameters required
 * @paramkey $params name
 * @paramkey $params class
 * @paramkey $params extra
 * @paramkey $params tabindex
 * @paramkey $params empty
 * @paramkey $params none
 * @paramkey $params selected
 * @paramkey $params parents_id
 * @paramkey $params status
 * @paramkey $params orderby
 * @paramkey $params resource
 * @return string HTML for a email_servers select box within the specified parameters
 */
function email_servers_select($params = null){
    try{
        array_ensure($params);
        array_default($params, 'name'    , 'seodomain');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No email servers available'));
        array_default($params, 'none'    , tr('Select an email server'));
        array_default($params, 'tabindex', 0);
        array_default($params, 'extra'   , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby' , '`domain`');

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status']).' :status ';
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seodomain`, `domain` FROM `email_servers` '.$where.' ORDER BY '.$params['orderby'];
        $params['resource'] = sql_query($query, $execute, 'core');
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e){
        throw new bException('email_servers_select(): Failed', $e);
    }
}



/*
 * Return data for the specified email_server
 *
 * This function returns information for the specified email_server. The email_server can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email_servers
 *
 * @param mixed $email_server The requested email_server. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @return mixed The email_server data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified email_server does not exist, NULL will be returned.
 */
function email_servers_get($email_server, $column = null, $status = null){
    try{
        if(is_numeric($email_server)){
            $where[] = ' `email_servers`.`id` = :id ';
            $execute[':id'] = $email_server;

        }else{
            $where[] = ' `email_servers`.`seodomain` = :seodomain ';
            $execute[':seodomain'] = $email_server;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `email_servers`.`status` '.sql_is($status).' :status';
        }

        $where   = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `email_servers` '.$where, true, $execute, 'core');

        }else{
            $retval = sql_get('SELECT    `email_servers`.`id`,
                                         `email_servers`.`createdon`,
                                         `email_servers`.`createdby`,
                                         `email_servers`.`meta_id`,
                                         `email_servers`.`status`,
                                         `email_servers`.`servers_id`,
                                         `email_servers`.`domains_id`,
                                         `email_servers`.`domain`,
                                         `email_servers`.`seodomain`,
                                         `email_servers`.`smtp_port`,
                                         `email_servers`.`imap`,
                                         `email_servers`.`poll_interval`,
                                         `email_servers`.`header`,
                                         `email_servers`.`footer`,
                                         `email_servers`.`description`,

                                         `servers`.`domain`    AS `server_domain`,
                                         `servers`.`seodomain` AS `server_seodomain`

                               FROM      `email_servers`

                               LEFT JOIN `servers`
                               ON        `servers`.`id` = `email_servers`.`servers_id` '.$where, null, $execute, 'core');
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('email_servers_get(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-servers
 *
 * @param array $servers
 * @return void
 */
function email_servers_update_password($email, $password){
    try{
        sql_query('UPDATE `accounts`

                   SET    `password` = ENCRYPT(:password, CONCAT("$6$", SUBSTRING(SHA(RAND()), -16)))

                   WHERE  `email`    = :email',

                   array(':email'    => $email,
                         ':password' => $password));

    }catch(Exception $e){
        throw new bException('email_servers_update_password(): Failed', $e);
    }
}
?>
