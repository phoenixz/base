<?php
/*
 * mbfunctions
 * PHP Multibyte Functions for PHP < 6
 *
 * Author: Ignacio Lago (www.ignaciolago.com)
 * Version: 1.2
 * Timestamp: 20101008
 *
 * The mainly purpouse is to use UTF-8 in PHP 5 while PHP 6 is not out & ready. Or what PHP < 6 should do but it doesn't! damn!
 *
 * All these functions extend the PHP 5 core mb functions (multibyte). You should use them to use php in no-ascii (utf8) environments.
 *
 * The functions are defined with the same arguments and returns that their no-multibyte counterparts.
 *
 * Note: Some of them could have added arguments with default values defined.
 */



if(!function_exists('utf8_decode')){
    throw new bException(tr('mb: php module "xml" appears not to be installed. Please install the modules first. On Ubuntu and alikes, use "sudo apt-get -y install php-xml php-mbstring; sudo php5enmod xml; sudo php5enmod mbstring" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-xml php-mbstring" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not_available');
}

if(!function_exists('mb_strlen')){
    throw new bException(tr('mb: php module "mbstring" appears not to be installed. Please install the modules first. On Ubuntu and alikes, use "sudo apt-get -y install php-xml php-mbstring; sudo php5enmod xml; sudo php5enmod mbstring" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-xml php-mbstring" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not_available');
}



/*
 * The main secret, the core of the magic is...
 *    utf8_decode ($str);
 *    utf8_encode ($str);
 */
define('UTF8_ENCODED_CHARLIST', 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ');
define('UTF8_DECODED_CHARLIST', utf8_decode('ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ'));


if (! function_exists ('mb_init'))
{
   function mb_init($locale = 'es_ES')
   {
      /*
       * Setting the Content-Type header with charset
       */
      setlocale(LC_CTYPE, $locale.'.UTF-8');

      if(version_compare(phpversion(), '5.6.0') == -1){
         /*
          * New PHP 5.6.0 no longer supports iconv_set_encoding() "output_encoding", and by default uses UTF8 for its default_charset
          */
         iconv_set_encoding("output_encoding", 'UTF-8');
      }

      mb_internal_encoding('UTF-8');
      mb_regex_encoding('UTF-8');
      //header('Content-Type: text/html; charset=utf-8');
   }
}

if (! function_exists ('mb_ucfirst'))
{
   function mb_ucfirst ($str)
   {
      return utf8_encode (ucfirst (utf8_decode($str)));
   }
}

if (! function_exists ('mb_lcfirst'))
{
   function mb_lcfirst ($str)
   {
      return utf8_encode (lcfirst (utf8_decode($str)));
   }
}

if (! function_exists ('mb_ucwords'))
{
   function mb_ucwords ($str)
   {
      return mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
   }
}

if (! function_exists ('mb_strip_accents'))
{
   function mb_strip_accents ($string)
   {
      return mb_strtr ($string, UTF8_ENCODED_CHARLIST, 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn');
   }
}

if (! function_exists ('mb_strtr'))
{
   function mb_strtr ($str, $from, $to = null)
   {
      if(is_array($from))
      {
         foreach($from as $k => $v)
         {
            $utf8_from[utf8_decode($k)]=utf8_decode($v);
         }
         return utf8_encode (strtr (utf8_decode ($str), $utf8_from));
      }
      return utf8_encode (strtr (utf8_decode ($str), utf8_decode($from), utf8_decode ($to)));
   }
}

if (! function_exists('mb_preg_replace'))
{
   function mb_preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = null)
   {
      if(is_array($pattern))
         foreach($pattern as $k => $v)
            $utf8_pattern[utf8_decode($k)]=utf8_decode($v);
      else
         $utf8_pattern=utf8_decode($pattern);

      if(is_array($replacement))
         foreach($replacement as $k => $v)
            $utf8_replacement[utf8_decode($k)]=utf8_decode($v);
      else
         $utf8_replacement=utf8_decode($replacement);

      if(is_array($subject))
         foreach($subject as $k => $v)
            $utf8_subject[utf8_decode($k)]=utf8_decode($v);
      else
         $utf8_subject=utf8_decode($subject);

      $r = preg_replace ($utf8_pattern,$utf8_replacement,$utf8_subject,$limit,$count);

      if(is_array($r))
         foreach($r as $k => $v)
            $return[utf8_encode($k)]=utf8_encode($v);
      else
         $return = utf8_encode($r);

      return $return;
   }
}

if (! function_exists ('mb_str_word_count'))
{
   function mb_str_word_count ($string, $format = 0, $charlist = UTF8_DECODED_CHARLIST)
   {
      /*
       * format
       * 0 - returns the number of words found
       * 1 - returns an array containing all the words found inside the string
       * 2 - returns an associative array, where the key is the numeric position of the word inside the string and the value is the actual word itself
       */
      $r = str_word_count(utf8_decode($string),$format,$charlist);
      if($format == 1 || $format == 2)
      {
         foreach($r as $k => $v)
         {
            $u[$k] = utf8_encode($v);
         }
         return $u;
      }
      return $r;
   }
}

if (! function_exists ('mb_html_entity_decode'))
{
   function mb_html_entity_decode ($string, $quote_style = ENT_COMPAT, $charset = 'UTF-8')
   {
      return html_entity_decode ($string, $quote_style, $charset);
   }
}

if (! function_exists ('mb_htmlentities'))
{
   function mb_htmlentities ($string, $quote_style = ENT_COMPAT, $charset = 'UTF-8', $double_encode = true)
   {
      return htmlentities ($string, $quote_style, $charset, $double_encode);
   }
}

//if (! function_exists ('mb_trim'))
//{
//   function mb_trim ($string, $charlist = null)
//   {
//      if($charlist == null)
//      {
//         return utf8_encode(trim (utf8_decode($string)));
//      }
//      return utf8_encode(trim (utf8_decode($string), utf8_decode($string)));
//   }
//}

/************************ EXPERIMENTAL ZONE ************************/

if (! function_exists('mb_strip_tags_all'))
{
   function mb_strip_tags_all($document,$repl = ''){
      $search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
                     '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
                     '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
                     '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
      );
      $text = mb_preg_replace($search, $repl, $document);
      return $text;
   }
}

if (! function_exists('mb_strip_tags'))
{
   function mb_strip_tags($document,$repl = ''){
      $search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
                     '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
                     '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
                     '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
      );
      $text = mb_preg_replace($search, $repl, $document);
      return $text;
   }
}

if (! function_exists('mb_strip_urls'))
{
   function mb_strip_urls($txt, $repl = ' ')
   {
      $txt = mb_preg_replace('@http[s]?://[^\s<>"\']*@',$repl,$txt);
      return $txt;
   }
}

// parse strings as identifiers
if(!function_exists('mb_string_url'))
{
   function mb_string_url($string, $to_lower = true)
   {
      $string = mb_strtolower($string);
      $string = mb_strip_accents($string);
      $string = preg_replace('@[^a-z0-9]@',' ',$string);
      $string = preg_replace('@\s+@','-',$string);
      return $string;
   }
}


/*
 * Remove invalid UTF8 sequences, or if $replace is true, replace them with
 * (hopefully) reasonably correct UTF8 charactres
 *
 * Taken from http://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
 * Rewritten by Sven Oostenrink for use in BASE framework
 */
function mb_strip_invalid($string, $replace = false){
    try{
        $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                      # ...one or more times
  )
| ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
| ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
/x
END;
        if($replace){
            return preg_replace_callback($regex, 'mb_utf8replacer', $string);
        }

        return preg_replace($regex, '$1', $string);

    }catch(Exception $e){
        throw new bException(tr('mb_strip_invalid(): Failed'), $e);
    }
}



