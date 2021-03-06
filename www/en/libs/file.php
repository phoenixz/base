<?php
/*
 * File library containing all kinds of filesystem related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Append specified data string to the end of the specified file
 */
function file_append($target, $data){
    global $_CONFIG;

    try {
        /*
         * Open target
         */
        if(!file_exists(dirname($target))){
            throw new bException(tr('file_append(): Specified target path ":target" does not exist', array(':target' => dirname($target))), 'not-exist');
        }

        $target_h = fopen($target, 'a');
        fwrite($target_h, $data);
        fclose($target_h);

    }catch(Exception $e){
        throw new bException('file_append(): Failed', $e);
    }
}



/*
 * Concatenates a list of files to a target file
 */
function file_concat($target, $sources){
    global $_CONFIG;

    try {
        if(!is_array($sources)){
            $sources = array($sources);
        }

        /*
         * Open target
         */
        if(!file_exists(dirname($target))){
            throw new bException(tr('file_concat(): Specified target path ":target" does not exist', array(':target' => dirname($target))), 'not-exist');
        }

        $target_h = fopen($target, 'a');

        foreach($sources as $source){
            $source_h = fopen($source, 'r');

            while(!feof($source_h)){
                $data = fread($source_h, 8192);
                fwrite($target_h, $data);
            }

            fclose($source_h);
        }

        fclose($target_h);

    }catch(Exception $e){
        throw new bException('file_concat(): Failed', $e);
    }
}



/*
 * Move uploaded image to correct target
 */
function file_get_uploaded($source){
    global $_CONFIG;

    try{
        $destination = ROOT.'data/uploads/';

        if(is_array($source)){
            /*
             * Asume this is a PHP file upload array entry
             */
            if(empty($source['tmp_name'])){
                throw new bException('file_move_uploaded(): Invalid source specified, must either be a string containing an absolute file path or a PHP $_FILES entry', 'invalid');
            }

            $real   = $source['name'];
            $source = $source['tmp_name'];

        }else{
            $real   = basename($source);
        }


        is_file($source);
        file_ensure_path($destination);

        /*
         * Ensure we're not overwriting anything!
         */
        if(file_exists($destination.$real)){
            $real = str_runtil($real, '.').'_'.substr(uniqid(), -8, 8).'.'.str_rfrom($real, '.');
        }

        if(!move_uploaded_file($source, $destination.$real)){
            throw new bException('file_move_uploaded(): Faield to move file "'.str_log($source).'" to destination "'.str_log($destination).'"', 'move');
        }

        /*
         * Return destination file
         */
        return $destination.$real;

    }catch(Exception $e){
        throw new bException('file_move_uploaded(): Failed', $e);
    }
}



/*
 * Create a target, but don't put anything in it
 */
function file_assign_target($path, $extension = false, $singledir = false, $length = 4){
    try{
        return file_move_to_target('', $path, $extension, $singledir, $length);

    }catch(Exception $e){
        throw new bException('file_assign_target(): Failed', $e);
    }
}



/*
 * Create a target, but don't put anything in it, and return path+filename without extension
 */
function file_assign_target_clean($path, $extension = false, $singledir = false, $length = 4){
    try{
        return str_replace($extension, '', file_move_to_target('', $path, $extension, $singledir, $length));

    }catch(Exception $e){
        throw new bException('file_assign_target_clean(): Failed', $e);
    }
}



/*
 * Copy specified file, see file_move_to_target for implementation
 */
function file_copy_to_target($file, $path, $extension = false, $singledir = false, $length = 4){
    try{
        if(is_array($file)){
            throw new bException(tr('file_copy_to_target(): Specified file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', array(':file' => str_log($file))));
        }

        return file_move_to_target($file, $path, $extension, $singledir, $length, true);

    }catch(Exception $e){
        throw new bException('file_copy_to_target(): Failed', $e);
    }
}



/*
 * Move specified file (must be either file string or PHP uploaded file array) to a target and returns the target name
 *
 * IMPORTANT! Extension here is just "the rest of the filename", which may be _small.jpg, or just the extension, .jpg
 * If only an extension is desired, it is VERY important that its specified as ".jpg" and not "jpg"!!
 *
 * $path sets the base path for where the file should be stored
 * If $extension is false, the files original extension will be retained. If set to a value, the extension will be that value
 * If $singledir is set to false, the resulting file will be in a/b/c/d/e/, if its set to true, it will be in abcde
 * $length specifies howmany characters the subdir should have (4 will make a/b/c/d/ or abcd/)
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 *
 * @param string $file
 * @param string $path
 * @param string $extension
 * @param string $singledir
 * @param string $length
 * @param string $copy
 * @param string $context
 * @return string The target file
 */
function file_move_to_target($file, $path, $extension = false, $singledir = false, $length = 4, $copy = false, $context = null){
    try{
        if(is_array($file)){
            $upload = $file;
            $file   = $file['name'];
        }

        if(isset($upload) and $copy){
            throw new bException(tr('file_move_to_target(): Copy option has been set, but specified file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', array(':file' => $file)));
        }

        $path     = file_ensure_path($path);
        $filename = basename($file);

        if(!$filename){
            /*
             * We always MUST have a filename
             */
            $filename = uniqid();
        }

        /*
         * Ensure we have a local copy of the file to work with
         */
        if($file){
            $file = file_get_local($file, $is_downloaded, $context);
        }

        if(!$extension){
            $extension = file_get_extension($filename);
        }

        if($length){
            $targetpath = slash(file_create_target_path($path, $singledir, $length));

        }else{
            $targetpath = slash($path);
        }

        $target = $targetpath.strtolower(str_convert_accents(str_runtil($filename, '.'), '-'));

        /*
         * Check if there is a "point" already in the extension
         * not obligatory at the start of the string
         */
        if($extension){
            if(strpos($extension, '.') === false){
                $target .= '.'.$extension;

            }else{
               $target .= $extension;
            }
        }

        /*
         * Only move file is target does not yet exist
         */
        if(file_exists($target)){
            if(isset($upload)){
                /*
                 * File was specified as an upload array
                 */
                return file_move_to_target($upload, $path, $extension, $singledir, $length, $copy);
            }

            return file_move_to_target($file, $path, $extension, $singledir, $length, $copy);
        }

        /*
         * Only move if file was specified. If no file specified, then we will only return the available path
         */
        if($file){
            if(isset($upload)){
                /*
                 * This is an uploaded file
                 */
                file_move_uploaded($upload['tmp_name'], $target);

            }else{
                /*
                 * This is a normal file
                 */
                if($copy and !$is_downloaded){
                    copy($file, $target);

                }else{
                    rename($file, $target);
                    file_clear_path(dirname($file));
                }
            }
        }

        return str_from($target, $path);

    }catch(Exception $e){
        throw new bException('file_move_to_target(): Failed', $e);
    }
}



/*
 * Creates a random path in specified base path (If it does not exist yet), and returns that path
 */
