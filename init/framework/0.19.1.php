<?php
/*
 * Create the default error and developers
 */
if(!sql_get('SELECT `id` FROM `notifications_classes` WHERE `name` = "error"', 'id')){
    script_exec('notifications/classes create name error methods email,sms description "All error messages will go to this class"');
}

if(!sql_get('SELECT `id` FROM `notifications_classes` WHERE `name` = "developers"', 'id')){
    script_exec('notifications/classes create name developers methods email,sms description "All developer messages will go to this class"');
}
?>