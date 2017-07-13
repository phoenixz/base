<?php
/*
 * Coinpayments library
 *
 * This is the library to communicate with coinpayments system
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 *
 * @see https://www.coinpayments.net/downloads/api-example.phps
 * @see https://www.coinpayments.net/merchant-tools
 * @see https://www.coinpayments.net/apidoc
 */



load_config('coinpayments');



/*
 * Make the call to the coinpayment system API
 * Code based off example taken from https://www.coinpayments.net/downloads/api-example.phps
 */
function coinpayments_call($command, $post = array()){
    global $_CONFIG;

    try{
        load_libs('curl,json');

        /*
         * Setup post request
         */
        $post['version'] = 1;
        $post['cmd']     = $command;
        $post['key']     = $_CONFIG['coinpayments']['api']['apikey'];
        $post['format']  = 'json';

        /*
         * Convert to query string
         */
        $post = http_build_query($post, '', '&');
        $hmac = hash_hmac('sha512', $post, $_CONFIG['coinpayments']['api']['secret']);

        /*
         * Execute request
         */
        $results = curl_get(array('url'         => 'https://www.coinpayments.net/api.php',
                                  'post'        => $post,
                                  'verify_ssl'  => false,
                                  'getheaders'  => false,
                                  'httpheaders' => array('Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                                                         'Cache-Control: max-age=0',
                                                         'Connection: keep-alive',
                                                         'Keep-Alive: 300',
                                                         'Expect:',
                                                         'Accept-Charset: utf-8,ISO-8859-1;q=0.7,*;q=0.7',
                                                         'Accept-Language: en-us,en;q=0.5',
                                                         'HMAC: '.$hmac)));

        /*
         * Process results
         */
        $results = json_decode_custom($results['data']);

        switch(isset_get($results['error'])){
            case '':
                // FALLTHROUGH
            case 'ok':
                break;

            case 'error':
                throw new bException(tr('coinpayments_call(): Coinpayments sent error ":error"', array(':error' => $results['error'])), 'remote-error');

            default:
                throw new bException(tr('coinpayments_call(): Coinpayments sent unknown error status ":error"', array(':error' => $results['error'])), 'remote-error');
        }

        return $results['result'];

    }catch(Exception $e){
        throw new bException('coinpayments_call(): Failed', $e);
    }
}



/*
 * Validate IPN calls coming from coinpayments
 */
function coinpayments_get_ipn_transaction(){
    global $_CONFIG;

    try{
        if(empty($_SERVER['HTTP_HMAC']) or empty($_SERVER['HTTP_HMAC'])){
            throw new bException(tr('coinpayments_get_ipn_transaction(): No HMAC sent'), 'not-specified');
        }

        $request = file_get_contents('php://input');

        if(empty($_POST['address'])){
            log_file(tr('Received invalid request, missing "address"'), 'coinpayments');
        }

        log_file(tr('Starting transaction for address ":address"', array(':address' => isset_get($_POST['address']))), 'coinpayments');

        if(empty($_POST)){
            throw new bException(tr('coinpayments_get_ipn_transaction(): Error reading POST data'), 'failed');
        }

        if(empty($_POST['merchant'])){
            throw new bException(tr('coinpayments_get_ipn_transaction(): No Merchant ID specified'), 'not-specified');
        }

        if($_POST['merchant'] != $_CONFIG['coinpayments']['ipn']['merchants_id']){
            throw new bException(tr('coinpayments_get_ipn_transaction(): Specified merchant ID ":id" is invalid', array(':id' => $_POST['merchant'])), 'invalid');
        }

        $hmac = hash_hmac('sha512', $request, $_CONFIG['coinpayments']['ipn']['secret']);

        if($hmac !== $_SERVER['HTTP_HMAC']){
            throw new bException(tr('coinpayments_get_ipn_transaction(): Specified HMAC ":hmac" is invalid', array(':hmac' => $_SERVER['HTTP_HMAC'])), 'invalid');
        }

        return $_POST;

    }catch(Exception $e){
        throw new bException('coinpayments_get_ipn_transaction(): Failed', $e);
    }
}



/*
 * Make the call to the coinpayment system
 */
function coinpayments_get_account_info(){
    try{
        $results = coinpayments_call('get_basic_info');

        return $results;

    }catch(Exception $e){
        throw new bException('coinpayments_get_account_info(): Failed', $e);
    }
}



/*
 * Make the call to the coinpayment system
 */
function coinpayments_get_rates($currencies = null){
    try{
        $results = coinpayments_call('rates');

        if($currencies){
            foreach(array_force($currencies) as $currency){
                if(empty($results[$currency])){
                    throw new bException(tr('coinpayments_get_rates(): Specified coin ":coin" was not found', array(':coin' => $currency)), 'not-found');
                }

                $filtered[$currency] = $results[$currency];
            }

            return $filtered;
        }

        return $results;

    }catch(Exception $e){
        throw new bException('coinpayments_get_rates(): Failed', $e);
    }
}



/*
 * Get balances (for specified coin, if needed)
 */
function coinpayments_get_balances($currencies = true){
    try{
        if($currency === true){
            $results = coinpayments_call('balances', array('all' => 1));

        }else{
            $results = coinpayments_call('balances');

            if($currencies){
                foreach(array_force($currencies) as $currency){
                    if(empty($results[$currency])){
                        throw new bException(tr('coinpayments_get_balances(): Specified coin ":coin" was not found', array(':coin' => $currency)), 'not-found');
                    }

                    $filtered[$currency] = $results[$currency];
                }

                return $filtered;
            }
        }

        return $results;

    }catch(Exception $e){
        throw new bException('coinpayments_get_balances(): Failed', $e);
    }
}



/*
 * Get balances (for specified coin, if needed)
 */
function coinpayments_get_address($currency){
    try{
        $results = coinpayments_call('get_deposit_address', array('currency' => $currency));

        return $results;

    }catch(Exception $e){
        throw new bException('coinpayments_get_address(): Failed', $e);
    }
}



/*
 * Get balances (for specified coin, if needed)
 */
function coinpayments_get_deposit_address($currency, $callback_url = null){
    try{
        if(!$callback_url){
            $callback_url = domain('/api/coinpayments');
        }

        $results = coinpayments_call('get_callback_address', array('currency' => $currency, 'ipn_url' => $callback_url));

        return $results;

    }catch(Exception $e){
        throw new bException('coinpayments_get_deposit_address(): Failed', $e);
    }
}
?>