function file_create_target_path($path, $singledir = false, $length = false){
    global $_CONFIG;

    try{
        if($length === false){
            $length = $_CONFIG['fs']['target_path_size'];
        }

        $path = unslash(file_ensure_path($path));

        if($singledir){
            /*
             * Assign path in one dir, like abcde/
             */
            $path = slash($path).substr(uniqid(), -$length, $length);

        }else{
            /*
             * Assign path in multiple dirs, like a/b/c/d/e/
             */
            foreach(str_split(substr(uniqid(), -$length, $length)) as $char){
                $path .= DIRECTORY_SEPARATOR.$char;
            }
        }

        return slash(file_ensure_path($path));

    }catch(Exception $e){
        throw new bException('file_create_target_path(): Failed', $e);
    }
}



/*
 * Ensure that the specified file exists in the specified path
 */
function file_ensure_file($file, $mode = null, $path_mode = null){
    try{
        file_ensure_path(dirname($file), $path_mode);

        if(!file_exists($file)){
            /*
             * Create the file
             */
            if(VERBOSE and PLATFORM_CLI){
                log_console('file_ensure_file(): Warning: file "'.str_log($file).'" did not existed and was created empty to ensure system stability, but information may be missing', 'yellow');
            }

            touch($file);

            if($mode){
                chmod($file, $mode);
            }
        }

        return $file;

    }catch(Exception $e){
        throw new bException('file_ensure_file(): Failed', $e);
    }
}



/*
 * Ensures existence of specified path
 */
function file_ensure_path($path, $mode = null){
    global $_CONFIG;

    try{
        if($mode === null){
            $mode = $_CONFIG['fs']['dir_mode'];
        }

        if(!file_exists(unslash($path))){
            /*
             * The complete requested path doesn't exist. Try to create it, but
             * directory by directory so that we can correct issues as we run in
             * to them
             */
            $dirs = explode('/', $path);
            $path = '';

            foreach($dirs as $dir){
                $path .= '/'.$dir;

                if(file_exists($path)){
                    if(!is_dir($path)){
                        /*
                         * Some normal file is in the way. Delete the file, and
                         * retry
                         */
                        file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode){
                            file_delete($path);
                        });

                        return file_ensure_path($path, $mode);
                    }

                    continue;

                }elseif(is_link($path)){
                    /*
                     * This is a dead symlink, delete it
                     */
                    file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode){
                        file_delete($path);
                    });
                }

                try{
                    /*
                     * Make sure that the parent path is writable when creating
                     * the directory
                     */
                    file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode){
                        mkdir($path, $mode);
                    });

                }catch(Exception $e){
                    /*
                     * It sometimes happens that the specified path was created
                     * just in between the file_exists and mkdir
                     */
                    if(!file_exists($path)){
                        throw $e;
                    }
                }
            }

        }elseif(!is_dir($path)){
            /*
             * Some other file is in the way. Delete the file, and retry
             */
            file_delete($path);
            return file_ensure_path($path, $mode);
        }

        return slash(realpath($path).'/');

    }catch(Exception $e){
        throw new bException(tr('file_ensure_path(): Failed to ensure path ":path"', array(':path' => $path)), $e);
    }
}



/*
 * Delete the path until directory is no longer empty
 */
function file_clear_path($path){
    try{
        if(!file_exists($path)){
            /*
             * This section does not exist, jump up to the next section
             */
            return file_clear_path(dirname($path));
        }

        if(!is_dir($path)){
            /*
             * This is a normal file. Delete it and continue with the directory above
             */
            unlink($path);

        }else{
            /*
             * This is a directory. See if its empty
             */
            $h        = opendir($path);
            $contents = false;

            while(($file = readdir($h)) !== false){
                /*
                 * Skip . and ..
                 */
                if(($file == '.') or ($file == '..')) continue;

                $contents = true;
                break;
            }

            closedir($h);

            if($contents){
                /*
                 * Do not remove anything more, there is contents here!
                 */
                return true;
            }

            /*
             * Remove this entry and continue;
             */
            try{
                file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path){
                    file_delete($path);
                });

            }catch(Exception $e){
                /*
                 * The directory WAS empty, but cannot be removed
                 *
                 * In all probability, a parrallel process added a new content
                 * in this directory, so it's no longer empty. Just register
                 * the event and leave it be.
                 */
                log_console(tr('file_clear_path(): Failed to remove empty path ":path" with exception ":e"', array(':path' => $path, ':e' => $e)), 'failed');
                return true;
            }
        }

        /*
         * Go one entry up and continue
         */
        $path = str_runtil(unslash($path), '/');
        file_clear_path($path);

    }catch(Exception $e){
        throw new bException('file_clear_path(): Failed', $e);
    }
}



/*
 * Return the extension of the specified filename
 */
function file_get_extension($filename){
    try{
        return str_rfrom($filename, '.');

    }catch(Exception $e){
        throw new bException('file_get_extension(): Failed', $e);
    }
}



/*
 * Return a temporary filename for the specified file in the specified path
 */
function file_temp($create = true, $name = null){
    try{
        file_ensure_path(TMP);

        if(!$name){
            $name = substr(hash('sha1', uniqid().microtime()), 0, 12);
        }

		file_delete(TMP.$name);

        if($create){
            touch(TMP.$name);
        }

        return TMP.$name;

    }catch(Exception $e){
        throw new bException('file_temp(): Failed', $e);
    }
}



/*
 * Tree delete
 *
 * Kindly taken from http://lixlpixel.org/recursive_function/php/recursive_directory_delete/
 * Slightly rewritten and cleaned up by Sven Oostenbrink
 */
function file_delete_tree($directory){
    try{
        $directory = unslash($directory);

        if(!file_exists($directory) and !is_link($directory)){
            /*
             * The path itself no (longer) exists. Maybe it was already
             * deleted, but the situation now is exactly how this function is
             * supposed to leave it behind, so we're okay and done!
             */
            return;

        }elseif(is_link($directory) or !is_dir($directory)){
            /*
             * This is a file (or symlink), fine, delete it and lets continue!
             */
            unlink($directory);
            return;
        }

        $handle = opendir($directory);

        while (false !== ($item = readdir($handle))){
            if($item != '.' && $item != '..'){
                $path = $directory.'/'.$item;

                if(is_dir($path)){
                    file_delete_tree($path);

                }else{
                    try{
                        unlink($path);

                    }catch(Exception $e){
                        /*
                         * Failed, very very likely access denied, so ATTEMPT to make writable and try again. If fails again, just exception
                         */
                        file_chmod_tree(dirname($path), 0660, 0770);
                        unlink($path);
                    }
                }
            }
        }

        closedir($handle);
        file_delete($directory);

    }catch(Exception $e){
        throw new bException('file_delete_tree(): Failed', $e);
    }
}



/*
 * Returns the files mimetype
 */
function file_mimetype($file){
    static $finfo = false;

    try{
        /*
         * Check the source file
         */
        if(!is_file($file)){
            throw new bException(tr('file_mimetype(): Specified path ":path" is not a file', array(':path' => $file)), 'not-file');
        }

        if(!$finfo){
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        }

        return finfo_file($finfo, $file);

    }catch(Exception $e){
        throw new bException('file_mimetype(): Failed', $e);
    }
}



/*
 * Returns true or false if file is ASCII or not
 */
function file_is_text($file){
    try{
        if(str_until(file_mimetype($file), '/') == 'text') return true;
        if(str_from (file_mimetype($file), '/') == 'xml' ) return true;

        return false;

    }catch(Exception $e){
        throw new bException('file_is_text(): Failed', $e);
    }
}



/*
 * Returns if the specified file exists and is a file
 */
