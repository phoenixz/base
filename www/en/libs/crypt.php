<?php
/*
 * Crypt library
 *
 * This lirary contains easy to use encrypt / decrypt functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



if(!function_exists('openssl_encrypt')){
    if(!function_exists('mcrypt_module_open')){
        throw new bException(tr('crypt: Neither php module "mcrypt" nor "openssl" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php-mcrypt; sudo phpenmod mcrypt" to install and enable the module. On Redhat and alikes use "sudo yum -y install php-mcrypt" to install mcrytpt (OBSOLETE!) ot "sudo yum -y install php-openssl" to install openssl. After this, a restart of your webserver or php-fpm server might be needed'), 'not-exists');
    }

    $core->register('backend', 'mcrypt');

}else{
    $core->register('backend', 'openssl');
}



/*
 *
 */
function encrypt($data, $key, $method = null){
    try{
        load_libs('json');

        $data = json_encode_custom($data);

        if($key){
            $td   = mcrypt_module_open('tripledes', '', 'ecb', '');
            $iv   = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

            mcrypt_generic_init($td, mb_substr($key, 0, mcrypt_enc_get_key_size($td)), $iv);

            $data = mcrypt_generic($td, $data);
        }

        //make save for transport
        return base64_encode($data);

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

        if($key){
            $td   = mcrypt_module_open('tripledes', '', 'ecb', '');
            $iv   = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

            mcrypt_generic_init($td, mb_substr($key, 0, mcrypt_enc_get_key_size($td)), $iv);

            $data = mdecrypt_generic($td, $data);
        }

        $data = trim($data);
        $data = json_decode_custom($data);

        return $data;

    }catch(Exception $e){
        throw new bException('decrypt(): Failed', $e);
    }
}
?>
