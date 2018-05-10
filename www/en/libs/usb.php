<?php
/*
 * USB library
 *
 * This library is a frontend to lsusb
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * List all available USB devices
 */
function usb_list($libusb = null){
    try{
        $results = safe_exec('lsusb');
        $devices = array();

        foreach($results as $result){
            //Bus 004 Device 001: ID 1d6b:0003 Linux Foundation 3.0 root hub
            preg_match('/Bus (\d{3}) Device (\d{3}): ID ([0-9a-f]{4}):([0-9a-f]{4}) (.+)/', $result, $matches);

            $device = array('raw'     => $matches[0],
                            'bus'     => $matches[1],
                            'device'  => $matches[2],
                            'vendor'  => $matches[3],
                            'product' => $matches[4],
                            'name'    => $matches[5]);

            if($libusb){
                if($libusb == $device['bus'].':'.$device['device']){
                    /*
                     *
                     */
                    return $device;
                }

                /*
                 *
                 */
                continue;
            }

            $devices[] = $device;

        }

        return $devices;

    }catch(Exception $e){
        throw new bException('usb_list(): Failed', $e);
    }
}
?>
