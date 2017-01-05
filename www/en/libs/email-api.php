<?php
/*
 * email-api library
 *
 * This library contains functions to quickly and easily interface with the BASE
 * email server management API
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



 load_libs('api');



/*
 * (Re) initialize the entire mail database from scratch
 */
function emailapi_init($server){
    try{
        return api_call_base($server, '/email/init');

    }catch(Exception $e){
        throw new bException('emailapi_init(): Failed', $e);
    }
}



/*
 * Clear all domains, all aliases and accounts from the email database
 */
function emailapi_clearall($server){
    try{
        return api_call_base($server, '/email/clearall');

    }catch(Exception $e){
        throw new bException('emailapi_clearall(): Failed', $e);
    }
}



/*
 * Create new email domains
 */
function emailapi_create_domains($servers, $domains){
    try{
        return api_call_base($server, '/email/create-domains', array('domains' => $domains));

    }catch(Exception $e){
        throw new bException('emailapi_create_domains(): Failed', $e);
    }
}



/*
 * Create new email accounts
 */
function emailapi_create_accounts($servers, $accounts){
    try{
        return api_call_base($server, '/email/create-accounts', array('accounts' => $accounts));

    }catch(Exception $e){
        throw new bException('emailapi_create_accounts(): Failed', $e);
    }
}



/*
 * Create new email aliases
 */
function emailapi_create_aliases($servers, $aliases){
    try{
        return api_call_base($server, '/email/create-aliases', array('aliases' => $aliases));

    }catch(Exception $e){
        throw new bException('emailapi_create_aliases(): Failed', $e);
    }
}



/*
 * Delete new email domains
 */
function emailapi_delete_domains($servers, $domains){
    try{
        return api_call_base($server, '/email/delete-domains', array('domains' => $domains));

    }catch(Exception $e){
        throw new bException('emailapi_delete_domains(): Failed', $e);
    }
}



/*
 * Delete new email accounts
 */
function emailapi_delete_accounts($servers, $accounts){
    try{
        return api_call_base($server, '/email/delete-accounts', array('accounts' => $accounts));

    }catch(Exception $e){
        throw new bException('emailapi_delete_accounts(): Failed', $e);
    }
}



/*
 * Delete new email aliases
 */
function emailapi_delete_aliases($servers, $aliases){
    try{
        return api_call_base($server, '/email/delete-aliases', array('aliases' => $aliases));

    }catch(Exception $e){
        throw new bException('emailapi_delete_aliases(): Failed', $e);
    }
}



/*
 * List new email domains
 */
function emailapi_list_domains($server){
    try{
        return api_call_base($server, '/email/list-domains');

    }catch(Exception $e){
        throw new bException('emailapi_list_domains(): Failed', $e);
    }
}



/*
 * List new email accounts
 */
function emailapi_list_accounts($servers, $domains){
    try{
        return api_call_base($server, '/email/list-accounts', array('domains' => $domains));

    }catch(Exception $e){
        throw new bException('emailapi_list_accounts(): Failed', $e);
    }
}



/*
 * List new email aliases
 */
function emailapi_list_aliases($servers, $domains){
    try{
        return api_call_base($server, '/email/list-accounts', array('domains' => $domains));

    }catch(Exception $e){
        throw new bException('emailapi_list_aliases(): Failed', $e);
    }
}



/*
 * List new email aliases
 */
function emailapi_update_passwords($servers, $accounts){
    try{
        return api_call_base($server, '/email/update-passwords', array('accounts' => $accounts));

    }catch(Exception $e){
        throw new bException('emailapi_list_aliases(): Failed', $e);
    }
}
?>
