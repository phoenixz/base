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
        $node         = node_check();
        $node_modules = node_check_modules();
        $npm          = node_check_npm();

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
        log_console('uglify_css_install(): Installing uglifycss', 'uglify', 'white');
        passthru($npm.' install uglifycss@0');
        log_console('uglify_css_install(): Finished installing uglifycss', 'uglify', 'green');

    }catch(Exception $e){
        throw new bException('uglify_css_install(): Failed', $e);
    }
}



/*
 * Check availability of uglifycss installation, and install if needed
 */
function uglify_css_check(){
    global $npm;

    try{
        uglify_check();

        log_console('uglify_css_check(): Checking uglifycss availability', 'uglify', 'white');

        $result = safe_exec($npm.' list uglifycss@0');

        if(empty($result[1])){
            throw new bException('uglify_js_check(): npm list uglifycss@0 returned invalid results', 'invalid_result');
        }

        if(substr($result[1], -7, 7) == '(empty)'){
            /*
             * uglifycss is not available, install it now.
             */
            log_console('uglify_css_check(): No uglifycss found, trying to install now', 'uglify', 'yellow');
            uglify_css_install($npm);
        }

        $result[1] = 'uglify'.str_from($result[1], 'uglify');

        log_console('uglify_css_check(): Using uglifycss "'.str_log($result[1]).'"', 'uglify', 'green');

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
            log_console('uglify_css(): Compressing all CSS files using uglifycss', 'uglify');
        }

        if(empty($path)){
            /*
             * Start at the base css path
             */
            $path = ROOT.'pub/css/';
        }

        if(is_dir($path)){
            $path = slash($path);
            log_console('uglify_css(): Compressing all CSS files in directory "'.str_log($path).'"', 'uglify');
            file_check_dir($path);

        }elseif(is_file($path)){
            log_console('uglify_css(): Compressing CSS file "'.str_log($path).'"', 'uglify');

        }else{
            throw new bException('uglify_css(): Specified file "'.str_log($path).'" is neither a file or a directory', 'unknow_file_type');
        }

        foreach(file_list_tree($path) as $file){
            if(is_dir($file)){
                /*
                 * Recurse into sub directories
                 */
                uglify_css($file);

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            if(is_link($file)){
                /*
                 * The file is a symlink
                 */
                $target = readlink($file);

                if(substr($file, -8, 8) == '.min.css'){
                    /*
                     * Delete the minimized symlinks, we'll regenerate them for the normal files
                     */
                    file_delete($file);

                    $processed[str_rfrom($file, '/')] = true;
                    continue;

                }elseif(substr($file, -4, 4) == '.css'){
                    /*
                     * If the symlink target does not exist, we can just ignore it
                     */
                    if(!file_exists($path.$target)){
                        log_console('uglify_css(): Ignorning symlink "'.str_log($file).'" with non existing target', 'uglify', 'yellow');

                        $processed[str_rfrom($file, '/')] = true;
                        continue;
                    }

                    /*
                     * If the symlink points to any path above or outside the current path, then only ensure there is a .min symlink for it
                     */
                    if(!strstr($path.$target, str_runtil($file, '/'))){
                        log_console('uglify_css(): Found symlink "'.str_log($file).'" with target "'.str_log($target).'" that points to location outside symlink path, ensuring minimized version pointing to the same file', 'uglify', 'yellow');

                        if(file_exists(substr($file, 0, -4).'.min.css')){
                            file_delete(substr($file, 0, -4).'.min.css');
                        }

                        symlink($target, substr($file, 0, -4).'.min.css');

                        $processed[str_rfrom($file, '/')] = true;
                        continue;
                    }

                    if(substr($file, 0, -4) == substr($target, 0, -8)){
                        /*
                         * This non minimized version points towards a minimized version of the same file. Move the minimized version to the normal version,
                         * and make a minimized version
                         */
                        log_console('uglify_css(): Found symlink "'.str_log($file).'" pointing to its minimized version. Switching files', 'uglify', 'yellow');

                        file_delete($file);
                        rename($target, $file);
                        symlink($file, $target);

                        $processed[str_rfrom($file, '/')] = true;
                        continue;
                    }

                    /*
                     * Create a symlink for the minimized file to the minimized version
                     */
                    if(substr($target, -8, 8) != '.min.css'){
                        /*
                         * Correct the targets file extension
                         */
                        $target = substr($target, 0, -4).'.min.css';
                    }

                    log_console('uglify_css(): Created minimized symlink for file "'.str_log($file).'"', 'uglify');
                    file_delete(substr($file, 0, -4).'.min.css');
                    symlink($target, substr($file, 0, -4).'.min.css');

                    $processed[str_rfrom($file, '/')] = true;
                    continue;

                }else{
                    log_console('uglify_css(): Ignorning non css symlink "'.str_log($file).'"', 'uglify', 'yellow');

                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }
            }

            if(!is_file($file)){
                log_console('uglify_css(): Ignorning unknown type file "'.str_log($file).'"', 'uglify', 'yellow');

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
                    log_console('uglify_css(): Ignoring minified file "'.str_log($file).'" as a source is available', 'uglify');
//                    file_delete($file);

                }else{
                    log_console('uglify_css(): Using minified file "'.str_log($file).'" as source is available', 'uglify');
                    rename($file, substr($file, 0, -8).'.css');
                }

                $file = substr($file, 0, -8).'.css';
            }

            if(substr($file, -4, 4) != '.css'){
                if(substr($file, -3, 3) == '.js'){
                    /*
                     * Found a js file in the CSS path
                     */
                    log_console('uglify_css(): Found js file "'.str_log($file).'" in CSS path, switching to uglifyjs', 'uglify', 'yellow');
                    uglify_js($file);

                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                log_console('uglify_css(): Ignorning non CSS file "'.str_log($file).'"', 'uglify', 'yellow');

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            try{
                log_console('uglify_css(): Compressing CSS file "'.str_log($file).'"', 'uglify');
                file_delete(substr($file, 0, -4).'.min.css');
                safe_exec($node.' '.$node_modules.'uglifycss/uglifycss '.$file.' >  '.substr($file, 0, -4).'.min.css');

                $processed[str_rfrom($file, '/')] = true;

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
    global $npm;

    try{
        log_console('uglify_js_install(): Installing uglify-js', 'uglify', 'white');
        passthru($npm.' install uglify-js@2');
        log_console('uglify_js_install(): Finished installing uglify-js', 'uglify', 'green');

    }catch(Exception $e){
        throw new bException('uglify_js_install(): Failed', $e);
    }
}



/*
 * Check availability of uglify-js installation, and install if needed
 */
function uglify_js_check(){
    global $npm;

    try{
        uglify_check();

        log_console('uglify_js_check(): Checking uglify-js availability', 'uglify', 'white');

        $result = safe_exec($npm.' list uglify-js@2');

        if(empty($result[1])){
            throw new bException('uglify_js_check(): npm list uglify-js@2 returned invalid results', 'invalid_result');
        }

        if(substr($result[1], -7, 7) == '(empty)'){
            /*
             * uglify-js is not available, install it now.
             */
            log_console('uglify_js_check(): No uglify-js found, trying to install now', 'uglify', 'yellow');
            uglify_js_install($npm);
        }

        $result[1] = 'uglify'.str_from($result[1], 'uglify');

        log_console('uglify_js_check(): Using uglify-js "'.str_log($result[1]).'"', 'uglify', 'green');

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
            log_console('uglify_js(): Compressing all javascript files using uglifyjs', 'uglify');
        }

        if(empty($path)){
            /*
             * Start at the base js path
             */
            $path = ROOT.'pub/js/';
        }

        if(is_dir($path)){
            $path = slash($path);
            log_console('uglify_js(): Compressing all javascript files in directory "'.str_log($path).'"', 'uglify');
            file_check_dir($path);

        }elseif(is_file($path)){
            log_console('uglify_js(): Compressing javascript file "'.str_log($path).'"', 'uglify');

        }else{
            throw new bException('uglify_js(): Specified file "'.str_log($path).'" is neither a file or a directory', 'unknow_file_type');
        }

        foreach(file_list_tree($path) as $file){
            if(is_dir($file)){
                /*
                 * Recurse into sub directories
                 */
                uglify_js($file);

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            if(is_link($file)){
                /*
                 * The file is a symlink
                 */
                $target = readlink($file);

                if(substr($file, -7, 7) == '.min.js'){
                    /*
                     * Delete the minimized symlinks, we'll regenerate them for the normal files
                     */
                    file_delete($file);

                    $processed[str_rfrom($file, '/')] = true;
                    continue;

                }elseif(substr($file, -3, 3) == '.js'){
                    /*
                     * If the symlink target does not exist, we can just ignore it
                     */
                    if(!file_exists($path.$target)){
                        log_console('uglify_js(): Ignorning symlink "'.str_log($file).'" with non existing target', 'uglify', 'yellow');

                        $processed[str_rfrom($file, '/')] = true;
                        continue;
                    }

                    /*
                     * If the symlink points to any path above or outside the current path, then only ensure there is a .min symlink for it
                     */
                    if(!strstr($path.$target, str_runtil($file, '/'))){
                        log_console('uglify_js(): Found symlink "'.str_log($file).'" with target "'.str_log($target).'" that points to location outside symlink path, ensuring minimized version pointing to the same file', 'uglify', 'yellow');

                        if(file_exists(substr($file, 0, -3).'.min.js')){
                            file_delete(substr($file, 0, -3).'.min.js');
                        }

                        symlink($target, substr($file, 0, -3).'.min.js');

                        $processed[str_rfrom($file, '/')] = true;
                        continue;
                    }

                    if(substr($file, 0, -3) == substr($target, 0, -7)){
                        /*
                         * This non minimized version points towards a minimized version of the same file. Move the minimized version to the normal version,
                         * and make a minimized version
                         */
                        log_console('uglify_js(): Found symlink "'.str_log($file).'" pointing to its minimized version. Switching files', 'uglify', 'yellow');

                        file_delete($file);
                        rename($target, $file);
                        symlink($file, $target);

                        $processed[str_rfrom($file, '/')] = true;
                        continue;
                    }

                    /*
                     * Create a symlink for the minimized file to the minimized version
                     */
                    if(substr($target, -7, 7) != '.min.js'){
                        /*
                         * Correct the targets file extension
                         */
                        $target = substr($target, 0, -3).'.min.js';
                    }

                    log_console('uglify_js(): Created minimized symlink for file "'.str_log($file).'"', 'uglify');
                    file_delete(substr($file, 0, -3).'.min.js');
                    symlink($target, substr($file, 0, -3).'.min.js');

                    $processed[str_rfrom($file, '/')] = true;
                    continue;

                }else{
                    log_console('uglify_js(): Ignorning non js symlink "'.str_log($file).'"', 'uglify', 'yellow');

                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }
            }

            if(!is_file($file)){
                log_console('uglify_js(): Ignorning unknown type file "'.str_log($file).'"', 'uglify', 'yellow');

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
                    log_console('uglify_js(): Ignoring minified file "'.str_log($file).'" as a source is available', 'uglify');
//                    file_delete($file);

                }else{
                    log_console('uglify_js(): Using minified file "'.str_log($file).'" as source is available', 'uglify');
                    rename($file, substr($file, 0, -7).'.js');
                }

                $file = substr($file, 0, -7).'.js';
            }

            if(substr($file, -3, 3) != '.js'){
                if(substr($file, -4, 4) == '.css'){
                    /*
                     * Found a CSS file in the javascript path
                     */
                    log_console('uglify_js(): Found CSS file "'.str_log($file).'" in javascript path, switching to uglifycss', 'uglify', 'yellow');
                    uglify_css($file);

                    $processed[str_rfrom($file, '/')] = true;
                    continue;
                }

                log_console('uglify_js(): Ignorning non javascript file "'.str_log($file).'"', 'uglify', 'yellow');

                $processed[str_rfrom($file, '/')] = true;
                continue;
            }

            try{
                log_console('uglify_js(): Compressing javascript file "'.str_log($file).'"', 'uglify');
                file_delete(substr($file, 0, -3).'.min.js');
                safe_exec($node.' '.$node_modules.'uglify-js/bin/uglifyjs --output '.substr($file, 0, -3).'.min.js '.$file);

                $processed[str_rfrom($file, '/')] = true;

            }catch(Exception $e){
                log_error('Failed to compress javascript file "'.str_log($file).'"', 'error/uglify');
            }
        }

    }catch(Exception $e){
        throw new bException('uglify_js(): Failed', $e);
    }
}
?>
