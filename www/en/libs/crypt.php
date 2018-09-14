<?php
/*
 * Crypt library
 *
 * This lirary contains easy to use encrypt / decrypt functions
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
 * @package sodium
 *
 * @return void
 */
function crypt_library_init(){
    try{
        switch(str_until(PHP_VERSION, '.')){
            case 5:
                if(!function_exists('mcrypt_module_open')){
                    throw new bException(tr('crypt: PHP module "mcrypt" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php-mcrypt" to install and enable the module. After this, a restart of your webserver or php-fpm server may be needed'), 'not-exists');
                }

                $core->register('crypt_backend', 'mcrypt');
                break;

            case 7:
                $core->register('crypt_backend', 'sodium');
                load_libs('sodium');
                break;

            default:
                throw new bException(tr('crypt_library_init(): Unsupported PHP version ":version"', array(':version' => PHP_VERSION)), 'unsupported');
        }

    }catch(Exception $e){
        throw new bException('crypt_library_init(): Failed', $e);
    }
}



/*
 * Encrypt the specified string with the specified key.
 *
 * This function will return an encrypted string made from the specified source string and secret key. The encrypted string will contain the used nonce appended in front of the ciphertext with the format library^nonce$ciphertext
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sodium
 *
 * @param string $string The text string to be encrypted
 * @param string $key The secret key to encrypt the text string with
 * @return string The encrypted ciphertext
 */
function encrypt($data, $key, $method = null){
    try{
        load_libs('json');

        switch($core->register('crypt_backend')){
            case 'sodium':
                $data = json_encode_custom($data);
                $data = 'sodium'.soduim_encrypt($data, $key);
                $data = base64_encode($data);
                break;

            case 'mcrypt':
                if($key){
                    $td   = mcrypt_module_open('tripledes', '', 'ecb', '');
                    $iv   = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

                    mcrypt_generic_init($td, mb_substr($key, 0, mcrypt_enc_get_key_size($td)), $iv);

                    $data = 'mcrypt^'.mcrypt_generic($td, $data);
                    $data = base64_encode($data);
                }
                break;
        }

        return $data;

    }catch(Exception $e){
        throw new bException('encrypt(): Failed', $e);
    }
}



/*
 *
 */
function decrypt($data, $key, $method = null){
    try{
        load_libs('json');

        $data = base64_decode($data);

        if($data === false){
            throw new bException(tr('decrypt(): base64_decode() asppears to have failed to decode data, probably invalid base64 string'), 'invalid');
        }

        $backend = str_from($data, '^');
        $data    = str_from($data, '^');

        if(!$backend){
            throw new bException(tr('decrypt(): Data has no backend specified'), 'invalid');
        }

        if($backend !== $core->register('crypt_backend')){
            throw new bException(tr('decrypt(): Data requires crypto backend ":data" but only ":system" is available', array(':system' => $core->register('crypt_backend'), ':data' => $backend)), 'not-available');
        }

        switch($core->register('crypt_backend')){
            case 'sodium':
                $data = soduim_decrypt($data, $key);
                break;

            case 'mcrypt':
                if($key){
                    $td = mcrypt_module_open('tripledes', '', 'ecb', '');
                    $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

                    mcrypt_generic_init($td, mb_substr($key, 0, mcrypt_enc_get_key_size($td)), $iv);

                    $data = mdecrypt_generic($td, $data);
                }

                break;
        }


        $data = trim($data);
        $data = json_decode_custom($data);

        return $data;

    }catch(Exception $e){
        throw new bException('decrypt(): Failed', $e);
    }
}
?>
