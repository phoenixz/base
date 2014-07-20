<?php
$_CONFIG['backup'] = array('sync'        => array('server' => ''),
                           'target'      => ROOT.'data/backups/',
                           'compression' => 'gzip',

                           // See "man mysqldump" for more information on options that have comments prefixed (mysqldump)
                           'mysql'       => array('compression'     => 'gzip',     // Either "gzip" or false, when using gzip, data will be compressed using gzip, database backup filenames will be .sql.gz
                                                  'username'        => 'backup',   // Username for the MySQL backup user account. This user must have access to the databases that will be backed up!
                                                  'password'        => 'backup',   // Password for the MySQL backup user account
                                                  'create_options'  => true,       // (mysqldump) Include all MySQL-specific table options in the CREATE TABLE statements.
                                                  'complete_insert' => true,       // (mysqldump) Use complete INSERT statements that include column names.
                                                  'comments'        => true,       // (mysqldump) Write additional information in the dump file such as program version, server version, and host.
                                                  'dump_date'       => true,       // (mysqldump) If the --comments option is given, mysqldump produces a comment at the end of the dump of the following form: -- Dump completed on DATE
                                                  'disable_keys'    => true,       // (mysqldump) For each table, surround the INSERT statements with /*!40000 ALTER TABLE tbl_name DISABLE KEYS */; and /*!40000 ALTER TABLE tbl_name ENABLE KEYS */;
                                                  'extended_insert' => true,       // (mysqldump) Use multiple-row INSERT syntax that include several VALUES lists. This results in a smaller dump file and speeds up inserts when the file is reloaded.
                                                  'no_create_db'    => true,       // (mysqldump) This option suppresses the CREATE DATABASE statements that are otherwise included in the output if the --databases or --all-databases option is given.
                                                  'routines'        => true),      // (mysqldump) Include stored routines (procedures and functions) for the dumped databases in the output

                           'path'        => array('compression'     => 'bzip2',    // Either "gzip" or "bzip2" or false. When using gzip, data will be compressed using gzip or bzip2, path backup filenames will be .tgz. When no compression is used, extensions will be .tar
                                                  'sudo'            => true),

                           'sync'        => array('compression'     => true),      // Either true or false. When set to true, SSH will be instructed to use compression

                           'clear'       => array(0, 1, 7, 14));                   // A 4 element array list showing the minimum required amount of days between backups. If two backups share the time frame, the older one will be deleted. The list shows the required days between for a one week period, two week period, one month period, and forever

?>
