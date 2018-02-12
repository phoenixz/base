<?php
/*
 * Drivers library
 *
 * This library does not contain drivers, but stores information from drivers for quick access
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Add a device to the drivers table
 */
function drivers_add_device($device, $type = null){
    try{
        array_ensure($device);
        array_default($device, 'type', $type);

        $device = drivers_validate_device($device);

        sql_query('INSERT INTO `drivers_devices` (`createdby`, `meta_id`, `type`, `manufacturer`, `model`, `vendor`, `product`, `libusb`, `bus`, `device`, `string`, `default`, `description`)
                   VALUES                        (:createdby , :meta_id , :type , :manufacturer , :model , :vendor , :product , :libusb , :bus , :device , :string , :default , :description )',

                   array(':createdby'    => $_SESSION['user']['id'],
                         ':meta_id'      => meta_action(),
                         ':type'         => $device['type'],
                         ':manufacturer' => $device['manufacturer'],
                         ':model'        => $device['model'],
                         ':vendor'       => $device['vendor'],
                         ':product'      => $device['product'],
                         ':libusb'       => $device['libusb'],
                         ':bus'          => $device['bus'],
                         ':device'       => $device['device'],
                         ':string'       => $device['string'],
                         ':default'      => $device['default'],
                         ':description'  => $device['description']));

        $device['id'] = sql_insert_id();

        return $device;

    }catch(Exception $e){
        throw new bException('drivers_add_device(): Failed', $e);
    }
}



/*
 *
 */
function drivers_validate_device($device){
    try{
        load_libs('validate');
        $v = new validate_form($device, 'type,manufacturer,model,vendor,product,libusb,device,bus,string');

        $exists = sql_get('SELECT `id`

                           FROM   `drivers_devices`

                           WHERE  `string` = :string
                           OR     `libusb` = :libusb',

                           true,

                           array(':string' => $device['string'],
                                 ':libusb' => $device['libusb']));

        if($exists){
            /*
             * Driver cache already exists for this device. Delete it all and insert it freshly
             */
            sql_query('DELETE FROM `drivers_devices` WHERE `id` = :id', array(':id' => $exists));
        }

        $device['default'] = !sql_get('SELECT COUNT(`id`) AS `count` FROM `drivers_devices` WHERE `type` = :type', true, array(':type' => $device['type']));

        return $device;

    }catch(Exception $e){
        throw new bException('drivers_validate_device(): Failed', $e);
    }
}



/*
 * Add options for a device
 */
function drivers_add_options($devices_id, $options){
    try{
        $count  = 0;
        $insert = sql_prepare('INSERT INTO `drivers_options` (`devices_id`, `key`, `value`, `default`)
                               VALUES                        (:devices_id , :key , :value , :default )');

        foreach($options as $key => $values){
            /*
             * Extract default values, if available
             */
            foreach($values['data'] as $value){
                $count++;

                if(strstr($value, '..')){
                    /*
                     * This is a single range entry
                     */
                    $default = $values['default'];

                }else{
                    $default = (($value == $values['default']) ? $value : null);
                }

                $insert->execute(array(':devices_id' => $devices_id,
                                       ':key'        => $key,
                                       ':value'      => $value,
                                       ':default'    => $default));
            }
        }

        return $count;

    }catch(Exception $e){
        throw new bException('drivers_add_options(): Failed', $e);
    }
}



/*
 *
 */
function drivers_validate_options($option){
    try{
        load_libs('validate');
        $v = new validate_form($device, 'key,value,default');

        return $option;

    }catch(Exception $e){
        throw new bException('drivers_validate_options(): Failed', $e);
    }
}



/*
 *
 */
function drivers_get_options($devices_id){
    try{
        $retval  = array();
        $options = sql_query('SELECT `key`, `value`, `default` FROM `drivers_options` WHERE `devices_id` = :devices_id', array(':devices_id' => $devices_id));

        if(!$options){
            throw new bException(tr('drivers_get_options(): Speficied drivers id ":id" does not exist', array(':id' => $devices_id)), 'not-exist');
        }

        foreach($options as $option){
            if(empty($retval[$option['key']])){
                $retval[$option['key']] = array();
            }

            $retval[$option['key']][] = $option['value'];

            if($option['default']){
                $retval[$option['key']]['default'] = $option['value'];
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('drivers_get_options(): Failed', $e);
    }
}



/*
 * Return the available registered drivers for the specified type
 */
function drivers_get_devices($type, $default_only = false){
    try{
        if($default_only){
            $where = 'WHERE  `type`    = :type
                      AND    `status`  IS NULL
                      AND    `default` = 1';

        }else{
            $where = 'WHERE  `type`   = :type
                      AND    `status` IS NULL';
        }

        $devices = sql_query('SELECT `id`,
                                     `meta_id`,
                                     `status`,
                                     `type`,
                                     `manufacturer`,
                                     `model`,
                                     `vendor`,
                                     `product`,
                                     `libusb`,
                                     `bus`,
                                     `device`,
                                     `string`,
                                     `default`,
                                     `description`

                              FROM   `drivers_devices`'.$where,

                              array(':type' => $type));

        return $devices;

    }catch(Exception $e){
        throw new bException('drivers_get_devices(): Failed', $e);
    }
}



/*
 * Return the device with the specified device string
 */
function drivers_get_device($device_string){
    try{
        $device = sql_get('SELECT `id`,
                                  `meta_id`,
                                  `status`,
                                  `type`,
                                  `manufacturer`,
                                  `model`,
                                  `vendor`,
                                  `product`,
                                  `libusb`,
                                  `bus`,
                                  `device`,
                                  `string`,
                                  `default`,
                                  `description`

                           FROM   `drivers_devices`

                           WHERE  `string` = :string
                           AND    `status` IS NULL',

                           array(':string' => $device_string));

        return $device;

    }catch(Exception $e){
        throw new bException('drivers_get_device(): Failed', $e);
    }
}



/*
 * Return the default device for the specified device type
 */
function drivers_get_default_device($type){
    try{
        $devices = drivers_get_devices($type, true);

        if(!$devices->rowCount()){
            return null;
        }

        while($device = sql_fetch($devices)){
            if($device['default']){
                return $device;
            }
        }

        return null;

    }catch(Exception $e){
        throw new bException('drivers_get_options(): Failed', $e);
    }
}



/*
 *
 */
function drivers_clear_devices($type){
    try{
        $delete = sql_query('DELETE FROM `drivers_devices` WHERE `type` = :type', array(':type' => $type));
        return $delete->rowCount();

    }catch(Exception $e){
        throw new bException('drivers_clear_devices(): Failed', $e);
    }
}
?>