function file_check($file){
    if(!file_exists($file)){
        throw new bException('file_check(): Specified file "'.str_log($file).'" does not exist', 'not-exist');
    }

    if(!is_file($file)){
        throw new bException('file_check(): Specified file "'.str_log($file).'" is not a file' , 'notafile');
    }
}



/*
 * Return all files in a directory
 */
function file_list_tree($path = '.', $recursive = true){
    try{
        if(!is_dir($path)){
            if(!is_file($path)){
                throw new bException('file_list_tree(): Specified path "'.str_log($path).'" is not a directory', 'path');
            }

            return array($path);
        }

        $files = array();
        $fh    = opendir($path);

        while (($file = readdir($fh)) !== false){
            # loop through the files, skipping . and .., and recursing if necessary
            if(($file == '.') or ($file == '..')){
                continue;
            }

            $filepath = slash($path).$file;

            if( is_dir($filepath) and $recursive){
                $files = array_merge($files, file_list_tree($filepath));

            }else {
                array_push($files, $filepath);
            }
        }

        closedir($fh);

        return $files;

    }catch(Exception $e){
        throw new bException('file_list_tree(): Failed for "'.str_log($path).'"', $e);
    }
}



/*
 * Delete a file, weather it exists or not, without error
 */
// :SECURITY: $pattern is NOT checked!!
function file_delete($patterns, $clean_path = false, $sudo = false){
    try{
        if(!$patterns){
            throw new bException('file_delete(): No files or patterns specified');
        }

        foreach(array_force($patterns) as $pattern){
            safe_exec(($sudo ? 'sudo ' : '').'rm -rf '.$pattern);

            if($clean_path){
                file_clear_path(dirname($patterns));
            }
        }

    }catch(Exception $e){
        throw new bException('file_delete(): Failed', $e);
    }
}



/*
 * Copy an entire tree with replace option
 *
 * Extensions (may be string or array with strings) sets which
 * file extensions will have search / replace. If set to false
 * all files will have search / replace applied.
 *
 * If either search or replace are not specified, both will be
 * set to null, and no replacements will be done
 *
 * Mode has 3 settings: (boolean) true, null, and some 0000 mode.
 * true will keep the copied file mode for the target, 0000 will
 * set the target file mode to the specified value, and null will
 * set $mode to the default value, specified in $_CONFIG, and then
 * do the same as 0000
 */
function file_copy_tree($source, $destination, $search = null, $replace = null, $extensions = null, $mode = true, $novalidate = false){
    global $_CONFIG;

    try{
        /*
         * Choose between copy filemode (mode is null), set filemode ($mode is a string or octal number) or preset filemode (take from config, TRUE)
         */
        if(!is_bool($mode) and !is_null($mode)){
            if(is_string($mode)){
                $mode = intval($mode, 8);
            }

            $filemode = $mode;
        }

        if(substr($destination, 0, 1) != '/'){
            /*
             * This is not an absolute path
             */
            $destination = PWD.$destination;
        }

        /*
         * Validations
         */
        if(!$novalidate){
            /*
             * Prepare search / replace
             */
            if(!$search){
                /*
                 * We can only replace if we search
                 */
                $search     = null;
                $replace    = null;
                $extensions = null;

            }else{
                if(!is_array($extensions)){
                    $extensions = array($extensions);
                }

                if(!is_array($search)){
                    $search = explode(',', $search);
                }

                if(!is_array($replace)){
                    $replace = explode(',', $replace);
                }

                if(count($search) != count($replace)){
                    throw new bException('file_copy_tree(): The search parameters count "'.count($search).'" and replace parameters count "'.count($destination).'" do not match', 'parameternomatch');
                }
            }

            if(!file_exists($source)){
                throw new bException('file_copy_tree(): Specified source "'.str_log($source).'" does not exist', 'not-exist');
            }

            $destination = unslash($destination);

            if(!file_exists($destination)){
                if(!file_exists(dirname($destination))){
                    throw new bException('file_copy_tree(): Specified destination "'.str_log(dirname($destination)).'" does not exist', 'not-exist');
                }

                if(!is_dir(dirname($destination))){
                    throw new bException('file_copy_tree(): Specified destination "'.str_log(dirname($destination)).'" is not a directory', 'not-directory');
                }

                if(is_dir($source)){
                    /*
                     * We are copying a directory, destination dir does not yet exist
                     */
                    mkdir($destination);

                }else{
                    /*
                     * We are copying just one file
                     */
                }

            }else{
                /*
                 * Destination already exists,
                 */
                if(is_dir($source)){
                    if(!is_dir($destination)){
                        throw new bException('file_copy_tree(): Cannot copy source directory "'.str_log($source).'" into destination file "'.str_log($destination).'"');
                    }

                }else{
                    /*
                     * Source is a file
                     */
                    if(!is_dir($destination)){
                        /*
                         * Remove destination file since it would be overwritten
                         */
                        file_delete($destination);
                    }
                }
            }
        }

        if(is_dir($source)){
            $source      = slash($source);
            $destination = slash($destination);

            foreach(scandir($source) as $file){
                if(($file == '.') or ($file == '..')){
                    /*
                     * Only replacing down
                     */
                    continue;
                }

                if(is_null($mode)){
                    $filemode = $_CONFIG['fs']['dir_mode'];

                }elseif(is_link($source.$file)){
                    /*
                     * No file permissions for symlinks
                     */
                    $filemode = false;

                }else{
                    $filemode = fileperms($source.$file);
                }

                if(is_dir($source.$file)){
                    /*
                     * Recurse
                     */
                    if(file_exists($destination.$file)){
                        /*
                         * Destination path already exists. This -by the way- means that the
                         * destination tree was not clean
                         */
                        if(!is_dir($destination.$file)){
                            /*
                             * Were overwriting here!
                             */
                            file_delete($destination.$file);
                        }
                    }

                    file_ensure_path($destination.$file, $filemode);
                }

                file_copy_tree($source.$file, $destination.$file, $search, $replace, $extensions, $mode, true);
           }

        }else{
            if(is_link($source)){
                $link = readlink($source);

                if(substr($link, 0, 1) == '/'){
                    /*
                     * Absolute link, this is ok
                     */
                    $reallink = $link;

                }else{
                    /*
                     * Relative link, get the absolute path
                     */
                    $reallink = slash(dirname($source)).$link;
                }

                if(!file_exists($reallink)){
                    /*
                     * This symlink points to no file, its dead
                     */
                    log_console('file_copy_tree(): Encountered dead symlink "'.$source.'", copying anyway...', 'warning');
                }

                /*
                 * This is a symlink. Just create a new symlink that points to the same path
                 */
                return symlink($link, $destination);
            }

            /*
             * Determine mode
             */
            if($mode === null){
                $filemode = $_CONFIG['file_mode'];

            }elseif($mode === true){
                $filemode = fileperms($source);
            }

            /*
             * Check if the file requires search / replace
             */
            if(!$search){
                /*
                 * No search specified, just copy tree
                 */
                $doreplace = false;

            }elseif(!$extensions){
                /*
                 * No extensions specified, search / replace all files in tree
                 */
                $doreplace = true;

            }else{
                /*
                 * Check extension if we should search / replace
                 */
                $doreplace = false;

                foreach($extensions as $extension){
                    $len = strlen($extension);

                    if(!substr($source, -$len, $len) != $extension){
                        $doreplace = true;
                        break;
                    }
                }
            }

            if(!$doreplace){
                /*
                 * Just a simple filecopy will suffice
                 */
                copy($source, $destination);

            }else{
                $data = file_get_contents($source);

                foreach($search as $id => $svalue){
                    if((substr($svalue, 0, 1 == '/')) and (substr($svalue, -1, 1 == '/'))){
                        /*
                         * Do a regex search / replace
                         */
                        $data = preg_replace($svalue, $replace[$id], $data);

                    }else{
                        /*
                         * Do a normal search / replace
                         */
                        $data = str_replace($svalue, $replace[$id], $data);
                    }
                }

                /*
                 * Done, now write to the target file!
                 */
                file_put_contents($destination, $data);
            }

            if($mode){
                /*
                 * Update file mode
                 */
                try{
                    chmod($destination, $filemode);

                }catch(Exception $e){
                    throw new bException('file_copy_tree(): Failed to set filemode for "'.$destination.'"', $e);
                }
            }
        }

        return $destination;

    }catch(Exception $e){
        throw new bException('file_copy_tree(): Failed', $e);
    }
}



