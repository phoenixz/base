// :TODO: Add documentation

* Welcome to the BASE project!

This readme is intended to be a comprehensive introduction to the BASE project,
how it works, etc. There are many other documentation files related to specific
topics, this readme file will refer to them where needed.

THIS FILE IS UNDER CONSTRUCTION!

SEE features.txt for a list of features with a short description!



* What is base?
BASE is a PHP based framework designed to create custom built websites quickly.
Where wordpress would probably make it faster to deploy, all wordpress sites
are variations on the same thing. The base framework



* Quick startup, how to?
BASE projects are pretty easy to set up. There are only a few required
modifications to be done. One important thing to understand is that BASE works
with configuration environments, and cascading configuration files. This allows
you to run different configurations for your development, testing, and
production environments. On production environments, BASE will always (upon
startup) load ./config/base/default.php and ./config/production.php. On any
other environment, it will load ./config/ENVIRONMENT.php, where ENVIRONMENT is
the name of the environment you have configured.

The ./config/production.php will override certain default configuration
settings defined in ./config/base/default.php, and if you have a non production
environment, your environment configuration file will override some settings
defined in ./config/production.php

With this in mind, it is fairly easy to get BASE started. Though the next list
seems long, this is about 1 minute of setup work. See also
new_base_project_howto.txt for more information



1) Determine the name of your project
2) modify the ./config/project.php file, and change the PROJECT define to the
   name of your project (In UPPERCASE)
3) Determine what environment you want to use. The environment will be a
   lowercase word
4) Configure the apache virtual host with the environment setting
   SetEnv FOOBAR_ENVIRONMENT environment
   Where FOOBAR is the name of your project, in upper case
5) For ease of use on the command line, add
   export FOOBAR_ENVIRONMENT=environment
   to your ~/.bashrc. Alternatively, you can run all scripts with the command
   line arguments "./scripts/foobar env environment"
6) Modify the ./config/production.php configuration file and update at least
   these settings:

   $_CONFIG['domain'] with the production domain name of your project

   $_CONFIG['cookies']['domain'] also production domain name of your project,
   optionally prefixed by a . to also make that cookie work on sub domains. If
   this configuration does not match $_CONFIG['domain'] (with or without dot),
   BASE will throw an exception on web page loads to avoid cookie problems

   $_CONFIG['db']['host'] (more or less optionally) The production database
   server you wish to use

   $_CONFIG['db']['db'] The production database name you wish to use

   $_CONFIG['db']['user'] The production database username you wish to use

   $_CONFIG['db']['db'] The production database password you wish to use

7) Copy ./config/production.php to ./config/ENVIRONMENT.php, where ENVIRONMENT
   is the name of the environment you are using

8) Modify the same entries from 6) as needed to have it working on your
   current environment

7) Make sure that on your environments (local, production, whichever) the
   configured users exist, and have rights to the database you specified

9) Run ./scripts/base/init, this should initalize the database automatically

10) If all is okay, you should be able to open up the first web page!



* Open source



* Framework and projects
BASE keeps internally track of two versions. The FRAMWORK version and the
DATABASE version. On page loads and shell script execution, it will always
perform a quick check if the framework and project versions stored in the
database still match the ones stored in the code. If they dont it means
(usually) that the code has been updated, and may be expecting a different
database format. If so, the system will automatically display a maintenance
page on production environments, or an error on non production environments.
Run ./scripts/base/init to update the database to the current version



* Platforms: Command line scripts and web (apache)



* Libraries



* System libraries & Custom libraries



* Configuration & Environments
BASE uses environments to determine what configuration to use. BASE uses a
cascading configuration file design. It will always load
./config/base/default.php, then always load ./config/production.php,
allowing that file to override some of the default settings, and then
optionally (If a non production environment was specified) an environment
specific configuration file that may also override some settings from
production. All configuration files are found in ./config



* Sub environments
See subenvironments.txt


* Init system
See initsystem.txt



* Command line scripts
BASE has a larger number of scripts in ./scripts/base (and you may add more
in ./scripts as well) to help you with development, deployment and site
management



* Deployment and updates
BASE has a very simple and effective update and deployment system. The
./script/base/update command can pull the latest version of the BASE framework
over your project. If you have a local up to date base install, use
./script/base/update local for faster processing. Use ./scripts/base/deploy to
deploy your current project to the required environment. By default, deploy
will send your site to the production environment to update your production
webiste. These scripts use rsync so both update and deploy are fast.



* Desktop and mobile websites
On first web page load (no cookie was found) base will detect if the connecting
device is a mobile device or a deskop. If mobile, a mobile version of the
website may be displayed. All this behaviour is configurable and may be
disabled if not required IMPORTANT! In order to detect client device, BASE
framework uses the browsecap.ini setting. See browsecap.txt for more
information on this subject



* JSON API and Ajax



* CSS and JS
CSS and JS files should be loaded by html_load_css() and html_load_js().
the html_header() will automatically place them in the <head> header, or
(depending on configuration) they can also appear in the footer, for faster
page loading



* Supported databases and operating systems
Though in theory, it should be able to run on any database and operating
system, BASE has (so far) only been tested on ubuntu, centos, and arch
linux, and only on a MySQL database



* Constants
BASE generates a number of internal constants that are important. See the
constants.txt file for more information on constants.



* File system layout (Location of everything)
See "directories_and_files.txt" for more information on this subject.



* Content
Content files can be found in data/content/LANGUAGE/name.html. These
content files are used by the load_content() call which will fetch this
content in the right language, replace some markers, and return it.



* Languages
BASE is multilingual and can show the same webpage in multiple languages. www/
contains 2 letter ISO language codes (en, es, nl, etc) for each language. Only
the www/en/ should be modified, the other language trees are generated
automatically by a translation script. There is a translation interface that
will assist in generating translations for texts. All texts in base that
should be translatable should have the tr() call around it so that the
translation interface can fetch the translatable texts. Depending on
configuration, BASE can use GEOIP to detect user location, and from there,
extrapolate the default language for that country



* Users & rights
BASE has a built in users and rights management system. There are basically
"normal" users and "admin" users. The difference is that "admin" users have
the "admin" flag in the users table, and the "admin" right assigned to their
rights list. Its possible to create any number of rights that you want, and
then assign those rights to the users you need. There are three rights that
are always available: "admin" (as stated before), "god", and "devil". Assigning
a user the "god" right is the same as assigning this user all rights, he will
have always access everywhere. Assigning a user the "devil" right is the other
way around, nomatter what rights he has assigned, he will never have any access.
All user and rights management scripts can be found in ./scripts/base/users/



* Debugging
show()

showdie()

debug_sql() used in sql_query(debug_sql(query, execute, columns)) or
sql_get(debug_sql(query, execute, columns))



* Tests



* What to change what NOT to change
NEVER change anything in a base/ directory, these changes are overwritten
without question on the next update from the BASE framework.
NEVER change the already available libraries in libs/ since these are also
overwritten without question on update. (Unless you are a BASE developer and
will send these changes to me so I can incorporate them in BASE)



* In the end...
BASE is not wordpress. Its lighter, faster, but so far quite a bit harder yet
to setup. It is not intended for the supermarket on the corner, you need to be
a web developer to actually set it up and then implement the pages. It will,
however, help a lot with implementation of websites, fast development and fast
deployment
