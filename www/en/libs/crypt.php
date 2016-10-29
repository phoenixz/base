<?php
/*
 * Crypt library
 *
 * This lirary contains easy to use encrypt / decrypt functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



if(!function_exists('mcrypt_module_open')){
    throw new bException(tr('crypt: php module "mcrypt" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php5-mcrypt; sudo php5enmod mcrypt" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-mcrypt" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not_available');
}



/*
 *
 */
function encrypt($data, $key) {
    try{
        load_libs('json');

        $data = json_encode_custom($data);
        $td   = mcrypt_module_open('tripledes', '', 'ecb', '');
        $iv   = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

        mcrypt_generic_init($td, mb_substr($key, 0, mcrypt_enc_get_key_size($td)), $iv);

        $encrypted_data = mcrypt_generic($td, $data);

        //make save for transport
        return base64_encode($encrypted_data);

    }catch(Exception $e){
        throw new bException('encrypt(): Failed', $e);
    }
}



/*
 *
 */
function decrypt($data, $key){
    try{
        load_libs('json');

        $encrypted_data = base64_decode($data);
        $td             = mcrypt_module_open('tripledes', '', 'ecb', '');
        $iv             = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

        mcrypt_generic_init($td, mb_substr($key, 0, mcrypt_enc_get_key_size($td)), $iv);

        $data = mdecrypt_generic($td, $encrypted_data);
        $data = trim($data);
        $data = json_decode_custom($data);

        return $data;

    }catch(Exception $e){
        throw new bException('decrypt(): Failed', $e);
    }
}
?>
