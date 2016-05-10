<?php
/*
 * SEO library
 *
 * This are functions only used for seo things
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Generate an unique seo name
 */
// :TODO: Update to use bound variable queries
function seo_unique($source, $table, $ownid = null, $field = 'seoname', $replace = '-', $first_suffix = null) {
    try{
        /*
         * Prepare string
         */
        $id = 0;

        if(empty($source)){
            throw new bException(tr('seo_unique(): Empty source spefified'), 'empty');
        }

        if(is_array($source)){
            /*
             * The specified source is a key => value array which can be used
             * for unique entries spanning multiple columns
             *
             * Example: geo_cities has unique states_id with seoname
             * $source = array('seoname'   => 'cityname',
             *                 'states_id' => 3);
             *
             * NOTE: The first column will have the identifier added
             */
            foreach($source as $column => &$value){
                if(empty($first)){
                    $first = array($column => $value);
                }

                $value = trim(seo_string($value, $replace));
            }

            unset($value);

        }else{
            $source = trim(seo_string($source, $replace));
        }

        /*
         * Filter out the id of the record itself
         */
        if($ownid){
            if(is_scalar($ownid)){
                $ownid = ' AND `id` != '.$ownid;

            }elseif(is_array($ownid)){
                $key   = key($ownid);

                if(!is_numeric($ownid[$key])){
                    if(!is_scalar($ownid[$key])){
                        throw new bException(tr('seo_unique(): Invalid $ownid array value datatype specified, should be scalar and numeric, but is "%type%"', array('%type%' => gettype($ownid[$key]))), 'invalid');
                    }

                    $ownid[$key] = '"'.$ownid[$key].'"';
                }

                $ownid = ' AND `'.$key.'` != '.$ownid[$key];

            }else{
                throw new bException(tr('seo_unique(): Invalid $ownid datatype specified, should be either scalar, or array, but is "%type%"', array('%type%' => gettype($ownid))), 'invalid');
            }

        }else{
            $ownid = '';
        }

        /*
         * If the seostring exists, add an identifier to it.
         */
        while(true) {
            if(is_array($source)){
                /*
                 * Check on multiple columns, add identifier on first column value
                 */
                if($id) {
                    if($first_suffix){
                        $source[key($first)] = reset($first).trim(seo_string($first_suffix, $replace));
                        $first_suffix        = null;
                        $id--;

                    }else{
                        $source[key($first)] = reset($first).$id;
                    }
                }

                $result = sql_get('SELECT COUNT(*) AS `count` FROM `'.$table.'` WHERE '.array_implode_with_keys($source, ' AND ', ' = ', true).$ownid.';');

                if(!$result['count']){
                    return $source[key($first)];
                }

            }else{
                if(!$id) {
                    $str = $source;

                } else {
                    if($first_suffix){
                        $source       = $source.trim(seo_string($first_suffix, $replace));
                        $first_suffix = null;
                        $id--;

                    }else{
                        $str = $source.$id;
                    }
                }

                $result = sql_get('SELECT COUNT(*) AS `count` FROM `'.$table.'` WHERE `'.$field.'` = "'.$str.'"'.$ownid.';');

                if(!$result['count']){
                    return $str;
                }
            }

            $id++;
        }

    }catch(Exception $e){
        throw new bException('seo_unique(): Failed', $e);
    }
}



/*
 * Return a seo appropriate string for given source string
 */
function seo_string($source, $replace = '-') {
    try{
        if(str_is_utf8($source)){
            load_libs('mb');

            //clean up string
            $source  = mb_strtolower(mb_trim(mb_strip_tags($source)));

            //convert spanish crap to english
            $source2 = str_convert_accents($source);

            //remove special chars
            $from    = array("'", '"', '\\');
            $to      = array('' , '' , '');
            $source3 = str_replace($from, $to, $source2);

            //remove double spaces
            $source = preg_replace('/\s\s+/', ' ', $source3);

            //Replace anything that is junk
            $last   = preg_replace ( '/[^a-zA-Z0-9]/u', $replace, $source);

            //Remove double "replace" chars
            return preg_replace('/\\'.$replace.'\\'.$replace.'+/', '-', $last);

        }else{
            //clean up string
            $source  = strtolower(trim(strip_tags($source)));
            //convert spanish crap to english
            $source2 = str_convert_accents($source);

            //remove special chars
            $from    = array("'", '"', '\\');
            $to      = array('' , '' , '');
            $source3 = str_replace($from, $to, $source2);

            //remove double spaces
            $source = preg_replace('/\s\s+/', ' ', $source3);

            //Replace anything that is junk
            $last   = preg_replace ( '/[^a-zA-Z0-9]/', $replace, $source);

            //Remove double "replace" chars
            return preg_replace('/\\'.$replace.'\\'.$replace.'+/', '-', $last);
        }

    }catch(Exception $e){
        throw new bException('seo_string(): Failed', $e);
    }
}



/*
 * Here be wrapper functions
 * DO NOT USE THESE, THESE FUNCTIONS ARE DEPRECATED AND WILL BE DROPPED IN THE NEAR FUTURE!!
 */
function seo_create_string($source, $replace = '-') {
    try{
        return seo_string($source, $replace = '-');

    }catch(Exception $e){
        throw new bException('seo_string(): Failed', $e);
    }
}

function seo_generate_unique_name($source, $table, $ownid = null, $field = 'seoname', $replace = '-', $first_suffix = null){
    try{
        return seo_unique($source, $table, $ownid, $field, $replace, $first_suffix);

    }catch(Exception $e){
        throw new bException('seo_generate_unique_name(): Failed', $e);
    }
}

function seo_unique_string($source, $table, $ownid = null, $field = 'seoname', $replace = '-', $first_suffix = null) {
    try{
        return seo_unique($source, $table, $ownid, $field, $replace, $first_suffix);

    }catch(Exception $e){
        throw new bException('seo_unique_string(): Failed', $e);
    }
}
?>
