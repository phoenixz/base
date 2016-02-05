<?php
/*
 * Uglify library
 *
 * This library contains functions to manage the uglifycss and uglify-js Node.JS programs
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



load_libs('node');



/*
 * Ensure that npm is available
 */
function uglify_check(){
    global $npm, $node, $node_modules;

    try{
        $node = node_check();
        $npm  = node_check_npm();

    }catch(Exception $e){
        throw new bException('uglify_check(): Failed', $e);
    }
}



/*
 * Install uglifycss
 */
function uglify_css_install(){
    global $npm;

    try{
        if(VERBOSE){
            log_console('uglify_css_install(): Installing uglifycss', 'uglify', 'white');
        }

        passthru($npm.' install uglifycss');

        if(VERBOSE){
            log_console('uglify_css_install(): Finished installing uglifycss', 'uglify', 'green');
        }

    }catch(Exception $e){
        throw new bException('uglify_css_install(): Failed', $e);
    }
}



/*
 * Check availability of uglifycss installation, and install if needed
 */
function uglify_css_check(){
    global $npm, $node_modules;

    try{
        uglify_check();

        if(VERBOSE){
            log_console('uglify_css_check(): Checking uglifycss availability', 'uglify', 'white');
        }

        $result = safe_exec($npm.' list uglifycss', 1);

        if(empty($result[1])){
            throw new bException('uglify_js_check(): npm list uglifycss returned invalid results', 'invalid_result');
        }

        if(substr($result[1], -7, 7) == '(empty)'){
            /*
             * uglifycss is not available, install it now.
             */
            if(VERBOSE){
                log_console('uglify_css_check(): No uglifycss found, trying to install now', 'uglify', 'yellow');
            }
            uglify_css_install($npm);
        }

        $result[1] = 'uglify'.str_from($result[1], 'uglify');

        $node_modules = node_check_modules();

        if(VERBOSE){
            log_console('uglify_css_check(): Using uglifycss "'.str_log($result[1]).'"', 'uglify', 'green');
        }

    }catch(Exception $e){
        throw new bException('uglify_css_check(): Failed', $e);
    }
}



/*
 * Uglify all CSS files in www/en/pub/css
 */
