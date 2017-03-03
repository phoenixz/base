<?php
/*
 * API configuration file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */
$_CONFIG['api'] = array('apikey'                            => '',
                        'signin_reset_session'              => '',

                        'whitelist'                         => array(),

                        'blacklist'                         => array(),

                        'list'                              => array('localhost' => array('baseurl' => 'http://localhost/api',
                                                                                          'apikey'  => '')));
?>