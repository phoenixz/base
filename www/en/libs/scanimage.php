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
        $params = scanimage_validate($params);
        $command = 'scanimage --format tiff '.$params['options'];

        /*
         * Finish scan command and execute it
         */
        try{
            switch($params['format']){
                case 'tiff':
                    $command .= ' > '.$params['file'];
                    $result   = safe_exec($command);
                    break;

                case 'jpeg':
                    $command .= ' | convert tiff:- '.$params['file'];
                    $result   = safe_exec($command);
                    break;
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
            $v->setError(tr('No file specified'));

        }elseif(file_exists($params['file'])){
            $v->setError(tr('Specified file ":file" already exists', array(':file' => $params['file'])), 'exists');

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
                $v->setError(tr('Unknown format ":format" specified', array(':format' => $params['format'])));
        }

        if(!empty($extension)){
            if(str_rfrom($params['file'], '.') != $extension){
                $v->setError(tr('Specified file ":file" has an incorrect file name extension for the requested format ":format", it should have the extension ":extension"', array(':file' => $params['file'], ':format' => $params['format'], ':extension' => $extension)));
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

                if(!$value){
                    unset($params['option']);
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

                if(strlen($key) == 1){
                    $options[] = '-'.$key.' "'.$value.'"';

                }else{
                    $options[] = '--'.$key.' "'.$value.'"';
                }
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
 *
 * Example scanimage -L outputs would be
 * device `brother4:bus4;dev1' is a Brother MFC-L8900CDW USB scanner
 * device `imagescan:esci:usb:/sys/devices/pci0000:00/0000:00:1c.0/0000:03:00.0/usb4/4-2/4-2:1.0' is a EPSON DS-1630
 */
function scanimage_search_devices(){
    try{
        $scanners = safe_exec('scanimage -L -q');
        $devices  = array();

        foreach($scanners as $scanner){
            if(substr($scanner, 0, 6) != 'device') continue;

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
            }else{
                $found = preg_match_all('/device `((.+?):.+?)\' is a (.+)/i', $scanner, $matches);

                if($found){
                    /*
                     * Found a scanner
                     */
                    $devices[] = array('raw'           => $matches[0][0],
                                       'driver'        => $matches[2][0],
                                       'bus'           => null,
                                       'device'        => null,
                                       'device_string' => $matches[1][0],
                                       'name'          => $matches[3][0]);
                }
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
            try{
                $options = scanimage_get_options($scanner['device_string']);
                $scanner = drivers_add_device(array('type'        => 'scanner',
                                                    'string'      => $scanner['device_string'],
                                                    'description' => $scanner['name']));

                $count   = drivers_add_options($scanner['id'], $options);

                log_file(tr('Added device ":device" with device string ":string"', array(':device' => $scanner['description'], ':string' => $scanner['string'])), 'scanner');

            }catch(Exception $e){
                $failed = true;

                /*
                 * One device failed to add, continue adding the rest
                 */
                log_file('scanimage_update_devices(): Device failed to add');
                log_file($scanner);
                log_file($e);

            }
        }

        if(empty($failed)){
            return $scanners;
        }

        throw new bException(tr('scanimage_update_devices(): Failed to add one or more scanners, see file log'), 'failed');

    }catch(Exception $e){
        throw new bException('scanimage_update_devices(): Failed', $e);
    }
}



/*
 * Get driver options for the specified scanner device from the drivers
 *
 * Devices confirmed to be working:
 * device `brother4:bus4;dev1' is a Brother MFC-L8900CDW USB scanner
 * device `imagescan:esci:usb:/sys/devices/pci0000:00/0000:00:1c.0/0000:03:00.0/usb4/4-2/4-2:1.0' is a EPSON DS-1630
 * device `hpaio:/usb/HP_LaserJet_CM1415fnw?serial=00CNF8BC4K04' is a Hewlett-Packard HP_LaserJet_CM1415fnw all-in-one
 */
function scanimage_get_options($device){
    try{
        $skip    = true;
        $results = safe_exec('scanimage -h -d "'.$device.'"');
        $retval  = array();

        foreach($results as $result){
            if($skip){
                if(preg_match('/Options specific to device \`'.str_replace(array('.', '/'), array('\.', '\/'), $device).'\':/', $result)){
                    $skip = false;
                    continue;
                }

                continue;
            }

            $result = trim($result);
            $status = null;

            if(substr($result, 0, 1) != '-'){
                /*
                 * Doesn't contain driver info
                 */
                continue;
            }

            /*
             * These are driver keys
             */
            if(substr($result, 0, 2) == '--'){
                /*
                 * These are double dash options
                 */
                if(!preg_match_all('/--([a-zA-Z-]+)(.+)/', $result, $matches)){
                    throw new bException(tr('scanimage_get_options(): Unknown driver line format encountered for key "resolution"'), 'unknown');
                }
// :DEBUG: Do not remove the folowing commented line(s), its for debugging purposes
//show($matches);

                $key     = $matches[1][0];
                $data    = $matches[2][0];
                $default = str_rfrom($data, ' [');
                $default = trim(str_runtil($default, ']'));
                $data    = trim(str_runtil($data, ' ['));

                if($default == 'inactive'){
                    $status  =  $default;
                    $default = null;
                }

// :DEBUG: Do not remove the folowing commented line(s), its for debugging purposes
//show($key);
//show($data);
//show($default);
                if($data == '[=(yes|no)]'){
                    /*
                     * Options are yes or no
                     */
                    $data = array('yes', 'no');

                }else{
                    switch($key){
                        case 'mode':
                            // FALLTHROUGH
                        case 'scan-area':
                            // FALLTHROUGH
                        case 'source':
                            $data = explode('|', $data);
                            break;

                        case 'resolution':
                            $data = str_replace('dpi', '', $data);

                            if(strstr($data, '..')){
                                /*
                                 * Resolutions given as a range instead of discrete values
                                 */
                                $data = array(trim($data));

                            }else{
                                $data = explode('|', $data);
                            }

                            break;

                        case 'brightness':
                            $data = str_until($data, '(');
                            $data = str_replace('%', '', $data);
                            $data = array(trim($data));
                            break;

                        case 'contrast':
                            $data = str_until($data, '(');
                            $data = str_replace('%', '', $data);
                            $data = array(trim($data));
                            break;

                        default:
                            if(!strstr($data, '|')){
                                if(!strstr($data, '..')){
                                    throw new bException(tr('scanimage_get_options(): Unknown driver line ":result" found', array(':result' => $result)), 'unknown');
                                }

                                /*
                                 * Unknown entry, but treat it as a range
                                 */
                                $data = str_until($data, '(');
                                $data = str_replace('%', '', $data);
                                $data = array(trim($data));

                            }else{
                                /*
                                 * Unknown entry, but treat it as a distinct list
                                 */
                                $data = str_until($data, '(');
                                $data = str_replace('%', '', $data);
                                $data = explode('|', $data);
                            }
                    }
                }

            }else{
                /*
                 * These are single dash options
                 */
                if(!preg_match_all('/-([a-zA-Z-]+)(.+)/', $result, $matches)){
                    throw new bException(tr('scanimage_get_options(): Unknown driver line format encountered for key "resolution"'), 'unknown');
                }
// :DEBUG: Do not remove the folowing commented line(s), its for debugging purposes
//show($matches);

                $key     = $matches[1][0];
                $data    = $matches[2][0];
                $default = str_rfrom($data, ' [');
                $default = trim(str_runtil($default, ']'));
                $data    = str_runtil($data, ' [');
                $data    = trim(str_replace('mm', '', $data));

                if($default == 'inactive'){
                    $status  =  $default;
                    $default = null;
                }

// :DEBUG: Do not remove the folowing commented line(s), its for debugging purposes
//show($key);
//show($data);
//show($default);
                switch($key){
                    case 'l':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = array(trim($data));
                        break;

                    case 't':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = array(trim($data));
                        break;

                    case 'x':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = array(trim($data));
                        break;

                    case 'y':
                        $data = str_until($data, '(');
                        $data = str_replace('%', '', $data);
                        $data = array(trim($data));
                        break;

                    default:
                        throw new bException(tr('scanimage_get_options(): Unknown driver key ":key" found', array(':key' => $key)), 'unknown');
                }
            }

            $retval[$key] = array('data'    => $data,
                                  'status'  => $status,
                                  'default' => $default);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('scanimage_get_options(): Failed for device ":device"', array(':device' => $device)), $e);
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
