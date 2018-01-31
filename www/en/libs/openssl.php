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
function openssl_encrypt($data){
    try{

    }catch(Exception $e){
        throw new bException('openssl_encrypt(): Failed', $e);
    }
}



/*
 * Decrypt using openssl
 */
function openssl_decrypt($data){
    try{

    }catch(Exception $e){
        throw new bException('openssl_decrypt(): Failed', $e);
    }
}
?>
