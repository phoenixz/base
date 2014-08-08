<?php
/*
 * File library containing all kinds of filesystem related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



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
                throw new lsException('file_move_uploaded(): Invalid source specified, must either be a string containing an absolute file path or a PHP $_FILES entry', 'invalid');
            }

            $real   = $source['name'];
            $source = $source['tmp_name'];

        }else{
            $real   = basename($source);
        }


        file_is($source);
        file_ensure_path($destination);

        /*
         * Ensure we're not overwriting anything!
         */
        if(file_exists($destination.$real)){
            $real = str_runtil($real, '.').'_'.substr(uniqid(), -8, 8).'.'.str_rfrom($real, '.');
        }

        if(!move_uploaded_file($source, $destination.$real)){
            throw new lsException('file_move_uploaded(): Faield to move file "'.str_log($source).'" to destination "'.str_log($destination).'"', 'move');
        }

        /*
         * Return destination file
         */
        return $destination.$real;

    }catch(Exception $e){
        throw new lsException('file_move_uploaded(): Failed', $e);
    }
}



/*
 * Create a target, but don't put anything in it
 */
function file_assign_target($path, $extension = false, $singledir = false, $length = 4) {
    try{
        return file_move_to_target('', $path, $extension, $singledir, $length);

    }catch(Exception $e){
        throw new lsException('file_assign_target(): Failed', $e);
    }
}



/*
 * Create a target, but don't put anything in it, and return path+filename without extension
 */
function file_assign_target_clean($path, $extension = false, $singledir = false, $length = 4) {
    try{
        return str_replace($extension, '', file_move_to_target('', $path, $extension, $singledir, $length));

    }catch(Exception $e){
        throw new lsException('file_assign_target_clean(): Failed', $e);
    }
}



/*
 * Copy specified file, see file_move_to_target for implementation
 */
function file_copy_to_target($file, $path, $extension = false, $singledir = false, $length = 4) {
    try{
        if(is_array($file)){
            throw new lsException('file_copy_to_target(): Specified file "'.str_log($file).'" is an uploaded file, and uploaded files cannot be copied, only moved');
        }

        return file_move_to_target($file, $path, $extension, $singledir, $length, true);

    }catch(Exception $e){
        throw new lsException('file_copy_to_target(): Failed', $e);
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
 */
function file_move_to_target($file, $path, $extension = false, $singledir = false, $length = 4, $copy = false) {
    try{
        if(is_array($file)){
            $upload = $file;
            $file   = $file['name'];
        }

        if(isset($upload) and $copy){
            throw new lsException('file_move_to_target(): Copy option has been set, but specified file "'.str_log($file).'" is an uploaded file, and uploaded files cannot be copied, only moved');
        }

        $path     = file_ensure_path($path);
        $filename = basename($file);

        if(!$filename) {
            /*
             * We always MUST have a filename
             */
            $filename = uniqid();
        }

        /*
         * Ensure we have a local copy of the file to work with
         */
        if($file){
            $file = file_get_local($file);
        }

        if(!$extension) {
            $extension = file_get_extension($filename);
        }

        $targetpath = slash(file_create_target_path($path, $singledir, $length));
        $target     = $targetpath.strtolower(str_convert_accents(str_runtil($filename, '.'), '-').($extension ? $extension : ''));

        /*
         * Only move file is target does not yet exist
         */
        if(file_exists($target)){
            if(isset($upload)){
                /*
                 * File was specified as an upload array
                 */
                return file_move_to_target($upload, $path, $extension, $singledir, $length);
            }

            return file_move_to_target($file, $path, $extension, $singledir, $length);
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
                if($copy){
                    copy($file, $target);

                }else{
                    rename($file, $target);
                }
            }
        }

        return str_from($target, $path);

    }catch(Exception $e){
        throw new lsException('file_move_to_target(): Failed', $e);
    }
}



/*
 * Creates a random path in specified base path (If it does not exist yet), and returns that path
 */
function file_create_target_path($path, $singledir = false, $length = false) {
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
        throw new lsException('file_create_target_path(): Failed', $e);
    }
}



/*
 * Ensure that the specified file exists in the specified path
 */
function file_ensure_file($file, $mode = null) {
    try{
        file_ensure_path(dirname($file));

        if(!file_exists($file)){
            /*
             * Create the file
             */
            log_console('file_ensure_file(): Warning: file "'.str_log($file).'" did not existed and was created empty to ensure system stability, but information may be missing', 'filenotexists', 'yellow');
            touch($file);

            if($mode){
                chmod($file, $mode);
            }
        }

        return $file;

    }catch(Exception $e){
        throw new lsException('file_ensure_file(): Failed', $e);
    }
}



