<?php
/*
 * QR library
 *
 * This library contains functions to encode information in QR images, and decode information from QR images
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 *
 */
function qr_check(){
    try{
        if(!is_callable('gd_info')){
            throw new bException(tr('qr_check(): The GD library is not available. On Debian based machines, please use apt-get install php-gd, or phpenmod gd to have this module installed and enabled'), 'not-available');
        }

        ensure_installed(array('name'      => 'php-qrcode-detector-decoder',
                               'project'   => 'php-qrcode-detector-decoder',
                               'callback'  => 'qr_install',
                               'checks'    => array(ROOT.'www/en/libs/external/php-qrcode-decoder/QrReader.php')));

    }catch(Exception $e){
        throw new bException('qr_check(): Failed', $e);
    }
}



/*
 *
 */
function qr_install(){
    try{
        $params['methods'] = array('download' => array('urls'      => array('https://github.com/khanamiryan/php-qrcode-detector-decoder.git'),
                                                       'locations' => array('lib' => ROOT.'www/'.LANGUAGE.'/libs/external/php-qrcode-decoder')));

        return install($params);

    }catch(Exception $e){
        throw new bException('qr_install(): Failed', $e);
    }
}



/*
 * Encode the specified data in a QR image
 */
function qr_encode($data, $height = 300, $width = 300, $provider = 'google'){
    try{
        switch($provider){
            case 'google':
                load_libs('html');
                return 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.urlencode($data).'&choe=UTF-8';

            case 'internal':
under_construction();
                break;

            default:
                throw new bException(tr('qr_decode(): Unknown provider ":provider" specified', array(':provider' => $provider)), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('qr_encode(): Failed', $e);
    }
}



/*
 * Encode the specified data in a QR image
 */
function qr_decode($image){
    try{
        qr_check();
        load_external('php-qrcode-decoder/QrReader.php');

        $qrcode = new QrReader($image);
        $text   = $qrcode->text();

        return $text;

    }catch(Exception $e){
        throw new bException('qr_decode(): Failed', $e);
    }
}
?>
