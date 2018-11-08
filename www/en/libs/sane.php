<?php
/*
 * sane library
 *
 * This library allows access to the SANE commands
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package sane
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package sane
 *
 * @return void
 */
function sane_library_init(){
    try{
        load_config('sane');

    }catch(Exception $e){
        throw new bException('sane_library_init(): Failed', $e);
    }
}



/*
 * Find available scanners
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package scanimage
 *
 * @param string $libusb The libusb identifier string of a specific device. If specified, only this device will be returned, if found
 * @return array All scanners found by SANE
 */
function sane_find_scanners($libusb = false){
    global $_CONFIG;

    try{
        $results = safe_exec('sudo sane-find-scanner -q | grep -v "Could not find" | grep -v "Pipe error"', 1);
        $retval  = array('count'     => 0,
                         'usb'       => array(),
                         'scsi'      => array(),
                         'parrallel' => array(),
                         'unknown'   => array());

        foreach($results as $result){
            if(substr($result, 0, 17) == 'found USB scanner'){
                /*
                 * Found a USB scanner
                 */
                if(preg_match_all('/found USB scanner \(vendor=0x([0-9a-f]{4}) \[([A-Za-z0-9-_ ]+)\], product=0x([0-9a-f]{4}) \[([A-Za-z0-9-_ ]+)\]\) at libusb:([0-9]{3}:[0-9]{3})/i', $result, $matches)){
                    $device = array('raw'          => $matches[0][0],
                                    'vendor'       => $matches[1][0],
                                    'product'      => $matches[3][0],
                                    'manufacturer' => $matches[2][0],
                                    'model'        => $matches[4][0],
                                    'libusb'       => $matches[5][0]);

                    if($libusb){
                        if($libusb == $device['libusb']){
                            /*
                             * Return only the requested device
                             */
                            return $device;
                        }

                        /*
                         * Only show the requested libusb device
                         */
                        continue;
                    }

                    $retval['count']++;
                    $retval['usb'][] = $device;

                }else{
                    $retval['unknown'][] = $result;
                }

            }elseif(substr($result, 0, 18) == 'found SCSI scanner'){
under_construction();
// :TEST: This has not been tested due to a lack of parrallel scanners. Do these still exist?
                /*
                 * Found a SCSI scanner
                 */
                if(preg_match_all('/found SCSI scanner (vendor=0x([0-9a-f]{4}) \[([A-Z0-9-_])\], product=0x([0-9a-f]{4}) \[([A-Z0-9-_])\]) at libusb:([0-9{3}]:[0-9]{3})/i', $result, $matches)){
                    $retval['count']++;
                    $retval['scsi'][] = array('vendor'       => $matches[0][0],
                                              'product'      => $matches[2][0],
                                              'manufacturer' => $matches[1][0],
                                              'libusb'       => $matches[4][0]);

                }else{
                    $retval['unknown'][] = $result;
                }

            }elseif(substr($result, 0, 23) == 'found parrallel scanner'){
under_construction();
// :TEST: This has not been tested due to a lack of parrallel scanners. Do these still exist?
                /*
                 * Found a parrallel scanner
                 */
                if(preg_match_all('/found parrallel scanner (vendor=0x([0-9a-f]{4}) \[([A-Z0-9-_])\], product=0x([0-9a-f]{4}) \[([A-Z0-9-_])\]) at libusb:([0-9{3}]:[0-9]{3})/i', $result, $matches)){
                    $retval['count']++;
                    $retval['parrallel'][] = array('vendor'       => $matches[0][0],
                                                   'product'      => $matches[2][0],
                                                   'manufacturer' => $matches[1][0],
                                                   'libusb'       => $matches[4][0]);

                }else{
                    $retval['unknown'][] = $result;
                }

            }elseif(substr($result, 0, 25) == 'could not open USB device'){
                /*
                 * Skip, this is not a scanner
                 */

            }else{
                $retval['unknown'][] = $result;
            }

        }

        return $retval;

    }catch(Exception $e){
        throw new bException('sane_find_scanners(): Failed', $e);
    }
}
?>
