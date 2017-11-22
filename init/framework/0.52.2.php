<?php
/*
 * Users can now independantly specify their timezones
 */
sql_column_exists('users', 'timezone', '!ALTER TABLE `users` ADD COLUMN `timezone` VARCHAR(32) NULL');

/*
 * Ensire that timezone configuration is correct
 */
if(is_string($_CONFIG['timezone'])){
    throw new bException(tr('Check your timezone configuration! $_CONFIG[timezone] is a string, which is invalid. This should be an assoc array containing at least "system" and "display" keys. See ROOT/config/base/default.php timezone key how it should be used.'), 'invalid');
}
?>
