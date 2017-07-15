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
        throw new bException(tr('crypto: Backend "coinbase" is not yet supported'), 'unsupported');

    case 'internal':
        break;

    default:
        throw new bException(tr('crypto: Unknown backend ":backend" specified', array(':backend' => $_CONFIG['crypto']['backend'])), 'unknown');
}



/*
 *
 */
function crypto_currencies_supported($currencies){
    global $_CONFIG;

    if(!$currencies){
        return false;
    }

    foreach(array_force($currencies) as $currency){
        if(!in_array($currency, $_CONFIG['crypto']['currencies'])){
            throw new bException(tr('crypto_currencies_supported(): Specified currency ":currency" is not supported', array(':currency' => $currency)), 'not-supported');
        }
    }
}



/*
 *
 */
function crypto_validate_transaction($transaction, $provider){
    global $_CONFIG;

    try{
        load_libs('validate');
        $v = new validate_form($transaction, 'users_id,status,status_text,type,mode,currency,confirms,api_transactions_id,tx_id,address,amount,amount_btc,amount_usd,fee,fee_btc,exchange_rate,description');

        crypto_currencies_supported($transaction['currency']);

        switch($provider){
            case 'coinpayments':
                $transaction['amount_btc']          = $transaction['amounti'];
                $transaction['fee_btc']             = $transaction['feei'];
                $transaction['tx_id']               = $transaction['txn_id'];
                $transaction['type']                = $transaction['ipn_type'];
                $transaction['mode']                = $transaction['ipn_mode'];
                $transaction['api_transactions_id'] = $transaction['ipn_id'];
                break;

            case 'coinbase':
                throw new bException(tr('crypto_validate_transaction(): Backend "coinbase" is not yet supported'), 'unsupported');

            case 'internal':
                break;

            default:
                throw new bException(tr('crypto_validate_transaction(): Unknown backend ":backend" specified', array(':backend' => $provider)), 'unknown');
        }

        return $transaction;

    }catch(Exception $e){
        throw new bException('crypto_validate_transaction(): Failed', $e);
    }
}



/*
 *
 */
function crypto_add_transaction($transaction, $provider){
    global $_CONFIG;

    try{
        $transaction = crypto_validate_transaction($transaction, $provider);

        if($provider == 'internal'){
            $transaction['currency'] = 'internal';

        }else{
            $transaction['exchange_rate']      = crypto_get_exchange_rate($transaction['currency']);
            $transaction['amount_usd']         = crypto_get_usd($transaction['amount_btc']);
            $transaction['amount_usd_rounded'] = floor($transaction['amount_usd'] * 100) / 100;
        }

        if($_CONFIG['production']){
            /*
             * Internal transactions will always have users_id specified
             */
            if($provider == 'internal'){
                if(empty($transaction['users_id'])){
                    throw new bException('crypto_add_transaction(): No users_id specified', 'not-specified');
                }

            }else{
                $transaction['users_id'] = sql_get('SELECT `users_id` FROM `crypto_addresses` WHERE `address` = :address', true, array(':address' => $transaction['address']));
            }

        }else{
            $transaction['users_id'] = 1;
        }

        sql_query('INSERT INTO `crypto_transactions` (`id`, `users_id`, `status`, `status_text`, `type`, `mode`, `currency`, `confirms`, `api_transactions_id`, `tx_id`, `address`, `amount`, `amount_btc`, `amount_usd`, `amount_usd_rounded`, `fee`, `fee_btc`, `exchange_rate`, `description`)
                   VALUES                            (:id , :users_id , :status , :status_text , :type , :mode , :currency , :confirms , :api_transactions_id , :tx_id , :address , :amount , :amount_btc , :amount_usd , :amount_usd_rounded , :fee , :fee_btc , :exchange_rate , :description )

                   ON DUPLICATE KEY UPDATE `modifiedon`             = NOW(),
                                           `status`                 = :mod_status,
                                           `status_text`            = :mod_status_text,
                                           `confirms`               = :mod_confirms,
                                           `fee`                    = :mod_fee,
                                           `fee_btc`                = :mod_fee_btc
                                           `mod_amount_btc`         = :mod_fee,
                                           `mod_amount_usd`         = :mod_fee_btc
                                           `mod_amount_usd_rounded` = :mod_fee,
                                           `mod_exchange_rate`      = :mod_exchange_rate',

                   array(':id'                     =>  isset_get($transaction['id']),
                         ':users_id'               =>  $transaction['users_id'],
                         ':status'                 =>  $transaction['status'],
                         ':status_text'            =>  $transaction['status_text'],
                         ':type'                   =>  $transaction['type'],
                         ':mode'                   =>  $transaction['mode'],
                         ':currency'               =>  $transaction['currency'],
                         ':confirms'               =>  $transaction['confirms'],
                         ':api_transactions_id'    =>  $transaction['api_transactions_id'],
                         ':tx_id'                  =>  $transaction['tx_id'],
                         ':address'                =>  $transaction['address'],
                         ':description'            =>  $transaction['description'],
                         ':amount'                 => ($transaction['amount']             ? ($transaction['amount']             * 100000000) : $transaction['amount']),
                         ':amount_btc'             => ($transaction['amount_btc']         ? ($transaction['amount_btc']         * 100000000) : $transaction['amount_btc']),
                         ':amount_usd'             => ($transaction['amount_usd']         ? ($transaction['amount_usd']         * 100000000) : $transaction['amount_usd']),
                         ':amount_usd_rounded'     => ($transaction['amount_usd_rounded'] ? ($transaction['amount_usd_rounded'] * 100000000) : $transaction['amount_usd_rounded']),
                         ':fee'                    => ($transaction['fee']                ? ($transaction['fee']                * 100000000) : $transaction['fee']),
                         ':fee_btc'                => ($transaction['fee_btc']            ? ($transaction['fee_btc']            * 100000000) : $transaction['fee_btc']),
                         ':exchange_rate'          => ($transaction['exchange_rate']      ? ($transaction['exchange_rate']      * 100000000) : $transaction['exchange_rate']),
                         ':mod_amount_btc'         => ($transaction['amount_btc']         ? ($transaction['amount_btc']         * 100000000) : $transaction['amount_btc']),
                         ':mod_amount_usd'         => ($transaction['amount_usd']         ? ($transaction['amount_usd']         * 100000000) : $transaction['amount_usd']),
                         ':mod_amount_usd_rounded' => ($transaction['amount_usd_rounded'] ? ($transaction['amount_usd_rounded'] * 100000000) : $transaction['amount_usd_rounded']),
                         ':mod_exchange_rate'      => ($transaction['exchange_rate']      ? ($transaction['exchange_rate']      * 100000000) : $transaction['exchange_rate']),
                         ':mod_confirms'           => ($transaction['confirms']           ?  $transaction['confirms']                        : $transaction['confirms']),
                         ':mod_fee'                => ($transaction['fee']                ? ($transaction['fee']                * 100000000) : $transaction['fee']),
                         ':mod_fee_btc'            => ($transaction['fee_btc']            ? ($transaction['fee_btc']            * 100000000) : $transaction['fee_btc']),
                         ':mod_status_text'        =>  $transaction['status_text'],
                         ':mod_status'             =>  $transaction['status']));

        /*
         * Set amounts back to correct amounts
         */
        return $transaction;

    }catch(Exception $e){
        log_file(tr('Crypto transaction for address ":address" failed with ":e"', array(':address' => isset_get($_POST['address']), ':e' => $e->getMessages())), 'crypto');
        throw new bException('crypto_add_transaction(): Failed', $e);
    }
}



