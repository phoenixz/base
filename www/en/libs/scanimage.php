<?php
/*
 * scanimage library
 *
 * This library allows to run the scanimage program, scan images and save them to disk
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_config('scanimage');



/*
 * Scan for an image
 *
 * Example command: scanimage --progress  --buffer-size --contrast 50 --gamma 1.8 --jpeg-quality 80 --transfer-format JPEG --mode Color --resolution 300 --format jpeg > test.jpg
 */
function scanimage($params){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'resolution'     , $_CONFIG['scanimage']['resolution']);
        array_default($params, 'contrast'       , $_CONFIG['scanimage']['contrast']);
        array_default($params, 'brightness'     , $_CONFIG['scanimage']['brightness']);
        array_default($params, 'gamma'          , $_CONFIG['scanimage']['gamma']);
        array_default($params, 'jpeg_quality'   , $_CONFIG['scanimage']['jpeg_quality']);
        array_default($params, 'transfer_format', $_CONFIG['scanimage']['transfer_format']);
        array_default($params, 'mode'           , $_CONFIG['scanimage']['mode']);
        array_default($params, 'format'         , $_CONFIG['scanimage']['format']);
        array_default($params, 'file'           , file_temp());
        array_default($params, 'device'         , null);

        $command = 'scanimage';

        /*
         * Validate parameters and apply them to $command
         */
        if($params['contrast']){
            if(!is_natural($params['contrast']) or ($params['contrast']) > 100){
                throw new bException(tr('scanimage(): Specified contrast ":value" is invalid. Please ensure the contrast is in between 0 and 100', array(':value' => $params['contrast'])), 'invalid');
            }

            $command .= ' --contrast '.$params['contrast'];
        }

        if($params['brightness']){
            if(!is_natural($params['brightness']) or ($params['brightness']) > 100){
                throw new bException(tr('scanimage(): Specified brightness ":value" is invalid. Please ensure the brightness is in between 0 and 100', array(':value' => $params['brightness'])), 'invalid');
            }

            $command .= ' --brightness '.$params['brightness'];
        }

        if($params['gamma']){
            if(!is_natural($params['gamma']) or ($params['gamma']) > 100){
                throw new bException(tr('scanimage(): Specified gamma ":value" is invalid. Please ensure the gamma is in between 0 and 100', array(':value' => $params['gamma'])), 'invalid');
            }

            $command .= ' --gamma '.$params['gamma'];
        }

        if($params['resolution']){
            switch($params['resolution']){
                case '75':
                case '150':
                case '300':
                case '600':
                case '1200':
                case '2400':
                case '4800':
                case '9600':
                    break;

                default:
                    throw new bException(tr('scanimage(): Specified resolution ":value" is invalid. Please ensure the resolution is one of 75, 150, 150, 300, 600 or 1200', array(':value' => $params['resolution'])), 'invalid');
            }

            $command .= ' --resolution '.$params['resolution'];
        }

        if($params['transfer_format']){
            switch($params['transfer_format']){
                case 'jpeg':
                    // FALLTHROUGH
                case 'tiff':
                    break;

                default:
                    throw new bException(tr('scanimage(): Specified transfer_format ":value" is invalid. Please ensure transfer_format is one of JPEG or TIFF', array(':value' => $params['transfer_format'])), 'invalid');
            }

            $command .= ' --transfer-format '.strtoupper($params['transfer_format']);
        }

        if($params['format']){
            switch($params['format']){
                case 'pnm':
                    $extension = '.pnm';
                    break;

                case 'tiff':
                    $extension = '.tiff';
                    break;

                case 'jpeg':
                    $params['format'] = 'tiff';
                    $extension = '.jpg';
                    $jpg = true;
                    break;

                default:
                    throw new bException(tr('scanimage(): Specified format ":value" is invalid. Please ensure transfer_format is one of JPEG or TIFF', array(':value' => $params['format'])), 'invalid');
            }

            $command .= ' --format '.$params['format'];
        }

        if($params['mode']){
            switch($params['mode']){
                case 'lineart':
                    // FALLTHROUGH
                case 'gray':
                    // FALLTHROUGH
                case 'color':
                    break;

                default:
                    throw new bException(tr('scanimage(): Specified mode ":value" is invalid. Please ensure mode is one of "color" or "grey" or "lineart"', array(':value' => $params['mode'])), 'invalid');
            }

            $command .= ' --mode '.str_capitalize($params['mode']);
        }

        if(!$params['file']){
            throw new bException(tr('scanimage(): No file specified'), 'invalid');
        }

        /*
         * Ensure file ends with correct extension
         */
        $len = strlen($extension);

        if(substr($params['file'], -$len, $len) != $extension){
            $params['file'] .= $extension;
        }

        /*
         * Finish scan command and execute it
         */
        try{
            if(empty($jpg)){
                $command .= ' > '.$params['file'];
                $result   = safe_exec($command);

            }else{
                $command .= ' | convert tiff:- '.$params['file'];
                $result   = safe_exec($command);
            }

        }catch(Exception $e){
            $data = $e->getData();
            $line = array_shift($data);

            switch(substr($line, 0, 33)){
                case 'scanimage: open of device images':
                    /*
                     *
                     */
                    throw new bException(tr('scanimage(): Scan failed'), 'failed');

                case 'scanimage: no SANE devices found':
                    /*
                     * No scanner found
                     */
                    throw new bException(tr('scanimage(): No scanner found'), 'not-found');

                default:
                    throw new bException(tr('scanimage(): Unknown error ":e"', array(':e' => $e->getData())), $e);
            }
        }

        return $params['file'];

    }catch(Exception $e){
        throw new bException('scanimage(): Failed', $e);
    }
}



/*
 * List the available scanner devices
 */
function scanimage_list(){
    try{
        $scanners = safe_exec('scanimage -L -q');
        $retval   = array('usb'       => array(),
                          'scsi'      => array(),
                          'parrallel' => array(),
                          'unknown'   => array());

        foreach($scanners as $scanner){
            $found = preg_match_all('/device `imagescan:esci:(usb|scsi|parrallel):(\/sys\/devices\/.+?)\' is a (.+)/i', $scanner, $matches);

            if($found){
                /*
                 * Found a scanner
                 */
                $retval[$matches[1][0]][] = array('name' => $matches[3][0],
                                                  'url'  => $matches[2][0]);

            }else{
                $retval['unknown'][] = $scanner;
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('scanimage(): Failed', $e);
    }
}
?>
