<?php
/*
 * INSTALL library
 *
 * This is the auto library installation library. This library contains the
 * required functions to automatically, on the fly, install libraries if they
 * are missing
 *
 * USAGE EXAMPLE
 *
  <?php
 /*
  * FOOBAR library
  *
  * This is an example for a library using the auto installer
  * /


  foobar_init();

 /*
  * Load the foobar library and its CSS requirements
  * /
 function foobar_init(){
     try{
         ensure_installed(array('name'      => 'foobar',
                                'project'   => 'foobar',
                                'callback'  => 'foobar_install',
                                'checks'    => array(ROOT.'pub/js/foobar/foobar.js',
                                                     ROOT.'pub/css/foobar/foobar.css')));

         load_config('foobar');
         html_load_js('foobar/foobar.js');
         html_load_css('foobar/foobar.css');

     }catch(Exception $e){
         throw new bException('foobar_load(): Failed', $e);
     }
 }



 /*
  * Install the foobar library
  * /
 function foobar_install($params){
     try{
         $params['methods'] = array('bower'    => array('command'   => 'npm install foobar',
                                                       'locations' => array('foobar-master/lib/foobar.js' => ROOT.'pub/js/foobar/foobar.js',
                                                                            'foobar-master/lib/modules'   => ROOT.'pub/js/foobar/modules',
                                                                            'foobar-master/themes'        => ROOT.'pub/css/foobar/themes',
                                                                            '@themes/google/google.css'   => ROOT.'pub/css/foobar/foobar.css')),

                                    'bower'    => array('command'   => 'bower install foobar',
                                                        'locations' => array('foobar-master/lib/foobar.js' => ROOT.'pub/js/foobar/foobar.js',
                                                                             'foobar-master/lib/modules'   => ROOT.'pub/js/foobar/modules',
                                                                             'foobar-master/themes'        => ROOT.'pub/css/foobar/themes',
                                                                             '@themes/google/google.css'   => ROOT.'pub/css/foobar/foobar.css')),

                                    'download' => array('url'       => 'https://github.com/foobar/archive/master.zip',
                                                        'command'   => 'unzip master.zip',
                                                        'locations' => array('foobar-master/lib/foobar.js' => ROOT.'pub/js/foobar/foobar.js',
                                                                             'foobar-master/lib/modules'   => ROOT.'pub/js/foobar/modules',
                                                                             'foobar-master/themes'        => ROOT.'pub/css/foobar/themes',
                                                                             '@themes/google/google.css'   => ROOT.'pub/css/foobar/foobar.css')));

         return install($params);

     }catch(Exception $e){
         throw new bException('foobar_install(): Failed', $e);
     }
 }

 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
*/



/*
 * Install
 */
