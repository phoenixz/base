<?php
/*
 * Basic BASE configuration file. DO NOT MODIFY THIS FILE! This file contains default values
 * that may be overwritten when you perform a system update!
 *
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY "debug" IS ON TOP FOR CONVENIENCE

 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */
$_CONFIG['crypto']                              = array('backend'       => 'coinpayments',   // Select what backend to use for crypto payments. Use either "coinpayments", "coinbase" (not supported yet!), or "local" for local wallets (not supported yet!)
                                                        'currencies'    => array('BTC', 'LTC', 'ETC'));
?>