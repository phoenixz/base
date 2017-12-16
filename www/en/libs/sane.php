<?php
/*
 * sane library
 *
 * This library allows access to the SANE commands
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_config('sane');



/*
 * Find available scanners
 */
function sane_find_scanners(){
    global $_CONFIG;

    try{
        $results = safe_exec('sane-find-scanner -q | grep -v "Could not find"');
        $retval  = array('usb'       => array(),
                         'scsi'      => array(),
                         'parrallel' => array(),
                         'unknown'   => array());

        foreach($results as $result){
            if(substr($result, 0, 17) == 'found USB scanner'){
                /*
                 * Found a USB scanner
                 */
                if(preg_match_all('/found USB scanner (vendor=0x([0-9a-f]{4}) \[([A-Z0-9-_])\], product=0x([0-9a-f]{4}) \[([A-Z0-9-_])\]) at libusb:([0-9{3}]:[0-9]{3})/i', $result, $matches)){
                    $retval['usb'][] = array('vendor'       => $matches[0],
                                             'product'      => $matches[2],
                                             'model'        => $matches[3],
                                             'manufacturer' => $matches[1],
                                             'libusb'       => $matches[4]);

                }else{
                    $retval['unknown'][] = $result;
                }

            }elseif(substr($result, 0, 18) == 'found SCSI scanner'){
// :TEST: This has not been tested due to a lack of parrallel scanners. Do these still exist?
                /*
                 * Found a SCSI scanner
                 */
                if(preg_match_all('/found SCSI scanner (vendor=0x([0-9a-f]{4}) \[([A-Z0-9-_])\], product=0x([0-9a-f]{4}) \[([A-Z0-9-_])\]) at libusb:([0-9{3}]:[0-9]{3})/i', $result, $matches)){
                    $retval['scsi'][] = array('vendor'       => $matches[0],
                                              'product'      => $matches[2],
                                              'model'        => $matches[3],
                                              'manufacturer' => $matches[1],
                                              'libusb'       => $matches[4]);

                }else{
                    $retval['unknown'][] = $result;
                }

            }elseif(substr($result, 0, 23) == 'found parrallel scanner'){
// :TEST: This has not been tested due to a lack of parrallel scanners. Do these still exist?
                /*
                 * Found a parrallel scanner
                 */
                if(preg_match_all('/found parrallel scanner (vendor=0x([0-9a-f]{4}) \[([A-Z0-9-_])\], product=0x([0-9a-f]{4}) \[([A-Z0-9-_])\]) at libusb:([0-9{3}]:[0-9]{3})/i', $result, $matches)){
                    $retval['parrallel'][] = array('vendor'       => $matches[0],
                                                   'product'      => $matches[2],
                                                   'model'        => $matches[3],
                                                   'manufacturer' => $matches[1],
                                                   'libusb'       => $matches[4]);

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
