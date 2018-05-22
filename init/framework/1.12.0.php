<?php

sql_column_exists('servers'  , 'os_type', '!ALTER TABLE `servers`   ADD  COLUMN `os_type` ENUM("linux", "windows", "freesd", "macos") NULL DEFAULT NULL AFTER `ssh_proxies_id`');

sql_column_exists('servers'  , 'os_group', '!ALTER TABLE `servers`   ADD  COLUMN `os_group` ENUM("debian", "ubuntu", "redhat", "gentoo", "slackware") NULL DEFAULT NULL AFTER `os_type`');

sql_column_exists('servers'  , 'os_name', '!ALTER TABLE `servers`   ADD  COLUMN `os_name` ENUM("ubuntu", "lubuntu", "kubuntu", "edubuntu", "xubuntu", "mint", "redhat", "fedora", "centos") NULL DEFAULT NULL AFTER `os_group`');

sql_column_exists('servers'  , 'os_version', '!ALTER TABLE `servers`   ADD  COLUMN `os_version` VARCHAR(6) NULL DEFAULT NULL AFTER `os_group`');
?>