<?php
/*
 * THIS LIBRARY IS DEPRECATED, NOT SUPPORTED, AND WILL BE REMOVED SOON
 *
 * DO NOT USE!
 */
showdie('The mysqli library is no longer supported! If you wish to use this library, remove this line!');
/*
 * Execute specified query
 */
function sql_query($sql) {
	global $_CONFIG;

	sql_connect();

	$r = mysqli_query($_CONFIG['dbconnection'], $sql);

	if(!mysqli_errno($_CONFIG['dbconnection'])) {
        return $r;
    }

    $body = "errno     : ".mysqli_errno($_CONFIG['dbconnection'])."
             errortext : ".mysqli_error($_CONFIG['dbconnection'])."
             query     : ".(PLATFORM == 'apache' ? "<b>".$sql."</b>" : $sql)."
             date      : ".date('d m y h:i:s')."\n";

    if(isset($_SESSION)) {
        $body .= "Session : ".print_r($_SESSION,true)."\n";
    }

    $body .= "POST   : ".print_r($_POST,true)."
              GET    : ".print_r($_GET,true)."
              SERVER : ".print_r($_SERVER,true)."\n";

    error_log('PHP SQL_ERROR: '.mysqli_error($_CONFIG['dbconnection']).' on '.$sql);

    if (ENVIRONMENT != 'production') {
        throw new lsException(nl2br($body));
    }

    throw new lsException(tr('An error has been detected, our staff has been notified about this problem.'));
}



/*
 * Fetch and return data from specified resource
 */
function sql_fetch($r, $column = false) {
	try{
		sql_connect();

		if(!$column){
			return mysqli_fetch_assoc($r);
		}

		$r = mysqli_fetch_assoc($r);

		if(!isset($r[$column])){
			throw new lsException('sql_fetch(): Specified column "'.str_log($column).'" does not exist in the specified result set');
		}

		return $r[$column];

	}catch(Exception $e){
		throw new lsException('sql_fetch(): Failed', $e);
	}
}



/*
 * Fetch and return data from specified resource
 */
function sql_fetch_column($r, $column) {
	$row = sql_fetch($r);

    if(!isset($row[$column])){

    }

    return $row[$column];
}



/*
 * Return insert id
 */
function sql_insert_id() {
	global $_CONFIG;

	sql_connect();
	return mysqli_insert_id($_CONFIG['dbconnection']);
}



/*
 * Return affected rows
 */
function sql_affected_rows() {
	global $_CONFIG;

	sql_connect();
	return mysqli_affected_rows($_CONFIG['dbconnection']);
}



/*
 * Return number of rows in the specified resource
 */
function sql_num_rows(&$r) {
	sql_connect();
	return mysqli_num_rows($r);
}



/*
 * Execute query and return only the first row
 */
function sql_get($sql, $column = false) {
	return sql_fetch(sql_query($sql), $column);
}



/*
 * Connect to database and do a DB version check.
 * If the database was already connected, then just ignore and continue.
 * If the database version check fails, then exception
 */
