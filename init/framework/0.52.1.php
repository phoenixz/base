<?php
/*
 * Users can now store HTML and encoded images in their description, so we'll need a lot more space
 */
sql_query('ALTER TABLE `users` MODIFY COLUMN `description` MEDIUMTEXT NULL');
?>
