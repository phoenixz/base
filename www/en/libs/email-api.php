<?php
/*
 * email-api library
 *
 * This library contains functions to quickly and easily interface with the BASE
 * email server management API
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
 * @package emailapi
 *
 * @return void
 */
function emailapi_library_init(){
    try{
        load_libs('api');

    }catch(Exception $e){
        throw new bException('emailapi_library_init(): Failed', $e);
    }
}



/*
 *
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_init(){
    try{
        return api_call_base($server, '/email/init');

    }catch(Exception $e){
        throw new bException('emailapi_init(): Failed', $e);
    }
}



/*
 * Clear all domains, all aliases and accounts from the email database
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_clear_all($server){
    try{
        return api_call_base($server, '/email/clear-all');

    }catch(Exception $e){
        throw new bException('emailapi_clear_all(): Failed', $e);
    }
}



/*
 * Create new email domains
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_create_domains($domains){
    try{
        return api_call_base($server, '/email/create-domains', array('domains' => $domains));

    }catch(Exception $e){
        throw new bException('emailapi_create_domains(): Failed', $e);
    }
}



/*
 * Create new email accounts
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_create_accounts($accounts){
    try{
        return api_call_base($server, '/email/create-accounts', array('accounts' => $accounts));

    }catch(Exception $e){
        throw new bException('emailapi_create_accounts(): Failed', $e);
    }
}



/*
 * Create new email aliases
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_create_aliases($aliases){
    try{
        return api_call_base($server, '/email/create-aliases', array('aliases' => $aliases));

    }catch(Exception $e){
        throw new bException('emailapi_create_aliases(): Failed', $e);
    }
}



/*
 * Delete new email domains
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_delete_domains($domains){
    try{
        return api_call_base($server, '/email/delete-domains', array('domains' => $domains));

    }catch(Exception $e){
        throw new bException('emailapi_delete_domains(): Failed', $e);
    }
}



/*
 * Delete new email accounts
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_delete_accounts($accounts){
    try{
        return api_call_base($server, '/email/delete-accounts', array('accounts' => $accounts));

    }catch(Exception $e){
        throw new bException('emailapi_delete_accounts(): Failed', $e);
    }
}



/*
 * Delete new email aliases
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_delete_aliases($aliases){
    try{
        return api_call_base($server, '/email/delete-aliases', array('aliases' => $aliases));

    }catch(Exception $e){
        throw new bException('emailapi_delete_aliases(): Failed', $e);
    }
}



/*
 * List new email domains
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
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
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_list_accounts($server, $domain){
    try{
        return api_call_base($server, '/email/list-accounts', array('domain' => $domain));

    }catch(Exception $e){
        throw new bException('emailapi_list_accounts(): Failed', $e);
    }
}



/*
 * List new email aliases
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @return array
 */
function emailapi_list_aliases($server, $domain){
    try{
        return api_call_base($server, '/email/list-accounts', array('domain' => $domain));

    }catch(Exception $e){
        throw new bException('emailapi_list_aliases(): Failed', $e);
    }
}



/*
 *
 *
 * @author Marcos Prudencio <marcos@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package emailapi
 *
 * @param array $servers
 * @return array The validated parameter data
 */
function emailapi_update_password($account, $password){
    try{
        return api_call_base($server, '/email/update-password', array('accounts' => $accounts));

    }catch(Exception $e){
        throw new bException('emailapi_update_password(): Failed', $e);
    }
}
?>
