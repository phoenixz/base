<?php
/*
 * Image library
 *
 * This contains image processing related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>, Johan Geuze
 */



if(!class_exists('Imagick')){
    throw new bException(tr('image: php module "imagick" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo apt-get -y install php5-imagick; sudo php5enmod imagick" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-imagick" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not_available');
}

load_libs('file');
load_config('images');
file_ensure_path(ROOT.'/data/log');


/*
 * Get and return text for image
 */
function image_get_text($image) {
    global $_CONFIG;

    try{
        load_libs('file');

        $tmpfile = file_tmp();

         safe_exec('tesseract '.$image.' '.$tmpfile);

         $retval = file_get_contents($tmpfile);

         file_delete($tmpfile);

         return $retval;

    }catch(Exception $e){
        if(!safe_exec('which tesseract')){
            throw new bException('image_get_text(): Failed to find the "tesseract" command, is it installed?', $e);
        }

        throw new bException('image_get_text(): Failed to get text from image "'.str_log($image).'"', $e);
    }
}



/*
 * Standard image conversion function
 */
function image_convert($source, $destination, $params = null){
    global $_CONFIG;

    try{
        load_libs('file');

        /*
         * Validations
         */
        if(file_exists($destination) and $destination != $source){
            throw new bException(tr('image_convert(): Destination file ":file" already exists', array(':file' => $destination)), 'exists');
        }

        ///*
        // * Validate format
        // */
        //if(empty($format) and !empty($destination)){
        //    $format = substr($destination, -3, 3);
        //
        //}elseif(!empty($format) and !empty($destination)){
        //    if($format != substr($destination, -3, 3)){
        //        throw new bException(tr('image_convert(): Specified format ":format1" differ from the given destination format ":format2"', array(':format1' => substr($destination, -3, 3), ':format2' => $format)));
        //    }
        //}

        $imagick = $_CONFIG['images']['imagemagick'];

        /*
         * Remove the log file so we surely have data from only this session
         *
         * Yeah, bullshit, with parrallel sessions, others sessions might
         * delete it while this is in process, etc.
         */
        file_ensure_path(ROOT.'data/log');
        file_delete(ROOT.'data/log/imagemagick-convert');


        /*
         * Ensure we have a local copy of the file to work with
         */
        $source = file_get_local($source);


        /*
         * Build command
         */
        $command = $imagick['convert'];

        if($imagick['nice']){
            $command = 'nice -n '.$imagick['nice'].' '.$command;
        }

        array_params($params);
        array_default($params, 'x'               , null);
        array_default($params, 'y'               , null);
        array_default($params, 'method'          , null);
        array_default($params, 'format'          , null);
        array_default($params, 'quality'         , $imagick['quality']);
        array_default($params, 'interlace'       , $imagick['interlace']);
        array_default($params, 'strip'           , $imagick['strip']);
        array_default($params, 'blur'            , $imagick['blur']);
        array_default($params, 'defines'         , $imagick['defines']);
        array_default($params, 'sampling_factor' , $imagick['sampling_factor']);
        array_default($params, 'keep_aspectratio', $imagick['keep_aspectratio']);
        array_default($params, 'limit_memory'    , $imagick['limit']['memory']);
        array_default($params, 'limit_map'       , $imagick['limit']['map']);
        array_default($params, 'log'             , ROOT.'data/log/imagemagic_convert.log');

        foreach($params as $key => $value){
            switch($key){
                case 'limit_memory':
                    if($value){
                       $command .= ' -limit memory '.$value;
                    }
                    break;

                case 'limit_map':
                    if($value){
                        $command .= ' -limit map '.$value;
                    }
                    break;

                case 'quality':
                    if($value){
                        $command .= ' -quality '.$value.'%';
                    }
                    break;

                case 'blur':
                    if($value){
                        $command .= ' -gaussian-blur '.$value;
                    }
                    break;

                case 'keep_aspectratio':
                    break;

                case 'sampling_factor':
                    if($value){
                        $command .= ' -sampling-factor '.$value;
                    }
                    break;

                case 'defines':
                    foreach($value as $define){
                        $command .= ' -define '.$define;
                    }

                    break;

                case 'strip':
                    //FALLTHROUGH
                case 'exif':
                    $command .= ' -strip ';
                    break;

                case 'interlace':
                    if($value){
                        $value    = image_interlace_valid(strtolower($value));
                        $command .= ' -interlace '.$value;
                    }
                    break;

                case 'updatemode':
                    if($params['updatemode'] === true){
                        $params['updatemode'] = $_CONFIG['fs']['dir_mode'];
                    }

                case 'x':
                    //do nothing (x-pos)
                    // FALLTHROUGH
                case 'y':
                    //do nothing (y-pos)
                    // FALLTHROUGH
                case 'h':
                    //do nothing (height)
                    // FALLTHROUGH
                case 'w':
                    //do nothing (width)
                    // FALLTHROUGH
                case 'custom':
                    //do nothing (custom imagemagick parameters)
                    //FALLTHROUGH
                case 'log':
                    //do nothing (custom imagemagick parameters)
                    //FALLTHROUGH
                case 'method':
                    //do nothing (function method)
                case 'format':
                    //do nothing (forced format)
                    break;
            }
        }


        /*
         * Check width / height
         *
         * If either width or height is not specified then
         */
        if(!$params['x'] or !$params['y']){
            $size = getimagesize($source);

            if($params['keep_aspectratio']){
                $ar = $size[1] / $size[0];

            }else{
                $ar = 1;
            }

            if(!$params['x']){
                $params['x'] = not_empty($params['y'], $size[1]) * (1 / $ar);
            }

            if(!$params['y']){
                $params['y'] = not_empty($params['y'], $size[0]) * $ar;
            }
        }

        /*
         * Check format and update destination file name to match
         */
        $source_path = dirname($source);
        $source_file = basename($source);

        $dest_path = dirname($destination);
        $dest_file = basename($destination);

        switch($params['format']){
            case 'gif':
                //FALLTHROUGH
            case 'png':
                /*
                 * Preserve transparent background
                 */
                $command  .= ' -background none';
                $dest_file = str_runtil($dest_file, '.').'.'.$params['format'];

                break;

            case 'jpg':
                $command  .= ' -background white';
                $dest_file = str_runtil($dest_file, '.').'.'.$params['format'];

                break;

            case '':
                /*
                 * Use current format. If source file has no extension (Hello PHP temporary upload files!)
                 * then let the dest file keep its own extension
                 */
                $extension = str_rfrom($source_file, '.');

                if(!$extension){
                    $dest_file = str_runtil($dest_file, '.').'.'.$extension;
                }

                break;

            default:
                throw new bException(tr('image_convert(): Unknown format ":format" specified.', array(':format' => $params['format'])), 'unknown');
        }


        $destination = slash($dest_path).$dest_file;

        /*
         * Execute command to convert image
         */
        switch($params['method']){
            case 'thumb':
                safe_exec($command.' -thumbnail '.$params['x'].'x'.$params['y'].'^ -gravity center -extent '.$params['x'].'x'.$params['y'].' -flatten "'.$source.'" "'.$destination.'"'.($params['log'] ? ' >> '.$params['log'].' 2>&1' : ''), 0);
                break;

            case 'resize-w':
                safe_exec($command.' -resize '.$params['x'].'x\> -flatten "'.$source.'" "'.$destination.'"'.($params['log'] ? ' >> '.$params['log'].' 2>&1' : ''), 0);
                break;

            case 'resize':
                safe_exec($command.' -resize '.$params['x'].'x'.$params['y'].'^ -flatten "'.$source.'" "'.$destination.'"'.($params['log'] ? ' >> '.$params['log'].' 2>&1' : ''), 0);
                break;

            case 'thumb-circle':
                load_libs('file');

                $tmpfname = tempnam("/tmp", "CVRT_");

                safe_exec($command.' -thumbnail '.$params['x'].'x'.$params['y'].'^ -gravity center -extent '.$params['x'].'x'.$params['y'].' -background white -flatten "'.$source.'" "'.$tmpfname.'"'.($params['log'] ? ' >> '.$params['log'].' 2>&1' : ''), 0);
                safe_exec($command.' -size '.$params['x'].'x'.$params['y'].' xc:none -fill "'.$tmpfname.'" -draw "circle '.(floor($params['x'] / 2) - 1).','.(floor($params['y'] / 2) - 1).' '.($params['x']/2).',0" "'.$destination.'"'.($params['log'] ? ' >> '.$params['log'].' 2>&1' : ''), 0);

                file_delete($tmpfname);
                break;

            case 'crop-resize':
                load_libs('file');
                safe_exec($command.' "'.$source.'" -crop '.cfi($params['w'], false).'x'.cfi($params['h'], false).'+'.cfi($params['x'], false).'+'.cfi($params['y'], false).' -resize '.cfi($params['x'], false).'x'.cfi($params['y'], false).' "'.$destination.'"'.($params['log'] ? ' >> '.$params['log'].' 2>&1' : ''), 0);
                break;

            case 'custom':
                load_libs('file');
                safe_exec($command.' "'.$source.'" '.isset_get($params['custom']).' "'.$destination.'"'.($params['log'] ? ' >> '.$params['log'].' 2>&1' : ''), 0);
                break;

            case '':
                throw new bException(tr('image_convert(): No method specified.'), 'not-specified');

            default:
                throw new bException(tr('image_convert(): Unknown method ":method" specified. Ensure method is one of thumb, resize-w, resize, thumb-circle, crop-resize, custom', array(':method' => $params['method'])), 'unknown');
        }

        /*
         * Verify results
         */
        if(!file_exists($destination)) {
            throw new bException(tr('image_convert(): Destination file "%file%" not found after conversion', array('%file%' => str_log($destination))), 'notfound');
        }

        if(!empty($params['updatemode'])){
            chmod($destination, $params['updatemode']);
        }

        return $destination;

    }catch(Exception $e){
        try{

            if(file_exists(ROOT.'data/log/imagemagic_convert.log')){
                $contents = file_get_contents(ROOT.'data/log/imagemagic_convert.log');
            }

        }catch(Exception $e){

            $contents = tr('image_convert(): Failed to get contents of imagemagick log file "%file%"', array('%file%' => ROOT.'data/log/imagemagic_convert.log'));
        }

        if(empty($contents)){
            throw new bException(tr('image_convert(): Failed'), $e);

        }else{
            foreach(array_force($contents) as $line){
                if(strstr($line, '/usr/bin/convert: not found')){
                    /*
                     * Dumbo! You don't have imagemagick installed!
                     */
                    throw new bException(tr('image_convert(): /usr/bin/convert could not be found, which means you probably do not have imagemagick installed. To resolve this, try on Ubuntu-alikes, try "sudo apt-get install imagemagick", or on RedHat-alikes, try "yum install imagemagick"'), 'notinstalled');
                }
            }

        }

        throw new bException(tr('image_convert(): Failed, with *possible* log data "%contents%"', array('%contents%' => $contents)), $e);
    }
}



/*
 *
 */
function image_interlace_valid($value, $source = false){
    if($source){
        $check = str_until($value, '-');

    }else{
        $check = str_from($value, '-');
    }

    switch($check){
        case 'jpeg':
            // FALLTHROUGH
        case 'gif':
            // FALLTHROUGH
        case 'png':
            // FALLTHROUGH
        case 'line':
            // FALLTHROUGH
        case 'partition':
            // FALLTHROUGH
        case 'plane':
            return $check;

        case 'none':
            return '';

        case 'auto':
            if(file_size($source) > 10240){
                /*
                 * Use specified interlace
                 */
                return image_interlace_valid($value);
            }

            /*
             * Don't use interlace
             */
            break;

        default:
            throw new bException(tr('image_interlace_valid(): Unknown interlace value "%value%" specified', array('%value%' => $value)), 'unknown');
    }
}



/*
 * Is this an image?
 */
function image_is_valid($filename, $minw = 0, $minh = 0) {
    try{
        if(!$img_size = getimagesize($filename)){
            throw new bException('image_is_valid(): File "'.str_log($filename).'" is not an image');
        }

        if(($img_size[0] < $minw) or ($img_size[1] < $minh)) {
            throw new bException('image_is_valid(): File "'.str_log($filename).'" has wxh "'.str_log($img_size[0].'x'.$img_size[1]).'" where a minimum wxh of "'.str_log($minw.'x'.$minh).'" is required');
        }

        return true;

    }catch(Exception $e){
        throw new bException('image_is_valid(): Failed', $e);
    }
}



/*
 * Create all required avatars for the specified image file
 */
function image_create_avatars($file){
    global $_CONFIG;

    try{
        $destination = file_assign_target(ROOT.'www/avatars/');

        foreach($_CONFIG['avatars']['types'] as $name => $type){
            if(count($type  = explode('x', $type)) != 3){
                throw new bException('image_create_avatar(): Invalid avatar type configuration for type "'.str_log($name).'"', 'invalid/config');
            }

            image_convert($file['tmp_name'][0], ROOT.'www/avatars/'.$destination.'_'.$name.'.'.file_get_extension($file['name'][0]), array('x'      => $type[0],
                                                                                                                                           'y'      => $type[1],
                                                                                                                                           'method' => $type[2]));
        }

        return $destination;

    }catch(Exception $e){
        throw new bException('image_create_avatar(): Failed to create avatars for image file "'.str_log($file).'"', $e);
    }
}



/*
 * Returns image type name or false if file is valid image or not
 */
function is_image($file){
    try{
        return (boolean) image_type($file);

    }catch(Exception $e){
        if($e->getCode() === 'not-file'){
            /*
             * Specified path is just not a file
             */
            return false;
        }

        throw new bException('is_image(): Failed', $e);
    }
}



/*
 *
 */
function image_info($file, $no_exif = false){
    global $_CONFIG;

    try{
        load_libs('file');

        $mime = file_mimetype($file);

        if(str_until($mime, '/') !== 'image'){
            throw new bException(tr('image_info(): The specified file ":file" is not an image', array(':file' => $file)), 'invalid');
        }

        $size = getimagesize($file);

        $retval['file'] = basename($file);
        $retval['size'] = filesize($file);
        $retval['path'] = slash(dirname($file));
        $retval['mime'] = $mime;
        $retval['bits'] = $size['bits'];
        $retval['x']    = $size[0];
        $retval['y']    = $size[1];

        /*
         * Get EXIF information from JPG or TIFF image files
         */
        switch(str_from($mime, '/')){
            case 'jpeg':
                try{
                    $retval['compression'] = safe_exec($_CONFIG['images']['imagemagick']['identify'].' -format "%Q" '.$file);
                    $retval['compression'] = array_shift($retval['compression']);

                }catch(Exception $e){
                    cli_log(tr('Failed to get compression information for file ":file" because ":e"', array(':e' => $e->getMessage(), ':file' => $file)), 'red');
                }

                if(!$no_exif){
                    $retval['exif'] = exif_read_data($file, null, true, true);
                }

                break;

            case 'tiff':
                if(!$no_exif){
                    $retval['exif'] = exif_read_data($file, null, true, true);
                }

                break;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('image_info(): Failed', $e);
    }
}



/*
 * Returns image type name or false if file is valid image or not
 */
function image_type($file){
    try{
        if(str_until(file_mimetype($file), '/') == 'image'){
            return str_from(file_mimetype($file), '/');
        }

        return false;

    }catch(Exception $e){
        throw new bException('image_type(): Failed', $e);
    }
}



/*
 * Sends specified image file to the client
 */
function image_send($file, $cache_maxage = 86400){
    try{
        if(!file_exists($file)){
            /*
             * Requested image does not exist
             */
            page_show(404);
        }

        /*
         * Get headers sent by the client.
         */
        $headers = apache_request_headers();

        /*
         * Check if the client is validating his cache and if it is current.
         */
        if($cache_maxage and isset($headers['If-Modified-Since']) and (strtotime($headers['If-Modified-Since']) >= filemtime($file))){
            /*
             * Client's cache IS current, so we just respond '304 Not Modified'.
             */
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 304);

        }else{
            /*
             * Image not cached or cache outdated, we respond '200 OK' and output the image.
             */
            load_libs('file');

            header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 200);

            if($cache_maxage){
                header('Pragma: public');
                header('Cache-Control: max-age='.$cache_maxage.', public');
                header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_maxage));
            }

            header('Content-Length: '.filesize($file));
            header('Content-Type: '.file_mimetype($file));
            readfile($file);
            die();
        }

    }catch(Exception $e){
        throw new bException('image_send(): Failed', $e);
    }
}



