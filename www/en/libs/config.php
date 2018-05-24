<?php
/*
 * Config library
 *
 * This library contains functions to manage the base configuration files
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Return configuration for specified environment
 */
function config_get_for_environment($environment){
    try{
        include(ROOT.'config/base/default.php');
        include(ROOT.'config/production.php');
        include(ROOT.'config/deploy.php');

        if($environment != 'production'){
            include(ROOT.'config/'.$environment.'.php');
        }

        /*
         * Optionally load the platform specific configuration file, if it exists
         */
        if(file_exists($file = ROOT.'config/'.$environment.'_'.PLATFORM.'.php')){
            include($file);
        }

        return $_CONFIG;

    }catch(Exception $e){
        throw new bException(tr('config_get_for_environment(): Failed'), $e);
    }
}



/*
 * Return configuration for specified environment with entry description (from commentaries) as well
 *
 * @param string $environment The environment for the configuration file you wish to read.
 * @param string (optional) $section a non default configuration section.
 * @example config_read('production', 'email') will return the configuration contents for the file ROOT/config/production_email.php
 */
function config_read($environment, $section = null){
    try{
        load_libs('array-tokenizer');

        if($section){
            $section = '_'.$section;
        }

        /*
         * Optionally load the platform specific configuration file, if it exists
         */
        if(!file_exists($file = ROOT.'config/'.$environment.$section.'.php')){
            throw new bException(tr('config_read(): The specified configuration file "ROOT/config/'.$environment.$section.'.php" does not exist'), 'not-exists');
        }

        $data   = file_get_contents($file);
        $config = array();

        preg_match_all('/\$_CONFIG\[(.+?)\]\s*=\s*(.+?;)(?:\s*\/\/\s*(.+?)\n)?/imus', $data, $matches);

        foreach($matches as $level => $submatches){
            if($level !== 1){
                continue;
            }

            foreach($submatches as $id => $match){
                $keys    = str_replace('\'', '', $match);
                $keys    = preg_replace('/\]\s*\[/', '][', $match);
                $keys    = explode('][', $keys);
                $section = &$config;

                foreach($keys as $key){
                    if((substr($key, 0, 1) == '"') or (substr($key, 0, 1) == "'")){
                        $key = substr($key, 1, -1);
                    }

                    if(!isset($section[$key])){
                        $section[$key] = array();
                    }

                    $section = &$section[$key];
                }

                $section['__value__'] = $matches[2][$id];

                if(preg_match('/array(.+?);/imus', $section['__value__'])){
                    /*
                     * The value is an array that can contain various keys, values and descriptions
                     */
                    $section['__value__'] = array_tokenizer($section['__value__']);

                }else{
                    /*
                     * Clean value, and correct datatype
                     */
                    $section['__value__'] = str_ends_not($section['__value__'], ';');
                    $section['__value__'] = force_datatype($section['__value__']);

                    if((substr($section['__value__'], 0, 1) == '"') or (substr($section['__value__'], 0, 1) == "'")){
                        $section['__value__'] = substr($section['__value__'], 1, -1);
                    }
                }

                unset($section);
            }
        }

        return $config;

    }catch(Exception $e){
        throw new bException(tr('config_read(): Failed'), $e);
    }
}



/*
 * Write the specified configuration data to the specified environment / section
 * The configuration file will be ROOT/config/$environment_$section.php
 *
 * Recursive arrays will also be stored correclty. When $environment and
 * $section are not specified, then the function will not write the information
 * to disk, but return it. This is used internally by this function to create
 * the recursed arrays.
 *
 * @param $data Contains the configuration data that will be written
 * @param $environment
 * @param $section
 */
function config_write($data, $environment, $section = false){
    try{
        $lines = array();

        if($section){
            $config_string = '$_CONFIG[\''.$section.'\']';

        }else{
            $config_string = '$_CONFIG';
        }

        foreach($data as $key => $value){
            $line = config_lines($key, $value);

            if($line){
                $lines   = array_merge($lines, config_lines($key, $value, $config_string));
                $lines[] = '';
            }
        }

        if(!$lines){
            return false;
        }

        if($section){
            $file = ROOT.'config/'.$environment.'_'.$section.'.php';

        }else{
            $file = ROOT.'config/'.$environment.'.php';
        }

        $lines = "<?php\n/* THIS CONFIGURATION FILE HAS BEEN GENERATED AUTOMATICALLY BY BASE */\n\n".implode("\n", $lines)."?>";

        load_libs('file');
        file_execute_mode($file, 0660, function($params, $file) use($lines) { file_put_contents($file, $lines); });
        return true;

    }catch(Exception $e){
        throw new bException(tr('config_write(): Failed'), $e);
    }
}



/*
 * Update the specified configuration section with the specified value. Only configuration leafs can be updated, configuration branches (arrays) can NOT be updated at once.
 *
 * @param $environment
 * @param $keys mixed CSV string or array containg the keys pointing to the configuration leaf that must be updated
 * @params $value mixed The value to update the leaf with.
 * @return void
 */
