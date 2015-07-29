
<?php
/*
 * Basic BASE configuration file. DO NOT MODIFY THIS FILE! This file contains default values
 * that may be overwritten when you perform a system update!
 *
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE

 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
 */
$_CONFIG['geo'] = array('lookup' => 'geonames',
                        'cities' => array('filter_type' => ' OR ',
                                          'filters'     => array('min_population' => 200000,
                                                                 'feature_code'   => 'PPLA,PPLA2')));
?>