function uglify_css($path = null){
    global $npm, $node, $node_modules;
    static $check;

    try{
        if(empty($check)){
            $check = true;
            uglify_css_check($npm);
            if(VERBOSE){
                log_console('uglify_css(): Compressing all CSS files using uglifycss', 'uglify');
            }
        }

        if(empty($path)){
            /*
             * Start at the base css path
             */
            $path = ROOT.'pub/css/';
        }

        if(is_dir($path)){
            $path = slash($path);
            if(VERBOSE){
                log_console('uglify_css(): Compressing all CSS files in directory "'.str_log($path).'"', 'uglify');
            }
            load_libs('file');
            file_check_dir($path);

        }elseif(is_file($path)){
            if(VERBOSE){
                log_console('uglify_css(): Compressing CSS file "'.str_log($path).'"', 'uglify');
            }

        }else{
            throw new bException('uglify_css(): Specified file "'.str_log($path).'" is neither a file or a directory', 'unknow_file_type');
        }

         /*
         * Replace all symlinks with copies of the target file. This way, later
         * on we dont have to worry about if source or target is min file or
         * not, etc.
         */
        foreach(file_list_tree($path) as $file){
            if(is_link($file)){
                if(substr($file, -7, 7) == '.min.js'){
                    /*
                     * If is minified then we have to copy
                     * from no-minified to minified
                     */
                    copy(substr($file, 0, -7).'.js', $file);

                }elseif(substr($file, -3, 3) == '.js'){
                    /*
                     * If is no-minified then we have to copy
                     * from minified to no-minified
                     */
                    copy(substr($file, 0, -3).'.min.js', $file);
                }
            }
        }

        foreach(file_list_tree($path) as $file){
            /*
             * Update path for each file since the file may be in a sub directory
             */
            $path = slash(dirname($file));

            if(is_dir($file)){
                /*
                 * Recurse into sub directories
                 */
                uglify_css($file);

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            //if(is_link($file)){
            //    /*
            //     * The file is a symlink
            //     */
            //    $target = readlink($file);
            //
            //    if(substr($file, -8, 8) == '.min.css'){
            //        /*
            //         * Delete the minimized symlinks, we'll regenerate them for the normal files
            //         */
            //        file_delete($file);
            //
            //        $processed[str_rfrom($file, '/')] = true;
            //        continue;
            //
            //    }elseif(substr($file, -4, 4) == '.css'){
            //        /*
            //         * If the symlink target does not exist, we can just ignore it
            //         */
            //        if(!file_exists($path.$target)){
            //            if(VERBOSE){
            //                log_console('uglify_css(): Ignorning symlink "'.str_log($file).'" with non existing target "'.str_log($path.$target).'"', 'uglify', 'yellow');
            //            }
            //
            //            $processed[str_rfrom($file, '/')] = true;
            //            continue;
            //        }
            //
            //        /*
            //         * If the symlink points to any path above or outside the current path, then only ensure there is a .min symlink for it
            //         */
            //        if(!strstr($path.$target, str_runtil($file, '/'))){
            //            if(VERBOSE){
            //                log_console('uglify_css(): Found symlink "'.str_log($file).'" with target "'.str_log($target).'" that points to location outside symlink path, ensuring minimized version pointing to the same file', 'uglify', 'yellow');
            //            }
            //
            //            if(file_exists(substr($file, 0, -4).'.min.css')){
            //                file_delete(substr($file, 0, -4).'.min.css');
            //            }
            //
            //            symlink($target, substr($file, 0, -4).'.min.css');
            //
            //            $processed[str_rfrom($file, '/')] = true;
            //            continue;
            //        }
            //
            //        if(substr(basename($file), 0, -4) == substr($target, 0, -8)){
            //            /*
            //             * This non minimized version points towards a minimized version of the same file. Move the minimized version to the normal version,
            //             * and make a minimized version
            //             */
            //            if(VERBOSE){
            //                log_console('uglify_css(): Found symlink "'.str_log($file).'" pointing to its minimized version. Switching files', 'uglify', 'yellow');
            //            }
            //
            //            file_delete($file);
            //            rename($path.$target, $file);
            //            copy($file, $path.$target);
            //
            //            $processed[str_rfrom($file, '/')] = true;
            //            continue;
            //        }
            //
            //        /*
            //         * Create a symlink for the minimized file to the minimized version
            //         */
            //        if(substr($target, -8, 8) != '.min.css'){
            //            /*
            //             * Correct the targets file extension
            //             */
            //            $target = substr($target, 0, -4).'.min.css';
            //        }
            //
            //        if(VERBOSE){
            //            log_console('uglify_css(): Created minimized symlink for file "'.str_log($file).'"', 'uglify');
            //        }
            //        file_delete(substr($file, 0, -4).'.min.css');
            //        symlink($target, substr($file, 0, -4).'.min.css');
            //
            //        $processed[str_rfrom($file, '/')] = true;
            //        continue;
            //
            //    }else{
            //        if(VERBOSE){
            //            log_console('uglify_css(): Ignorning non css symlink "'.str_log($file).'"', 'uglify', 'yellow');
            //        }
            //
            //        $processed[str_rfrom($file, '/')] = true;
            //        continue;
            //    }
            //}

            if(!is_file($file)){
                if(VERBOSE){
                    log_console('uglify_css(): Ignorning unknown type file "'.str_log($file).'"', 'uglify', 'yellow');
                }

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            if(substr($file, -8, 8) == '.min.css'){
                /*
                 * This file is already minified. IF there is a source .css file, then remove it (it will be minified again later)
                 * If no source .css is availalbe, then make this the source now, and it will be minified later.
                 *
                 * Reason for this is that sometimes we only have minified versions available.
                 */
                if(file_exists(substr($file, 0, -8).'.css') and !is_link(substr($file, 0, -8).'.css')){
                    if(VERBOSE){
                        log_console('uglify_css(): Ignoring minified file "'.str_log($file).'" as a source is available', 'uglify');
                    }
//                    file_delete($file);

                }else{
                    if(VERBOSE){
                        log_console('uglify_css(): Using minified file "'.str_log($file).'" as source is available', 'uglify');
                    }
                    rename($file, substr($file, 0, -8).'.css');
                }

                $file = substr($file, 0, -8).'.css';
            }

            if(substr($file, -4, 4) != '.css'){
                if(substr($file, -3, 3) == '.js'){
                    /*
                     * Found a js file in the CSS path
                     */
                    if(VERBOSE){
                        log_console('uglify_css(): Found js file "'.str_log($file).'" in CSS path, switching to uglifyjs', 'uglify', 'yellow');
                    }
                    uglify_js($file);

                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                if(VERBOSE){
                    log_console('uglify_css(): Ignorning non CSS file "'.str_log($file).'"', 'uglify', 'yellow');
                }

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            try{
                /*
                 * If file exists and FORCE option wasn't given then proceed
                 */
                $minfile = str_runtil($file, '.').'.min.css';

                if(file_exists($minfile)){
                    /*
                     * Compare filemtimes, if they match then we will assume that
                     * the file has not changed, so we can skip compressing
                     */
                    if((filemtime($minfile) == filemtime($file)) and !FORCE){
                        /*
                         * Do not compress, just continue with next file
                         */
                        if(VERBOSE){
                            log_console('uglify_css(): NOT Compressing CSS file "'.str_log($file).'", file has not changed', 'uglify', 'yellow');
                        }

                        continue;
                    }
                }

                /*
                 * Compress file
                 */
                if(VERBOSE){
                    log_console('uglify_css(): Compressing CSS file "'.str_log($file).'"', 'uglify');
                }

                file_delete(substr($file, 0, -4).'.min.css');

                try{
                    safe_exec($node.' '.$node_modules.'uglifycss/uglifycss '.$file.' >  '.substr($file, 0, -4).'.min.css');

                }catch(Exception $e){
                    /*
                     * If uglify fails then make a copy of min file
                     */
                    copy($file, substr($file, 0, -4).'.min.css');
                }

                $processed[str_rfrom($file, '/')] = true;

                /*
                 * Make mtime equal
                 */
                $time = time();
                touch(str_runtil($file, '.').'.css'    , $time, $time);
                touch(str_runtil($file, '.').'.min.css', $time, $time);

            }catch(Exception $e){
                log_error('Failed to compress CSS file "'.str_log($file).'"', 'error/uglify');
            }
        }

    }catch(Exception $e){
        throw new bException('uglify_css(): Failed', $e);
    }
}



/*
 * Install uglify-js
 */
function uglify_js_install(){
    global $npm, $node_modules;

    try{
        if(VERBOSE){
            log_console('uglify_js_install(): Installing uglify-js', 'uglify', 'white');
        }
        passthru($npm.' install uglify-js');
        if(VERBOSE){
            log_console('uglify_js_install(): Finished installing uglify-js', 'uglify', 'green');
        }

    }catch(Exception $e){
        throw new bException('uglify_js_install(): Failed', $e);
    }
}



/*
 * Check availability of uglify-js installation, and install if needed
 */
function uglify_js_check(){
    global $npm, $node_modules;

    try{
        uglify_check();

        if(VERBOSE){
            log_console('uglify_js_check(): Checking uglify-js availability', 'uglify', 'white');
        }

        $result = safe_exec($npm.' list uglify-js', 1);

        if(empty($result[1])){
            throw new bException('uglify_js_check(): npm list uglify-js returned invalid results', 'invalid_result');
        }

        if(substr($result[1], -7, 7) == '(empty)'){
            /*
             * uglify-js is not available, install it now.
             */
            if(VERBOSE){
                log_console('uglify_js_check(): No uglify-js found, trying to install now', 'uglify', 'yellow');
            }
            uglify_js_install($npm);
        }

        $result[1] = 'uglify'.str_from($result[1], 'uglify');

        $node_modules = node_check_modules();

        if(VERBOSE){
            log_console('uglify_js_check(): Using uglify-js "'.str_log($result[1]).'"', 'uglify', 'green');
        }

    }catch(Exception $e){
        throw new bException('uglify_js_check(): Failed', $e);
    }
}



/*
 * Uglify all js files in www/en/pub/js
 */
function uglify_js($path = null){
    global $npm, $node, $node_modules;
    static $check;

    try{
        if(empty($check)){
            $check = true;
            uglify_js_check($npm);
            if(VERBOSE){
                log_console('uglify_js(): Compressing all javascript files using uglifyjs', 'uglify');
            }
        }

        if(empty($path)){
            /*
             * Start at the base js path
             */
            $path = ROOT.'pub/js/';
        }

        if(is_dir($path)){
            $path = slash($path);
            if(VERBOSE){
                log_console('uglify_js(): Compressing all javascript files in directory "'.str_log($path).'"', 'uglify');
            }
            load_libs('file');
            file_check_dir($path);

        }elseif(is_file($path)){
            if(VERBOSE){
                log_console('uglify_js(): Compressing javascript file "'.str_log($path).'"', 'uglify');
            }

        }else{
            throw new bException('uglify_js(): Specified file "'.str_log($path).'" is neither a file or a directory', 'unknow_file_type');
        }

        /*
         * Replace all symlinks with copies of the target file. This way, later
         * on we dont have to worry about if source or target is min file or
         * not, etc.
         */
        foreach(file_list_tree($path) as $file){
            if(is_link($file)){
                if(substr($file, -7, 7) == '.min.js'){
                    /*
                     * If is minified then we have to copy
                     * from no-minified to minified
                     */
                    copy(substr($file, 0, -7).'.js', $file);

                }elseif(substr($file, -3, 3) == '.js'){
                    /*
                     * If is no-minified then we have to copy
                     * from minified to no-minified
                     */
                    copy(substr($file, 0, -3).'.min.js', $file);
                }
            }
        }


        foreach(file_list_tree($path) as $file){
            /*
             * Update path for each file since the file may be in a sub directory
             */
            $path = slash(dirname($file));

            if(is_dir($file)){
                /*
                 * Recurse into sub directories
                 */
                uglify_js($file);

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

//            if(is_link($file)){
//                /*
//                 * The file is a symlink
//                 */
//                $target = readlink($file);
//
//
//                if(substr($file, -7, 7) == '.min.js'){
//                    /*
//                     * Delete the minimized symlinks, we'll regenerate them for the normal files
//                     */
//                    file_delete($file);
//                    $processed[str_rfrom($file, '/')] = true;
//                    continue;
//
//                }elseif(substr($file, -3, 3) == '.js'){
//                    /*
//                     * If the symlink target does not exist, we can just ignore it
//                     */
//                    if(!file_exists($path.$target)){
//                        if(VERBOSE){
//                            log_console('uglify_js(): Ignorning symlink "'.str_log($file).'" with non existing target "'.str_log($path.$target).'"', 'uglify', 'yellow');
//                        }
//
//                        $processed[str_rfrom($file, '/')] = true;
//                        continue;
//                    }
//
//                    /*
//                     * If the symlink points to any path above or outside the current path, then only ensure there is a .min symlink for it
//                     */
//                    if(!strstr($path.$target, str_runtil($file, '/'))){
//                        if(VERBOSE){
//                            log_console('uglify_js(): Found symlink "'.str_log($file).'" with target "'.str_log($target).'" that points to location outside symlink path, ensuring minimized version pointing to the same file', 'uglify', 'yellow');
//                        }
//
//                        if(file_exists(substr($file, 0, -3).'.min.js')){
//                            file_delete(substr($file, 0, -3).'.min.js');
//                        }
//
//                        symlink($target, substr($file, 0, -3).'.min.js');
//
//                        $processed[str_rfrom($file, '/')] = true;
//                        continue;
//                    }
//
//                    if(substr(basename($file), 0, -3) == substr($target, 0, -7)){
//                        /*
//                         * This non minimized version points towards a minimized version of the same file. Move the minimized version to the normal version,
//                         * and make a minimized version
//                         */
//                        if(VERBOSE){
//                            log_console('uglify_js(): Found symlink "'.str_log($file).'" pointing to its minimized version. Switching files', 'uglify', 'yellow');
//                        }
//
//                        file_delete($file);
//                        rename($path.$target, $file);
//                        copy($file, $path.$target);
//
//                        $processed[str_rfrom($file, '/')] = true;
//                        continue;
//                    }
//
//                    /*
//                     * Create a symlink for the minimized file to the minimized version
//                     */
//                    if(substr($target, -7, 7) != '.min.js'){
//                        /*
//                         * Correct the targets file extension
//                         */
//                        $target = substr($target, 0, -3).'.min.js';
//                    }
//
//                    if(VERBOSE){
//                        log_console('uglify_js(): Created minimized symlink for file "'.str_log($file).'"', 'uglify');
//                    }
//                    file_delete(substr($file, 0, -3).'.min.js');
//                    symlink($target, substr($file, 0, -3).'.min.js');
//
//                    $processed[str_rfrom($file, '/')] = true;
//                    continue;
//
//                }else{
//                    if(VERBOSE){
//                        log_console('uglify_js(): Ignorning non js symlink "'.str_log($file).'"', 'uglify', 'yellow');
//                    }
//
//                    $processed[str_rfrom($file, '/')] = true;
//                    continue;
//                }
//            }

            if(!is_file($file)){
                if(VERBOSE){
                    log_console('uglify_js(): Ignorning unknown type file "'.str_log($file).'"', 'uglify', 'yellow');
                }

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            if(substr($file, -7, 7) == '.min.js'){
                /*
                 * This file is already minified. IF there is a source .js file, then remove it (it will be minified again later)
                 * If no source .js is availalbe, then make this the source now, and it will be minified later.
                 *
                 * Reason for this is that sometimes we only have minified versions available.
                 */
                if(file_exists(substr($file, 0, -7).'.js') and !is_link(substr($file, 0, -7).'.js')){
                    if(VERBOSE){
                        log_console('uglify_js(): Ignoring minified file "'.str_log($file).'" as a source is available', 'uglify');
                    }
//                    file_delete($file);

                }else{
                    if(VERBOSE){
                        log_console('uglify_js(): Using minified file "'.str_log($file).'" as source is available', 'uglify');
                    }
                    rename($file, substr($file, 0, -7).'.js');
                }

                $file = substr($file, 0, -7).'.js';
            }

            if(substr($file, -3, 3) != '.js'){
                if(substr($file, -4, 4) == '.css'){
                    /*
                     * Found a CSS file in the javascript path
                     */
                    if(VERBOSE){
                        log_console('uglify_js(): Found CSS file "'.str_log($file).'" in javascript path, switching to uglifycss', 'uglify', 'yellow');
                    }
                    uglify_css($file);

                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                if(VERBOSE){
                    log_console('uglify_js(): Ignorning non javascript file "'.str_log($file).'"', 'uglify', 'yellow');
                }

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            try{
                 /*
                 * If file exists and FORCE option wasn't given then proceed
                 */
                $minfile = str_runtil($file, '.').'.min.js';

                if(file_exists($minfile)){
                    /*
                     * Compare filemtimes, if they match then we will assume that
                     * the file has not changed, so we can skip compressing
                     */
                    if((filemtime($minfile) == filemtime($file)) and !FORCE){
                        /*
                         * Do not compress, just continue with next file
                         */
                        if(VERBOSE){
                            log_console('uglify_js(): NOT Compressing javascript file "'.str_log($file).'", file has not changed', 'uglify', 'yellow');
                        }

                        continue;
                    }
                }

                /*
                 * Compress file
                 */
                if(VERBOSE){
                    log_console('uglify_js(): Compressing javascript file "'.str_log($file).'"', 'uglify');
                }

                file_delete(substr($file, 0, -3).'.min.js');

                try{
                    safe_exec($node.' '.$node_modules.'uglify-js/bin/uglifyjs --output '.substr($file, 0, -3).'.min.js '.$file);

                }catch(Exception $e){
                    /*
                     * If uglify fails then make a copy of min file
                     */
                    copy($file, substr($file, 0, -3).'.min.js');
                }

                $processed[str_rfrom($file, '/')] = true;

                /*
                 * Make mtime equal
                 */
                $time = time();
                touch(str_runtil($file, '.').'.js'    , $time, $time);
                touch(str_runtil($file, '.').'.min.js', $time, $time);

            }catch(Exception $e){
                log_error('Failed to compress javascript file "'.str_log($file).'"', 'error/uglify');

            }
        }

    }catch(Exception $e){
        throw new bException('uglify_js(): Failed', $e);
    }
}
?>
