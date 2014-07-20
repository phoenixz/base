<?php
/*
 * PDF library
 *
 * This library contains various PDF functions
 *
 * pdf_string() and pdf_extract_text() taken from http://php.net/manual/en/ref.pdf.php,
 * written by by donatas at spurgius dot com, updated for UTF8 compatibility by Sven Oostenbrink
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Convert the specified PDF file to string text
 */
function pdf_string($file){
    try{
        $textArray = array ();
        $objStart  = 0;

        /*
         * Get file contents
         */
        $content = file_get_contents($file);

        $search_tag_start       = chr(13).chr(10).'stream';
        $search_tag_start_lenght = strlen ($search_tag_start);

        while (($objStart = strpos($content, $search_tag_start, $objStart)) and ($objEnd = strpos ($content, 'endstream', $objStart + 1))){
            $data = substr($content, $objStart + $search_tag_start_lenght + 2, $objEnd - ($objStart + $search_tag_start_lenght) - 2);
            $data = gzuncompress($data);

            if (($data !== false) and (strpos($data, 'BT') !== false) and (strpos($data, 'ET') !== false)){
                $textArray [] = pdf_extract_text($data);
            }

            $objStart = $objStart < $objEnd ? $objEnd : $objStart + 1;
        }

        return $textArray;

    }catch(Exception $e){
        throw new lsException('pdf2string(): Failed', $e);
    }
}



/*
 * Extract plain string text from the specified postscript data
 */
function pdf_extract_text($postscript_data){
    try{
        while ((($textStart = strpos($postscript_data, '(', $textStart)) && ($textEnd = strpos($postscript_data, ')', $textStart + 1)) && substr($postscript_data, $textEnd - 1) != '\\')){
            $plainText .= substr($postscript_data, $textStart + 1, $textEnd - $textStart - 1);

            if (substr($postscript_data, $textEnd + 1, 1) == ']'){ //this adds quite some additional spaces between the words
                $plainText .= ' ';
            }

            $textStart = $textStart < $textEnd ? $textEnd : $textStart + 1;
        }

        return stripslashes($plainText);

    }catch(Exception $e){
        throw new lsException('pdf_extract_text(): Failed', $e);
    }
}
?>
