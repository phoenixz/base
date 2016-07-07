<?php
/*
 * This is the standard PHP strings extension library
 *
 * Mostly written by Sven Oostenbrink and Johan Geuze, some additions from stackoverflow
 *
 * With few exceptions at the end of this file, all functions have the str_ prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */



/*
 * Fix urls that dont start with http://
 */
function str_ensure_url($url, $protocol = 'http://') {
    if(substr($url, 0, mb_strlen($protocol)) != $protocol) {
        return $protocol.$url;

    } else {
        return $url;
    }
}



/*
 * Return "casa" or "casas" based on number
 */
function str_plural($cnt, $single_text, $multiple_text) {
    if($cnt == 1) {
        return $single_text;

    }

    return $multiple_text;
}



/*
 * Returns true if string is serialized, false if not
 */
function str_is_serialized($data) {
    return (boolean) preg_match( "/^([adObis]:|N;)/u", $data );
}



/*
 * Fix urls that dont start with http://
 */
function str_ensure_utf8($string) {
    if(str_is_utf8($string)) {
        return $string;
    }

    return utf8_encode($string);
}



/*
 * Returns true if string is UTF-8, false if not
 */
function str_is_utf8($source) {
    return mb_check_encoding($source, 'UTF8');
    /*return preg_match('%^(?:
    [\x09\x0A\x0D\x20-\x7E] # ASCII
    | [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
    | \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
    | \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
    | \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
    | [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
    | \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
    )*$%xs', $source);*/
}



/*
 * Return string will not contain HTML codes for Spanish haracters
 */
function str_fix_spanish_chars($source) {
    $from = array('&Aacute;','&aacute;','&Eacute;','&eacute;','&Iacute;','&iacute;','&Oacute;','&oacute;','&Ntilde;','&ntilde;','&Uacute;','&uacute;','&Uuml;','&uuml;','&iexcl;','&ordf;','&iquest;','&ordm;');
    $to   = array('Á','á','É','é','Í','í','Ó','ó','Ñ','ñ','Ú','ú','Ü','ü','¡','ª','¿','º');

    return str_replace($from, $to, $source);
}



/*
 * Return a lowercased string with the first letter capitalized
 */
function str_capitalize($source, $position = 0){
    if(!$position){
        return mb_strtoupper(mb_substr($source, 0, 1)).mb_strtolower(mb_substr($source, 1));
    }

    return mb_strtolower(mb_substr($source, 0, $position)).mb_strtoupper(mb_substr($source, $position, 1)).mb_strtolower(mb_substr($source, $position + 1));
}



/*
 * Return the given string from the specified needle
 */
function str_from($source, $needle, $more = 0){
    try{
        if(!$needle){
            throw new bException('str_from(): No needle specified');
        }

        $pos = mb_strpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $pos + mb_strlen($needle) - $more);

    }catch(Exception $e){
        throw new bException('str_from(): Failed for "'.str_log($source).'"', $e);
    }
}



/*
 * Return the given string from 0 until the specified needle
 */
function str_until($source, $needle, $more = 0, $start = 0){
    try{
        if(!$needle){
            throw new bException('str_until(): No needle specified');
        }

        $pos = mb_strpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $start, $pos + $more);

    }catch(Exception $e){
        throw new bException('str_until(): Failed for "'.str_log($source).'"', $e);
    }
}



/*
 * Return the given string from the specified needle, starting from the end
 */
function str_rfrom($source, $needle, $more = 0){
    try{
        if(!$needle){
            throw new bException('str_rfrom(): No needle specified');
        }

        $pos = mb_strrpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $pos + mb_strlen($needle) - $more);

    }catch(Exception $e){
        throw new bException('str_rfrom(): Failed for "'.str_log($source).'"', $e);
    }
}



/*
 * Return the given string from 0 until the specified needle, starting from the end
 */
function str_runtil($source, $needle, $more = 0, $start = 0){
    try{
        if(!$needle){
            throw new bException('str_runtil(): No needle specified');
        }

        $pos = mb_strrpos($source, $needle);

        if($pos === false) return $source;

        return mb_substr($source, $start, $pos + $more);

    }catch(Exception $e){
        throw new bException('str_runtil(): Failed for "'.str_log($source).'"', $e);
    }
}