/*
 * Ensures existence of specified path
 */
function file_ensure_path($path, $mode = null) {
    global $_CONFIG;

    try{
        if($mode === null){
            $mode = $_CONFIG['fs']['dir_mode'];
        }

        if(!file_exists($path)){
            mkdir($path, $mode, true);

        }elseif(!is_dir($path)){
            throw new lsException('file_ensure_path(): Specified "'.$path.'" is not a directory');
        }

        if(!$realpath = realpath($path)){
            throw new lsException('file_ensure_path(): realpath() failed for "'.$path.'"');
        }

        return slash($realpath);

    }catch(Exception $e){
        throw new lsException('file_ensure_path(): Failed to ensure path "'.str_log($path).'"', $e);
    }
}



/*
 * Delete the path until directory is no longer empty
 */
function file_clear_path($path) {
    try{
        if(!file_exists($path)){
            return false;
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

            while($file = readdir($h)){
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
                rmdir($path);

            }catch(Exception $e){
                /*
                 * The directory WAS empty, but cannot be removed
                 *
                 * In all probability, a parrallel process added a new content
                 * in this directory, so it's no longer empty. Just register
                 * the event and leave it be.
                 */
                log_error('file_clear_path(): Failed to remove empty path "'.$path.'", probably a parrallel process added new content here?', 'failed');
                return true;
            }
        }

        /*
         * Go one entry up and continue
         */
        $path = str_runtil(unslash($path), '/');
        file_clear_path($path);

    }catch(Exception $e){
        throw new lsException('file_clear_path(): Failed', $e);
    }
}



/*
 * Return the extension of the specified filename
 */
function file_get_extension($filename){
    try{
        return str_rfrom($filename, '.');

    }catch(Exception $e){
        throw new lsException('file_get_extension(): Failed', $e);
    }
}



/*
 * Return a temporary filename for the specified file in the specified path
 */
function file_temp($path = '', $filename = '', $usepid = true){
    global $_CONFIG;

    try{
        $base = str_until($filename, '.');
        $ext  = str_from ($filename, '.');

        file_ensure_path($path = TMP.slash(($usepid ? getmypid().'_' : '').$path));

        return tempnam($path, ($base ? $base.'_' : '')).($ext ? '.'.$ext : '');

    }catch(Exception $e){
        throw new lsException('file_temp(): Failed', $e);
    }
}



/*
 * Tree delete
 *
 * Kindly taken from http://lixlpixel.org/recursive_function/php/recursive_directory_delete/
 * Slightly rewritten and cleaned up by Sven Oostenbrink
 */
