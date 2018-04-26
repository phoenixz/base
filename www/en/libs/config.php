<?php
/*
 * Config library
 *
 * This library contains configuration functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
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
 *
 */
function config_read($section, $environment){
    try{

    }catch(Exception $e){
        throw new bException(tr('config_read(): Failed'), $e);
    }
}



/*
 *
 */
function config_write($section, $environment, $data){
    try{

    }catch(Exception $e){
        throw new bException(tr('config_write(): Failed'), $e);
    }
}



/*
 *
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
 * REQUIRE_SUBENVIRONMENTS If set to true, sub environments will be used. See documentation
 *                         subenvironments.txt on more information on sub environment, but in short: If you have multiple
 *                         projects (websites) that basically do the same thing, while using a
 *                         different data source (database), configuration, CSS interface, etc,
 *                         then you may want to use sub environments, which allows you to do just
 *                         that. In Apache you would need to use 2 SetEnv instructions, one for the
 *                         PROJECTNAME_ENVIRONMENT, and one for the PROJECTNAME_SUBENVIRONMENT. You
 *                         will need configuration files like production.php (general production
 *                         configuration), and production_subenvironmentname.php for configuration
 *                         settings specific to that sub environment
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
?>