/*
 * Truncate string using the specified fill and method
 */
function str_truncate($source, $length, $fill = ' ... ', $method = 'right', $on_word = false){
    if(!$length or ($length < (mb_strlen($fill) + 1))){
        throw new bException('str_truncate(): No length or insufficient length specified. You must specify a length of minimal $fill length + 1');
    }

    if($length >= mb_strlen($source)){
        /*
         * No need to truncate, the string is short enough
         */
        return $source;
    }

    /*
     * Correct length
     */
    $length -= mb_strlen($fill);

    switch($method){
        case 'right':
            $retval = mb_substr($source, 0, $length);
            if($on_word and (strpos(substr($source, $length, 2), ' ') === false)){
                if($pos = strrpos($retval, ' ')){
                    $retval = substr($retval, 0, $pos);
                }
            }

            return trim($retval).$fill;

        case 'center':
            return mb_substr($source, 0, floor($length / 2)).$fill.mb_substr($source, -ceil($length / 2));

        case 'left':
            $retval = mb_substr($source, -$length, $length);

            if($on_word and substr($retval)){
                if($pos = strpos($retval, ' ')){
                    $retval = substr($retval, $pos);
                }
            }

            return $fill.trim($retval);

        default:
            throw new bException('str_truncate(): Invalid method "'.$method.'" specified, please use "left", "center", or "right" or undefined which will default to "right"');
    }
}


/*
 * Return a random string
 */
function str_random($length = 8, $unique = false, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $string     = '';
    $charlen    = mb_strlen($characters);

    if($unique and ($length > $charlen)){
        throw new bException('str_random(): Can not create unique character random string with size "'.str_log($length).'". When $unique is requested, the string length can not be larger than "'.str_log($charlen).'" because there are no more then that amount of unique characters');
    }

    for ($i = 0; $i < $length; $i++) {
        $char = $characters[mt_rand(0, $charlen - 1)];

        if($unique and (mb_strpos($string, $char) !== false)){
            /*
             * We want all characters to be unique, do not readd this character again
             */
            $i--;
            continue;
        }

        $string .= $char;
    }

    return $string;
}



/*
 * Is spanish alphanumeric
 */
function str_is_alpha($s, $extra = '\s'){
    $reg   = "/[^\p{L}\d$extra]/u";
    $count = preg_match($reg, $s, $matches);

    return $count == 0;
}



/*
 * Return a clean string, basically leaving only printable latin1 characters,
 */
function str_clean($source, $replace = '-'){
    return preg_replace('/\s|\/|\?|&+/u', $replace, cfm($source));
}



/*
 * Return a clean string, basically leaving only printable latin1 characters,
 */
function str_escape_for_jquery($source, $replace = ''){
    return preg_replace('/[#;&,.+*~\':"!^$[\]()=>|\/]/gu', '\\\\$&', $source);
}



/*
 * Remove double "replace" chars
 */
function str_nodouble($source, $replace = '\1', $character = '.', $case_insensitive = true){
    return preg_replace('/('.$character.')\\1+/u'.($case_insensitive ? 'i' : ''), $replace, $source);
}



/*
 *
 */
function str_strip_function($string){
    return trim(str_from($string, '():'));
}



/*
 * Ensure that specified source string starts with specified string
 */
function str_starts($source, $string){
    if(mb_substr($source, 0, mb_strlen($string)) == $string){
        return $source;
    }

    return $string.$source;
}



/*
 * Ensure that specified source string starts NOT with specified string
 */
function str_starts_not($source, $string){
    if(mb_substr($source, 0, mb_strlen($string)) == $string){
        return mb_substr($source, mb_strlen($string));
    }

    return $source;
}



/*
 * Ensure that specified string ends with specified character
 */
