<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


load_config('crypto');



switch($_CONFIG['crypto']['backend']){
    case 'coinpayments':
        load_libs('coinpayments');
        break;

    case 'coinbase':
        throw new bException(tr('crypty: Backend "coinbase" is not yet supported'), 'unsupported');

    case 'local':
        throw new bException(tr('crypty: Backend "local" is not yet supported'), 'unsupported');

    default:
        throw new bException(tr('crypty: Unknown backend ":backend" specified', array(':backend' => $_CONFIG['crypto']['backend'])), 'unknown');
}



/*
 * Get information about our account
 */
function crypto_get_account_info(){
    global $_CONFIG;

    try{
        switch($_CONFIG['crypto']['backend']){
            case 'coinpayments':
                return coinpayments_get_account_info();
        }

    }catch(Exception $e){
        throw new bException('crypto_get_account_info(): Failed', $e);
    }
}



/*
 * Get ratesfor the specified currency
 */
function crypto_get_rates($coin = null){
    global $_CONFIG;

    try{
        switch($_CONFIG['crypto']['backend']){
            case 'coinpayments':
                return coinpayments_get_rates($coin);
        }

    }catch(Exception $e){
        throw new bException('crypto_get_rates(): Failed', $e);
    }
}



/*
 * Get information about our account
 */
function crypto_get_balances($coin = null){
    global $_CONFIG;

    try{
        switch($_CONFIG['crypto']['backend']){
            case 'coinpayments':
                return coinpayments_get_balances($coin);
        }

    }catch(Exception $e){
        throw new bException('crypto_get_balances(): Failed', $e);
    }
}
?>
