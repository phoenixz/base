<?php
/*
 * Set some very very basic system variables. These are the only "configurable" variables outside
 * the config file that MUST be edited separately for each project!
 *
 * Remember! Base uses two versions, one for the code, and one for the database. These versions are
 *           used to execute init files to update the system
 *
 *
 * SEED                    Place seed string here or the project won't start! Can be just a long
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
 */
define('SEED'                   , '');
define('PROJECT'                , '');
define('PROJECTCODEVERSION'     , '0.0.0');
define('REQUIRE_SUBENVIRONMENTS', false);
?>