function str_ends($source, $string){
    try{
        $length = mb_strlen($string);

        if(mb_substr($source, -$length, $length) == $string){
            return $source;
        }

        return $source.$string;

    }catch(Exception $e){
        throw new bException('str_ends(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends NOT with specified character
 */
function str_ends_not($source, $strings, $loop = true){
    try{
        if(is_array($strings)){
            /*
             * For array test, we always loop
             */
            $redo = true;

            while($redo){
                $redo = false;

                foreach($strings as $string){
                    $new = str_ends_not($source, $string, true);

                    if($new != $source){
                        // A change was made, we have to rerun over it.
                        $redo = true;
                    }

                    $source = $new;
                }
            }

        }else{
            /*
             * Check for only one character
             */
            $length = mb_strlen($strings);

            while(mb_substr($source, -$length, $length) == $strings){
                $source = mb_substr($source, 0, -$length);
                if(!$loop) break;
            }
        }

        return $source;

    }catch(Exception $e){
        throw new bException('str_ends_not(): Failed', $e);
    }
}



/*
 * Will fix a base64 coded string with missing termination = marks before decoding it
 */
function str_safe_base64_decode($source){
    if($mod = mb_strlen($source) % 4){
        $source .= str_repeat('=', 4 - $mod);
    }

    return base64_decode($source);
}



/*
 * Cut and return a piece out of the source string, starting from the start string, stopping at the stop string.
 */
function str_cut($source, $start, $stop){
    return str_until(str_from($source, $start), $stop);
}



/*
 * Return a safe size string for displaying
 */
function str_safe($source, $maxsize = 50){
    load_libs('json');
    return str_truncate(json_encode_custom($source), $maxsize);
}



/*
 * Return the entire string in HEX ASCII
 */
function str_hex($source){
    return bin2hex($source);
}



/*
 * Return a string that is suitable for logging.
 */
function str_log($source, $truncate = 511, $separator = ', '){
    try{
        load_libs('json');

        if(!$source){
            if(is_numeric($source)){
                return 0;
            }

            return '';
        }

        if(!is_scalar($source)){
            if(is_array($source)){
                try{
                    $source = mb_trim(json_encode_custom($source));

                }catch(Exception $e){
                    /*
                     * Most likely (basically only) reason for this would be that implode failed on imploding an array with sub arrays.
                     * Use json_encode_custom() instead
                     */
                    $source = mb_trim(json_encode_custom($source));
                }

            }else{
                $source = mb_trim(json_encode_custom($source));
            }
        }

// :DELETE: str_log() should not modify data specially for HTML display, only should be a valid string, not too large, etc
        //if(PLATFORM == 'http'){
        //    return htmlentities(str_replace('  ', ' ', str_replace("\n", ' ', str_truncate($source, $truncate, ' ... ', 'center'))), ENT_DISALLOWED | ENT_SUBSTITUTE | ENT_NOQUOTES | ENT_HTML5, 'UTF-8', false);
        //}

        return str_replace('  ', ' ', str_replace("\n", ' ', str_truncate($source, $truncate, ' ... ', 'center')));

    }catch(Exception $e){
        throw new bException('str_log(): Failed', $e);
    }
}



/*
 * Return a camel cased string
 */
function str_camelcase($source, $separator = ' ') {
    $source = explode($separator, mb_strtolower($source));

    foreach($source as $key => &$value){
        $value = mb_ucfirst($value);
    }

    unset($value);

    return implode($separator, $source);
}



/*
 * Fix PHP explode
 */
function str_explode($separator, $source){
    if(!$source){
        return array();
    }

    return explode($separator, $source);
}



/*
 * Interleave given string with given secundary string
 *
 *
 *
 */
function str_interleave($source, $interleave, $end = 0, $chunksize = 1){
    if(!$source){
        throw new bException('str_interleave: No source specified');
    }

    if(!$interleave){
        throw new bException('str_interleave: No interleave specified');
    }

    if($end){
        $begin = mb_substr($source, 0, $end);
        $end   = mb_substr($source, $end);

    }else{
        $begin = $source;
        $end   = '';
    }

    $begin  = mb_str_split($begin, $chunksize);
    $retval = '';

    foreach($begin as $chunk){
        $retval .= $chunk.$interleave;
    }

    return mb_substr($retval, 0, -1).$end;
}



/*
 * Convert weird chars to their standard ASCII variant
 */
function str_convert_accents($source) {
    $from = explode(',', "ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,Ú,ñ,Ñ,º");
    $to   = explode(',', "c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,U,n,n,o");

    return preg_replace('/[^[:alnum:] -]/', '', str_replace($from, $to, $source));
}



/*
 * Strip whitespace
 */
function str_strip_html_whitespace($string){
    return preg_replace('/>\s+</u', '><', $string);
}



/*
 * Return the specified string quoted or not
 */
function str_auto_quote($string){
    if(is_numeric($string)){
        return $string;
    }

    return '"'.$string.'"';
}



/*
 * Return if specified source is a valid version or not
 */
function str_is_version($source){
    try{
        return preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}$/', $source);

    }catch(Exception $e){
        throw new bException('str_is_version(): Failed', $e);
    }
}



/*
 * Returns true if the specified source string contains HTML
 */
function str_is_html($source){
  return !preg_match('/<[^<]+>/', $source);
}



/*
 * Return if specified source is a JSON string or not
 */
function str_is_json($source){
//    return !preg_match('/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/', preg_replace('/"(\\.|[^"\\])*"/g', '', $source));
    return !empty($source) && is_string($source) && preg_match('/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/', $source);
}



/*
 * HERE BE SOME NON str_........ functions that ARE string functions anyway!
 */

/*
 * mb_trim
 *
 * No, trim() is not MB save
 * Yes, this (so far) seems to be safe
 *
 * IMPORTANT! COMMENT THE mb_trim() call in the mb.php libray!! The one there is NOT MB SAFE!!
 */
function mb_trim($string) {
    try{
        return preg_replace("/(^\s+)|(\s+$)/us", "", $string);

    }catch(Exception $e){
        throw new bException('mb_trim(): Failed', $e);
    }
}



/*
 * Proper unicode mb_str_split()
 * Taken from http://php.net/manual/en/function.str-split.php
 */
function mb_str_split($source, $l = 0) {
    try{
        if ($l > 0) {
            $retval = array();
            $length = mb_strlen($source, 'UTF-8');

            for ($i = 0; $i < $length; $i += $l) {
                $retval[] = mb_substr($source, $i, $l, 'UTF-8');
            }

            return $retval;
        }

        return preg_split("//u", $source, -1, PREG_SPLIT_NO_EMPTY);

    }catch(Exception $e){
        throw new bException('mb_str_split(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends with slash
 */
function slash($string){
    try{
        return str_ends($string, '/');

    }catch(Exception $e){
        throw new bException('slash(): Failed', $e);
    }
}



/*
 * Ensure that specified string ends NOT with slash
 */
function unslash($string, $loop = true){
    try{
        return str_ends_not($string, '/', $loop);

    }catch(Exception $e){
        throw new bException('unslash(): Failed', $e);
    }
}



/*
 * Correctly converts <br> to \n
 */
function br2nl($string, $nl = "\n") {
    $string = preg_replace("/(\r\n|\n|\r)/u", '' , $string);
    return    preg_replace("/<br *\/?>/iu"  , $nl, $string);
}



/*
 * Returns true if the specified text has one (or all) of the specified keywords
 */
function str_has_keywords($text, $keywords, $has_all = false, $regex = false, $unicode = true){
    try{
        if(!is_array($keywords)){
            if(!is_string($keywords) and !is_numeric($keywords)){
                throw new bException('str_has_keywords(): Specified keywords are neither string or array', 'invalid');
            }

            if($regex){
                $keywords = array($keywords);

            }else{
                $keywords = explode(',', $keywords);
            }
        }

        $count = 0;

        foreach($keywords as $keyword){
            /*
             * Ensure keywords are trimmed, and don't search for empty keywords
             */
            if(!trim($keyword)){
                continue;
            }

            if($regex){
                if(preg_match('/'.$keyword.'/ims'.($unicode ? 'u' : ''), $text, $matches) !== false){
                    if(!$has_all){
                        /*
                         * We're only interrested in knowing if it has one of the specified keywords
                         */
                        return array_shift($matches);
                    }

                    $count++;
                }

            }else{
                if(stripos($text, $keyword) !== false){
                    if(!$has_all){
                        /*
                         * We're only interrested in knowing if it has one of the specified keywords
                         */
                        return $keyword;
                    }

                    $count++;
                }
            }
        }

        return $count == count($keywords);

    }catch(Exception $e){
        throw new bException('str_has_keywords(): Failed', $e);
    }
}



/*
 * Returns the source string in the specified type
 * styles may be:
 *
 * lowercase              abcdefg
 * uppercase              ABCDEFG
 * capitalize             Abcdefg
 * doublecapitalize       AbcdefG
 * invertcapitalize       aBCDEFG
 * invertdoublecapitalize aBCDEFg
 * interleave             aBcDeFg
 * invertinterleave       AbCdEfG
 * consonantcaps          aBCDeFG
 * vowelcaps              AbcdEfg
 * lowercentercaps        abcDefg
 * capscenterlower        ABCdEFG
 */
function str_caps($string, $type){
    try{
        /*
         * First find all words
         */
        preg_match_all('/\b(?:\w|\s)+\b/umsi', $string, $results);

        if($type == 'random'){
            $type = pick_random(1,
                                'lowercase',
                                'uppercase',
                                'capitalize',
                                'doublecapitalize',
                                'invertcapitalize',
                                'invertdoublecapitalize',
                                'interleave',
                                'invertinterleave',
                                'consonantcaps',
                                'vowelcaps',
                                'lowercentercaps',
                                'capscenterlower');
        }

        /*
         * Now apply the specified type to all words
         */
        foreach($results as $words){
            foreach($words as $word){
                /*
                 * Create the $replace string
                 */
                switch($type){
                    case 'lowercase':
                        $replace = strtolower($word);
                        break;

                    case 'uppercase':
                        $replace = strtoupper($word);
                        break;

                    case 'capitalize':
                        $replace = strtoupper(substr($word, 0, 1)).strtolower(substr($word, 1));
                        break;

                    case 'doublecapitalize':
                        $replace = strtoupper(substr($word, 0, 1)).strtolower(substr($word, 1, -1)).strtoupper(substr($word, -1, 1));
                        break;

                    case 'invertcapitalize':
                        $replace = strtolower(substr($word, 0, 1)).strtoupper(substr($word, 1));
                        break;

                    case 'invertdoublecapitalize':
                        $replace = strtolower(substr($word, 0, 1)).strtoupper(substr($word, 1, -1)).strtolower(substr($word, -1, 1));
                        break;

                    case 'interleave':
                        $replace = $word;
                        break;

                    case 'invertinterleave':
                        $replace = $word;
                        break;

                    case 'consonantcaps':
                        $replace = $word;
                        break;

                    case 'vowelcaps':
                        $replace = $word;
                        break;

                    case 'lowercentercaps':
                        $replace = $word;
                        break;

                    case 'capscenterlower':
                        $replace = $word;
                        break;

                    default:
                        throw new bException('str_caps(): Unknown type "'.str_log($type).'" specified', 'unknowntype');
                }

                str_replace($word, $replace, $string);
            }
        }

        return $string;

    }catch(Exception $e){
        throw new bException('str_caps(): Failed', $e);
    }
}



/*
 * Returns an estimation of the caps style of the string
 * styles may be:
 *
 * lowercase               abcdefg
 * uppercase               ABCDEFG
 * capitalized             Abcdefg
 * doublecapitalized       AbcdefG
 * invertcapitalized       aBCDEFG
 * invertdoublecapitalized aBCDEFg
 * interleaved             aBcDeFg
 * invertinterleaved       AbCdEfG
 * consonantcaps           aBCDeFG
 * vowelcaps               AbcdEfg
 * lowercentercaps         abcDefg
 * capscenterlower         ABCdEFG
 */
function str_caps_guess($string){
    try{
        $posibilities = array('lowercase'             ,
                              'uppercase'             ,
                              'capitalize'            ,
                              'doublecapitalize'      ,
                              'invertcapitalize'      ,
                              'invertdoublecapitalize',
                              'interleave'            ,
                              'invertinterleave'      ,
                              'consonantcaps'         ,
                              'vowelcaps'             ,
                              'lowercentercaps'       ,
                              'capscenterlower'       );

        /*
         * Now, find all words
         */
        preg_match_all('/\b(?:\w\s)+\b/umsi', $string, $words);

        /*
         * Now apply the specified type to all words
         */
        foreach($words as $word){
        }

    }catch(Exception $e){
        throw new bException('str_caps_guess_type(): Failed', $e);
    }
}



/*
 * Force the specified source to be a string
 */
function str_force($source, $separator = ','){
    try{
        if(!is_scalar($source)){
            if(!is_array($source)){
                if(!$source){
                    return '';
                }

                throw new bException('str_force(): Specified source is neither array or string');
            }

            return implode($separator, $source);
        }

        return (string) $source;

    }catch(Exception $e){
        throw new bException('str_force(): Failed', $e);
    }
}



/*
 * Force the specified string to be the specified size.
 */
function str_size($source, $size, $add = ' ', $prefix = false){
    try{
        $strlen = mb_strlen($source);

        if($strlen == $size){
            return $source;
        }

        if($strlen > $size){
            return substr($source, 0, $size);
        }

        if($prefix){
            return str_repeat($add, $size - $strlen).$source;
        }

        return $source.str_repeat($add, $size - $strlen);

    }catch(Exception $e){
        throw new bException('str_size(): Failed', $e);
    }
}



/*
 *
 */
function str_escape($string, $escape = '"'){
    try{
        for($i = (mb_strlen($escape) - 1); $i <= 0; $i++){
            $string = str_replace($escape[$i], '\\'.$escape[$i], $string);
        }

        return $string;

    }catch(Exception $e){
        throw new bException('str_escape(): Failed', $e);
    }
}



/*
 *
 */
function str_xor($a, $b){
    try{
        $diff   = $a ^ $b;
        $retval = '';

        for ($i = 0, $len = mb_strlen($diff); $i != $len; ++$i) {
            $retval[$i] === "\0" ? ' ' : '#';
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('str_xor(): Failed', $e);
    }
}



/*
 *
 */
function str_similar($a, $b, $percent){
    return similar_text($a, $b, $percent);
}



/*
 * Recursively trim all strings in the specified array tree
 */
function str_trim_array($source, $recurse = true){
    try{
        foreach($source as $key => &$value){
            if(is_string($value)){
                $value = mb_trim($value);

            }elseif(is_array($value)){
                if($recurse){
                    $value = str_trim_array($value);
                }
            }
        }

        return $source;

    }catch(Exception $e){
        throw new bException('str_trim_array(): Failed', $e);
    }
}



/*
 *
 * Taken from https://github.com/paulgb/simplediff/blob/5bfe1d2a8f967c7901ace50f04ac2d9308ed3169/simplediff.php
 * Originally written by https://github.com/paulgb
 * Adapted by Sven Oostnbrink support@ingiga.com for use in BASE project
 */
function str_diff(){
    try{
        foreach($old as $oindex => $ovalue){
            $nkeys = array_keys($new, $ovalue);

            foreach($nkeys as $nindex){
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;

                if($matrix[$oindex][$nindex] > $maxlen){
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax   = $oindex + 1 - $maxlen;
                    $nmax   = $nindex + 1 - $maxlen;
                }
            }
        }

        if($maxlen == 0) return array(array('d' => $old, 'i' => $new));

        return array_merge(diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)), array_slice($new, $nmax, $maxlen), diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));

    }catch(Exception $e){
        throw new bException('str_diff(): Failed', $e);
    }
}



/*
 *
 */
function str_boolean($value){
    if($value){
        return 'true';
    }

    return 'false';
}



/* From http://stackoverflow.com/questions/11151250/how-to-compare-two-very-large-strings, implement?
$string1 = "This is a sample text to test a script to highlight the differences between 2 strings, so the second string will be slightly different";
$string2 = "This is 2 s4mple text to test a scr1pt to highlight the differences between 2 strings, so the first string will be slightly different";
for($i=0;$i<strlen($string1);$i++){
    if($string1[$i]!=$string2[$i]){
        $string3[$i] = "<mark>{$string1[$i]}</mark>";
        $string4[$i] = "<mark>{$string2[$i]}</mark>";
    }
    else {
        $string3[$i] = "{$string1[$i]}";
        $string4[$i] = "{$string2[$i]}";
    }
}
$string3 = implode("",$string3);
$string4 = implode("",$string4);

echo "$string3". "<br />". $string4;*/

/*
 * Obsolete functions
 * These functions only exist as wrappers for compatibility purposes
 */
function str_decrypt($data, $key){
    load_libs('crypt');
    return decrypt($data, $key);
}

function str_encrypt($data, $key){
    load_libs('crypt');
    return encrypt($data, $key);
}
?>
