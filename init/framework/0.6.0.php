<?php
/*
 * Add GEO tables
 */
load_libs('seo');

sql_query('DROP TABLE IF EXISTS `users_old_passwords`;');
sql_query('DROP TABLE IF EXISTS `users_signins`;');

sql_query('DROP TABLE IF EXISTS `geo_cities`;');
sql_query('DROP TABLE IF EXISTS `geo_provences`;');
sql_query('DROP TABLE IF EXISTS `geo_states`;');
sql_query('DROP TABLE IF EXISTS `geo_countries`;');
sql_query('DROP TABLE IF EXISTS `geo_subregions`;');
sql_query('DROP TABLE IF EXISTS `geo_regions`;');
sql_query('DROP TABLE IF EXISTS `geo_timezones`;');
sql_query('DROP TABLE IF EXISTS `geo_features`;');



sql_query('CREATE TABLE `users_signins` (`id`        INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `users_id`  INT(11)     NOT NULL,
                                         `addedon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `ip`        INT(11)         NULL,
                                         `latitude`  FLOAT(10,6)     NULL,
                                         `longitude` FLOAT(10,6)     NULL,

                                         INDEX (`users_id`),
                                         INDEX (`addedon`),
                                         INDEX (`longitude`),
                                         INDEX (`latitude`),

                                         CONSTRAINT `fk_users_signins_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `users_old_passwords` (`id`       INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                               `users_id` INT(11)     NOT NULL,
                                               `addedon`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                               `password` VARCHAR(64) NOT NULL,

                                               INDEX (`users_id`),
                                               INDEX (`addedon`),

                                               CONSTRAINT `fk_users_old_passwords_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE

                                              ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geo_features` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `addedon`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `code`        VARCHAR(10)  NOT NULL,
                                        `name`        VARCHAR(32)  NOT NULL,
                                        `description` VARCHAR(255)     NULL,

                                        INDEX (`addedon`),
                                        UNIQUE(`code`),
                                        INDEX (`name`)

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geo_timezones` (`id`             INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `addedon`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `cc`             VARCHAR(2)       NULL,
                                         `coordinates`    VARCHAR(16)      NULL,
                                         `utc_offset`     VARCHAR(6)   NOT NULL,
                                         `utc_dst_offset` VARCHAR(6)   NOT NULL,
                                         `name`           VARCHAR(64)  NOT NULL,
                                         `seoname`        VARCHAR(64)  NOT NULL,
                                         `comments`       VARCHAR(255)     NULL,
                                         `notes`          VARCHAR(255)     NULL,

                                         INDEX (`cc`),
                                         INDEX (`coordinates`),
                                         UNIQUE(`name`),
                                         UNIQUE(`seoname`),
                                         INDEX (`utc_offset`),
                                         INDEX (`utc_dst_offset`)

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geo_regions` (`id`      INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                       `code`    VARCHAR(2)  NOT NULL,
                                       `name`    VARCHAR(64) NOT NULL,
                                       `seoname` VARCHAR(64) NOT NULL,

                                       INDEX (`code`),
                                       UNIQUE(`name`),
                                       UNIQUE(`seoname`)

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geo_subregions` (`id`         INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                          `regions_id` INT(11)     NOT NULL,
                                          `code`       VARCHAR(2)  NOT NULL,
                                          `name`       VARCHAR(64) NOT NULL,
                                          `seoname`    VARCHAR(64) NOT NULL,

                                          INDEX (`regions_id`),
                                          INDEX (`code`),
                                          UNIQUE(`name`),
                                          UNIQUE(`seoname`),

                                          CONSTRAINT `fk_geo_subregions_regions_id` FOREIGN KEY (`regions_id`) REFERENCES `geo_regions` (`id`) ON DELETE CASCADE

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geo_countries` (`id`            INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `regions_id`    INT(11)         NULL,
                                         `subregions_id` INT(11)         NULL,
                                         `code`          VARCHAR(2)      NULL,
                                         `code_iso`      VARCHAR(2)      NULL,
                                         `tld`           VARCHAR(2)      NULL,
                                         `name`          VARCHAR(64) NOT NULL,
                                         `seoname`       VARCHAR(64) NOT NULL,

                                         INDEX (`regions_id`),
                                         INDEX (`subregions_id`),
                                         INDEX (`code`),
                                         INDEX (`code_iso`),
                                         INDEX (`tld`),
                                         UNIQUE(`name`),
                                         UNIQUE(`seoname`),

                                         CONSTRAINT `fk_geo_countries_regions_id`    FOREIGN KEY (`regions_id`)    REFERENCES `geo_regions`    (`id`) ON DELETE CASCADE,
                                         CONSTRAINT `fk_geo_countries_subregions_id` FOREIGN KEY (`subregions_id`) REFERENCES `geo_subregions` (`id`) ON DELETE CASCADE

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geo_states` (`id`            INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                      `countries_id`  INT(11)     NOT NULL,
                                      `regions_id`    INT(11)     NOT NULL,
                                      `subregions_id` INT(11)     NOT NULL,
                                      `code`          VARCHAR(2)  NOT NULL,
                                      `name`          VARCHAR(64) NOT NULL,
                                      `seoname`       VARCHAR(64) NOT NULL,

                                       INDEX (`countries_id`),
                                       INDEX (`regions_id`),
                                       INDEX (`subregions_id`),
                                       INDEX (`code`),
                                       UNIQUE(`name`),
                                       UNIQUE(`seoname`),

                                       CONSTRAINT `fk_geo_states_regions_id`    FOREIGN KEY (`regions_id`)    REFERENCES `geo_regions`    (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `fk_geo_states_subregions_id` FOREIGN KEY (`subregions_id`) REFERENCES `geo_subregions` (`id`) ON DELETE CASCADE,
                                       CONSTRAINT `fk_geo_states_countries_id`  FOREIGN KEY (`countries_id`)  REFERENCES `geo_countries`  (`id`) ON DELETE CASCADE

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geo_provences` (`id`            INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                         `states_id`     INT(11)     NOT NULL,
                                         `countries_id`  INT(11)     NOT NULL,
                                         `regions_id`    INT(11)     NOT NULL,
                                         `subregions_id` INT(11)     NOT NULL,
                                         `code`          VARCHAR(2)  NOT NULL,
                                         `name`          VARCHAR(64) NOT NULL,
                                         `seoname`       VARCHAR(64) NOT NULL,

                                          INDEX (`states_id`),
                                          INDEX (`countries_id`),
                                          INDEX (`regions_id`),
                                          INDEX (`subregions_id`),
                                          INDEX (`code`),
                                          UNIQUE(`name`),
                                          UNIQUE(`seoname`),

                                          CONSTRAINT `fk_geo_provences_regions_id`    FOREIGN KEY (`regions_id`)    REFERENCES `geo_regions`    (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `fk_geo_provences_subregions_id` FOREIGN KEY (`subregions_id`) REFERENCES `geo_subregions` (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `fk_geo_provences_countries_id`  FOREIGN KEY (`countries_id`)  REFERENCES `geo_countries`  (`id`) ON DELETE CASCADE,
                                          CONSTRAINT `fk_geo_provences_states_id`     FOREIGN KEY (`states_id`)     REFERENCES `geo_states`     (`id`) ON DELETE CASCADE

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `geo_cities` (`id`                      INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                      `is_city`                 INT(11)          NULL,
                                      `regions_id`              INT(11)          NULL,
                                      `subregions_id`           INT(11)          NULL,
                                      `geonames_id`             INT(11)          NULL,
                                      `provences_id`            INT(11)          NULL,
                                      `states_id`               INT(11)          NULL,
                                      `countries_id`            INT(11)          NULL,
                                      `country_code`            VARCHAR(2)       NULL,
                                      `name`                    VARCHAR(128) NOT NULL,
                                      `realname`                VARCHAR(128) NOT NULL,
                                      `seoname`                 VARCHAR(128) NOT NULL,
                                      `alternate_names`         TEXT(5000)       NULL,
                                      `alternate_country_codes` VARCHAR(60)      NULL,
                                      `latitude`                FLOAT(10,6)      NULL,
                                      `longitude`               FLOAT(10,6)      NULL,
                                      `elevation`               INT(11)          NULL,
                                      `population`              INT(11)          NULL,
                                      `timezones_id`            INT(11)          NULL,
                                      `timezone`                VARCHAR(64)      NULL,
                                      `feature_code`            VARCHAR(10)      NULL,
                                      `dem`                     VARCHAR(10)      NULL,
                                      `modification_date`       DATETIME         NULL,

                                       INDEX (`provences_id`),
                                       INDEX (`states_id`),
                                       INDEX (`countries_id`),
                                       INDEX (`regions_id`),
                                       INDEX (`subregions_id`),
                                       INDEX (`geonames_id`),
                                       INDEX (`country_code`),
                                       UNIQUE(`name`),
                                       UNIQUE(`seoname`),
                                       INDEX (`longitude`),
                                       INDEX (`latitude`),
                                       INDEX (`population`),
                                       INDEX (`elevation`),
                                       INDEX (`timezones_id`),
                                       INDEX (`timezone`),
                                       INDEX (`feature_code`),
                                       INDEX (`dem`),
                                       INDEX (`is_city`),
                                       INDEX (`modification_date`),

                                       CONSTRAINT `fk_geo_cities_regions_id`    FOREIGN KEY (`regions_id`)    REFERENCES `geo_regions`    (`id`)   ON DELETE CASCADE,
                                       CONSTRAINT `fk_geo_cities_subregions_id` FOREIGN KEY (`subregions_id`) REFERENCES `geo_subregions` (`id`)   ON DELETE CASCADE,
                                       CONSTRAINT `fk_geo_cities_countries_id`  FOREIGN KEY (`countries_id`)  REFERENCES `geo_countries`  (`id`)   ON DELETE CASCADE,
                                       CONSTRAINT `fk_geo_cities_country_code`  FOREIGN KEY (`country_code`)  REFERENCES `geo_countries`  (`code`) ON DELETE CASCADE,
                                       CONSTRAINT `fk_geo_cities_states_id`     FOREIGN KEY (`states_id`)     REFERENCES `geo_states`     (`id`)   ON DELETE CASCADE,
                                       CONSTRAINT `fk_geo_cities_provences_id`  FOREIGN KEY (`provences_id`)  REFERENCES `geo_provences`  (`id`)   ON DELETE CASCADE,
                                       CONSTRAINT `fk_geo_cities_timezones_id`  FOREIGN KEY (`timezones_id`)  REFERENCES `geo_timezones`  (`id`)   ON DELETE SET NULL,
                                       CONSTRAINT `fk_geo_cities_feature_code`  FOREIGN KEY (`feature_code`)  REFERENCES `geo_features`   (`code`) ON DELETE SET NULL

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Add the regions
 */
sql_query('INSERT INTO geo_regions (`code`, `name`, `seoname`) VALUES (1, "Africa"    , "africa")');
sql_query('INSERT INTO geo_regions (`code`, `name`, `seoname`) VALUES (2, "Americas"  , "americas")');
sql_query('INSERT INTO geo_regions (`code`, `name`, `seoname`) VALUES (3, "Antarctica", "antartica")');
sql_query('INSERT INTO geo_regions (`code`, `name`, `seoname`) VALUES (4, "Asia"      , "asia")');
sql_query('INSERT INTO geo_regions (`code`, `name`, `seoname`) VALUES (5, "Europe"    , "europe")');
sql_query('INSERT INTO geo_regions (`code`, `name`, `seoname`) VALUES (6, "Oceania"   , "oceania")');



/*
 * Add the subregions
 */
$subregions = array('1A' => array('region' => 1, 'name' => 'Central Africa'),
                    '1B' => array('region' => 1, 'name' => 'Eastern Africa'),
                    '1C' => array('region' => 1, 'name' => 'Indian Ocean'),
                    '1D' => array('region' => 1, 'name' => 'Northern Africa'),
                    '1E' => array('region' => 1, 'name' => 'Southern Africa'),
                    '1F' => array('region' => 1, 'name' => 'Western Africa'),
                    '2A' => array('region' => 1, 'name' => 'Central America'),
                    '2B' => array('region' => 2, 'name' => 'North America'),
                    '2C' => array('region' => 2, 'name' => 'South America'),
                    '2D' => array('region' => 4, 'name' => 'West Indies'),
                    '3A' => array('region' => 3, 'name' => 'Antarctica'),
                    '3B' => array('region' => 2, 'name' => 'Atlantic Ocean'),
                    '4A' => array('region' => 4, 'name' => 'Central Asia'),
                    '4B' => array('region' => 4, 'name' => 'East Asia'),
                    '4C' => array('region' => 4, 'name' => 'Northern Asia'),
                    '4D' => array('region' => 4, 'name' => 'South Asia'),
                    '4E' => array('region' => 4, 'name' => 'South East Asia'),
                    '4F' => array('region' => 4, 'name' => 'South West Asia'),
                    '5A' => array('region' => 5, 'name' => 'Central Europe'),
                    '5B' => array('region' => 5, 'name' => 'Eastern Europe'),
                    '5C' => array('region' => 5, 'name' => 'Northern Europe'),
                    '5D' => array('region' => 5, 'name' => 'South East Europe'),
                    '5E' => array('region' => 5, 'name' => 'South West Europe'),
                    '5F' => array('region' => 5, 'name' => 'Southern Europe'),
                    '5G' => array('region' => 5, 'name' => 'Western Europe'),
                    '6A' => array('region' => 6, 'name' => 'North Pacific Ocean'),
                    '6B' => array('region' => 6, 'name' => 'Pacific'),
                    '6C' => array('region' => 6, 'name' => 'South Pacific Ocean'));

foreach($subregions as $code => $data){
//    sql_query('INSERT INTO geo_subregions (`regions_id`, `code`, `name`, `seoname`) VALUES ('.$data['region'].', "'.$code.'", "'.$data['name'].'", "'.seo_create_string($data['name']).'")');
}



/*
 * Fill features table
 */
$path  = get_global_data_path();

$h     = fopen(file_ensure_file($path.'sources/geo/features.txt'), 'r');
$count = 0;

log_console('Populating geo_features table', '', '', false);

while($line = fgets($h, 8192)){
    /*
     * TSV file, CODE NAME DESCRIPTION
     */
    $line = explode("\t", $line);

    if(count($line) != 3){
        /*
         * Skip lines that do not have 3 items
         */
        continue;
    }

    sql_query('INSERT INTO `geo_features` (`code`             , `name`             , `description`)
               VALUES                     ("'.cfm($line[0]).'", "'.cfm($line[1]).'", "'.cfm($line[2]).'");');

    log_console('.', '', 'green', false);
}

log_console('Done', '');



/*
 * Fill timezones table
 */
$h     = fopen(file_ensure_file($path.'sources/geo/timezones.txt'), 'r');
$count = 0;

log_console('Populating geo_timezones table', '', '', false);

while($line = fgets($h, 16384)){
    /*
     * Skip first line, it contains the definitions
     * TSV file, CC*	Coordinates*	TZ*	Comments*	UTC offset	UTC DST offset	Notes
     */
    if(!$count++) continue;

    $line = explode("\t", $line);

    while(count($line) < 7){
        array_unshift($line, '');
    }

    foreach($line as $key => &$item){
        if(!$item){
            $item = 'NULL';

        }else{
            switch($key){
                case 2:
                    $seoname = '"'.seo_generate_unique_name(str_replace('/', '--', cfm($item)), 'geo_timezones').'"';
                    break;

                case 4:
                    // FALLTHROUGH
                case 5:
                    $item = str_replace('−', '-', $item);
            }

            $item = '"'.cfm($item).'"';
        }
    }

    sql_query('INSERT INTO `geo_timezones` (`cc`        , `coordinates`, `utc_offset`, `utc_dst_offset`, `name`      ,    `seoname` , `comments`   , `notes`)
               VALUES                      ('.$line[0].', '.$line[1].' , '.$line[4].', '.$line[5].'    , '.$line[2].',  '.$seoname.',  '.$line[3].', '.$line[6].');');

    log_console('.', '', 'green', false);
}

log_console('Done', '');



///*
// * Import all countries
// */
//$h     = fopen(ROOT.'data/sources/geo/geodatasource/GEODATASOURCE-COUNTRY.TXT', 'r');
//$count = 0;
//
//log_console('Populating geo_countries table', '', '', false);
//
//while($line = fgets($h, 16384)){
//    /*
//     * Skip first line, it contains the definitions
//     *
//     * TSV file, CC_FIPS CC_ISO  TLD     COUNTRY_NAME
//     */
//    if(!$count++) continue;
//
//    $line = explode("\t", $line);
//
//    if($line[1] == '-'){
//        $line[1] = '';
//    }
//
//    $line[2] = substr($line[2], 1, 2);
//
//    foreach($line as $key => &$item){
//        if(!$item){
//            $item = 'NULL';
//
//        }else{
//            if($key == 3){
//                $seoname = '"'.seo_generate_unique_name(cfm($item), 'geo_countries').'"';
//            }
//
//            $item = '"'.cfm($item).'"';
//        }
//    }
//
//    sql_query('INSERT INTO `geo_countries` (`code`      , `code_iso`  , `tld`       , `name`      ,   `seoname`                                               )
//               VALUES                      ('.$line[0].', '.$line[1].', '.$line[2].', '.$line[3].', '.$seoname.');');
//
//    log_console('.', '', 'green', false);
//}

log_console('Done', '');
?>