function file_delete_tree($directory, $empty = false){
    try{
        $directory = unslash($directory);

        if(!file_exists($directory) and !is_link($directory)){
            if(!file_exists(dirname($directory))){
                throw new lsException('file_delete_tree(): Specified directory "'.str_log($directory).'" does not exist');
            }

            /*
             * The path itself no (longer) exists, but the parent does. Maybe it was already deleted,
             * but the situation now is exactly how this function is supposed to leave it behind, so
             * we're okay and done!
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
                    unlink($path);
                }
            }
        }

        closedir($handle);

        if(!$empty){
            if(!rmdir($directory)){
                throw new lsException('file_delete_tree(): Specified path "'.str_log($directory).'" could not be deleted');
            }
        }

    }catch(Exception $e){
        throw new lsException('file_delete_tree(): Failed', $e);
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
        file_is($file);

        if(!$finfo){
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        }

        return finfo_file($finfo, $file);

    }catch(Exception $e){
        throw new lsException('file_mimetype(): Failed', $e);
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
        throw new lsException('file_is_text(): Failed', $e);
    }
}



/*
 * Returns if the specified file exists and is a file
 */
function file_is($file){
    if(!file_exists($file)){
        throw new lsException('file_is(): Specified file "'.str_log($file).'" does not exist', 'notexist');
    }

    if(!is_file($file)){
        throw new lsException('file_is(): Specified file "'.str_log($file).'" is not a file' , 'notafile');
    }
}



/*
 * Return all files in a directory
 */
function file_list_tree($path = '.', $recursive = true) {
    try{
        if(!is_dir($path)) {
        throw new lsException('file_list_tree(): Specified path "'.str_log($path).'" is not a directory', 'path');
        }

        $files = array();
        $fh    = opendir($path);

        while (($file = readdir($fh)) !== false) {
            # loop through the files, skipping . and .., and recursing if necessary
            if(($file == '.') or ($file == '..')){
                continue;
            }

            $filepath = $path . '/' . $file;

            if( is_dir($filepath) and $recursive){
                $files = array_merge($files, file_list_tree($filepath));

            } else {
                array_push($files, $filepath);
            }
        }

        closedir($fh);

        return $files;

    }catch(Exception $e){
        throw new lsException('file_list_tree(): Failed for "'.str_log($path).'"', $e);
    }
}



/*
 * Delete a file, weather it exists or not, without error
 */
// :SECURITY: $pattern is NOT checked!!
function file_delete($pattern){
    try{
        if(!$pattern){
            throw new lsException('file_delete(): No file or pattern specified');
        }

        safe_exec('rm '.$pattern.' -rf');

        return $pattern;

    }catch(Exception $e){
        throw new lsException('file_delete(): Failed', $e);
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
                    throw new lsException('file_copy_tree(): The search parameters count "'.count($search).'" and replace parameters count "'.count($destination).'" do not match', 'parameternomatch');
                }
            }

            if(!file_exists($source)){
                throw new lsException('file_copy_tree(): Specified source "'.str_log($source).'" does not exist', 'sourcenoexist');
            }

            $destination = unslash($destination);

            if(!file_exists($destination)){
                if(!file_exists(dirname($destination))){
                    throw new lsException('file_copy_tree(): Specified destination "'.str_log(dirname($destination)).'" does not exist', 'destinationnoexist');
                }

                if(!is_dir(dirname($destination))){
                    throw new lsException('file_copy_tree(): Specified destination "'.str_log(dirname($destination)).'" is not a directory', 'notadirectory');
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
                        throw new lsException('file_copy_tree(): Cannot copy source directory "'.str_log($source).'" into destination file "'.str_log($destination).'"');
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
                    log_message('file_copy_tree(): Encountered dead symlink "'.$source.'", copying anyway...', 'warning');
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
                    throw new lsException('file_copy_tree(): Failed to set filemode for "'.$destination.'"', $e);
                }
            }
        }

        return $destination;

    }catch(Exception $e){
        throw new lsException('file_copy_tree(): Failed', $e);
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
            throw new lsException('file_rename(): Specified source "'.str_log($source).'" does not exist', 'sourcenoexist');
        }

        if(!file_exists($destination)){
            throw new lsException('file_rename(): Specified destination "'.str_log($destination).'" does not exist', 'destinationnoexist');
        }

        if(!is_dir($destination)){
            throw new lsException('file_rename(): Specified destination "'.str_log(dirname($destination)).'" is not a directory', 'destinationnotdirectory');
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
        throw new lsException('file_rename(): Failed', $e);
    }
}



/*
 * Create temporary directory (sister function from tempnam)
 */
function file_temp_dir($prefix = '', $system = null, $mode = null) {
    global $_CONFIG;

    try{
        /*
         * Use default configged mode, or specific mode?
         */
        if($mode === null){
            $mode = $_CONFIG['fs']['dir_mode'];
        }

        /*
         * Use default configged location, or specific one?
         */
        if($system === null){
            $system = $_CONFIG['fs']['system_tempdir'];
        }

        /*
         * Determine the base directory
         */
        if($system){
            if(is_bool($system)){
                /*
                 * Use system tmp dir (on linux, always /tmp)
                 */
                $path = slash(sys_get_temp_dir());

            }else{
                /*
                 * Use specific
                 */
                $path = slash($system);
            }

        }else{
            /*
             * Use project tmp path
             * (This might be VERY useful if for example the project install is on a different mount than the system /tmp!)
             */
            file_ensure_path($path = TMP);
        }

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
        throw new lsException('file_tempdir(): Failed', $e);
    }
}



/*
 * chmod an entire directory, recursively
 * Copied from http://www.php.net/manual/en/function.chmod.php#84273
 */
function file_chmod_directory($path, $filemode, $dirmode = 0770) {
    file_chmod_tree($path, $filemode, $dirmode);
}

