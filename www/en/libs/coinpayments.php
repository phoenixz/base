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

        if($results['error']){
            throw new bException(tr('coinpayments_call(): Coinpayments sent error ":error"', array(':error' => $results['error'])), 'remote-error');
        }

        return $results['error'];

    }catch(Exception $e){
        throw new bException('coinpayments_call(): Failed', $e);
    }
}



/*
 * Make the call to the coinpayment system
 */
function coinpayments_get_account_info($coin = null){
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
function coinpayments_get_exchange_rates($coin = null){
    try{
        $results = coinpayments_call('rates');

        if($coin){
            if(empty($results[$coin])){
                throw new bException(tr('coinpayments_get_exchange_rates(): Specified coin ":coin" was not found', array(':coin' => $coin)), 'not-found');
            }

            $results = $results[$coin];
        }

        return $results;

    }catch(Exception $e){
        throw new bException('coinpayments_get_account_info(): Failed', $e);
    }
}



/*
 * Get balances (for specified coin, if needed)
 */
function coinpayments_get_coin_balances($coin = null){
    try{
        if($coin === true){
            $results = coinpayments_call('balances', array('all' => 1));

        }else{
            $results = coinpayments_call('balances');

            if($coin){
                if(empty($results[$coin])){
                    throw new bException(tr('coinpayments_get_coin_balances(): Specified coin ":coin" was not found', array(':coin' => $coin)), 'not-found');
                }

                $results = $results[$coin];
            }
        }

        return $results;

    }catch(Exception $e){
        throw new bException('coinpayments_get_coin_balances(): Failed', $e);
    }
}
?>
