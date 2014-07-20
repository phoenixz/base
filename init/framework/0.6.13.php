<?php
sql_query('ALTER TABLE geo_countries CHANGE COLUMN `name`    `name`    VARCHAR(200) NOT NULL;');
sql_query('ALTER TABLE geo_countries CHANGE COLUMN `seoname` `seoname` VARCHAR(200) NOT NULL;');

sql_query('ALTER TABLE geo_states    CHANGE COLUMN `name`    `name`    VARCHAR(200) NOT NULL;');
sql_query('ALTER TABLE geo_states    CHANGE COLUMN `seoname` `seoname` VARCHAR(200) NOT NULL;');

sql_query('ALTER TABLE geo_cities    CHANGE COLUMN `name`    `name`    VARCHAR(200) NOT NULL;');
sql_query('ALTER TABLE geo_cities    CHANGE COLUMN `seoname` `seoname` VARCHAR(200) NOT NULL;');
?>