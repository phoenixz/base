<?php
/*
 * geo_columns library
 *
 * This library file contains functions that return the column definitions of the geo_ tables
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 * Return the columns of the geo_features table
 */
function geo_features_columns(){
    return array('addedon',
                 'code',
                 'name',
                 'description');
}



/*
 * Return the columns of the geo_timezones table
 */
function geo_timezones_columns(){
    return array('addedon',
                 'cc',
                 'coordinates',
                 'utc_offset',
                 'utc_dst_offset',
                 'name',
                 'seoname',
                 'comments',
                 'notes');
}



/*
 * Return the columns of the geo_regions table
 */
function geo_regions_columns(){
    return array('geonames_id',
                 'code',
                 'name',
                 'seoname');
}



/*
 * Return the columns of the geo_subregions table
 */
function geo_subregions_columns(){
    return array('geonames_id',
                 'regions_id',
                 'code',
                 'name',
                 'seoname');
}



/*
 * Return the columns of the geo_countries table
 */
function geo_countries_columns(){
    return array('geonames_id',
                 'regions_id',
                 'subregions_id',
                 'code',
                 'code_iso',
                 'tld',
                 'name',
                 'seoname');
}



/*
 * Return the columns of the geo_states table
 */
function geo_states_columns(){
    return array('geonames_id',
                 'countries_id',
                 'regions_id',
                 'subregions_id',
                 'code',
                 'name',
                 'seoname',
                 'alternate_names',
                 'latitude',
                 'longitude');
}



/*
 * Return the columns of the geo_counties table
 */
function geo_counties_columns(){
    return array('geonames_id',
                 'states_id',
                 'countries_id',
                 'regions_id',
                 'subregions_id',
                 'code',
                 'name',
                 'seoname',
                 'alternate_names',
                 'latitude',
                 'longitude');
}



/*
 * Return the columns of the geo_cities table
 */
function geo_cities_columns(){
    return array('geonames_id',
                 'counties_id',
                 'states_id',
                 'countries_id',
                 'regions_id',
                 'subregions_id',
                 'geonames_id',
                 'is_city',
                 'country_code',
                 'name',
                 'realname',
                 'seoname',
                 'alternate_names',
                 'alternate_country_codes',
                 'latitude',
                 'longitude',
                 'elevation',
                 'population',
                 'timezones_id',
                 'timezone',
                 'features_code',
                 'dem',
                 'modification_date');
}
?>
