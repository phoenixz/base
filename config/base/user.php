<?php
/*
 * USER CONFIGURATION FILE
 *
 * @author Sven Oostenbrink <support@capmega.com>,
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Configuration
 * @package user
 */
$_CONFIG['user']                                                                = array('location' => array('detect'     => true,   // Auto detect city / state / country on user_update_location() calls
                                                                                                            'max_offset' => 0));    // Use offset in meters to hide users exact location offset_latitude / offset_longitude while still having users exact location in latitude / longitude
?>