function sql_connect() {
	global $_CONFIG;

	try{
		if(!empty($_CONFIG['dbconnection'])) {
			/*
			 * Already connected to DB
			 */
			return false;
		}

		/*
		 * Connect!
		 */
		if(!$_CONFIG['dbconnection'] = mysqli_connect($_CONFIG['db']['host'], $_CONFIG['db']['user'], $_CONFIG['db']['pass'], $_CONFIG['db']['db'])) {
			//error
			throw new isException('sql_connect(): Unable to connect to database');
		}

		/*
		 * Ensure correct character set usage
		 */
		sql_query('SET NAMES "'.$_CONFIG['db']['charset'].'"');
// :DELETE:SVEN:20130709: Next line is replaced with line below, seems more obvious to use native function then query for this
//		sql_query('SET CHARACTER SET '.$_CONFIG['charset']);
		mysqli_set_charset($_CONFIG['dbconnection'], $_CONFIG['charset']);

		if((PLATFORM == 'shell') and (SCRIPT == 'init') and FORCE){
			/*
			 * We're doing a forced init from shell. Forced init will
			 * basically set database version to 0 BY DROPPING THE FUCKER SO BE CAREFUL!
			 *
			 * Forced init is NOT allowed on production (for obvious safety reasons, doh!)
			 */
			if(ENVIRONMENT == 'production'){
				throw new lsException('sql_connect(): For safety reasons, init force is NOT allowed on production environment! Please drop the database yourself manually and continue with a normal init', 'denied');
			}

			if(!str_is_version(FORCE)){
				if(!is_bool(FORCE)){
					throw new lsException('sql_connect(): Invalid "force" sub parameter "'.str_log(FORCE).'" specified. "force" can only be followed by a valid init version number', 'invalidforce');
				}

				/*
				 * Dump database, and recreate it
				 */
				sql_query('DROP   DATABASE '.$_CONFIG['db']['db']);
				sql_query('CREATE DATABASE '.$_CONFIG['db']['db']);
				sql_query('USE             '.$_CONFIG['db']['db']);
			}
		}

		/*
		 * Get database version
		 *
		 * In some VERY FEW cases, this check must be skipped. Skipping is done by setting the global variable
		 * skipversioncheck to true. If you don't know why this should be done, then DONT USE IT!
		 */
		if(!isset($GLOBALS['skipversioncheck'])){
			try{
				$r = sql_query('SELECT `project`, `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

				if(!sql_num_rows($r)){
					log_console('startup: No versions in versions table found, assumed empty database', 'warning/versions', 'yellow');

					define('FRAMEWORKDBVERSION', 0);
					define('PROJECTDBVERSION'  , 0);

				}else{
					$versions = sql_fetch($r);

					define('FRAMEWORKDBVERSION', $versions['framework']);
					define('PROJECTDBVERSION'  , $versions['project']);
				}

			}catch(Exception $e){
				/*
				 * Database version lookup failed. Usually, this would be due to the database being empty,
				 * and versions table does not exist (yes, that makes a query fail). Just to be sure that
				 * it did not fail due to other reasons, check why the lookup failed.
				 */
				load_libs('init');
				init_process_version_fail($e);
			}

			/*
			 * On console, show current versions
			 */
			if((PLATFORM == 'shell') and empty($GLOBALS['quiet'])){
				log_console('startup: Found framework code version "'.str_log(FRAMEWORKCODEVERSION).'" and framework database version "'.str_log(FRAMEWORKDBVERSION).'"');
				log_console('startup: Found project code version "'.str_log(PROJECTCODEVERSION).'" and project database version "'.str_log(PROJECTDBVERSION).'"');
			}


			/*
			 * Validate code and database version. If both FRAMEWORK and PROJECT versions of the CODE and DATABASE do not match,
			 * then check exactly what is the version difference
			 */
			if((FRAMEWORKCODEVERSION != FRAMEWORKDBVERSION) or (PROJECTCODEVERSION != PROJECTDBVERSION)){
				load_libs('init');
				init_process_version_diff();
			}
		}

	}catch(Exception $e){
		if($e->code === 'invalidforce'){
			foreach($e->getMessages() as $message){
				log_screen($message);
			}

			die(1);
		}

		/*
		 * Init check has failed, DB is out of sync, we cannot continue
		 */
		uncaught_exception($e);
	}
}



/*
 * Import data from specified file
 */
function sql_import($file) {
	try {
		if(!file_exists($file)){
			throw new lsException('sql_import(): Specified file "'.str_log($file).'" does not exist', 'notexist');
		}

		$tel    = 0;
		$handle = @fopen($file, 'r');

		if(!$handle){
			throw new isException('sql_import(): Could not open file', 'notopen');
		}

		while (($buffer = fgets($handle)) !== false) {
			$buffer = trim($buffer);

			if(!empty($buffer)) {
				sql_query(trim($buffer));

				$tel++;
// :TODO:SVEN:20130717: Right now it updates the display for each record. This may actually slow down import. Make display update only every 10 records or so
				echo 'Importing SQL data ('.$file.') : '.number_format($tel)."\n";
				//one line up!
				echo "\033[1A";
			}
		}

		echo "\nDone\n";

		if(!feof($handle)){
			throw new isException('sql_import(): Unexpected EOF', '');
		}

		fclose($handle);

	}catch(Exception $e){
		throw new lsException('sql_import(): Failed to import file "'.$file.'"', $e);
	}
}



/*
 * Returns if specified index exists
 */
function sql_index_exists($table, $index, $query = ''){
    global $pdo;

    try{
        $retval = sql_get('SHOW INDEX FROM `'.cfm($table).'` WHERE `Column_name` = "'.cfm($index).'"');

        if(empty($retval)){
			if(substr($query, 0, 1) != '!'){
				return false;
			}

			$query = substr($query, 1);
        }

		if($query){
			sql_query($query);
		}

        return $retval;

    }catch(Exception $e){
        throw new lsException('sql_index_exists(): Failed', $e);
    }
}



/*
 * Returns if specified column exists
 */
function sql_column_exists($table, $column, $query = ''){
    global $pdo;

    try{
        $retval = sql_get('SHOW COLUMNS FROM `'.cfm($table).'` WHERE `Field` = "'.cfm($column).'"');

        if(empty($retval)){
			if(substr($query, 0, 1) != '!'){
				return false;
			}

			$query = substr($query, 1);
        }

		if($query){
			sql_query($query);
		}

        return $retval;

    }catch(Exception $e){
        throw new lsException('sql_column_exists(): Failed', $e);
    }
}



/*
 * Returns if specified foreign key exists
 */
function sql_foreignkey_exists($table, $foreign_key, $query = '', $database = ''){
    global $pdo, $_CONFIG;

    try{
        if(!$database){
            $database = $_CONFIG['db']['db'];
        }

        $retval = sql_get($q='SELECT * FROM `information_schema`.`TABLE_CONSTRAINTS` WHERE `CONSTRAINT_TYPE` = "FOREIGN KEY" AND CONSTRAINT_SCHEMA = "'.cfm($database).'" AND TABLE_SCHEMA = "'.cfm($table).'" AND CONSTRAINT_NAME = "'.cfm($foreign_key).'"');

        if(empty($retval)){
			if(substr($query, 0, 1) != '!'){
				return false;
			}

			$query = substr($query, 1);
        }

		if($query){
			sql_query($query);
		}

        return $retval;

    }catch(Exception $e){
        throw new lsException('sql_foreignkey_exists(): Failed', $e);
    }
}



/*
 * Wrapper functions
 * DO NOT USE THESE FUNCTIONS, THEY WILL BE REMOVED IN TIME!
 */
function sql_fetch_assoc($r) {
    return sql_fetch($r);
}

function connect_db() {
	return sql_connect();
}

function db_load_queries($file) {
	return sql_load_queries($file);
}

function sql_load_queries($file) {
	return sql_import($file);
}
?>
