<?php
/*
 * mbox library
 *
 * This library contains functions to manage mbox type email systems
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @return void
 */
function mbox_library_init(){
    try{
        load_config('mbox');

    }catch(Exception $e){
        throw new bException('mbox_library_init(): Failed', $e);
    }
}



/*
 * Import an mbox file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mbox
 *
 * @param
 * @return
 */
function mbox_import_file($domain, $user, $file, $box = 'Archives', $mail_path = ''){
    try{
        $path  = mbox_test_access($mail_path);
        $path .= $path.'vhosts/'.$domain.'/'.$user.'/mail/';

        file_ensure_path($path);

        if(file_exists($path.$box)){
            /*
             * We need to concat these files together
             */
            safe_exec('cat '.$file.' '.$path.$box.' > '.$path.$box.'~ ');
            file_delete($path.$box);
            rename($path.$box.'~ ', $path.$box);

        }else{
            /*
             * Just drop the file in place
             */
            rename($file, $path.$box);
        }

    }catch(Exception $e){
        throw new bException('mbox_import_file(): Failed', $e);
    }
}



/*
 * Convert a maildir path to an mbox file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mbox
 *
 * @param string $path
 * @return string The converted mbox file
 */
function mbox_convert_maildir($maildir_path, $box, $mail_path){
    try{
        $path  = mbox_test_access($mail_path);
        $path .= $path.'vhosts/'.$domain.'/'.$user.'/mail/';
        safe_exec(ROOT.'scripts/md2mb.py '.$path.' ');

    }catch(Exception $e){
        throw new bException('mbox_convert_maildir(): Failed', $e);
    }
}



/*
 * Tests access to the configured or specified mail directory
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mbox
 *
 * @param string $path
 * @return string The converted mbox file
 */
function mbox_test_access($path){
    global $_CONFIG;

    try{
        if(!$path){
            $path = $_CONFIG['mbox']['path'];
        }

        $path = slash($path);
        load_libs('file');

        if(!file_exists($path)){
            throw new bException(tr('mbox_test_access(): The configured (or specified) mail directory ":path" does not exist. Please check the configuration option $_CONFIG[mbox][path]', array(':path' => $path)), 'not-exists');
        }

        if(file_exists($path.'base-test')){
            file_delete($path.'base-test');
        }

        touch($path.'base-test');
        file_delete($path.'base-test');

        return $path;

    }catch(Exception $e){
        throw new bException('mbox_test_access(): Failed', $e);
    }
}
?>
