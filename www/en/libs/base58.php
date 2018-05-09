<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function base58_library_init(){
    try{
        base58_load();

    }catch(Exception $e){
        throw new bException('base58_library_init(): Failed', $e);
    }
}



/*
 *
 */
function base58_load(){
    try{
        ensure_installed(array('name'      => 'base58php',
                               'project'   => 'base58php',
                               'callback'  => 'base58_install',
                               'checks'    => array(ROOT.'www/en/libs/external/base58php/Base58.php')));

        load_external('base58php/ServiceInterface.php');
        load_external('base58php/BCMathService.php');
        load_external('base58php/GMPService.php');
        load_external('base58php/Base58.php');

    }catch(Exception $e){
        throw new bException('base58_check(): Failed', $e);
    }
}



/*
 *
 */
function base58_install($params){
    try{
        $params['methods'] = array('download' => array('urls'      => array('https://github.com/stephen-hill/base58php.git'),
                                                       'locations' => array('src' => ROOT.'www/'.LANGUAGE.'/libs/external/base58php')));

        return install($params);

    }catch(Exception $e){
        throw new bException('base58_install(): Failed', $e);
    }
}



/*
 * Encode the specified string into a base58 string
 */
function base58_encode($source, $reduced = false){
    try{
        switch($reduced){
            case false:
                $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            case 'lower':
                $alphabet = '123456789abcdefghijkmnopqrstuvwxyzabcdefghjklmnpqrstuvwxyz';
                break;

            case 'upper':
                $alphabet = '123456789ABCDEFGHIJKMNOPQRSTUVWXYZABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            default:
                $alphabet = $reduced;
        }

        $converter = new StephenHill\Base58($alphabet);

		return $converter->encode($source);

    }catch(Exception $e){
        if($e->getMessage() == 'Please install the BC Math or GMP extension.'){
            throw new bException(tr('base58_encode(): The PHP BC Math or PHP GMP extensions are not installed. On ubuntu, please install or enable these extensions using "sudo apt-get install php-bcmath", "sudo phpenmod bcmath", "sudo apt-get install php-gmp", or "sudo phpenmod gmp"'), 'not-available');
        }

        throw new bException(tr('base58_encode(): Failed'), $e);
    }
}



/*
 * Decode the specified base58 string
 */
function base58_decode($base58, $reduced = false){
    try{
        switch($reduced){
            case false:
                $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            case 'lower':
                $alphabet = '123456789abcdefghijkmnopqrstuvwxyz';
                break;

            case 'upper':
                $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                break;

            default:
                $alphabet = $reduced;
        }

        $converter = new StephenHill\Base58($alphabet);

		return $converter->decode($source);

    }catch(Exception $e){
        if($e->getMessage() == 'Please install the BC Math or GMP extension.'){
            throw new bException(tr('base58_decode(): The PHP BC Math or PHP GMP extensions are not installed. On ubuntu, please install or enable these extensions using "sudo apt-get install php-bcmath", "sudo phpenmod bcmath", "sudo apt-get install php-gmp", or "sudo phpenmod gmp"'), 'not-available');
        }

        throw new bException(tr('base58_decode(): Failed'), $e);
    }
}



?>