/*
 * Compares the image file type with the extension, and if it
 * does not match, will fix the extension
 */
function image_fix_extension($file){
    try{
        /*
         * Get specified extension and determine file mimetype
         */
        $mimetype  = file_mimetype($file);
        $extension = strtolower(file_get_extension($file));

        if(($extension == 'jpg') or ($extension == 'jpeg')){
            $specified = 'jpeg';

        }else{
            $specified = $extension;
        }

        /*
         * If the file is not an image then we're done
         */
        if(str_until($mimetype, '/') != 'image'){
            throw new bException('image_fix_extension(): Specified file "'.str_log($file).'" is not an image', 'invalid');
        }

        /*
         * If the extension specified type differs from the mimetype, then autorename the file to the correct extension
         */
        if($specified != str_from($mimetype, '/')){
            $new = str_from($mimetype, '/');

            if($new == 'jpeg'){
                $new = 'jpg';
            }

            $new = str_runtil($file, '.'.$extension).'.'.$new;

            rename($file, $new);
            return $new;
        }

        return $file;

    }catch(Exception $e){
        throw new bException('image_fix_extension(): Failed', $e);
    }
}



/*
 * Add fancybox image support
 *
 * Example
 *
 * <a href="pub/img/test/image.jpg" rel="example_group" class="hover_image">
 *     <span class="mask"></span>
 *    '.html_img('/pub/img/test/montage/image.jpg" >
 * </a>
 *
 * image_fancybox(array(options...);
 *
 * See http://www.fancyapps.com/fancybox/#docs for documentation on options
 */