function file_chmod_tree($path, $filemode, $dirmode = 0770) {
    try{
        if(!is_dir($path)){
            return chmod($path, $filemode);
        }

        $dh = opendir($path);

        while (($file = readdir($dh)) !== false) {
            if(($file != '.') or ($file != '..')) continue;

            $fullpath = $path.'/'.$file;

            if(is_link($fullpath)){
                /*
                 * This is a link. ignore it.
                 */

            }elseif(!is_dir($fullpath)){
                if(!chmod($fullpath, $filemode)){
                    throw new lsException('file_chmod_tree(): Failed to chmod file "'.str_log($fullpath).'" to mode "'.str_log($filemode).'"', 'failed');
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
            throw new lsException('file_chmod_tree(): Failed to chmod directory "'.str_log($path).'" to mode "'.str_log($dirmode).'"', 'failed');
        }

        return true;

    }catch(Exception $e){
        throw new lsException('file_chmod_tree(): Failed', $e);
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
function file_get_local($file){
    try{
        $file = trim($file);

        if((stripos($file, 'http:') === false) and (stripos($file, 'https:') === false) and (stripos($file, 'ftp:') === false)){
            if(!file_exists($file)){
                throw new lsException('file_get_local(): Specified file "'.str_log($file).'" does not exist');
            }

            return $file;
        }

        /*
         * First download the file to a temporary location
         */
        $orgfile = $file;
        $file    = file_temp('', $file);

        file_ensure_path(dirname($file));
        file_put_contents($file, file_get_contents($orgfile));

        return $file;

    }catch(Exception $e){
        throw new lsException('file_get_local(): Failed for file "'.str_log($file).'"', $e);
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
            return '/pub/img'.(SUBENVIRONMENT ? '/'.SUBENVIRONMENT : '').'/'.$path;

        case 'css':
            // FALLTHROUGH
        case 'style':
            return '/pub/css'.(SUBENVIRONMENT ? '/'.SUBENVIRONMENT : '').'/'.$path;

        default:
            throw new lsException('file_system_path(): Unknown type "'.str_log($type).'" specified', 'unknown');
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
            throw new lsException('file_random(): The specified path "'.str_log($path).'" does not exist', 'notexists');
        }

        if(!file_exists($path)){
            throw new lsException('file_random(): The specified path "'.str_log($path).'" does not exist', 'notexists');
        }

        $files = scandir($path);

        unset($files[array_search('.' , $files)]);
        unset($files[array_search('..', $files)]);

        if(!$files){
            throw new lsException('file_random(): The specified path "'.str_log($path).'" contains no files', 'notexists');
        }

        return slash($path).array_get_random($files);

    }catch(Exception $e){
        throw new lsException('file_random(): Failed', $e);
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
        throw new lsException('file_session_store(): Failed', $e);
    }
}



/*
 * Checks if the specified path exists, is a dir, and optionally, if its writable or not
 */
function file_check_dir($path, $writable = false){
    try{
        if(!file_exists($path)){
            throw new lsException('file_check_dir(): The specified path "'.str_log($path).'" does not exist', 'notexists');
        }

        if(!is_dir($path)){
            throw new lsException('file_check_dir(): The specified path "'.str_log($path).'" is not a directory', 'notadirectory');
        }

        if($writable and !is_writable($path)){
            throw new lsException('file_check_dir(): The specified path "'.str_log($path).'" is not writable', 'notwritable');
        }

    }catch(Exception $e){
        throw new lsException('file_check_dir(): Failed', $e);
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
            throw new lsException('file_http_download(): No file specified', '');
        }

        // make sure the file exists
        if(file_exists($file)){
            header('HTTP/1.0 404 Not Found');
            throw new lsException('file_http_download(): Specified file "'.str_log($file).'" does not exist or is not accessible', 'notexist');
        }

        // make sure the file can be opened
        if(is_readable($file)){
            header('HTTP/1.0 500 Internal Server Error');
            throw new lsException('file_http_download(): Specified file "'.str_log($file).'" exists but is not readable', 'notreadable');
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
            throw new lsException('file_http_download(): Specified file "'.str_log($file).'" failed to be opened', 'fileopenfailed');
        }

        // set the headers, prevent caching
        header('Pragma: public');
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
                throw new lsException('file_http_download(): Unknown size_unit "'.str_log($size_unit).'" specified, please ensure its "bytes"', 'filenotexist');
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
        throw new lsException('file_http_download(): Failed', $e);
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
        throw new lsException('', $e);
    }
}



/*
 * Below are obsolete wrapper functions, that should no longer be used
 */
function listdir($path = '.', $recursive = true) {
    return file_list_tree($path, $recursive);
}
function tempdir($prefix = '', $system = null, $mode = null){
    return file_temp_dir($prefix, $system, $mode);
}
?>