/*
 * Replace captured invalid UTF8 sequences with (hopefully) reasonably correct
 * UTF8 charactres
 *
 * Taken from http://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
 * Rewritten by Sven Oostenrink for use in BASE framework
 */
function mb_utf8replacer($captures){
    try{
        if ($captures[1] != "") {
            /*
             * Valid byte sequence. Return unmodified.
             */
            return $captures[1];
        }

        if ($captures[2] != "") {
            /*
             * Invalid byte of the form 10xxxxxx.
             * Encode as 11000010 10xxxxxx.
             */
            return "\xC2".$captures[2];
        }

        /*
         * Invalid byte of the form 11xxxxxx.
         * Encode as 11000011 10xxxxxx.
         */
        return "\xC3".chr(ord($captures[3])-64);

    }catch(Exception $e){
        throw new bException(tr('mb_utf8replacer(): Failed'), $e);
    }
}



/*
 * Taken from https://stackoverflow.com/questions/10199017/how-to-solve-json-error-utf8-error-in-php-json-decode
 * Rewritten by Sven Oostenbrink
 */
function mb_utf8ize($source){
    try{
        if(is_array($source)){
            foreach ($source as $key => $value){
                $source[$key] = mb_utf8ize($value);
            }

        }elseif(is_string($source)){
            return utf8_encode($source);
        }

        return $source;

    }catch(Exception $e){
        throw new bException(tr('mb_utf8ize(): Failed'), $e);
    }
}
?>