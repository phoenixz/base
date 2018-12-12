<?php
/*
 * Coupons library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 * @category Function reference
 * @package coupons
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @packagecoupons
 *
 * @return void
 */
function coupons_library_init(){
    try{

    }catch(Exception $e){
        throw new bException('coupons_library_init(): Failed', $e);
    }
}



/*
 * Validate the specified coupon
 *
 * This function will validate and sanitize the specified coupon data for
 * database use
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @exception A "validation" type exception will be thrown if the validation fails
 * @see coupon_insert()
 * @see coupon_update()
 * @version 1.23.0: Added documentation
 * @example
 * code
 * $clean = coupon_validate($dirty);
 * showdie($clean);
 * /code
 *
 * This would return
 * code
 *
 * /code
 *
 * @param params $coupon A coupon params array containing coupon data
 * @params string $coupon[category]
 * @params string $coupon[code]
 * @params string $coupon[reward]
 * @return params The specified coupon, validated and sanitized
 */
function coupons_validate($coupon){
    global $_CONFIG;

    try{
        load_libs('validate,seo,categories');

        $v = new validate_form($coupon, 'id,category,code,reward,description');

        /*
         * Validate the code
         */
        $v->notEmpty($coupon['code'], tr('Please specify a coupon code'));
        $v->isRegex($coupon['code'], '/[a-z0-9-]{2-16}/i',  tr('Please specify a valid coupon code: minimum 2 characters, maximum 16 characters, only a-z, A-Z, 0-9 and -'));

        /*
         * Validate the reward
         */
        $v->notEmpty($coupon['reward'], tr('Please specify a reward'));
        $v->isRegex($coupon['reward'], '/[0-9-]{1-8}%?/',  tr('Please specify a valid reward: minimum 1 number, maximum 8 numbers, may end in %'));

        /*
         * Validate the description
         */
        if($server['description']){
            $v->hasMinChars($server['description'],   16, tr('Please specifiy a minimum of 16 characters for the description'));
            $v->hasMaxChars($server['description'], 2047, tr('Please specifiy a maximum of 2047 characters for the description'));

            $server['description'] = cfm($server['description']);

        }else{
            $server['description'] = '';
        }

        /*
         * Validate the category
         */
        if($coupon['category']){
            $exist = categories_get($coupon['category'], 'id');

            if(!$exist){
                $v->setError(tr('The category ":category" does not exist', array(':category' => $coupon['category'])));
            }

        }else{
            $coupon['categories_id'] = null;
        }

        $v->isValid();

        $coupon['seocode'] = seo_unique($coupon['code'], 'coupons', $coupon['id'], 'seocode');

        return $coupon;

    }catch(Exception $e){
        throw new bException('coupons_validate(): Failed', $e);
    }
}



/*
 * Insert specified coupon in the database
 *
 * This function will validate and sanitize the specified coupon and then insert it into the database. Once finished, the validated and sanitized coupon will be returned
 * *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @exception A "validation" type exception will be thrown if the validation fails
 * @see coupon_insert()
 * @see coupon_update()
 * @version 1.23.0: Added documentation
 * @example
 * code
 * $clean = coupon_insert($dirty);
 * showdie($clean);
 * /code
 *
 * This would return
 * code
 *
 * /code
 *
 * @param params $coupon A coupon params array containing coupon data
 * @params string $coupon[category]
 * @params string $coupon[code]
 * @params string $coupon[reward]
 * @params string $coupon[description]
 * @return params The specified coupon, validated, and sanitized
 */
function coupons_insert($coupon){
    try{
        $coupon = coupons_validate($coupon);

        sql_query('INSERT INTO `coupons` (`createdby`, `meta_id`, `categories_id`, `coupon`, `seocoupon`, `reward`, `description`)
                   VALUES                (:createdby , :meta_id , :categories_id , :coupon , :seocoupon , :reward , :description )',

                   array(':createdby'     => isset_get($_SESSION['user']['id']),
                         ':meta_id'       => meta_action(),
                         ':categories_id' => $coupon['categories_id'],
                         ':coupon'        => $coupon['coupon'],
                         ':seocoupon'     => $coupon['seocoupon'],
                         ':reward'        => $coupon['reward'],
                         ':description'   => $coupon['description']));

        $coupon['id'] = sql_insert_id();

        return $coupon;

    }catch(Exception $e){
        throw new bException('coupons_insert(): Failed', $e);
    }
}



