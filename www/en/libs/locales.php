<?php
/*
 * Languages library
 *
 * This library contains various functions related to langages
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Return the locale for the specified IP
 */
function locales_get_for_ip($ip){
    try{
        load_libs('geoip,geo');

        if(!$country = geoip_get_country($ip)){
            /*
             * Fall back to basic US english
             */
            return 'en-US';
        }

        $locales = sql_get('SELECT `languages`
                            FROM   `geo_countries`
                            WHERE  `code` = :code',

                            'languages',

                            array(':code' => strtolower($country)));

        if(!$locales){
            throw new bException('locales_get_for_ip(): Country code "'.str_log($country).'" from the geoip table was not found in the  geo_countries table');
        }

        return $locales;

    }catch(Exception $e){
        throw new bException('locales_get_for_ip(): Failed', $e);
    }
}
?>