/*
 * Seach for $search file in $source, and move them all to $destination using the $rename result expression
 */
function file_rename($source, $destination, $search, $rename){
    try{
        /*
         * Validations
         */
        if(!file_exists($source)){
            throw new bException('file_rename(): Specified source "'.str_log($source).'" does not exist', 'sourcenoexist');
        }

        if(!file_exists($destination)){
            throw new bException('file_rename(): Specified destination "'.str_log($destination).'" does not exist', 'destinationnoexist');
        }

        if(!is_dir($destination)){
            throw new bException('file_rename(): Specified destination "'.str_log(dirname($destination)).'" is not a directory', 'destinationnotdirectory');
        }

        if(is_file($source)){
            /*
             * Rename just one file
             */

        }else{
            /*
             * Rename all files in this directory
             */

        }


    }catch(Exception $e){
        throw new bException('file_rename(): Failed', $e);
    }
}



/*
 * Create temporary directory (sister function from tempnam)
 */
function file_temp_dir($prefix = '', $mode = null){
    global $_CONFIG;

    try{
        /*
         * Use default configged mode, or specific mode?
         */
        if($mode === null){
            $mode = $_CONFIG['fs']['dir_mode'];
        }

        file_ensure_path($path = TMP);

        while(true){
            $unique = uniqid($prefix);

            if(!file_exists($path.$unique)){
                break;
            }
        }

        $path = $path.$unique;

        /*
         * Make sure the temp dir exists
         */
        file_ensure_path($path);

        return slash($path);

    }catch(Exception $e){
        throw new bException('file_tempdir(): Failed', $e);
    }
}



/*
 * chmod an entire directory, recursively
 * Copied from http://www.php.net/manual/en/function.chmod.php#84273
 */
function file_chmod_tree($path, $filemode, $dirmode = 0770){
    try{
        if(!is_dir($path)){
            return chmod($path, $filemode);
        }

        $dh = opendir($path);

        while (($file = readdir($dh)) !== false){
            if(($file != '.') or ($file != '..')) continue;

            $fullpath = $path.'/'.$file;

            if(is_link($fullpath)){
                /*
                 * This is a link. ignore it.
                 */

            }elseif(!is_dir($fullpath)){
                if(!chmod($fullpath, $filemode)){
                    throw new bException(tr('file_chmod_tree(): Failed to chmod file ":fullpath" to mode ":filemode"', array(':fullpath' => $fullpath, ':filemode' => $filemode)), 'failed');
                }

            }else{
                /*
                 * This is a directory, recurse
                 */
                file_chmod_tree($fullpath, $filemode, $dirmode);
            }
        }

        closedir($dh);

        if(!chmod($path, $dirmode)){
            throw new bException(tr('file_chmod_tree(): Failed to chmod directory ":path" to mode ":mode"', array(':path' => $path, ':mode' => $dirmode)), 'failed');
        }

        return true;

    }catch(Exception $e){
        throw new bException('file_chmod_tree(): Failed', $e);
    }
}



/*
 * Return the extension for the specified file
 */
function file_extension($file){
    return pathinfo($file, PATHINFO_EXTENSION);
}



/*
 * If the specified file is an HTTP, HTTPS, or FTP URL, then get it locally as a temp file
 */
