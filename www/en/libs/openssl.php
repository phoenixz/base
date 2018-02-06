<?php
/*
 * openssl library
 *
 * This is a front-end library for using PHP openssl for encryption
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 *
 * @see https://github.com/ioncube/php-openssl-cryptor/blob/master/src/Cryptor.php for information on correct openssl usage
 * @see http://php.net/manual/en/function.openssl-encrypt.php for information on correct openssl usage
 */



openssl_init();



/*
 * Init library
 */
function openssl_init(){
    try{
        if(!function_exists('openssl_encrypt')){
            throw new bException(tr('openssl_init(): PHP module "openssl" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php-openssl; sudo phpenmod openssl" to install and enable the module., on Redhat and alikes use "sudo yum -y install php-openssl" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not-exists');
        }
under_construction();

    }catch(Exception $e){
        throw new bException('openssl_init(): Failed', $e);
    }
}



/*
 * Encrypt using openssl
 */
function openssl_simple_encrypt($data, $password, $seed = false){
    global $_CONFIG;

    try{
        /*
         * Default and test cipher
         */
        if(!$cipher){
            $cipher = $_CONFIG['openssl']['cipher'];
        }

        openssl_simple_test_cypher($cipher);

        if($seed){
            /*
             * Use a seed for the password hash
             */
            if($seed === true){
                /*
                 * Use the sitewide configured seed
                 */
                $seed = $_CONFIG['openssl']['seed'];
            }
        }

        /*
         * Securely hash password, create safe init vector, and build the encrypted string
         */
        $password    = openssl_digest($seed.$password, 'sha256');
        $init_vector = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        $init_vector = base64_encode($init_vector);
        $encrypted   = openssl_encrypt($data, $cipher, $password, 0, $iv);
        $encrypted   = $init_vector.':'.$encrypted;

        return $encrypted;

    }catch(Exception $e){
        throw new bException('openssl_simple_encrypt(): Failed', $e);
    }
}



/*
 * Decrypt using openssl
 */
function openssl_simple_decrypt($data, $password, $cipher = null){
    global $_CONFIG;

    try{
        /*
         * Default and test cipher
         */
        if(!$cipher){
            $cipher = $_CONFIG['openssl']['cipher'];
        }

        openssl_simple_test_cypher($cipher);

        /*
         * Get a hash for the password and get the initalization vector from the specified data
         */
        $password    = openssl_digest($_CONFIG['openssl']['seed'].$password, 'sha256');
        $init_vector = str_until($data, ':');

        if(!$init_vector){
            throw new bException(tr('openssl_simple_decrypt(): Specified encrypted string has no init vector'), 'empty');
        }

        $init_vector = base64_decode($data);
        $data        = base64_decode($data);
        $data        = openssl_decrypt($init_vector, $cipher, $encryption_key, 0, $data);

        return $data;

    }catch(Exception $e){
        throw new bException('openssl_simple_decrypt(): Failed', $e);
    }
}


function openssl_simple_test_cypher($cipher){
    try{
        if(!in_array($cipher, openssl_get_cipher_methods())){
            throw new bException(tr('openssl_simple_test_cypher(): Unknown cipher ":cipher" specifed', array(':cipher' => $cipher)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('openssl_simple_test_cypher(): Failed', $e);
    }
}
?>
