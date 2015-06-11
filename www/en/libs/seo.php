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
function seo_unique_string($string, $table, $ownid = null, $field = 'seoname', $replace = '-', $first_suffix = null) {
    try{
        //prepare string
        $string = trim(seo_string($string, $replace));
        $id     = 0;

// :DELETE: Parameter shuffling should be avoided where possible
        //if($ownid and !is_scalar($ownid)){
        //    /*
        //     * ??
        //     */
        //    $first_suffix = $replace;
        //    $replace      = $field;
        //    $field        = $ownid;
        //}

        //If the seostring exists, add an identifier to it.
        while(true) {
            if(!$id) {
                $str = $string;

            } else {
                if($first_suffix){
                    $string       = $string.trim(seo_string($first_suffix, $replace));
                    $first_suffix = null;
                    $id--;

                }else{
                    $str = $string.$id;
                }
            }

            if($ownid){
                if(is_scalar($ownid)){
                    $ownid = ' AND `id` != '.$ownid;

                }elseif(is_array($ownid)){
                    $key   = key($ownid);

                    if(!is_numeric($ownid[$key])){
                        if(!is_scalar($ownid[$key])){
                            throw new bException(tr('seo_unique_string(): Invalid $ownid array value datatype specified, should be scalar, but is "%type%"', array('%type%' => gettype($ownid[$key]))), 'invalid');
                        }

                        $ownid[$key] = '"'.$ownid[$key].'"';
                    }

                    $ownid = ' AND `'.$key.'` != '.$ownid[$key];

                }else{
                    throw new bException(tr('seo_unique_string(): Invalid $ownid datatype specified, should be either scalar, or array, but is "%type%"', array('%type%' => gettype($ownid))), 'invalid');
                }
            }

            $result = sql_get('SELECT COUNT(*) AS count FROM `'.$table.'` WHERE `'.$field.'` = "'.$str.'"'.$ownid.';');

            if(!$result['count']){
                return $str;
            }

            $id++;
        }

    }catch(Exception $e){
        throw new bException('seo_unique_string(): Failed', $e);
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
    return seo_string($source, $replace = '-');
}

function seo_generate_unique_name($string, $table, $ownid = null, $field = 'seoname', $replace = '-', $first_suffix = null){
    return seo_unique_string($string, $table, $ownid, $field, $replace, $first_suffix);
}
?>
