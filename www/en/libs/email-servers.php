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
 * Validate a email-server
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
function email_servers_validate($email_server){
    try{
        load_libs('validate,seo');

        $v = new validate_form($email_server, 'id,server_seohostname,domain,smtp_port,imap,poll_interval,header,footer,description');

        $email_server['domains_id'] = null;

        if($email_server['server_seohostname']){
            load_libs('servers');
            $server = servers_get($email_server['server_seohostname'], false, true, true);

            if(!$server){
                $v->setError(tr('The specified server ":server" does not exist', array(':server' => $email_server['seohostname'])));
            }

            $email_server['servers_id'] = $server['id'];

        }else{
            $email_server['servers_id'] = null;
        }

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

        $email_server['seohostname'] = seo_unique($email_server['seohostname'], 'email_servers', $email_server['id'], 'seohostname');

        return $email_server;

    }catch(Exception $e){
        throw new bException(tr('email_servers_validate(): Failed'), $e);
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
        array_default($params, 'name'    , 'seohostname');
        array_default($params, 'class'   , 'form-control');
        array_default($params, 'selected', null);
        array_default($params, 'status'  , null);
        array_default($params, 'empty'   , tr('No email servers available'));
        array_default($params, 'none'    , tr('Select a email_server'));
        array_default($params, 'tabindex', 0);
        array_default($params, 'extra'   , 'tabindex="'.$params['tabindex'].'"');
        array_default($params, 'orderby' , '`hostname`');

        if($params['status'] !== false){
            $where[] = ' `status` '.sql_is($params['status']).' :status ';
            $execute[':status'] = $params['status'];
        }

        if(empty($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seohostname`, `hostname` FROM `email_servers` '.$where.' ORDER BY '.$params['orderby'];
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
            $where[] = ' `email_servers`.`seohostname` = :seohostname ';
            $execute[':seohostname'] = $email_server;
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
                                         `email_servers`.`hostname`,
                                         `email_servers`.`seohostname`,
                                         `email_servers`.`smtp_port`,
                                         `email_servers`.`imap`,
                                         `email_servers`.`poll_interval`,
                                         `email_servers`.`header`,
                                         `email_servers`.`footer`,
                                         `email_servers`.`description`,

                                         `servers`.`hostname`    AS `server_hostname`,
                                         `servers`.`seohostname` AS `server_seohostname`

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