/*
 * Update the specified coupon in the database
 *
 * This function will validate and sanitize the specified coupon and then update in the database. Once finished, the validated and sanitized coupon will be returned
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @exception A "validation" type exception will be thrown if the validation fails
 * @see coupon_insert()
 * @see coupon_update()
 * @version 1.23.0: Added documentation
 * @example
 * code
 * $clean = coupon_update($dirty);
 * showdie($clean);
 * /code
 *
 * This would return
 * code
 *
 * /code
 *
 * @param params $coupon A coupon params array containing coupon data
 * @params string $coupon[category]
 * @params string $coupon[code]
 * @params string $coupon[reward]
 * @params string $coupon[description]
 * @return params The specified coupon, validated, and sanitized
 */
function coupons_update($coupon){
    try{
        $coupon = coupons_validate($coupon);
        meta_action($coupon['meta_id'], 'update');

        sql_query('UPDATE `coupons`

                   SET    `categories_id` = :categories_id,
                          `coupon`        = :coupon,
                          `seocoupon`     = :seocoupon,
                          `reward`        = :reward,
                          `description`   = :description

                   WHERE  `id`            = :id',

                   array(':id'            =>  $coupon['id'],
                         ':categories_id' => $coupon['categories_id'],
                         ':coupon'        => $coupon['coupon'],
                         ':seocoupon'     => $coupon['seocoupon'],
                         ':reward'        => $coupon['reward'],
                         ':description'   => $coupon['description']));

        coupons_update_domains($coupon['id'], $coupon['domains']);
        return $coupon;

    }catch(Exception $e){
        throw new bException('coupons_update(): Failed', $e);
    }
}



/*
 * Read the specified coupon from the database and return it
 *
 * This function will read the specified coupon from the databaes and return it
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package coupon
 * @exception A "validation" type exception will be thrown if the validation fails
 * @see coupon_insert()
 * @see coupon_update()
 * @version 1.23.0: Added documentation
 * @example
 * code
 * $clean = coupon_update($dirty);
 * showdie($clean);
 * /code
 *
 * This would return
 * code
 *
 * /code
 *
 * @param mixed $coupon The requested coupon. Can either be specified by id (natural number) or string (seocode)
 * @param string $column The specific column that has to be returned
 * @param string $status Filter by the specified status
 * @param natural $categories_id Filter by the specified categories_id. If NULL, the customer must NOT belong to any category
 * @return array the data for the requested coupon
 */
function coupons_get($coupon, $column = null, $status = null, $categories_id = false){
    try{
        if(is_numeric($coupon)){
            $where[] = ' `coupons`.`id` = :id ';
            $execute[':id'] = $coupon;

        }else{
            $where[] = ' `coupons`.`seocode` = :seocode ';
            $execute[':seocode'] = $coupon;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `coupons`.`status` '.sql_is($status).' :status';
        }

        if($categories_id !== false){
            $execute[':categories_id'] = $categories_id;
            $where[] = ' `customers`.`categories_id` '.sql_is($categories_id).' :categories_id';
        }

        $where = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `coupons` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT    `coupons`.`id`,
                                         `coupons`.`createdon`,
                                         `coupons`.`createdby`,
                                         `coupons`.`meta_id`,
                                         `coupons`.`status`,
                                         `coupons`.`categories_id`,
                                         `coupons`.`code`,
                                         `coupons`.`seocode`,
                                         `coupons`.`reward`,
                                         `coupons`.`description`,

                                         `parents`.`name`    AS `parent`,
                                         `parents`.`seoname` AS `seoparent`

                               FROM      `coupons`

                               LEFT JOIN `coupons` AS `parents`
                               ON        `parents`.`id` = `coupons`.`parents_id` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('coupons_get(): Failed', $e);
    }
}
?>