function install($params, $force = false){
    try{
        array_default($params, 'method'   , null); // download, npm, bower
        array_default($params, 'methods'  , null); // 'download,npm,bower'
        array_default($params, 'name'     , null);
        array_default($params, 'project'  , null);
        array_default($params, 'force'    , $force);

        load_libs('file');
        file_ensure_path(ROOT.'pub/vendor');

        if(!$params['name']){
            throw new bException(tr('install(): No name specified for library'), 'not-specified');
        }

        if(!$params['project']){
            throw new bException(tr('install(): No name specified for library ":name"', array(':name' => $params['name'])), 'not-specified');
        }

        if(empty($params['test'])){
            if(empty($params['method'])){
                if(!$params['methods']){
                    throw new bException(tr('install(): No installation method specified for library ":name"', array(':name' => $params['name'])), 'not-specified');
                }

                $params['method'] = $params['methods'];
                unset($params['methods']);

            }else{
                if(!empty($params['methods'])){
                    /*
                     * Can't have that!
                     */
                    throw new bException(tr('install(): Both "method" and "methods" specified specified for library ":name"', array(':name' => $params['name'])), 'invalid');
                }
            }
        }

        if(!$params['checks']){
            throw new bException(tr('install(): No checks specified for library with checks":checks"', array(':checks' => $params['checks'])), 'not-specified');
        }

        foreach(array_force($params['checks']) as $path){
            if(!file_exists($path)){
                $fail = $path;
            }
        }

        if(empty($fail)){
            /*
             * This library is already installed according to the checks!
             */
            if(!empty($params['test'])){
                log_database(tr('Library ":name" successfully installed, passed checks', array(':name' => $params['name'])), 'install');
            }

            return false;

        }elseif(!empty($params['test'])){
            /*
             * Oops, we just installed library $test, but it failed!
             * So installation failed, but perhaps we still have another
             * installation method?
             */
            if($params['method']){
                /*
                 * Yes, we still have alternative installation methods
                 * available, try those first!
                 */
                log_database(tr('Library ":name" failed to install, check ":check" failed. Retrying with different methods', array(':name' => $params['name'], ':check' => $path)), 'install');
                return install($params);
            }

            throw new bException(tr('install(): After installation test failed for library ":name"', array(':name' => $params['name'])), 'not-specified');
        }

        foreach($params['method'] as $method => $instructions){
            /*
             * Remove the current method as we're trying this now
             */
            unset($params['method'][$method]);
            log_database(tr('Library ":name" not found, auto installing now with method ":method"', array(':name' => $params['name'], ':method' => $method)), 'install');

            if(empty($instructions['locations'])){
                throw new bException(tr('install(): No install locations specified for method ":method" for library ":name"', array(':name' => $params['name'], ':method' => $method)), 'not-specified');
            }

            if(empty($instructions['command'])){
                throw new bException(tr('install(): No command specified for method ":method" for library ":name"', array(':name' => $params['name'], ':method' => $method)), 'not-specified');
            }

            switch($method){
                case 'bower':
                    // FALLTHROUGH
                case 'npm':
                    /*
                     * Try installation using npm
                     */
                    try{
                        safe_exec($instructions['command']);
                        $params['test'] = true;

                    }catch(Exception $e){
                        /*
                         * Crap! Install using npm failed. Any other method
                         * left?
                         */
                        if($params['method']){
                            log_database(tr('Library ":name" installation with method ":method" failed, trying next method', array(':name' => $params['name'], ':method' => $method)), 'install');
                            break;
                        }
                    }

                    break;

                case 'download':
                    /*
                     * Install the required library and continue
                     */
                    if(preg_match('/^https?:\/\/github\.com\/.+?\/.+?\.git$/i', $instructions['url'])){
                        /*
                         * This is a github install file. Clone it, and install from there
                         */
                        $project = str_rfrom($instructions['url'], '/');
                        $project = str_until($project, '.git');

                        log_database(tr('Cloning GIT project ":project"', array(':project' => $project)), 'git/clone');

                        load_libs('git');
                        file_delete(TMP.$project);
                        git_clone($instructions['url'], TMP);

                    }elseif(preg_match('/^https?:\/\/.+?\.zip/i', $instructions['url'])){
                        /*
                         * Set temp path, delete it first to be sure there is no
                         * garbage in the way!
                         */
                        $temp_path = TMP.$params['project'].'/';
                        file_delete($temp_path);

                        /*
                         * Download the file to the specified path, and unpack it
                         */
                        $file = file_move_to_target($instructions['url'], $temp_path, false, true, 0);
                        safe_exec('cd '.$temp_path.'; '.$instructions['command']);

                    }else{
                        /*
                         * Errr, unknown install link, don't know how to process this..
                         */
                        throw new bException(tr('install(): Unknown install URL type ":url" specified, it is not known how to process this URL', array(':url' => $instructions['url'])), 'not-specified');
                    }

                    /*
                     * Okay, files available, now move them to their required
                     * locations as specified. Ensure that the target paths
                     * exists first to avoid crashes on missing paths.
                     */
                    foreach($instructions['locations'] as $source => $target){
                        $target_path = dirname($target);
                        file_ensure_path($target_path);

                        file_execute_mode($target_path, (is_writable($target_path) ? false : 0770), function($params, $path, $mode) use ($temp_path, $source, $target){
                            global $_CONFIG;

                            if(file_exists($target)){
                                safe_exec('chmod ug+w '.$target.' -R');
                                file_delete($target);
                            }

                            if($source[0] == '@'){
                                /*
                                 * $target will be a symlink to specified source
                                 */
                                symlink(substr($source, 1), $target);

                                if($_CONFIG['production']){
                                    /*
                                     * On production environments, always ensure
                                     * that all files are readonly for safety
                                     */
                                    safe_exec('chmod ug-w '.$target);
                                }

                            }else{
                                rename($temp_path.$source, $target);

                                if($_CONFIG['production']){
                                    /*
                                     * On production environments, always ensure
                                     * that all files are readonly for safety
                                     */
                                    safe_exec('chmod ug-w '.$target.' -R');
                                }
                            }
                        });
                    }

                    /*
                     * Install done! Remove temporary crap
                     */
                    $params['test'] = true;
                    file_delete($temp_path, true);
                    log_database(tr('Library ":name" installed successfully with method ":method", proceeding with post installation test', array(':name' => $params['name'], ':method' => $method)), 'install');
                    break;

                default:
                    throw new bException(tr('install(): Unknown installation method ":method" specified for library ":name"', array(':name' => $params['name'], ':method' => $params['method'])), 'not-specified');
            }

            if(!empty($params['test'])){
                /*
                 * First installation method was successfule, we're done!
                 */
                break;
            }
        }

        /*
         * Okay then, library should be installed now! Test this by re-executing
         * install(), it should not have any problems with the checks now
         */
        return install($params);

    }catch(Exception $e){
        throw new bException('install(): Failed', $e);
    }
}
?>
