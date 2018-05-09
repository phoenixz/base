<?php
/*
 * EXIF library
 *
 * This library contains functions to manage JPEG EXIF data
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Remove exif information from the specified image file
 * Taken from https://stackoverflow.com/questions/3614925/remove-exif-data-from-jpg-using-php
 * Rewritten by Sven Oostenbrink for use in Base
 */
function exif_clear($file, $target = null){
    try{
        $buffer = 4096;
        $hi     = fopen($file, 'rb');
        $ho     = fopen($file, 'wb');

        while(($buffer = fread($hi, $buffer)) !== false){
            //  \xFF\xE1\xHH\xLLExif\x00\x00 - Exif
            //  \xFF\xE1\xHH\xLLhttp://      - XMP
            //  \xFF\xE2\xHH\xLLICC_PROFILE  - ICC
            //  \xFF\xED\xHH\xLLPhotoshop    - PH
            while (preg_match('/\xFF[\xE1\xE2\xED\xEE](.)(.)(exif|photoshop|http:|icc_profile|adobe)/si', $buffer, $match, PREG_OFFSET_CAPTURE)){
//                echo "found: '{$match[3][0]}' marker\n";
                $len = ord($match[1][0]) * 256 + ord($match[2][0]);
//                echo "length: {$len} bytes\n";
//                echo "write: {$match[0][1]} bytes to output file\n";
                fwrite($ho, substr($buffer, 0, $match[0][1]));
                $filepos = $match[0][1] + 2 + $len - strlen($buffer);
                fseek($hi, $filepos, SEEK_CUR);
//                echo "seek to: ".ftell($hi)."\n";
                $buffer = fread($hi, $buffer_len);
            }

            fwrite($ho, $buffer, strlen($buffer));
        }

        fclose($ho);
        fclose($hi);

    }catch(Exception $e){
        throw new bException('exif_clear(): Failed', $e);
    }
}
















define("IPTC_OBJECT_NAME", "005");
define("IPTC_EDIT_STATUS", "007");
define("IPTC_PRIORITY", "010");
define("IPTC_CATEGORY", "015");
define("IPTC_SUPPLEMENTAL_CATEGORY", "020");
define("IPTC_FIXTURE_IDENTIFIER", "022");
define("IPTC_KEYWORDS", "025");
define("IPTC_RELEASE_DATE", "030");
define("IPTC_RELEASE_TIME", "035");
define("IPTC_SPECIAL_INSTRUCTIONS", "040");
define("IPTC_REFERENCE_SERVICE", "045");
define("IPTC_REFERENCE_DATE", "047");
define("IPTC_REFERENCE_NUMBER", "050");
define("IPTC_CREATED_DATE", "055");
define("IPTC_CREATED_TIME", "060");
define("IPTC_ORIGINATING_PROGRAM", "065");
define("IPTC_PROGRAM_VERSION", "070");
define("IPTC_OBJECT_CYCLE", "075");
define("IPTC_BYLINE", "080");
define("IPTC_BYLINE_TITLE", "085");
define("IPTC_CITY", "090");
define("IPTC_PROVINCE_STATE", "095");
define("IPTC_COUNTRY_CODE", "100");
define("IPTC_COUNTRY", "101");
define("IPTC_ORIGINAL_TRANSMISSION_REFERENCE", "103");
define("IPTC_HEADLINE", "105");
define("IPTC_CREDIT", "110");
define("IPTC_SOURCE", "115");
define("IPTC_COPYRIGHT_STRING", "116");
define("IPTC_CAPTION", "120");
define("IPTC_LOCAL_CAPTION", "121");

class IPTC
{
    var $meta = [];
    var $file = null;

    function __construct($filename)
    {
        $info = null;

        $size = getimagesize($filename, $info);

        if(isset($info["APP13"])) $this->meta = iptcparse($info["APP13"]);

        $this->file = $filename;
    }

    function getValue($tag)
    {
        return isset($this->meta["2#$tag"]) ? $this->meta["2#$tag"][0] : "";
    }

    function setValue($tag, $data)
    {
        $this->meta["2#$tag"] = [$data];

        $this->write();
    }

    private function write()
    {
        $mode = 0;

        $content = iptcembed($this->binary(), $this->file, $mode);

        $filename = $this->file;

        if(file_exists($this->file)) unlink($this->file);

        $fp = fopen($this->file, "w");
        fwrite($fp, $content);
        fclose($fp);
    }

    private function binary()
    {
        $data = "";

        foreach(array_keys($this->meta) as $key)
        {
            $tag = str_replace("2#", "", $key);
            $data .= $this->iptc_maketag(2, $tag, $this->meta[$key][0]);
        }

        return $data;
    }

    function iptc_maketag($rec, $data, $value)
    {
        $length = strlen($value);
        $retval = chr(0x1C) . chr($rec) . chr($data);

        if($length < 0x8000)
        {
            $retval .= chr($length >> 8) .  chr($length & 0xFF);
        }
        else
        {
            $retval .= chr(0x80) .
                       chr(0x04) .
                       chr(($length >> 24) & 0xFF) .
                       chr(($length >> 16) & 0xFF) .
                       chr(($length >> 8) & 0xFF) .
                       chr($length & 0xFF);
        }

        return $retval . $value;
    }

    function dump()
    {
        echo "<pre>";
        print_r($this->meta);
        echo "</pre>";
    }

    #requires GD library installed
    function removeAllTags()
    {
        $this->meta = [];
        $img = imagecreatefromstring(implode(file($this->file)));
        if(file_exists($this->file)) unlink($this->file);
        imagejpeg($img, $this->file, 100);
    }
}

$file = "photo.jpg";
$objIPTC = new IPTC($file);

//set title
$objIPTC->setValue(IPTC_HEADLINE, "A title for this picture");

//set description
$objIPTC->setValue(IPTC_CAPTION, "Some words describing what can be seen in this picture.");

echo $objIPTC->getValue(IPTC_HEADLINE);
?>
