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
 * Scan image using the scanimage command line program
 *
 * Example command: scanimage --progress  --buffer-size --contrast 50 --gamma 1.8 --jpeg-quality 80 --transfer-format JPEG --mode Color --resolution 300 --format jpeg > test.jpg
 */
function scanimage($params){
    try{
        $params  = scanimage_validate($params);
        $command = 'scanimage -d "'.$params['device'].'"'.$device['options'];
showdie($command);
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
 * Validate the specified scanimage parameters
 */
function scanimage_validate($params){
    global $_CONFIG;

    try{
        load_libs('validate');
        $v       = new validate_form($params, 'device,jpeg_quality,format,file,buffer_size,options');
        $options = array();

        /*
         * Get the device with the device options list
         */
        if($params['device']){
            $device = scanimage_get($params['device']);

        }else{
            $device = scanimage_get_default();

            if(!$device){
                $v->setError(tr('No scanner specified and no default scanner found'));
            }
        }

        $options[] = '--device "'.$device['string'].'"';

        /*
         * Validate target file
         */
        if(!$params['file']){
            $v->setError(tr('scanimage(): No file specified'));

        }elseif(file_exists($params['file'])){
            $v->setError(tr('scanimage(): Specified file ":file" already exists', array(':file' => $params['file'])), 'exists');

        }else{
            file_ensure_path(dirname($params['file']));
        }

        /*
         * Validate scanner buffer size
         */
        if($params['buffer_size']){
            $v->isNatural($params['buffer_size'], tr('Please specify a valid natural numbered buffer size'));
            $v->isBetween($params['buffer_size'], 1, 1024, tr('Please specify a valid buffer size between 1 and 1024'));
        }

        /*
         * Ensure requested format is known and file ends with correct extension
         */
        switch($params['format']){
            case 'jpeg':
                $extension = 'jpg';
                break;

            case 'tiff':
                $extension = 'tiff';
                break;

            default:
                $v->setError(tr('scanimage(): Unknown format ":format" specified', array(':format' => $params['format'])));
        }

        if(!empty($extension)){
            if(str_rfrom($params['file'], '.') != $extension){
                $v->setError(tr('scanimage(): Specified file ":file" has an incorrect file name extension for the requested format ":format", it should have the extension ":extension"', array(':file' => $params['file'], ':format' => $params['format'], ':extension' => $extension)));
            }
        }

        /*
         * Validate parameters against the device
         */
        if(!is_array($params['options'])){
            $v->setError(tr('Please ensure options are specified as an array'));

        }else{
            foreach($params['options'] as $key => $value){
                if(!isset($device['options'][$key])){
                    $v->setError(tr('Driver option ":key" is not supported by device ":device"', array(':option' => $key, ':device' => $params['device'])));
                    continue;
                }

                if(is_string($device['options'][$key])){
                    /*
                     * This is a value range
                     */
                    $device['options'][$key] = array('min' => str_until($key, '..'),
                                                     'max' => str_from($key, '..'));

                    $v->isNatural($value, tr('Please specify a numeric contrast value'));
                    $v->isBetween($value, $device['options'][$key]['min'], $device['options'][$key]['max'], tr('Please ensure that ":key" is in between ":min" and ":max"', array(':key' => $key, ':min' => $device['options'][$key]['min'], ':max' => $device['options'][$key]['max'])));

                }else{
                    $v->inArray($value, $device['options'][$key], tr('Please select a valid ":key" value', array(':key' => $key)));
                }

                $options[] = '--key "'.$value.'"';
            }
        }

        $v->isValid();

        $params['options'] = implode(' ', $options);

        return $params;

    }catch(Exception $e){
        throw new bException('scanimage_validate(): Failed', $e);
    }
}



/*
 * List the available scanner devices from the driver database
 */
function scanimage_list($device = null, $cached = true){
    try{
        /*
         * Get device data from cache
         */
        load_libs('drivers');
        $devices = drivers_get_devices('scanner', $device);

        if($devices){
            $devices = sql_list($devices);
            return $devices;
        }

        return null;

    }catch(Exception $e){
        throw new bException('scanimage_list(): Failed', $e);
    }
}



/*
 * Search devices from the scanner drivers. This might take a while, easily up
 * to 30 seconds or more
 */
function scanimage_search_devices(){
    try{
        $scanners = safe_exec('scanimage -L -q');
        $devices  = array();

        foreach($scanners as $scanner){
            if(substr($scanner, 0, 6) != 'device') continue;

//            $found = preg_match_all('/device `imagescan:esci:(usb|scsi|parrallel):(\/sys\/devices\/.+?)\' is a (.+)/i', $scanner, $matches);
//            device `brother4:bus4;dev1' is a Brother MFC-L8900CDW USB scanner

            $found = preg_match_all('/device `(.+?):bus(\d+);dev(\d+)\' is a (.+)/i', $scanner, $matches);

            if($found){
                /*
                 * Found a scanner
                 */
                $devices[] = array('raw'           => $matches[0][0],
                                   'driver'        => $matches[1][0],
                                   'bus'           => $matches[2][0],
                                   'device'        => $matches[3][0],
                                   'device_string' => $matches[1][0].':bus'.$matches[2][0].';dev'.$matches[3][0],
                                   'name'          => $matches[4][0]);
            }
        }

        return $devices;

    }catch(Exception $e){
        throw new bException('scanimage_search_devices(): Failed', $e);
    }
}



/*
 * List the available scanner devices
 */
function scanimage_update_devices(){
    try{
        load_libs('drivers');
        drivers_clear_devices('scanner');

        $scanners = scanimage_search_devices();

        foreach($scanners as $scanner){
            $options = scanimage_get_scanner_details($scanner['device_string']);
            $scanner = drivers_add_device(array('type'        => 'scanner',
                                                'string'      => $scanner['device_string'],
                                                'description' => $scanner['name']));

            $count   = drivers_add_options($scanner['id'], $options);
        }

        return $scanners;

    }catch(Exception $e){
        throw new bException('scanimage_update_devices(): Failed', $e);
    }
}



/*
 * Get details for the specified scanner device
 */
function scanimage_get_scanner_details($device){
    try{
        $skip    = true;
        $results = safe_exec('scanimage -h -d "'.$device.'"');
        $retval  = array();

        foreach($results as $result){
            if(preg_match('/Options specific to device \`'.$device.'\':/', $result)){
                $skip = false;
                continue;
            }

            while($skip){
                goto skip;
            }

            $result = trim($result);

            if(substr($result, 0, 1) != '-'){
                /*
                 * Doesn't contain driver info
                 */
                goto skip;
            }

            /*
             * These are driver keys
             */
            if(substr($result, 0, 2) == '--'){
                $key     = trim(str_until(substr($result, 2), ' '));
                $data    = trim(str_from($result, ' '));
                $default = '';

                switch($key){
                    case 'mode':
                        $default = str_cut($data, ' [', ']');
                        $data    = str_until($data, ' [');
                        $data    = explode('|', $data);

                        $data['default'] = $default;
                        break;

                    case 'resolution':
                        $default = str_cut($data, ' [', ']');
                        $data    = str_until($data, ' [');
                        $data    = explode('|', $data);

                        $data['default'] = $default;
                        break;

                    case 'source':
                        $default = str_cut($data, ' [', ']');
                        $data    = str_until($data, ' [');
                        $data    = explode('|', $data);

                        $data['default'] = $default;
                        break;

                    case 'brightness':
                        $data    = str_until($data, '(');
                        $data    = str_replace('%', '', $data);
                        $data    = trim($data);
                        break;

                    case 'contrast':
                        $data    = str_until($data, '(');
                        $data    = str_replace('%', '', $data);
                        $data    = trim($data);
                        break;

                    default:
                        throw new bException(tr('sane_get_scanner_defails(): Unknown driver key ":key" found', array(':key' => $key)), 'unknown');
                }

            }else{
                $key  = trim(substr($result, 1 , 1));
                $data = trim(substr($result, 3));

                switch($key){
                    case 'l':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = trim($data);
                        break;

                    case 't':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = trim($data);
                        break;

                    case 'x':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = trim($data);
                        break;

                    case 'y':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = trim($data);
                        break;

                    default:
                        throw new bException(tr('sane_get_scanner_defails(): Unknown driver key ":key" found', array(':key' => $key)), 'unknown');
                }
            }

            $retval[$key] = $data;
            skip:
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('scanimage_get_scanner_details(): Failed', $e);
    }
}



/*
 * Return the data on the default scanner
 */
function scanimage_get_default(){
    try{
        $scanners = scanimage_list();

        foreach($scanners as $devices_id => $scanner){
            if($scanner['default']){
                load_libs('drivers');

                $scanner['options'] = drivers_get_options($devices_id);
                return $scanner;
            }
        }

        return null;

    }catch(Exception $e){
        throw new bException('scanner_get_default(): Failed', $e);
    }
}



/*
 * Return the data on the default scanner
 */
function scanimage_get($device_string){
    try{
        load_libs('drivers');
        $scanner = drivers_get_device($device_string);

        if(!$scanner){
            throw new bException(tr('scanner_get(): Specified scanner with device string ":string" does not exist', array(':string' => $device_string)), 'not-exist');
        }

        $scanner['options'] = drivers_get_options($scanner['id']);
        return $scanner;

    }catch(Exception $e){
        throw new bException('scanner_get_default(): Failed', $e);
    }
}
?>
