<?php
/*
 * email-domains library
 *
 * This library contains functions to manage email domains through SQL queries
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-server
 *
 * @return void
 */
function email_domains_library_init(){
    try{

    }catch(Exception $e){
        throw new bException('email_domains_library_init(): Failed', $e);
    }
}



/*
 * Create new email domains
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package email-server
 *
 * @return array
 */
function email_domains_validate($domain){
    try{
        $v = new validate_form($domain, 'createdby,createdon,meta_id,status,servers_id,domain,smtp_host,smtp_port,imap,poll_interval,header,footer,description');

// :TODO: Implement

        $v->isValid();

        return $domain;

    }catch(Exception $e){
        throw new bException('email_domains_validate(): Failed', $e);
    }
}
?>
