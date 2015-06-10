<?php
/*
 * Image library
 *
 * This contains image processing related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>, Johan Geuze
 */



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
function image_convert($source, $destination, $x, $y, $type, $params = array()) {
    global $_CONFIG;

    try{
        /*
         * Validations
         */
        if(file_exists($destination)){
            throw new bException('image_convert(): Destination file "'.str_log($destination).'" already exists');
        }

        /*
         * Remove the log file so we surely have data from only this session
         *
         * Yeah, bullshit, with parrallel sessions, others sessions might
         * delete it while this is in process, etc.
         */
        unlink(TMP.'imagemagic_convert.log');

        /*
         * Ensure we have a local copy of the file to work with
         */
        $source = file_get_local($source);

        /*
         * Process params
         */
        $quality     = 75;
        $memorylimit = 16;
        $maplimit    = 16;

        foreach($params as $key => $value){
            switch($key){
                case 'memorylimit':
                    $memorylimit = $value;
                    break;

                case 'maplimit':
                    $maplimit = $value;
                    break;

                case 'quality':
                    $quality = $value;
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
                    break;

                default:
                    throw new bException('image_convert(): Unknown parameter key "'.str_log($key).'" specified', 'unknown');
            }
        }

        /*
         * Build command
         */
        $command = $_CONFIG['imagemagic_convert'];

        if($quality){
            $command .= ' -quality '.$quality;
        }

        if($memorylimit){
            $command .= ' -limit memory '.$memorylimit;
        }

        if($maplimit){
            $command .= ' -limit map '.$maplimit;
        }

        /*
         * Execute command to convert image
         */
        switch ($type) {
            case 'thumb':
                safe_exec($command.' -thumbnail '.$x.'x'.$y.'^ -gravity center -extent '.$x.'x'.$y.' -background white -flatten "'.$source.'" "'.$destination.'" >> '.TMP.'imagemagic_convert.log 2>&1',0);
                break;

            case 'resize-w':
                safe_exec($command.' -resize '.$x.'x\> -background white -flatten "'.$source.'" "'.$destination.'" >>'.TMP.'imagemagic_convert.log 2>&1',0);
                break;

            case 'resize':
                safe_exec($command.' -resize '.$x.'x'.$y.'^ -background white -flatten "'.$source.'" "'.$destination.'" >>'.TMP.'imagemagic_convert.log 2>&1',0);
                break;

            case 'thumb-circle':
                load_libs('file');

                $tmpfname = tempnam("/tmp", "CVRT_");

                safe_exec($_CONFIG['imagemagic_convert'].' -limit memory 16 -limit map 16 -quality 100 -thumbnail '.$x.'x'.$y.'^ -gravity center -extent '.$x.'x'.$y.' -background white -flatten "'.$source.'" "'.$tmpfname.'"                >> '.TMP.'imagemagic_convert.log 2>&1', 0);
                safe_exec($_CONFIG['imagemagic_convert'].' -limit memory 16 -limit map 16 -quality 75 -size '.$x.'x'.$y.' xc:none -fill "'.$tmpfname.'" -draw "circle '.(floor($x/2)-1).','.(floor($y/2)-1).' '.($x/2).',0" "'.$destination.'" >> '.TMP.'imagemagic_convert.log 2>&1', 0);

                file_delete($tmpfname);
                break;

            case 'crop-resize':
                load_libs('file');
                safe_exec($_CONFIG['imagemagic_convert'].' -limit memory 16 -limit map 16 -quality 75 "'.$source.'" -crop '.cfi($params['w']).'x'.cfi($params['h']).'+'.cfi($params['x']).'+'.cfi($params['y']).' -resize '.cfi($x).'x'.cfi($y).' "'.$destination.'" >> '.TMP.'imagemagic_convert.log 2>&1', 0);
                break;

            case 'custom':
                load_libs('file');
                safe_exec($_CONFIG['imagemagic_convert'].' -limit memory 16 -limit map 16 -quality 75 "'.$source.'" '.isset_get($params['custom']).' "'.$destination.'" >> '.TMP.'imagemagic_convert.log 2>&1', 0);
                break;

            default:
                throw new bException('image_convert(): Unknown type "'.str_log($type).'" specified', 'unknown');
        }

        /*
         * Verify results
         */
        if(!file_exists($destination)) {
            throw new bException('image_convert(): Destination file "'.str_log($destination).'" not found after conversion', 'notfound');
        }

        if(!empty($params['updatemode'])){
            chmod($destination, $params['updatemode']);
        }

        return $destination;

    }catch(Exception $e){
        try{
            if(file_exists(TMP.'imagemagic_convert.log')){
                $contents = file_get_contents(TMP.'imagemagic_convert.log');
            }

        }catch(Exception $e){
            $contents = tr('image_convert(): Failed to get contents of imagemagick log file "%file%"', array('%file%' => TMP.'imagemagic_convert.log'));
        }

        if(empty($contents)){
            throw new bException(tr('image_convert(): Failed'), $e);
        }

        throw new bException(tr('image_convert(): Failed, with *possible* log data "%contents%"', array('%contents%' => $contents)), $e);
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

            image_convert($file, ROOT.'www/avatars/'.$destination.'_'.$name.'.'.file_get_extension($file), $type[0], $type[1], $type[2]);
        }

        return $destination;

    }catch(Exception $e){
        throw new bException('image_create_avatar(): Failed to create avatars for image file "'.str_log($file).'"', $e);
    }
}



/*
 * Returns image type name or false if file is valid image or not
 */
function image_type($filename){
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
            page_404();
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
        array_params($params, 'selector');
        array_default($params, 'selector'   , '.fancy');
        array_default($params, 'openEffect' , 'fade');
        array_default($params, 'closeEffect', 'fade');
        array_default($params, 'arrows'     , 'true');

        html_load_js('base/fancybox/jquery.fancybox');
        html_load_css('base/fancybox/jquery.fancybox');

        $selector = $params['selector'];
        $options  = array();

        unset($params['selector']);

        foreach($params as $key => $value){
            $options[] = $key .' : "'.$value.'"';
        }

        return html_script('$("'.$selector.'").fancybox({'.implode(',', $options).'});');

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
?>
