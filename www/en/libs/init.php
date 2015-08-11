<?php
/*
 * Init library
 *
 * This library file contains the init function
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Execute the init
 */
function init($projectfrom = null, $frameworkfrom = null){
    global $_CONFIG;

    try{
        /*
         * Are we allowed to init?
         */
        if(!$_CONFIG['init'][PLATFORM]){
            throw new bException('init(): This platform is not authorized to do init()', 'denied');
        }

        load_libs('file,pdo_exists');

        /*
         * Check tmp dir configuration
         */
        file_ensure_path(TMP.'www');
        touch(TMP.'www/.donotdelete');

        /*
         * To do the init, we need the database version data. The database version check is ONLY executed on sql_connect(),
         * so connect to DB to force this check and get the DB version constants
         */
        sql_init();

        if(!empty($GLOBALS['time_zone_fail'])){
            /*
             * MySQL has no time_zone data, first initialize that, then reconnect
             */

            sql_init();
        }

        /*
         * Determine framework DB version (either from DB, or from command line)
         */
        if($frameworkfrom === null){
            $codeversions['FRAMEWORK'] = FRAMEWORKDBVERSION;

        }else{
            /*
             * We're (probably) redoing earlier versions, so remove registrations from earlier versions
             */
            sql_query('DELETE FROM `versions` WHERE (SUBSTRING(`framework`, 1, 1) != "-") AND (INET_ATON(CONCAT(`framework`, REPEAT(".0", 3 - CHAR_LENGTH(`framework`) + CHAR_LENGTH(REPLACE(`framework`, ".", ""))))) >= INET_ATON(CONCAT("'.$frameworkfrom.'", REPEAT(".0", 3 - CHAR_LENGTH("'.$frameworkfrom.'") + CHAR_LENGTH(REPLACE("'.$frameworkfrom.'", ".", ""))))))');
            $codeversions['FRAMEWORK'] = sql_get('SELECT `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;', 'framework');
        }

        /*
         * Determine project DB version (either from DB, or from command line)
         */
        if($projectfrom === null){
            $codeversions['PROJECT'] = PROJECTDBVERSION;

        }else{
            /*
             * We're (probably) doing earlier versions, so remove registrations from earlier versions
             */
            sql_query('DELETE FROM `versions` WHERE (SUBSTRING(`project`, 1, 1) != "-") AND (INET_ATON(CONCAT(`project`, REPEAT(".0", 3 - CHAR_LENGTH(`project`) + CHAR_LENGTH(REPLACE(`project`, ".", ""))))) >= INET_ATON(CONCAT("'.$projectfrom.'", REPEAT(".0", 3 - CHAR_LENGTH("'.$projectfrom.'") + CHAR_LENGTH(REPLACE("'.$projectfrom.'", ".", ""))))))');
            $codeversions['PROJECT'] = sql_get('SELECT `project` FROM `versions` ORDER BY `id` DESC LIMIT 1;', 'project');
        }

        if(!$codeversions['FRAMEWORK'] or FORCE){
            /*
             * We're at 0, we must init everything!
             *
             * This point is just to detect that we need to init below. Dont init anything yet here
             *
             * Create a fake user session in case some init parts require some username
             */
            if(empty($_SESSION['user'])){
                $_SESSION['user'] = array('id'       => null,
                                          'name'     => 'System Init',
                                          'username' => 'init',
                                          'email'    => 'init@'.$_CONFIG['domain'],
                                          'rights'   => array('admin', 'users', 'rights'));
            }

        }elseif(!FORCE and (FRAMEWORKCODEVERSION == $codeversions['FRAMEWORK']) and (PROJECTCODEVERSION == $codeversions['PROJECT'])){
            /*
             * Fetch me a pizza, all is just fine!
             */
            log_message('The framework code and project code versions matches the database versions, so all is fine!', 'init/ok', 'white');
            $noinit = true;
        }

        if(version_compare(FRAMEWORKCODEVERSION, $codeversions['FRAMEWORK']) < 0){
            if(!str_is_version(FRAMEWORKCODEVERSION)){
                throw new bException('init(): Cannot continue, the FRAMEWORK code version "'.str_log(FRAMEWORKCODEVERSION).'" (Defined at the top of '.ROOT.'/libs/system.php) is invalid', 'invalidframeworkcode');
            }

            throw new bException('init(): Cannot continue, the FRAMEWORK code version is OLDER than the database version, the project is running with either old code or a too new database!', 'oldframeworkcode');
        }

        if(version_compare(PROJECTCODEVERSION, $codeversions['PROJECT']) < 0){
            if(!str_is_version(PROJECTCODEVERSION)){
                throw new bException('init(): Cannot continue, the PROJECT code version "'.str_log(PROJECTCODEVERSION).'" (Defined in '.ROOT.'/config/project.php) is invalid', 'invalidframeworkcode');
            }

            throw new bException('init(): Cannot continue, the PROJECT code version is OLDER than the database version, the project is running with either old code or a too new database!', 'oldprojectcode');
        }

        /*
         * From this point on, we are doing an init
         */
        if(FORCE){
            if(!is_bool(FORCE) and !str_is_version(FORCE)){
                throw new bException('init(): Invalid "force" sub parameter "'.str_log(FORCE).'" specified. "force" can only be followed by a valid init version number', 'invalidforce');
            }

            $init = 'forced init';

        }else{
            $init = 'init';
        }

        if(empty($noinit)){
            if(FORCE){
                log_console('Starting '.$init.' FORCED from version "'.FORCE.'" for "'.$_CONFIG['name'].'" using PHP "'.phpversion().'"', 'init', 'white');

            }else{
                log_console('Starting '.$init.' for "'.$_CONFIG['name'].'" using PHP "'.phpversion().'"', 'init', 'white');
            }

            /*
             * Clear all cache
             */
            load_libs('cache');
            cache_clear();
            log_console(tr('Cleared cache'), 'clear', 'green');

            define('INITPATH', slash(realpath(ROOT.'init')));

            $versions = array('framework' => $codeversions['FRAMEWORK'],
                              'project'   => $codeversions['PROJECT']);

            /*
             * ALWAYS First init framework, then project
             */
            foreach(array('framework', 'project') as $type){
                log_console('Starting init', 'init/'.$type);

                /*
                 * Get path for the init type (either init/framework or init/project)
                 * and then get a list of all init files for the init type, and walk
                 * over each init file, and see if it needs execution or not
                 */
                $initpath  = INITPATH.slash($type);
                $files     = scandir($initpath);
                $utype     = strtoupper($type);
                $dbversion = ((FORCE and str_is_version(FORCE)) ? FORCE : $codeversions[$utype]);

                /*
                 * Cleanup and order list
                 */
                foreach($files as $key => $file){
                    /*
                     * Skip garbage
                     */
                    if(($file == '.') or ($file == '..') or (file_extension($file) != 'php') or !str_is_version(str_until($file, '.php'))) {
                        unset($files[$key]);
                        continue;
                    }

                    $files[$key] = substr($file, 0, -4);
                }

                usort($files, 'init_sort_files');

                /*
                 * Go over each init file, see if it needs execution or not
                 */
                foreach($files as $file){
                    $version = $file;
                    $file    = $file.'.php';

                    if(version_compare($version, constant($utype.'CODEVERSION')) >= 1){
                        /*
                         * This init file has a higher version number than the current code, so it should not yet be executed (until a later time that is)
                         */
                        log_console('Skipped future init file "'.$version.'"', 'init/'.$type);

                    }else{
                        if(($dbversion === 0) or (version_compare($version, $dbversion) >= 1)){
                            /*
                             * This init file is higher than the DB version, but lower than the code version, so it must be executed
                             */
                            try{
                                if(file_exists($hook = $initpath.'hooks/pre_'.$file)){
                                    log_console('Executing newer init "pre" hook file with version "'.$version.'"', 'init/'.$type,'green');
                                    include_once($hook);
                                }

                            }catch(Exception $e){
                                /*
                                 * INIT FILE FAILED!
                                 */
                                throw new bException('init('.$type.'): Init "pre" hook file "'.$file.'" failed', $e);
                            }

                            try{
                                log_console('Executing newer init file with version "'.$version.'"', 'init/'.$type,'green');
                                include_once($initpath.$file);

                            }catch(Exception $e){
                                /*
                                 * INIT FILE FAILED!
                                 */
                                throw new bException('init('.$type.'): Init file "'.$file.'" failed', $e);
                            }

                            try{
                                if(file_exists($hook = $initpath.'hooks/post_'.$file)){
                                    log_console('Executing newer init "post" hook file with version "'.$version.'"', 'init/'.$type,'green');
                                    include_once($hook);
                                }

                            }catch(Exception $e){
                                /*
                                 * INIT FILE FAILED!
                                 */
                                throw new bException('init('.$type.'): Init "post" hook file "'.$file.'" failed', $e);
                            }

                            $versions[$type] = $version;

                            $GLOBALS['sql_core']->query('INSERT INTO `versions` (`framework`, `project`) VALUES ("'.cfm((string) $versions['framework']).'", "'.cfm((string) $versions['project']).'")');

                            log_console('Finished init version "'.$version.'"', 'init/'.$type);

                        }else{
                            /*
                             * This init file has already been executed so we can skip it.
                             */
                            log_console('Skipped older init file "'.$version.'"', 'init/'.$type);
                        }
                    }
                }

                /*
                 * There are no more init files. If the last executed init file has a lower
                 * version than the code version still, then update the DB version to the
                 * code version now.
                 *
                 * This way, the code version can be upped without having to add empty init files.
                 */
                if(version_compare(constant($utype.'CODEVERSION'), $versions[$type]) > 0){
                    log_console('Last init file was "'.$versions[$type].'" while code version is still higher at "'.constant($utype.'CODEVERSION').'"', 'init/'.$type, 'yellow');
                    log_console('Updating database version to code version manually'                                                                  , 'init/'.$type, 'yellow');

                    $versions[$type] = constant($utype.'CODEVERSION');

                    $GLOBALS['sql_core']->query('INSERT INTO `versions` (`framework`, `project`) VALUES ("'.cfm((string) $versions['framework']).'", "'.cfm((string) $versions['project']).'")');
                }

                /*
                 * Finished one init part (either type framework or type project)
                 */
                log_console('Finished init', 'init/'.$type, 'white');
            }
        }

        if(ENVIRONMENT == 'production'){
            log_console('Removing data symlink in all languages', 'init/cleanup');

            foreach($_CONFIG['language']['supported'] as $language => $name){
                file_delete(ROOT.'www/'.substr($language, 0, 2).'/data');
            }

            log_console('Finished data symlink cleanup', 'init/cleanup');
        }

        log_console('Finished all', 'init/finished', 'white');

    }catch(Exception $e){
        if($e->getCode() === 'invalidforce'){
            foreach($e->getMessages() as $message){
                log_screen($message);
            }

            die(1);
        }

        throw new bException('init(): Failed', $e);
    }
}



