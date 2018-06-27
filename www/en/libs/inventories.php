<?php
/*
 * inventories library
 *
 * This library contains functions for the inventory system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Validate the specified inventory item
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param
 * @return
 */
function inventories_validate_item($item){
    try{
        return $item;

    }catch(Exception $e){
        throw new bException('inventories_validate_item(): Failed', $e);
    }
}



/*
 * Validate the specified inventory item
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package inventories
 *
 * @param
 * @return
 */
function inventories_validate_item($item){
    try{
        return $item;

    }catch(Exception $e){
        throw new bException('inventories_validate_item(): Failed', $e);
    }
}
?>
t