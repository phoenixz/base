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

        sql_query('INSERT INTO `drivers_devices` (`createdby`, `meta_id`, `type`, `manufacturer`, `model`, `vendor`, `product`, `libusb`, `bus`, `device`, `string`, `seostring`, `default`, `description`)
                   VALUES                        (:createdby , :meta_id , :type , :manufacturer , :model , :vendor , :product , :libusb , :bus , :device , :string , :seostring , :default , :description )',

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
                         ':seostring'    => $device['seostring'],
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
        load_libs('validate,seo');
        $v = new validate_form($device, 'type,manufacturer,model,vendor,product,libusb,bus,device,string,default,description');

        $v->isAlphaNumericDash($device['type'], tr('Please specify a valid device type string (only alpha numeric characters and a -)'));
        $v->isNotEmpty($device['type'], tr('Please specify a device type'));
        $v->hasMinChars($device['type'],  2, tr('Please specify a device type of 2 characters or more'));
        $v->hasMaxChars($device['type'], 32, tr('Please specify a device type of maximum 32 characters'));

        $v->isAlphaNumericDash($device['manufacturer'], tr('Please specify a valid device manufacturer'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMinChars($device['manufacturer'],  2, tr('Please specify a device manufacturer of 2 characters or more'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['manufacturer'], 32, tr('Please specify a device manufacturer of maximum 32 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isAlphaNumericDash($device['model'], tr('Please specify a valid device model'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMinChars($device['model'],  2, tr('Please specify a device model of 2 characters or more'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['model'], 32, tr('Please specify a device model of maximum 32 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isHexadecimal($device['vendor'], tr('Please specify a valid hexadecimal device vendor string'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMinChars($device['vendor'], 4, tr('Please specify a device vendor of 4 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['vendor'], 4, tr('Please specify a device vendor of 4 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isHexadecimal($device['product'], tr('Please specify a valid hexadecimal device vendor string'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMinChars($device['product'], 4, tr('Please specify a device vendor of 4 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['product'], 4, tr('Please specify a device vendor of 4 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->isNatural($device['bus']   , 1, tr('Please specify a valid , natural device bus number'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($device['device'], 1, tr('Please specify a valid, natural device number'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->hasMinChars($device['libusb'], 7, tr('Please specify a libusb string of 7 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['libusb'], 7, tr('Please specify a libusb string of 7 characters'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isRegex($device['libusb'], '/\d{3}:\d{3}/', tr('Please specify a libusb string in the format nnn:nnn'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->hasMinChars($device['string'],   2, tr('Please specify a device string of 2 characters or more'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['string'], 128, tr('Please specify a device string of maximum 128 characters'), VALIDATE_ALLOW_EMPTY_NULL);

        $v->hasMinChars($device['description'],   2, tr('Please specify a device description of 2 characters or more'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($device['description'], 255, tr('Please specify a device description of maximum 255 characters'), VALIDATE_ALLOW_EMPTY_NULL);

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

        if($device['default']){
            /*
             * Ensure that there is not another device already the default
             */
            $exists = sql_get('SELECT `string` FROM `drivers_devices` WHERE `type` = :type AND `default` IS NOT NULL AND `id` != :id', array(':type' => $device['type'], ':id' => $device['id']));

            if($exists){
                $v->setError(tr('Device ":device" already is the default for ":type" devices', array(':device' => $exists, ':type' => $device['type'])));
            }

        }else{
            $device['default'] = !sql_get('SELECT COUNT(`id`) AS `count` FROM `drivers_devices` WHERE `type` = :type', true, array(':type' => $device['type']));

            if(!$device['default']){
                $device['default'] = null;
            }
        }

        $v->isValid();

        /*
         * Cleanup
         */
        $device['seostring']   = seo_unique($device['seostring'], 'drivers_devices', $device['id'], 'seostring');
        $device['description'] = str_replace('_', ' ', $device['description']);

        return $device;

    }catch(Exception $e){
        throw new bException('drivers_validate_device(): Failed', $e);
    }
}



/*
 *
 */
function drivers_device_status($device, $status){
    try{
        if(is_numeric($device)){
            $delete = sql_query('UPDATE `drivers_devices` SET `status` = :status WHERE `id` = :id'        , array(':id'     => $device, ':status' => $status));

        }else{
            $delete = sql_query('UPDATE `drivers_devices` SET `status` = :status WHERE `string` = :string', array(':string' => $device, ':status' => $status));
        }

        return $delete->rowCount();

    }catch(Exception $e){
        throw new bException('drivers_disable_device(): Failed', $e);
    }
}



/*
 * Add options for a device
 */
function drivers_add_options($devices_id, $options){
    try{
        $count  = 0;
        $insert = sql_prepare('INSERT INTO `drivers_options` (`devices_id`, `status`, `key`, `value`, `default`)
                               VALUES                        (:devices_id , :status , :key , :value , :default )');

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
                                       ':status'     => $values['status'],
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
function drivers_get_options($devices_id, $inactive = false){
    try{
        if($inactive){
            $retval  = array();
            $options = sql_query('SELECT `key`, `value`, `default` FROM `drivers_options` WHERE `devices_id` = :devices_id', array(':devices_id' => $devices_id));

        }else{
            $retval  = array();
            $options = sql_query('SELECT `key`, `value`, `default` FROM `drivers_options` WHERE `devices_id` = :devices_id AND `status` IS NULL', array(':devices_id' => $devices_id));
        }

        if(!$options){
            throw new bException(tr('drivers_get_options(): Speficied drivers id ":id" does not exist', array(':id' => $devices_id)), 'not-exist');
        }

        foreach($options as $option){
            if(empty($retval[$option['key']])){
                $retval[$option['key']] = array();
            }

            $retval[$option['key']][$option['value']] = $option['value'];

            if($option['default']){
                $retval[$option['key']]['default'] = $option['default'];
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
function drivers_get_devices($type, $all = false, $default_only = false){
    try{
        if($default_only){
            $where = 'WHERE  `type`    = :type
                      AND    `status`  IS NULL
                      AND    `default` = 1';

        }elseif($all){
            $where = 'WHERE  `type`    = :type';

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
        $devices = drivers_get_devices($type, false, true);

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