function file_get_local($file, &$is_downloaded = false, $context = null){
    try{
        $context = file_create_stream_context($context);
        $file    = trim($file);

        if((stripos($file, 'http:') === false) and (stripos($file, 'https:') === false) and (stripos($file, 'ftp:') === false)){
            if(!file_exists($file)){
                throw new bException(tr('file_get_local(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
            }

            if(is_uploaded_file($file)){
                $tmp  = file_get_uploaded($file);
                $file = file_temp($file);

                rename($tmp, $file);
            }

            return $file;
        }

        /*
         * First download the file to a temporary location
         */
        $orgfile       = $file;
        $file          = str_replace(array('://', '/'), '-', $file);
        $file          = file_temp($file);
        $is_downloaded = true;

        file_ensure_path(dirname($file));
        file_put_contents($file, file_get_contents($orgfile, false, $context));

        return $file;

    }catch(Exception $e){
        if(strstr($e->getMessage(), 'HTTP/1.1 404 Not Found')){
            throw new bException(tr('file_get_local(): URL ":file" does not exist', array(':file' => $file)), 404);
        }

        throw new bException(tr('file_get_local(): Failed for file ":file"', array(':file' => $file)), $e);
    }
}



/*
 * Return a system path for the specified type
 */
function file_system_path($type, $path = ''){
    switch($type){
        case 'img':
            // FALLTHROUGH
        case 'image':
            return '/pub/img/'.$path;

        case 'css':
            // FALLTHROUGH
        case 'style':
            return '/pub/css/'.$path;

        default:
            throw new bException('file_system_path(): Unknown type "'.str_log($type).'" specified', 'unknown');
    }
}



/*
 * Pick and return a random file name from the specified path
 *
 * Warning: This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
 */
function file_random($path){
    try{
        if(!file_exists($path)){
            throw new bException('file_random(): The specified path "'.str_log($path).'" does not exist', 'not-exist');
        }

        if(!file_exists($path)){
            throw new bException('file_random(): The specified path "'.str_log($path).'" does not exist', 'not-exist');
        }

        $files = scandir($path);

        unset($files[array_search('.' , $files)]);
        unset($files[array_search('..', $files)]);

        if(!$files){
            throw new bException('file_random(): The specified path "'.str_log($path).'" contains no files', 'not-exist');
        }

        return slash($path).array_get_random($files);

    }catch(Exception $e){
        throw new bException('file_random(): Failed', $e);
    }
}



/*
 * Store a file temporarily with a label in $_SESSION['files'][label]
 */
function file_session_store($label, $file = null, $path = TMP){
    try{
        if($file === null){
            /*
             * No file specified, return the file name for the specified label
             * Then remove the temporary file and the label
             */
            if(isset($_SESSION['files'][$label])){
                $file = $_SESSION['files'][$label];
                unset($_SESSION['files'][$label]);
                return $file;
            }

            return false;
        }

        /*
         * Store this file temporary
         * Check if a file already exists. If so, remove it, and store this one.
         */
        if(!empty($_SESSION['files'][$label])){
            file_delete_tree($_SESSION['files'][$label]);
        }

        array_ensure($_SESSION, 'files');

        $target = file_move_to_target($file, $path, false, true, 1);

        $_SESSION['files'][$label] = $file;

        return $file;

    }catch(Exception $e){
        throw new bException('file_session_store(): Failed', $e);
    }
}



/*
 * Checks if the specified path exists, is a dir, and optionally, if its writable or not
 */
function file_check_dir($path, $writable = false){
    try{
        if(!file_exists($path)){
            throw new bException('file_check_dir(): The specified path "'.str_log($path).'" does not exist', 'not-exist');
        }

        if(!is_dir($path)){
            throw new bException('file_check_dir(): The specified path "'.str_log($path).'" is not a directory', 'notadirectory');
        }

        if($writable and !is_writable($path)){
            throw new bException('file_check_dir(): The specified path "'.str_log($path).'" is not writable', 'notwritable');
        }

    }catch(Exception $e){
        throw new bException('file_check_dir(): Failed', $e);
    }
}



/*
 * Send a file over HTTP "the right way" with headers et-al
 *
 * Copyright 2012 Armand Niculescu - media-division.com
 * Rewritten for use in BASE by Sven Oostenbrink <so.oostenbrink@gmail.com>
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE FREEBSD PROJECT "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
// :TEST: This function still needs to be tested to confirm that it works correctly
function file_http_download($file, $stream = false){
    try{
        // get the file request, throw error if nothing supplied

        //- turn off compression on the server
        apache_setenv('no-gzip', 1);
        ini_set('zlib.output_compression', 'Off');

        if(empty($file)){
            header('HTTP/1.0 400 Bad Request');
            throw new bException('file_http_download(): No file specified', '');
        }

        // make sure the file exists
        if(file_exists($file)){
            header('HTTP/1.0 404 Not Found');
            throw new bException('file_http_download(): Specified file "'.str_log($file).'" does not exist or is not accessible', 'not-exist');
        }

        // make sure the file can be opened
        if(is_readable($file)){
            header('HTTP/1.0 500 Internal Server Error');
            throw new bException('file_http_download(): Specified file "'.str_log($file).'" exists but is not readable', 'notreadable');
        }

        // sanitize the file request, keep just the name and extension
        // also, replaces the file location with a preset one ('./myfiles/' in this example)
        $path_parts = pathinfo($file);
        $file_name  = $path_parts['basename'];
        $file_ext   = $path_parts['extension'];
        $file_path  = './myfiles/' . $file_name;
        $file_size  = filesize($file);
        $file       = fopen($file, 'rb');

        if(!$file){
            header('HTTP/1.0 500 Internal Server Error');
            throw new bException('file_http_download(): Specified file "'.str_log($file).'" failed to be opened', 'fileopenfailed');
        }

        // set the headers, prevent caching
        header('Expires: -1');
        header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');

        /*
         * set appropriate headers for attachment or streamed file
         */
// :BUG: Possible bug, the Content-Disposition: attachment header is already specified in the last line, while with $stream it would be inline?
        if($stream){
            header('Content-Disposition: inline;');

        }else{
            header('Content-Disposition: attachment; filename="'.$file_name.'"');
        }

        // set the mime type based on extension, add yours if needed.
        $ctype_default = 'application/octet-stream';
        $content_types = array('exe' => 'application/octet-stream',
                               'zip' => 'application/zip',
                               'mp3' => 'audio/mpeg',
                               'mpg' => 'video/mpeg',
                               'avi' => 'video/x-msvideo');

        $ctype = isset($content_types[$file_ext]) ? $content_types[$file_ext] : $ctype_default;
        header("Content-Type: ".$ctype);

        //check if http_range is sent by browser (or download manager)
        if(isset($_SERVER['HTTP_RANGE'])){
            list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if($size_unit == 'bytes'){
                /*
                 * multiple ranges could be specified at the same time, but for simplicity only serve the first range
                 * http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                 */
                list($range, $extra_ranges) = explode(',', $range_orig, 2);

            }else{
                $range = '';
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                throw new bException('file_http_download(): Unknown size_unit "'.str_log($size_unit).'" specified, please ensure its "bytes"', 'filenotexist');
            }

        }else{
            $range = '';
        }

        //figure out download piece from range (if set)
        list($seek_start, $seek_end) = explode('-', $range, 2);

        //set start and end based on range (if set), else set defaults
        //also check for invalid ranges.
        $seek_end   = (empty($seek_end)) ? ($file_size - 1) : min(abs(intval($seek_end)), ($file_size - 1));
        $seek_start = (empty($seek_start) or ($seek_end < abs(intval($seek_start)))) ? 0 : max(abs(intval($seek_start)), 0);

        //Only send partial content header if downloading a piece of the file (IE workaround)
        if($seek_start or ($seek_end < ($file_size - 1))){
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
            header('Content-Length: '.($seek_end - $seek_start + 1));

        }else{
            header('Content-Length: '.$file_size);
        }

        header('Accept-Ranges: bytes');

        set_time_limit(0);
        fseek($file, $seek_start);

        /*
         * Download file to client
         */
        while(!feof($file)){
            print(fread($file, 8912));
            ob_flush();
            flush();

            if(connection_status()){
                fclose($file);
                exit;
            }
        }

        /*
         * file download was a success
         */
        fclose($file);

    }catch(Exception $e){
        throw new bException('file_http_download(): Failed', $e);
    }
}



/*
 * Copy a file with progress notification
 *
 * @example:
 * function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max){
 *     if($notification_code == STREAM_NOTIFY_PROGRESS){
 *         // save $bytes_transferred and $bytes_max to file or database
 *     }
 * }
 *
 * file_copy_progress($source, $target, 'stream_notification_callback');
 */
function file_copy_progress($source, $target, $callback){
    try{
        $c = stream_context_create();
        stream_context_set_params($c, array('notification' => $callback));
        copy($source, $target, $c);

    }catch(Exception $e){
        throw new bException('file_copy_progress(): Failed', $e);
    }
}



/*
 *
 */