function image_fancybox($params = null){
    try{
        load_libs('json');

        array_params($params, 'selector');
        array_default($params, 'selector', '.fancy');
        array_default($params, 'options' , array());

        array_default($params['options'], 'openEffect'   , 'fade');
        array_default($params['options'], 'closeEffect'  , 'fade');
        array_default($params['options'], 'arrows'       , true);
        array_default($params['options'], 'titleShow'    , true);
        array_default($params['options'], 'titleFromAlt' , true);
        array_default($params['options'], 'titlePosition', 'outside'); // over, outside, inside

        html_load_js('base/fancybox/jquery.fancybox');
        html_load_css('base/fancybox/jquery.fancybox');

        return html_script('$("'.$params['selector'].'").fancybox('.json_encode_custom($params['options']).');');

    }catch(Exception $e){
        throw new bException('image_fancybox(): Failed', $e);
    }
}



/*
 * Place a watermark over an image
 */
function image_watermark($params){
    try{
        array_params($params);
        array_default($params, 'image'    , '');
        array_default($params, 'watermark', '');
        array_default($params, 'target'   , '');
        array_default($params, 'opacity'  , '50%');
        array_default($params, 'margins'  , array());

        array_default($params['margins'], 'top'   , '0');
        array_default($params['margins'], 'left'  , '0');
        array_default($params['margins'], 'right' , '10');
        array_default($params['margins'], 'bottom', '10');

        /*
         * Verify image and water mark image
         */
        foreach(array('image' => $params['image'], 'watermark' => $params['watermark']) as $type => $filename){
            if(!file_exists($params['target'])){
                throw new bException(tr('image_watermark(): The specified %type% file "%file%" does not exists', array('%type%' => $type, '%file%' => str_log($filename))), 'imagenotexists');
            }

            if(!$size = getimagesize($filename)){
                throw new bException(tr('image_watermark(): The specified %type% file "%file%" is not a valid image', array('%type%' => $type, '%file%' => str_log($filename))), 'imagenotvalid');
            }
        }

        unset($size);

        /*
         * Make sure the target does not yet exist, UNLESS we're writing to the same image
         */
        if((realpath($params['target']) != realpath($params['image'])) and file_exists($params['target'])){
            throw new bException('image_watermark(): The specified target "'.str_log($params['target']).'" already exists', 'targetexists');
        }

        /*
         * Load the image and watermark into memory
         */
        $image     = imagecreatefromany($params['image']);
        $watermark = imagecreatefromany($params['watermark']);

        $sx        = imagesx($watermark);
        $sy        = imagesy($watermark);

        /*
         * Merge the stamp onto our photo with the specified opacity
         */
        imagecopymerge_alpha($image, $watermark, imagesx($image) - $sx - $params['margins']['right'], imagesy($image) - $sy - $params['margins']['bottom'], 0, 0, imagesx($watermark), imagesy($watermark), 50);

        /*
         * Save the image to file and free memory
         */
        imagepng($image, $params['target']);

        imagedestroy($image);
        imagedestroy($watermark);

    }catch(Exception $e){
        throw new bException('image_watermark(): Failed', $e);
    }
}



