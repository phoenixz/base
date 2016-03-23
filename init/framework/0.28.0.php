<?php
/*
 * Default characterset and collations have changed. Ensure that database and tables follow current settings
 */
sql_query('ALTER DATABASE '.$_CONFIG['db']['core']['db'].' CHARACTER SET '.$_CONFIG['db']['core']['charset'].' COLLATE '.$_CONFIG['db']['core']['collate'].';');
$r = sql_query('SHOW TABLES');

log_console(tr('Fixing table charset and collate'), '', 'white');

while($table = sql_fetch($r, 'Tables_in_'.$_CONFIG['db']['core']['db'])){
    cli_dot(1);
    sql_query('ALTER TABLE `'.$table.'` CHARACTER SET '.$_CONFIG['db']['core']['charset'].' COLLATE '.$_CONFIG['db']['core']['collate'].';');
}

cli_dot(false);
?>
