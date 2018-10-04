<?php
/*
 * sessions-mc library
 *
 * This library contains all required functions to manage sessions using memcached as backend
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sessions_sql
 *
 * @return void
 */
function sessions_mc_library_init(){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mc_library_init(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sessions_sql
 *
 * @param string $save_path
 * @param string $session_name
 * @return
 */
function sessions_mc_open($save_path, $session_name){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mc_open(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sessions_sql
 *
 * @return
 */
function sessions_mc_close(){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mc_close(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sessions_sql
 *
 * @param string $sessions_id
 * @return
 */
function sessions_mc_read($sessions_id){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mc_read(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sessions_sql
 *
 * @param string $sesisons_id
 * @param string $data
 * @return
 */
function sessions_mc_write($sessions_id, $data){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mc_write(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sessions_sql
 *
 * @param string $sessions_id
 * @return
 */
function sessions_mc_destroy($sessions_id){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mc_destroy(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sessions_sql
 *
 * @param string $lifetime
 * @return
 */
function sessions_mc_gc($lifetime){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mc_gc(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sessions_sql
 *
 * @param string $save_path
 * @param string $session_name
 * @return
 */
function sessions_mc_create_sid($save_path, $session_name){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mc_create_sid(): Failed', $e);
    }
}
?>