/*
 * There is a version difference between either the framework code and database versions,
 * or the projects code and database versions. Determine which one differs, and how, so
 * we can diplay the correct error
 *
 * Differences may be:
 *
 * Project or framework database may be older than the code
 * Project or framework database may be newer than the code
 *
 * This function is only meant to display the correct error
 *
 */
function init_process_version_diff(){
    global $_CONFIG;

    if((SCRIPT == 'init') or (SCRIPT == 'update-from-base')){
        return false;
    }

    $compare_project   = version_compare(PROJECTCODEVERSION  , PROJECTDBVERSION);
    $compare_framework = version_compare(FRAMEWORKCODEVERSION, FRAMEWORKDBVERSION);

    if(PROJECTDBVERSION === 0){
        $versionerror = 'Database is empty';

    }else{
        if($compare_framework > 0){
            $versionerror = 'Framework database version is older than code version, please update database first';

        }elseif($compare_framework < 0){
            $versionerror = 'Framework database version is newer than code version, the core database "'.str_log($_CONFIG['db']['core']['db']).'" is running with old code!';
        }

        if($compare_project > 0){
            $versionerror = (empty($versionerror) ? "" : "\n").'Project core database "'.str_log($_CONFIG['db']['core']['db']).'" version is older than code version, please update database first';

        }elseif($compare_project < 0){
            $versionerror = (empty($versionerror) ? "" : "\n").'Project core database "'.str_log($_CONFIG['db']['core']['db']).'" version is newer than code version, the database is running with old code!';
        }
    }

    if((PLATFORM == 'http') or !argument('noversioncheck')){
        throw new bException(tr('init_process_version_diff(): Please run the init script because "'.str_log($versionerror).'"'), 'doinit');
    }
}