/*
 * One function to open any type of image in GD
 *
 * FUCK YOU PHP for making me having to use the @ operator here,
 * but apparently, GD just throws text into the output buffer without
 * actually generating an error..
 *
 * Google "Parse error</b>:  imagecreatefromjpeg(): gd-jpeg, libjpeg: recoverable error:" for more information
 */
function imagecreatefromany($filename){
    try{
        switch(exif_imagetype($filename)){
            case IMAGETYPE_GIF:
                $resource = @imagecreatefromgif($filename);
                break;

            case IMAGETYPE_JPEG:
                $resource = @imagecreatefromjpeg($filename);
                break;

            case IMAGETYPE_PNG:
                $resource = @imagecreatefrompng($filename);
                break;

            case IMAGETYPE_WBMP:
                $resource = @imagecreatefrombmp($filename);
                break;

            case IMAGETYPE_SWF:
                // FALLTHROUGH
            case IMAGETYPE_PSD:
                // FALLTHROUGH
            case IMAGETYPE_BMP:
                // FALLTHROUGH
            case IMAGETYPE_TIFF_II: // (intel byte order)
                // FALLTHROUGH
            case IMAGETYPE_TIFF_MM: // (motorola byte order)
                // FALLTHROUGH
            case IMAGETYPE_JPC:
                // FALLTHROUGH
            case IMAGETYPE_JP2:
                // FALLTHROUGH
            case IMAGETYPE_JPX:
                // FALLTHROUGH
            case IMAGETYPE_JB2:
                // FALLTHROUGH
            case IMAGETYPE_SWC:
                // FALLTHROUGH
            case IMAGETYPE_IFF:
                // FALLTHROUGH
            case IMAGETYPE_XBM:
                // FALLTHROUGH
            case IMAGETYPE_ICO:
                throw new bException('imagecreatefromany(): Image types "'.exif_imagetype($filename).'" of file "'.str_log($filename).'" is not supported', 'notsupported');

            default:
                throw new bException('imagecreatefromany(): The file "'.exif_imagetype($filename).'" is not an image', 'notsupported');
        }

        if(!$resource){
            throw new bException('imagecreatefromany(): Failed to open image type "'.exif_imagetype($filename).'" file "'.$filename.'"', 'failed');
        }

        return $resource;

    }catch(Exception $e){
        if(!file_exists($filename)){
            throw new bException('imagecreatefromany(): Specified file "'.str_log($filename).'" does not exist', $e);
        }

        throw new bException('imagecreatefromany(): Failed', $e);
    }
}



