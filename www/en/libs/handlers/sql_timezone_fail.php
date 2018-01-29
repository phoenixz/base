<?php
    global $core;

    if(empty($core->register['no_time_zone']) and (SCRIPT != 'init')){
        throw $e;
    }

    /*
     * Indicate that time_zone settings failed (this will subsequently be used by the init system to automatically initialize that as well)
     */
    unset($core->register['no_time_zone']);
    $core->register['time_zone_fail'] = true;
?>