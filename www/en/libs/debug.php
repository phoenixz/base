<?php
/*
 * Debug library
 *
 * This library contains debug functions
 *
 * These functions do not have a prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



load_config('debug');
showdie(debug_trace());
?>