/*
 * Taken from http://www.php.net/manual/en/function.imagecopymerge.php, thanks to user Sina Salek

 * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
 * by Sina Salek
 *
 * Bugfix by Ralph Voigt (bug which causes it
 * to work only for $src_x = $src_y = 0.
 * Also, inverting opacity is not necessary.)
 * 08-JAN-2011
 *
 **/
function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
    try{
        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);

        // copying relevant section from background to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

        // insert cut resource to destination image
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);

    }catch(Exception $e){
        throw new bException('imagecopymerge_alpha(): Failed for source image "'.str_log($src_im).'"', $e);
    }
}



/*
 * Create an HTML / JQuery image picker that sets the selected images as form values
 */
function image_picker($params){
    try{
        html_load_js('image-picker/image-picker');
        html_load_css('image-picker');

        array_params($params);
        array_default($params, 'resource'  , null);
        array_default($params, 'name'      , 'image-picker');
        array_default($params, 'id'        , 'image-picker');
        array_default($params, 'path'      , null);
        array_default($params, 'class'     , 'image-picker show-html');
        array_default($params, 'masonry'   , true);
        array_default($params, 'loaded'    , true);
        array_default($params, 'none'      , false);
        array_default($params, 'show_label', false);

        if($params['masonry']){
            html_load_js('masonry.pkgd');
            $params['class'] .= ' masonry';
        }

        /*
         * If resource is a string, then assume its a path to an image directory
         */
        if(is_string($params['resource'])){
            $params['resource'] = scandir($params['resource']);
            $params['resource'] = array_merge_keys_values($params['resource'], $params['resource']);
        }

        /*
         * Convert image file names into URL's
         * Remove ., .., and hidden files
         */
        if(!empty($params['url'])){
            foreach($params['resource'] as $key => &$image){
                if(!$image) continue;

                if($image[0] == '.'){
                    unset($params['resource'][$key]);
                }

                $image = str_replace(':image', $image, $params['url']);
            }
        }

        unset($image);

        /*
         * Add required data info for html_select();
         */
        if(empty($params['data_resources'])){
            $params['data_resources'] = array();
        }

        $params['data_resources']['img-src'] = $params['resource'];

        $retval = html_select($params).
                  html_script('$("#'.$params['id'].'").imagepicker(
                    { show_label : '.str_boolean($params['show_label']).'}
                  );');

        if($params['masonry']){
            if($params['loaded']){
                html_load_js('imagesloaded');
                $retval .= html_script('
                    var $grid = $("#'.$params['id'].'").masonry({
                        itemSelector: "li",
                        columnWidth: 200
                    });

console.log($grid);
                    // layout Masonry after each image loads
                    $grid.imagesLoaded().progress( function() {
console.log("imagesloaded");
                        $grid.masonry("layout");
                    });');

            }else{
                $retval .= html_script('
                    $("'.$params['masonry'].'").masonry({
                        // options
                        itemSelector: "li",
                        columnWidth: 200
                    });');
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('image_picker(): Failed', $e);
    }
}



/*
 * Returns HTML and loads JS and CSS for sliders.
 *
 * Supported sliders are:
 * A-Slider : http://varunnaik.github.io/A-Slider/
 *            https://github.com/varunnaik/A-Slider
 *
 * Jssor    : http://www.jssor.com/support.html
 *
 */
function image_slider($params = null){
    try{
        array_params($params);
        array_default($params, 'library' , 'bxslider');
        array_default($params, 'selector', '#slider');
        array_default($params, 'options'  , array());

        switch($params['library']){
            case 'aslider':
                ensure_installed(array('checks'    => 'aslider',
                                       'checks'    => '',
                                       'locations' => array('js'  => ROOT.'pub/js/aslider',
                                                            'css' => ROOT.'pub/css/aslider'),
                                       'install'   => 'http://varunnaik.github.io/A-Slider/a-slider.zip'));
// :TODO: Implement
                break;

            case 'bxslider':
                /*
                 * http://bxslider.com/
                 * https://github.com/stevenwanderski/bxslider-4
                 * GIT REPO: https://github.com/stevenwanderski/bxslider-4.git
                 */
                ensure_installed(array('name'      => 'bxslider',
                                       'checks'    => ROOT.'pub/js/bxslider',
                                       'locations' => array('src/js'     => ROOT.'pub/js/bxslider',
                                                            'src/css'    => ROOT.'pub/css/bxslider',
                                                            'src/vendor' => ROOT.'pub/js'),
                                       'url'       => 'https://github.com/stevenwanderski/bxslider-4.git'));

                html_load_js('jquery,bxslider/bxslider');
                html_load_css('bxslider/bxslider');

                $html = html_script('$(document).ready(function(){
                    $("'.$params['selector'].'").bxSlider({'.array_implode_with_keys($params['options'], ',', ':').'});
                });');

                return $html;

            default:
                throw new bException(tr('image_picker(): Unknown library ":library" specified', array(':library' => $params['library'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('image_slider(): Failed', $e);
    }
}
?>
