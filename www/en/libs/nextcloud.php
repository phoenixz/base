<?php
/*
 * Nextcloud library
 *
 * This library is a front-end library to control nextcloud installations on registered servers over SSH
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package nextcloud
 */



/*
 * Create a user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_add($server, $user){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_add(): Failed', $e);
    }
}



/*
 * Delete the specified user from the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_delete($server, $user){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_delete(): Failed', $e);
    }
}



/*
 * Disable the specified existing user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_disable($server, $user){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_disable(): Failed', $e);
    }
}



/*
 * Enable the specified existing user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_enable($server, $user){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_enable(): Failed', $e);
    }
}



/*
 * Get and return information about the speficied user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_info($server, $user){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_info(): Failed', $e);
    }
}



/*
 * Get and return last seen information about the speficied user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_users_last_seen($server, $user){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_last_seen(): Failed', $e);
    }
}



/*
 * List the available users on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @return
 */
function nextcloud_users_list($server){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_list(): Failed', $e);
    }
}



/*
 * Get and return a list of how many users have access on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @return
 */
function nextcloud_users_report($server){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_report(): Failed', $e);
    }
}



/*
 * Reset the password for the specified user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @param mixed $password
 * @return
 */
function nextcloud_users_reset_password($server, $user, $password){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_reset_password(): Failed', $e);
    }
}



/*
 * Read and return, or modify settings for the specified user on the specifed nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @param array settings
 * @return
 */
function nextcloud_users_setting($server, $user, $settings = null){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_users_setting(): Failed', $e);
    }
}



/*
 * Create a user on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 *
 * @param mixed $server
 * @param mixed $user
 * @return
 */
function nextcloud_check_user_ldap($server, $user){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_check_user_ldap(): Failed', $e);
    }
}



/*
 * Add the specified user to the specified group on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud
 * @see nextcloud_remove_user_from_group()
 *
 * @param mixed $server
 * @param mixed $user
 * @param mixed $group
 * @return
 */
function nextcloud_add_user_to_group($server, $user, $group){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_add_user_to_group(): Failed', $e);
    }
}



/*
 * Remove the specified user from the specified group on the specified nextcloud server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package nextcloud nextcloud
 * @see nextcloud_add_user_to_group()
 *
 * @param mixed $server
 * @param mixed $user
 * @param mixed $group
 * @return
 */
function nextcloud_remove_user_from_group($server, $user, $group){
    try{

    }catch(Exception $e){
        throw new bException('nextcloud_remove_user_from_group(): Failed', $e);
    }
}
?>
