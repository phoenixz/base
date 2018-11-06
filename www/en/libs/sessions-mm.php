<?php
/*
 * sessions-mm library
 *
 * This library contains all required functions to manage sessions using shared memory as backend
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
function sessions_mm_library_init(){
    try{
under_construction();

    }catch(Exception $e){
        throw new bException('sessions_mm_library_init(): Failed', $e);
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
function sessions_mm_open($save_path, $session_name){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mm_open(): Failed', $e);
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
function sessions_mm_close(){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mm_close(): Failed', $e);
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
function sessions_mm_read($sessions_id){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mm_read(): Failed', $e);
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
function sessions_mm_write($sessions_id, $data){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mm_write(): Failed', $e);
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
function sessions_mm_destroy($sessions_id){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mm_destroy(): Failed', $e);
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
function sessions_mm_gc($lifetime){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mm_gc(): Failed', $e);
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
function sessions_mm_create_sid($save_path, $session_name){
    try{

    }catch(Exception $e){
        throw new bException('sessions_mm_create_sid(): Failed', $e);
    }
}
?>
