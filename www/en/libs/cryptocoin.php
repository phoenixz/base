<?php
/*
 * cryptocoin library
 *
 * This library contains various functions to interface with crypto wallets.
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Return the output of the "getinfo" command
 */
function crypto_getinfo(){
    try{

    }catch(Exception $e){
        throw new bException('crypto_getinfo(): Failed', $e);
    }
}
?>
