<?php
/*
 * Avoid double entries in html_img caching table
 */
sql_index_exists ('html_img', 'url', '!ALTER TABLE `html_img` ADD UNIQUE `url` (`url`)');
?>