function file_mode_readable($mode){
    try{
        $retval = '';
        $mode   = substr((string) decoct($mode), -3, 3);

        for($i = 0; $i < 3; $i++){
            $number = (integer) substr($mode, $i, 1);

            if(($number - 4) >= 0){
                $retval .= 'r';
                $number -= 4;

            }else{
                $retval .= '-';
            }

            if(($number - 2) >= 0){
                $retval .= 'w';
                $number -= 2;

            }else{
                $retval .= '-';
            }

            if(($number - 1) >= 0){
                $retval .= 'x';

            }else{
                $retval .= '-';
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('file_mode_readable(): Failed', $e);
    }
}



/*
 * Calculate either the total size of the tree under the specified path, or the amount of files (directories not included in count)
 * @$method (string) either "size" or "count", the required value to return
 */
function file_tree($path, $method){
    try{
        if(!file_exists($path)){
            throw new bException(tr('file_tree(): Specified path "%path%" does not exist', array('%path%' => str_log($path))), 'not-exist');
        }

        switch($method){
            case 'size':
                // FALLTHROUGH
            case 'count':
                break;

            default:
                throw new bException(tr('file_tree(): Unknown method "%method%" specified', array('%method%' => str_log($method))), 'unknown');
        }

        $retval = 0;
        $path   = slash($path);

        foreach(scandir($path) as $file){
            if(($file == '.') or ($file == '..')) continue;

            if(is_dir($path.$file)){
                $retval += file_tree($path.$file, $method);

            }else{
                switch($method){
                    case 'size':
                        $retval += filesize($path.$file);
                        break;

                    case 'count':
                        $retval++;
                        break;
                }
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('file_tree(): Failed', $e);
    }
}



/*
 *
 */
function file_ensure_writable($path){
    try{
        if(is_writable($path)){
            return false;
        }

        $perms = fileperms($path);

        if(is_dir($path)){
            chmod($path, 0770);

        }else{
            if(is_executable($path)){
                chmod($path, 0770);

            }else{
                chmod($path, 0660);
            }
        }

        return $perms;

    }catch(Exception $e){
        throw new bException('file_ensure_writable(): Failed', $e);
    }
}



/*
 * Returns array with all permission information about the specified file.
 *
 * Idea taken from http://php.net/manual/en/function.fileperms.php
 */
function file_type($file){
    try{
        $perms  = fileperms($file);

        $socket    = (($perms & 0xC000) == 0xC000);
        $symlink   = (($perms & 0xA000) == 0xA000);
        $regular   = (($perms & 0x8000) == 0x8000);
        $bdevice   = (($perms & 0x6000) == 0x6000);
        $cdevice   = (($perms & 0x2000) == 0x2000);
        $directory = (($perms & 0x4000) == 0x4000);
        $fifopipe  = (($perms & 0x1000) == 0x1000);

        if($socket){
            /*
             * This file is a socket
             */
            return 'socket';

        }elseif($symlink){
            /*
             * This file is a symbolic link
             */
            return 'symbolic link';

        }elseif($regular){
            /*
             * This file is a regular file
             */
            return 'regular file';

        }elseif($bdevice){
            /*
             * This file is a block device
             */
            return 'block device';

        }elseif($directory){
            /*
             * This file is a directory
             */
            return 'directory';

        }elseif($cdevice){
            /*
             * This file is a character device
             */
            return 'character device';

        }elseif($fifopipe){
            /*
             * This file is a FIFO pipe
             */
            return 'fifo pipe';
        }

        /*
         * This file is an unknown type
         */
        return 'unknown';

    }catch(Exception $e){
        throw new bException(tr('file_type(): Failed for file ":file"', array(':file' => $file)), $e);
    }
}



/*
 * Returns array with all permission information about the specified file.
 *
 * Idea taken from http://php.net/manual/en/function.fileperms.php
 */
function file_get_permissions($file){
    try{
        $perms  = fileperms($file);
        $retval = array();

        $retval['socket']    = (($perms & 0xC000) == 0xC000);
        $retval['symlink']   = (($perms & 0xA000) == 0xA000);
        $retval['regular']   = (($perms & 0x8000) == 0x8000);
        $retval['bdevice']   = (($perms & 0x6000) == 0x6000);
        $retval['cdevice']   = (($perms & 0x2000) == 0x2000);
        $retval['directory'] = (($perms & 0x4000) == 0x4000);
        $retval['fifopipe']  = (($perms & 0x1000) == 0x1000);
        $retval['perms']     = $perms;
        $retval['unknown']   = false;

        if($retval['socket']){
            /*
             * This file is a socket
             */
            $retval['mode'] = 's';
            $retval['type'] = 'socket';

        }elseif($retval['symlink']){
            /*
             * This file is a symbolic link
             */
            $retval['mode'] = 'l';
            $retval['type'] = 'symbolic link';

        }elseif($retval['regular']){
            /*
             * This file is a regular file
             */
            $retval['mode'] = '-';
            $retval['type'] = 'regular file';

        }elseif($retval['bdevice']){
            /*
             * This file is a block device
             */
            $retval['mode'] = 'b';
            $retval['type'] = 'block device';

        }elseif($retval['directory']){
            /*
             * This file is a directory
             */
            $retval['mode'] = 'd';
            $retval['type'] = 'directory';

        }elseif($retval['cdevice']){
            /*
             * This file is a character device
             */
            $retval['mode'] = 'c';
            $retval['type'] = 'character device';

        }elseif($retval['fifopipe']){
            /*
             * This file is a FIFO pipe
             */
            $retval['mode'] = 'p';
            $retval['type'] = 'fifo pipe';

        }else{
            /*
             * This file is an unknown type
             */
            $retval['mode']    = 'u';
            $retval['type']    = 'unknown';
            $retval['unknown'] = true;
        }

        $retval['owner'] = array('r' =>  ($perms & 0x0100),
                                 'w' =>  ($perms & 0x0080),
                                 'x' => (($perms & 0x0040) and !($perms & 0x0800)),
                                 's' => (($perms & 0x0040) and  ($perms & 0x0800)),
                                 'S' =>  ($perms & 0x0800));

        $retval['group'] = array('r' =>  ($perms & 0x0020),
                                 'w' =>  ($perms & 0x0010),
                                 'x' => (($perms & 0x0008) and !($perms & 0x0400)),
                                 's' => (($perms & 0x0008) and  ($perms & 0x0400)),
                                 'S' =>  ($perms & 0x0400));

        $retval['other'] = array('r' =>  ($perms & 0x0004),
                                 'w' =>  ($perms & 0x0002),
                                 'x' => (($perms & 0x0001) and !($perms & 0x0200)),
                                 't' => (($perms & 0x0001) and  ($perms & 0x0200)),
                                 'T' =>  ($perms & 0x0200));

        /*
         * Owner
         */
        $retval['mode'] .= (($perms & 0x0100) ? 'r' : '-');
        $retval['mode'] .= (($perms & 0x0080) ? 'w' : '-');
        $retval['mode'] .= (($perms & 0x0040) ?
                           (($perms & 0x0800) ? 's' : 'x' ) :
                           (($perms & 0x0800) ? 'S' : '-'));

        /*
         * Group
         */
        $retval['mode'] .= (($perms & 0x0020) ? 'r' : '-');
        $retval['mode'] .= (($perms & 0x0010) ? 'w' : '-');
        $retval['mode'] .= (($perms & 0x0008) ?
                           (($perms & 0x0400) ? 's' : 'x' ) :
                           (($perms & 0x0400) ? 'S' : '-'));

        /*
         * World
         */
        $retval['mode'] .= (($perms & 0x0004) ? 'r' : '-');
        $retval['mode'] .= (($perms & 0x0002) ? 'w' : '-');
        $retval['mode'] .= (($perms & 0x0001) ?
                           (($perms & 0x0200) ? 't' : 'x' ) :
                           (($perms & 0x0200) ? 'T' : '-'));

        return $retval;

    }catch(Exception $e){
        throw new bException('file_get_permissions(): Failed', $e);
    }
}



/*
 * Execute the specified callback on all files in the specified tree
 */
function file_tree_execute($params){
    try{
        array_params($params);
        array_default($params, 'ignore_exceptions', true);
        array_default($params, 'path'             , null);
        array_default($params, 'filters'          , null);
        array_default($params, 'follow_symlinks'  , false);
        array_default($params, 'follow_hidden'    , false);
        array_default($params, 'recursive'        , false);
        array_default($params, 'execute_directory', false);
        array_default($params, 'params'           , null);

        /*
         * Validate data
         */
        if(empty($params['callback'])){
            throw new bException(tr('file_tree_execute(): No callback function specified'), 'not-specified');
        }

        if(!is_callable($params['callback'])){
            throw new bException(tr('file_tree_execute(): Specified callback is not a function'), 'invalid');
        }

        if(!($params['path'])){
            throw new bException(tr('file_tree_execute(): No path specified'), 'not-specified');
        }

        if(substr($params['path'], 0, 1) !== '/'){
            throw new bException(tr('file_tree_execute(): No absolute path specified'), 'invalid');
        }

        if(!file_exists($params['path'])){
            throw new bException(tr('file_tree_execute(): Specified path ":path" does not exist', array(':path' => $params['path'])), 'not-exist');
        }

        /*
         * Follow hidden files?
         */
        if((substr(basename($params['path']), 0, 1) == '.') and !$params['follow_hidden']){
            if(VERBOSE and PLATFORM_CLI){
                log_console(tr('file_tree_execute(): Skipping file ":file" because its hidden', array(':file' => $params['path'])), 'yellow');
            }

            return 0;
        }

        /*
         * Filter this path?
         */
        foreach(array_force($params['filters']) as $filter){
            if(preg_match($filter, $params['path'])){
                if(VERBOSE and PLATFORM_CLI){
                    log_console(tr('file_tree_execute(): Skipping file ":file" because of filter ":filter"', array(':file' => $params['path'], ':filter' => $filter)), 'yellow');
                }

                return 0;
            }
        }

        $count = 0;
        $type  = file_type($params['path']);

        switch($type){
            case 'regular file':
                $params['callback']($params['path']);
                $count++;

                if(PLATFORM_CLI){
                    if(VERBOSE){
                        log_console(tr('file_tree_execute(): Executed code for file ":file"', array(':file' => $params['path'])), 'green');

                    }else{
                        cli_dot();
                    }
                }

                break;

            case 'symlink':
                if($params['follow_symlinks']){
                    $params['path'] = readlink($params['path']);
                    $count += file_tree_execute($params);
                }

                break;

            case 'directory':
                $h    = opendir($params['path']);
                $path = slash($params['path']);

                while(($file = readdir($h)) !== false){
                    try{
                        if(($file == '.') or ($file == '..')) continue;

                        if((substr(basename($file), 0, 1) == '.') and !$params['follow_hidden']){
                            if(VERBOSE and PLATFORM_CLI){
                                log_console(tr('file_tree_execute(): Skipping file ":file" because its hidden', array(':file' => $file)), 'yellow');
                            }

                            continue;
                        }

                        $file = $path.$file;

                        if(!file_exists($file)){
                            throw new bException(tr('file_tree_execute(): Specified path ":path" does not exist', array(':path' => $file)), 'not-exist');
                        }

                        $type = file_type($file);

                        switch($type){
                            case 'link':
                                if(!$params['follow_symlinks']){
                                    continue;
                                }

                                $file = readlink($file);

                                /*
                                 * We got the target file, but we don't know what it is.
                                 * Restart the process recursively to process this file
                                 */

                                // FALLTHROUGH

                            case 'directory':
                                // FALLTHROUGH
                            case 'regular file':
                                if(($type != 'directory') or $params['execute_directory']){
                                    /*
                                     * Filter this path?
                                     */
                                    $skip = false;

                                    foreach(array_force($params['filters']) as $filter){
                                        if(preg_match($filter, $file)){
                                            if(VERBOSE and PLATFORM_CLI){
                                                log_console(tr('file_tree_execute(): Skipping file ":file" because of filter ":filter"', array(':file' => $params['path'], ':filter' => $filter)), 'yellow');
                                            }

                                            $skip = true;
                                        }
                                    }

                                    if(!$skip){
                                        $result = $params['callback']($file, $type, $params['params']);
                                        $count++;

                                        if($result === false){
                                            /*
                                             * When the callback returned boolean false, cancel all other files
                                             */
                                            log_console(tr('file_tree_execute(): callback returned FALSE for file ":file", skipping rest of directory contents!', array(':file' => $file)), 'yellow');
                                            goto end;
                                        }

                                        if(PLATFORM_CLI){
                                            if(VERBOSE){
                                                log_console(tr('file_tree_execute(): Executed code for file ":file"', array(':file' => $file)), 'green');

                                            }else{
                                                cli_dot();
                                            }
                                        }
                                    }
                                }

                                if(($type == 'directory') and $params['recursive']){
                                    $params['path'] = $file;
                                    $count         += file_tree_execute($params);
                                }

                                break;

                            default:
                                /*
                                 * Skip this unsupported file type
                                 */
                                if(VERBOSE and PLATFORM_CLI){
                                    log_console(tr('file_tree_execute(): Skipping file ":file" with unsupported file type ":type"', array(':file' => $file, ':type' => $type)), 'yellow');
                                }
                        }

                    }catch(Exception $e){
                        if(!$params['ignore_exceptions']){
                            throw $e;
                        }

                        if($e->getCode() === 'file-not-exist'){
                            if(VERBOSE and PLATFORM_CLI){
                                log_console(tr('file_tree_execute(): Skipping file ":file", it does not exist (in case of a symlink, it may be that the target does not exist)', array(':file' => $file)), 'yellow');
                            }

                        }else{
                            log_console($e);
                        }
                    }
                }

                end:
                closedir($h);

                break;

            default:
                /*
                 * Skip this unsupported file type
                 */
                if(VERBOSE and PLATFORM_CLI){
                    log_console(tr('file_tree_execute(): Skipping file ":file" with unsupported file type ":type"', array(':file' => $file, ':type' => $params['path'])), 'yellow');
                }
        }

        return $count;

    }catch(Exception $e){
        throw new bException('file_tree_execute(): Failed', $e);
    }
}



/*
 * If specified path is not absolute, then return a path that is sure to start
 * from the current working directory
 */
function file_absolute($path, $root = null){
    try{
        if(empty($root)){
            $root = slash(getcwd());
        }

        if(substr($path, 0, 1) !== '/'){
            $path = $root.$path;
        }

        return $path;

    }catch(Exception $e){
        throw new bException('file_absolute(): Failed', $e);
    }
}



/*
 * If specified path is not absolute, then return a path that is sure to start
 * within ROOT
 */
function file_root($path){
    try{
        if(substr($path, 0, 1) !== '/'){
            $path = ROOT.$path;
        }

        return $path;

    }catch(Exception $e){
        throw new bException('file_root(): Failed', $e);
    }
}



/*
 * Execute the specified callback after setting the specified mode on the
 * specified path. Once the callback has finished, return to the original file
 * mode.
 *
 * NOTE: When params are specified, they are specified BEFORE the anonymous
 * function!!!
 *
 * This means that if NO params are specified, the function signature is this
 * file_execute_mode($path, $mode, $callback)
 *
 * When params ARE specified, the function signature is like this:
 * file_execute_mode($path, $mode, $params, $callback)
 */
function file_execute_mode($path, $mode, $callback, $params = null){
    try{
        if(!file_exists($path)){
            throw new bException(tr('file_execute_mode(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exist');
        }

        if($params){
            /*
             * Switch parameters
             */
            $tmp      = $params;
            $params   = $callback;
            $callback = $tmp;
        }

        if($mode){
            $original_mode = fileperms($path);
            chmod($path, $mode);
        }

        $path   = slash($path);
        $retval = $callback($params, $path, $mode);

        if($mode){
            chmod($path, $original_mode);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('file_execute_mode(): Failed for path ":path"', array(':path' => $path)), $e);
    }
}



/*
 *
 */
function file_link_exists($file){
    if(file_exists($file)){
        return true;
    }

    if(is_link($file)){
        throw new bException(tr('file_link_exists(): Symlink ":source" has non existing target ":target"', array(':source' => $file, ':target' => readlink($file))), 'no-target');
    }

    throw new bException(tr('file_link_exists(): Symlink ":source" has non existing target ":target"', array(':source' => $file, ':target' => readlink($file))), 'not-exist');
}



/*
 * Open the specified source, read the contents, and replace $search with $replace. Write results in $target
 * $replaces should be a $search => $replace key value array, where the $search values are regex expressions
 */
function file_search_replace($source, $target, $replaces){
    try{
        if(!file_exists($source)){
            throw new bException(tr('file_search_replace(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exist');
        }

        if(!file_exists(dirname($target))){
            throw new bException(tr('file_search_replace(): Specified target path ":targetg" does not exist', array(':target' => $target)), 'not-exist');
        }

        if(!is_array($replaces)){
            throw new bException(tr('file_search_replace(): Specified $replaces ":replaces" should be a search => replace array', array(':replaces' => $replaces)), 'invalid');
        }

        $fs       = fopen($source, 'r');
        $ft       = fopen($target, 'w');

        $position = 0;
        $length   = 8192;
        $filesize = filesize($source);

        while($position < $filesize){
             $data      = fread($fs, $length);
             $position += $length;
             fseek($fs, $position);

             /*
              * Execute search / replaces
              */
             foreach($replaces as $search => $replace){
                $data = preg_replace($search, $replace, $data);
             }

             fwrite($ft, $data, strlen($data));
        }

        fclose($fs);
        fclose($ft);

    }catch(Exception $e){
        throw new bException('file_search_replace(): Failed', $e);
    }
}



/*
 * Return line count for this file
 */
function file_line_count($source){
    try{
        if(!file_exists($source)){
            throw new bException(tr('file_line_count(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exist');
        }

    }catch(Exception $e){
        throw new bException('file_line_count(): Failed', $e);
    }
}



/*
 * Return word count for this file
 */
function file_word_count($source){
    try{
        if(!file_exists($source)){
            throw new bException(tr('file_word_count(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exist');
        }

    }catch(Exception $e){
        throw new bException('file_word_count(): Failed', $e);
    }
}



/*
 * Scan the entire file path upward for the specified file.
 * If the specified file doesn't exist in the specified path, go one dir up,
 * all the way to root /
 */
function file_scan($path, $file){
    try{
        if(!file_exists($path)){
            throw new bException(tr('file_scan(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exist');
        }

        while(strlen($path) > 1){
            $path = slash($path);

            if(file_exists($path.$file)){
                /*
                 * The requested file is found! Return the path where it was found
                 */
                return $path;
            }

            $path = dirname($path);
        }

        return false;

    }catch(Exception $e){
        throw new bException('file_word_count(): Failed', $e);
    }
}



/*
 * Move specified path to a backup
 */
function file_move_to_backup($path){
    try{
        if(!file_exists($path)){
            /*
             * Specified path doesn't exist, just ignore
             */
            return false;
        }

        $backup_path = $path.'~'.date_convert(null, 'Ymd-His');

        /*
         * Main sitemap file already exist, move to backup
         */
        if(file_exists($backup_path)){
            /*
             * Backup already exists as well, script run twice
             * in under a second. Delete the current one
             * as the backup was generated less than a second
             * ago
             */
            file_delete($path);
            return true;
        }

        rename($path, $backup_path);
        return true;

    }catch(Exception $e){
        throw new bException('file_move_to_backup(): Failed', $e);
    }
}



/*
 * Update the specified file owner and group
 */
function file_chown($file, $user = null, $group = null){
    try{
        if(!$user){
             $user = posix_getpwuid(posix_getuid());
             $user = $user['name'];
        }

        if(!$group){
             $group = posix_getpwuid(posix_getuid());
             $group = $group['name'];
        }

        $file = realpath($file);

        if(!$file){
            throw new bException(tr('file_chown(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exist');
        }

        if(!strstr($file, ROOT)){
            throw new bException(tr('file_chown(): Specified file ":file" is not in the projects ROOT path ":path"', array(':path' => $path, ':file' => $file)), 'invalid');
        }

        safe_exec('sudo chown '.$user.':'.$group.' '.$file);

    }catch(Exception $e){
        throw new bException('file_chown(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 *
 * @param string $path
 * @param string $prefix
 * @return boolean True if the specified $path (optionally prefixed by $prefix) contains a symlink, false if not
 */
function file_path_contains_symlink($path, $prefix = null){
    try{
        if(!$path){
            throw new bException(tr('file_path_contains_symlink(): No path specified'), 'not-specified');
        }

        if(substr($path, 0, 1) === '/'){
            if($prefix){
                throw new bException(tr('file_path_contains_symlink(): The specified path ":path" is absolute, which requires $prefix to be null, but it is ":prefix"', array(':path' => $path, ':prefix' => $prefix)), 'invalid');
            }

            $location = '/';

        }else{
            /*
             * Specified $path is relative, so prefix it with $prefix
             */
            if(substr($prefix, 0, 1) !== '/'){
                throw new bException(tr('file_path_contains_symlink(): The specified path ":path" is relative, which requires an absolute $prefix but it is ":prefix"', array(':path' => $path, ':prefix' => $prefix)), 'invalid');
            }

            $location = str_ends($prefix, '/');
        }

        $path = str_ends_not(str_starts_not($path, '/'), '/');

        foreach(explode('/', $path) as $section){
            $location .= $section;

            if(!file_exists($location)){
                throw new bException(tr('file_path_contains_symlink(): The specified path ":path" with prefix ":prefix" leads to ":location" which does not exist', array(':path' => $path, ':prefix' => $prefix, ':location' => $location)), 'not-exists');
            }

            if(is_link($location)){
                return true;
            }

            $location .= '/';
        }

        return false;

    }catch(Exception $e){
        throw new bException('file_path_contains_symlink(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @see https://secure.php.net/manual/en/migration56.openssl.php
 * @see https://secure.php.net/manual/en/function.stream-context-create.php
 * @see https://secure.php.net/manual/en/wrappers.php
 * @see https://secure.php.net/manual/en/context.php
 *
 * @param array $context The stream context
 * @param string $prefix
 * @return boolean True if the specified $path (optionally prefixed by $prefix) contains a symlink, false if not
 */
function file_create_stream_context($context){
    try{
        if(!$context) return null;

        if(!is_array($context)){
            throw new bException(tr('file_create_stream_context(): Specified context is invalid, should be an array but is an ":type"', array(':type' => gettype($context))), 'invalid');
        }

        return stream_context_create($context);

    }catch(Exception $e){
        throw new bException('file_create_stream_context(): Failed', $e);
    }
}
?>
