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
 *
 */
function crypto_currency_supported($currency){
    global $_CONFIG;

    if(!in_array($currency, $_CONFIG['crypto']['currencies'])){
        throw new bException(tr('crypto_currency_supported(): Specified currency ":currency" is not supported', array(':currency' => $currency)), 'not-supported');
    }
}



/*
 *
 */
function crypto_add_transaction($transaction, $provider){
    global $_CONFIG;

    try{
        crypto_currency_supported($currency);

        sql_query('INSERT INTO `crypto_transactions` (``)
                   VALUES                            ()

                   ON DUPLICATE KEY SET `modifiedon` = NOW()',

                   array());

    }catch(Exception $e){
        throw new bException('crypto_add_transaction(): Failed', $e);
    }
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
function crypto_get_rates($currencies = null){
    global $_CONFIG;

    try{
        crypto_currency_supported($currencies);

        switch($_CONFIG['crypto']['backend']){
            case 'coinpayments':
                return coinpayments_get_rates($currencies);
        }

    }catch(Exception $e){
        throw new bException('crypto_get_rates(): Failed', $e);
    }
}



/*
 * Get information about our account
 */
function crypto_get_balances($currencies = null){
    global $_CONFIG;

    try{
        crypto_currency_supported($currencies);

        switch($_CONFIG['crypto']['backend']){
            case 'coinpayments':
                return coinpayments_get_balances($currencies);
        }

    }catch(Exception $e){
        throw new bException('crypto_get_balances(): Failed', $e);
    }
}



/*
 * Get information about our account
 */
function crypto_get_address($currency){
    global $_CONFIG;

    try{
        crypto_currency_supported($currency);

        switch($_CONFIG['crypto']['backend']){
            case 'coinpayments':
                return coinpayments_get_address($currency);
        }

    }catch(Exception $e){
        throw new bException('crypto_get_address(): Failed', $e);
    }
}



/*
 * Get information about our account
 */
function crypto_get_deposit_address($currency, $callback_url = null, $force = false){
    global $_CONFIG;

    try{
        crypto_currency_supported($currency);

        $exist = sql_get('SELECT `id`,
                                 `address`

                          FROM   `crypto_addresses`

                          WHERE  `users_id` = :users_id
                          AND    `currency` = :currency',

                          array(':users_id' => $_SESSION['user']['id'],
                                ':currency' => $currency));

        if($exist and !$force){
            /*
             * The user already has a wallet for this currency
             */
            return $exist['address'];
        }

        switch($_CONFIG['crypto']['backend']){
            case 'coinpayments':
                $address = coinpayments_get_deposit_address($currency, $callback_url);
                break;
        }

        sql_query('INSERT INTO `crypto_addresses` (`createdby`, `users_id`, `currency`, `provider`, `address`)
                   VALUES                         (:createdby , :users_id , :currency , :provider , :address )',

                   array(':createdby' => $_SESSION['user']['id'],
                         ':users_id'  => $_SESSION['user']['id'],
                         ':currency'  => $currency,
                         ':provider'  => $_CONFIG['crypto']['backend'],
                         ':address'   => $address['address']));

        return $address;

    }catch(Exception $e){
        throw new bException('crypto_get_deposit_address(): Failed', $e);
    }
}
?>