/*
 * Version check failed. Check why
 *
 * Basically, this function is ONLY executed if we are executing the init script. The version check failed,
 * which PROBABLY was because the database is empty at this point, but we cannot be 100% sure of that. This
 * function will just make sure that the version check did not fail because of other reason, so that we can
 * safely continue with system init
 */
function init_process_version_fail($e){
    global $_CONFIG;

    $r = $GLOBALS['sql_core']->query('SHOW TABLES WHERE `Tables_in_'.$_CONFIG['db']['core']['db'].'` = "versions";');

    if(!$r->rowCount($r)){
        define('FRAMEWORKDBVERSION', 0);
        define('PROJECTDBVERSION'  , 0);

        if(PLATFORM == 'shell'){
            log_console('init_process_version_fail(): No versions table found, assumed empty database', 'warning/versions', 'yellow');
        }

    }else{
        throw new bException('init_process_version_fail(): Failed version detection', $e);
    }
}



/*
 * Sort the init files by version
 */
function init_sort_files($a, $b){
    return version_compare($a, $b);
}



/*
 * Execute specified hook file
 */
function init_hook($hook, $params = null, $disabled = false){
    try{
        /*
         * Reshuffle arguments, if needed
         */
        if(is_bool($params)){
            $disabled = $params;
            $params   = null;
        }

        if(is_array($disabled)){
            $params   = $disabled;
            $disabled = $params;
        }

        if(!$disabled and file_exists(ROOT.'scripts/hooks/'.$hook)){
            return script_exec('hooks/'.$hook, $params);
        }

    }catch(Exception $e){
        throw new bException('init_hook(): Hook "'.str_log($hook).'" failed', $e);
    }
}



/*
 * Upgrade the specified part of the specified version
 */
function init_version_upgrade($version, $part){
    if(!str_is_version($version)){
        throw new bException('init_version_upgrade(): Specified version is not a valid n.n.n version format');
    }

    $version = explode('.', $version);

    switch($part){
        case 'major':
            $version[0]++;
            break;

        case 'minor':
            $version[1]++;
            break;

        case 'revision':
            $version[2]++;
            break;

        default:
            throw new bException('init_version_upgrade(): Unknown version part type "" specified. Please specify one of "major", "minor", or "revision"');
    }

    return implode('.', $version);
}
?>