function config_update($environment, $keys, $value){
    global $_CONFIG;

    try{
        if(!$keys){
            throw new bException(tr('config_update(): No keys specified. Please specify a valid configuration key set'), 'not-specified');
        }

        if(!$value){
            throw new bException(tr('config_update(): No value specified.'), 'not-specified');
        }

        if(!$environment){
            throw new bException(tr('config_update(): No environment specified.'), 'not-specified');
        }

        $keys    = array_force($keys);
        $basekey = current($keys);
        $section = false;

        if(config_exists($basekey)){
            /*
             * This base key is not available in default configuration, load
             * specific configuration file
             */
            $section = $basekey;
            $CONFIG  = config_read($environment, $basekey);

            if(!isset($CONFIG[$basekey])){
                throw new bException(tr('config_update(): The specified configuration section ":section" does not exist', array(':section' => $basekey)), 'not-exist');
            }

        }else{
            $CONFIG = config_read($environment);
        }

        $config = &$CONFIG;

        foreach($keys as $key){
            if(!array_key_exists($key, $config)){
                throw new bException(tr('config_update(): The specified configuration section ":section" does not exist', array(':section' => $keys)), 'not-exist');
            }

            $config = &$config[$key];
        }

        $value = force_datatype($value);

        if(!array_key_exists('__value__', $config)){
            throw new bException(tr('config_update(): The specified configuration section ":section" is not a configuration leaf node and cannot be configured.', array(':section' => $keys)), 'invalid');
        }

        if((gettype($config['__value__']) != gettype($value)) and ($config['__value__'] !== null)){
            throw new bException(tr('config_update(): The specified configuration section ":section" should be of datatype ":current" but is specified as datatype ":specified"', array(':current' => gettype($config['__value__']), ':specified' => gettype($value))), 'invalid');
        }

        if($config['__value__'] === $value){
            /*
             * The value hasn't changed, do not write anything
             */
            return false;
        }

        $config['__value__'] = $value;

        return config_write($CONFIG, $environment, $section);

    }catch(Exception $e){
        throw new bException(tr('config_update(): Failed'), $e);
    }
}



/*
 * Build up configuration lines
 *
 * @param $key
 * @param $value
 * @param $config_string
 * @return array Returns an array with the lines for the specified key / value converted into PHP code
 */
function config_lines($key, $value, $config_string = '$_CONFIG'){
    try{
        $lines = array();

        if(array_key_exists('__value__', $value)){
            /*
             * Leaf node. Clean data and insert
             */
            $value   = var_export($value['__value__'], true);
            $value   = str_replace("\n", ' ', $value);
            $value   = preg_replace('/(\(|,|\=>)\s+/', '$1 ', $value);
            $value   = str_replace('array ( ', 'array(', $value);
            $value   = str_replace(', )', ')', $value);
            $lines[] = str_size($config_string.'[\''.$key.'\']', 120).'= '.$value.';';

        }else{
            /*
             * Branch node, keep building more
             */
            foreach($value as $subkey => $subvalue){
                $lines = array_merge($lines, config_lines($subkey, $subvalue, $config_string.'[\''.$key.'\']'));
            }
        }

        return $lines;

    }catch(Exception $e){
        throw new bException(tr('config_lines(): Failed'), $e);
    }
}



/*
 * Writes the ROOT/config/project.php file from the specified variables
 *
 * @param $project
 * @param $project_code_version
 * @param $seed
 * $return void
 */
function config_write_project($project, $project_code_version, $seed){
    try{
        $data = '<?php
/*
 * Set some very very basic system variables. These are the only "configurable" variables outside
 * the config file that MUST be edited separately for each project!
 *
 * Remember! Base uses two versions, one for the code, and one for the database. These versions are
 *           used to execute init files to update the system
 *
 *
 * SEED                    Place seed string here or the project won\'t start! Can be just a long
 *                         string of random characters like "4H%&^}{Jh}{ik9". DO NOT LOSE THIS
 *                         STRING!
 *
 * PROJECT                 Replace the name "BASE" with YOURPROJECTNAME in UPPERCASE. You will have
 *                         to use YOURPROJECTNAME_ENVIRONMENT in apache (SubEnv
 *                         YOURPROJECTNAME_ENVIRONMENT development) or bash (export
 *                         YOURPROJECTNAME_ENVIRONMENT=production) to set require configuration
 *                         environment
 *
 * PROJECTCODEVERSION      Current project version in Major.Minor.Revision format. Up these as you
 *                         like yourself, and up these once you add new init files
 *
 * IMPORTANT!!! DO NOT CHANGE THE SEED VALUE ONCE YOUR PROJECT IS IN USE OR ALL YOUR ACCOUNT AUTHENTICATIONS WILL IRREPARABLY FAIL!
 *
 */
';

        $data .= str_size('define(\'SEED\''              , 33).', \''.$seed.'\');'.CRLF;
        $data .= str_size('define(\'PROJECT\''           , 33).', \''.$project.'\');'.CRLF;
        $data .= str_size('define(\'PROJECTCODEVERSION\'', 33).', \''.$project_code_version.'\');'.CRLF;
        $data .= '?>'.CRLF;

        file_put_contents(ROOT.'config/project.php', $data);

    }catch(Exception $e){
        throw new bException(tr('config_write_project(): Failed'), $e);
    }
}



/*
 * Check if specified configuration section $file exists as a separate file.
 *
 * @param string $file The section file to check for
 * @return bool Returns true if files for the specified configuration section exist, false if not.
 */
function config_exists($file){
    static $paths;

    try{
        if(!$paths){
            $paths = array(ROOT.'config/base/',
                           ROOT.'config/production',
                           ROOT.'config/'.ENVIRONMENT.'');
        }

        $file = trim($file);

        /*
         * Include first the default configuration file, if available, then
         * production configuration file, if available, and then, if
         * available, the environment file
         */
        foreach($paths as $id => $path){
            if(!$file){
                /*
                 * Trying to load default configuration files again
                 */
                if(!$id){
                    $path .= 'default.php';

                }else{
                    $path .= '.php';
                }

            }else{
                if($id){
                    $path .= '_'.$file.'.php';

                }else{
                    $path .= $file.'.php';
                }
            }

            if(file_exists($path)){
                return true;
            }
        }

        return false;

    }catch(Exception $e){
        throw new bException(tr('config_exists(): Failed to check config file(s) ":file"', array(':file' => $files)), $e);
    }
}
?>
