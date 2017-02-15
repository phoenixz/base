<?php
/*
 * Store original url and cluster name in forwarder clicks to be better able to debug cluster less clicks
 */
sql_column_exists('forwarder_clicks', 'landingpages_id', '!ALTER TABLE `forwarder_clicks` ADD COLUMN  `landingpages_id` INT(11)          NULL AFTER `browser`');
sql_column_exists('forwarder_clicks', 'forwarder_url'  , '!ALTER TABLE `forwarder_clicks` ADD COLUMN  `forwarder_url`   VARCHAR(255) NOT NULL AFTER `referrer`');
sql_column_exists('forwarder_clicks', 'cluster_name'   , '!ALTER TABLE `forwarder_clicks` ADD COLUMN  `cluster_name`    VARCHAR(34)  NOT NULL AFTER `forwarder_url`');
?>