/*
 * Cache the latest exchange rates
 */
function crypto_update_exchange_rates(){
    global $_CONFIG;

    try{
        $currencies = crypto_get_rates();
        $createdon  = sql_get('SELECT NOW()', true);
        $insert     = sql_prepare('INSERT INTO `crypto_rates` (`createdon`, `status`, `currency`, `provider`, `rate_btc`, `fee`)
                                   VALUES                     (:createdon , :status , :currency , :provider , :rate_btc , :fee )');

        foreach($currencies as $code => $currency){
            $insert->execute(array(':createdon' => $createdon,
                                   ':status'    => $currency['status'],
                                   ':provider'  => $_CONFIG['crypto']['backend'],
                                   ':currency'  => $code,
                                   ':rate_btc'  => $currency['rate_btc'] * 100000000,
                                   ':fee'       => $currency['tx_fee']   * 100000000));
        }

        return count($currencies);

    }catch(Exception $e){
        throw new bException('crypto_update_exchange_rates(): Failed', $e);
    }
}



/*
 * Get the exchange rate with BTC for the specified currency
 */
function crypto_get_exchange_rate($currency){
    global $_CONFIG;
    static $update;

    try{
        if(!$currency){
            throw new bException('crypto_get_exchange_rate(): No currency specified', 'not-specified');
        }

        $exchange_rate = sql_get('SELECT   `rate_btc`

                                  FROM     `crypto_rates`

                                  WHERE    `currency`   = :currency
                                  AND      `createdon` >= NOW() - INTERVAL '.$_CONFIG['crypto']['rates']['cache'].' SECOND

                                  ORDER BY `createdon` DESC

                                  LIMIT 1',

                                  array(':currency' => $currency));

        if($exchange_rate){
            return $exchange_rate['rate_btc'];
        }

        if($update){
            throw new bException('crypto_get_exchange_rate(): Exchange rates have already been updated in this process', 'failed');
        }

        $update = true;
        crypto_update_exchange_rates();
        return crypto_get_exchange_rate($currency);

    }catch(Exception $e){
        throw new bException('crypto_get_exchange_rate(): Failed', $e);
    }
}



/*
 * Get the equal to USD value for the specified transaction
 */
function crypto_get_usd($amount){
    global $_CONFIG;

    try{
        $usd = crypto_get_exchange_rate('USD');
        $usd = $amount / $usd;

        return $usd;

    }catch(Exception $e){
        throw new bException('crypto_get_usd(): Failed', $e);
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
        crypto_currencies_supported($currencies);

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
        crypto_currencies_supported($currencies);

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
        crypto_currencies_supported($currency);

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
        crypto_currencies_supported($currency);

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



/*
 * Return a human readable string that neatly displays the amount of money
 */
function crypto_display($amount, $currency){
    try{
        if($currency == 'internal'){
            return '';
        }

        if(($amount * 1000000) < 1){
            return ($amount * 1000000).' u'.strtoupper($currency);
        }

        if(($amount * 1000) < 1){
            return ($amount * 1000).' m'.strtoupper($currency);
        }

        return $amount.' '.strtoupper($currency);

    }catch(Exception $e){
        throw new bException('crypto_get_deposit_address(): Failed', $e);
    }
}
?>
