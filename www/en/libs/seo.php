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
function seo_generate_unique_name($string, $table, $ownid = null, $field = 'seoname', $replace = '-', $first_suffix = null) {
    try{
        //prepare string
        $string = trim(seo_create_string($string, $replace));
        $id     = 0;

        if(($ownid !== null) and !is_numeric($ownid)){
            $first_suffix = $replace;
            $replace      = $field;
            $field        = $ownid;
        }

        //If the seostring exists, add an identifier to it.
        while(true) {
            if(!$id) {
                $str = $string;

            } else {
                if($first_suffix){
                    $string       = $string.trim(seo_create_string($first_suffix, $replace));
                    $first_suffix = null;
                    $id--;

                }else{
                    $str = $string.$id;
                }
            }

            $result = sql_get('SELECT COUNT(*) AS count FROM `'.$table.'` WHERE `'.$field.'` = "'.$str.'"'.($ownid ? ' AND `id` != '.$ownid : '').';');

            if(!$result['count']){
                return $str;
            }

            $id++;
        }

    }catch(Exception $e){
        throw new bException('seo_generate_unique_name(): Failed', $e);
    }
}



/*
 * Return a seo appropriate string for given source string
 */
function seo_create_string($source, $replace = '-') {
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
        throw new bException('seo_create_string(): Failed', $e);
    }
}



/*
 * Here be wrapper functions
 * DO NOT USE THESE, THESE FUNCTIONS ARE DEPRECATED AND WILL BE DROPPED IN THE NEAR FUTURE!!
 */
function str_create_seoname($source, $replace = '-'){
    return seo_create_string($string, $replace);
}

function generate_unique_seoname($string, $table, $field = 'seoname'){
    return seo_generate_unique_name($string, $table, $field);
}
?>
