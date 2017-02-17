<?php
/*
 * CDN library
 *
 * This library contains functions to manage the CDN servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */
define('CDN', str_from(ENVIRONMENT, 'cdn'));



/*
 * Adds the required amount of copies of the specified object to random CDN servers
 */
function cdn_add_object($file, $table = 'pub'){
    global $_CONFIG;

    try{
        load_libs('ssh');

        if(!$table){
            throw new bException(tr('cdn_add_object(): No table specified'), 'not-specified');
        }

        if(!$file){
            throw new bException(tr('cdn_add_object(): No file specified'), 'not-specified');
        }

        $servers = cdn_assign_servers();

        foreach($servers as $servers_id){
            $server = sql_get('SELECT `cdn`.`path`,
                                      `ssh_accounts`.`username`,
                                      `ssh_accounts`.`ssh_key`

                               FROM   `cdn`

                               JOIN   `ssh_accounts`
                               ON     `ssh_accounts`.`id` = `cdn`.`ssh_accounts_id`

                               WHERE  `cdn`.`id` = :id',

                               array(':id' => $servers_id));

            if(!$server){
                /*
                 * CDN server is configured in $_CONFIG but not in the DB!
                 */
                notify('cdn-not-configured', tr('CDN server ":id" is not configured in the database', array(':id' => $servers_id)), 'developers');
                continue;
            }

            $server['domain'] = cdn_get_domain($servers_id);
show($server);
            ssh_start_control_master($server, TMP.'cdn'.$servers_id.'.sock');
            safe_exec('rsync -e "ssh -p '.$_CONFIG['cdn']['port'].' -o ConnectTimeout='.$_CONFIG['cdn']['timeout'].' -o ControlPath='.TMP.'cdn' .$servers_id.'.sock" -az --rsync-path="mkdir -p '.$server['path'].'/'.strtolower(PROJECT).'/'.$table.'/'.' && rsync" '.$file.' '.$server['username'].'@'.$server['domain'].':'.$server['path'].'/'.strtolower(PROJECT).'/'.$table.'/'.basename($file));
            //safe_exec('rsync -e "ssh -p '.$_CONFIG['cdn']['port'].' -o ConnectTimeout='.$_CONFIG['cdn']['timeout'].' -o ControlPath='.TMP.'cdn' .$servers_id.'.sock" -az '.$file.' '.$server['username'].'@'.$server['domain'].':'.$server['path'].'/'.strtolower(PROJECT).'/'.$table.'/'.basename($file));
        }

        return ','.implode(',', $servers).',';

    }catch(Exception $e){
        throw new bException('cdn_add_object(): Failed', $e);
    }
}



/*
 * Removes the specified object from all CDN servers
 */
function cdn_remove_object($table = 'pub', $file, $id){
    global $_CONFIG;

    try{
        load_libs('ssh');

        if(!$table){
            throw new bException(tr('cdn_remove_object(): No table specified'), 'not-specified');
        }

        if(!$file){
            throw new bException(tr('cdn_remove_object(): No file specified'), 'not-specified');
        }

        if(!$id){
            throw new bException(tr('cdn_remove_object(): No ID specified'), 'not-specified');
        }

        if(!is_numeric($id)){
            throw new bException(tr('cdn_remove_object(): Invalid ID ":id" specified, must be numeric', array(':id' => $id)), 'invalid');
        }

        //$servers = sql_get('SELECT `cdns`
        //
        //                    FROM   `blogs_media`
        //
        //                    WHERE  `blogs_posts_id` = :blogs_posts_id
        //
        //                    LIMIT  1',
        //
        //                    array(':blogs_posts_id' => $id));

        $servers = ',2,3,'; ///
        $servers = explode(',', trim($servers, ','));

        foreach($servers as $servers_id){

            $server = sql_get('SELECT `cdn`.`path`,
                                      `ssh_accounts`.`username`,
                                      `ssh_accounts`.`ssh_key`

                               FROM   `cdn`

                               JOIN   `ssh_accounts`
                               ON     `ssh_accounts`.`id` = `cdn`.`ssh_accounts_id`

                               WHERE  `cdn`.`id` = :id',

                               array(':id' => $servers_id));

            $server['domain'] = cdn_get_domain($servers_id);

            ssh_start_control_master($server, TMP.'cdn'.$servers_id.'.sock');
            safe_exec('rsync -e "ssh -p '.$_CONFIG['cdn']['port'].' -o ConnectTimeout='.$_CONFIG['cdn']['timeout'].' -o ControlPath='.TMP.'cdn' .$servers_id.'.sock" --remove-source-files -a  '.$server['username'].'@'.$server['domain'].':'.$server['path'].'/'.strtolower(PROJECT).'/'.$table.'/'.basename($file).' -p '.$_CONFIG['cdn']['port']);
            //Remove full path (?)
        }

        return 'OK';

    }catch(Exception $e){
showdie($e);
        throw new bException('cdn_remove_object(): Failed', $e);
    }
}



/*
 * Assigns random CDN servers for the object to be stored in the CDN
 */
function cdn_assign_servers(){
    global $_CONFIG;

    try{
        $assigned = array();

        for($i = 0; $i <= ($_CONFIG['cdn']['copies'] - 1); $i++){

            $cdn = array_random_value($_CONFIG['cdn']['servers']);

            while(!empty($assigned[$cdn])){
                $cdn = array_random_value($_CONFIG['cdn']['servers']);
            }

            $assigned[$cdn] = $cdn;
        }

        return array_values($assigned);

    }catch(Exception $e){
        throw new bException('cdn_assign_servers(): Failed', $e);
    }
}



/*
 * Returns a CDN server id from $_CONFIG[‘cdn’][servers’] or the specified cdns list
 */
function cdn_pick_server($cdns){
    global $_CONFIG;
    static $key = null;

    try{
        if(!$cdns){
            throw new bException(tr('cdn_pick_server(): No CDNs specified'), 'not-specified');
        }

        if(!is_array($cdns)){
            throw new bException(tr('cdn_pick_server(): Invalid CDN ":cdns" specified, must be array', array(':cdns' => $cdns)), 'invalid');
        }

        if(!array_diff($_CONFIG['cdn']['servers'], $cdns)){
            throw new bException(tr('cdn_pick_server(): Specified CDN ":cdns" does not exist, check "$_CONFIG[cdn][servers]" configuration', array(':cdns' => $cdns)), 'invalid');
        }

        if($key === null){
            if(empty($_SESSION['cdn']['first_id'])){
                /*
                 * Get $_SESSION['cdn'] data first!
                 */
                //cdn_get_session_data();
            }

            $key = $_SESSION['cdn']['first_id'];
        }

        if(++$key > count($cdns) - 1){
            $key = 0;
        }

        return $cdns[$key];

    }catch(Exception $e){
        throw new bException('cdn_pick_server(): Failed', $e);
    }
}



/*
 * Will balance all objects over the available CDN servers using the configured amount of required copies
 */
function cdn_balance($params){
    global $_CONFIG;

    try{
        //
    }catch(Exception $e){
        throw new bException('cdn_balance(): Failed', $e);
    }
}



/*
 * Update $_SESSION[‘cdn’] from the CDN filesystem structure
 */
function cdn_update_session(){
    global $_CONFIG;

    try{
        //
    }catch(Exception $e){
        throw new bException('cdn_update_session(): Failed', $e);
    }
}



/*
 *
 */
function cdn_get_url($table, $filename){
    global $_CONFIG;

    try{

//        return /'.$_CONFIG['domain'].'/'.$table.'/'.$filename.'/';

    }catch(Exception $e){
        throw new bException('cdn_get_url(): Failed', $e);
    }
}



/*
 *
 */
function cdn_get_domain($cdn_id){
    global $_CONFIG;

    try{
        return str_replace(':id', $cdn_id, $_CONFIG['cdn']['domain']);

    }catch(Exception $e){
        throw new bException('cdn_get_url(): Failed', $e);
    }
}



/*
 * Validate CDN server
 */
function cdn_validate_server($cdn, $insert = true){

    try{
        load_libs('validate');

        $v = new validate_form($cdn, 'ide,path,ssh_accounts_id');

        $v->isNotEmpty ($cdn['ide'],  tr('No cdn id specified'));
        $v->isNatural  ($cdn['ide'],  tr('Please ensure the cdn id is numeric'));

        $v->isNotEmpty ($cdn['path']     , tr('No path specified'));
        $v->hasMinChars($cdn['path'],   2, tr('Please ensure the path has at least 2 characters'));
        $v->hasMaxChars($cdn['path'], 255, tr('Please ensure the path has less than 255 characters'));

        $v->isNotEmpty ($cdn['ssh_accounts_id'],  tr('No ssh account id specified'));
        $v->isNumeric  ($cdn['ssh_accounts_id'],  tr('Please ensure the ssh account id is numeric'));

        if($insert AND $cdn['ide']){
            $id = sql_get('SELECT `id` FROM `cdn` WHERE `id` = :id', array(':id' => $cdn['ide']));
            if(!empty($id)){
                $v->setError(tr('The ID already exists'));
            }
        }

        $v->isValid();

        return $cdn;

    }catch(Exception $e){
//showdie($e);
        throw new bException(tr('cdn_validate_server(): Failed'), $e);
    }
}



/*
 * Validate CDN project
 */
function cdn_validate_project($cdn, $insert = true){

    try{
        load_libs('validate');

        $v = new validate_form($cdn, 'ide,path,ssh_accounts_id');

        $v->isNotEmpty ($cdn['ide'],  tr('No cdn id specified'));
        $v->isNatural  ($cdn['ide'],  tr('Please ensure the cdn id is numeric'));

        $v->isNotEmpty ($cdn['path']     , tr('No path specified'));
        $v->hasMinChars($cdn['path'],   2, tr('Please ensure the path has at least 2 characters'));
        $v->hasMaxChars($cdn['path'], 255, tr('Please ensure the path has less than 255 characters'));

        $v->isNotEmpty ($cdn['ssh_accounts_id'],  tr('No ssh account id specified'));
        $v->isNumeric  ($cdn['ssh_accounts_id'],  tr('Please ensure the ssh account id is numeric'));

        if($insert AND $cdn['ide']){
            $id = sql_get('SELECT `id` FROM `cdn` WHERE `id` = :id', array(':id' => $cdn['ide']));
            if(!empty($id)){
                $v->setError(tr('The ID already exists'));
            }
        }

        $v->isValid();

        return $cdn;

    }catch(Exception $e){
//showdie($e);
        throw new bException(tr('cdn_validate_project(): Failed'), $e);
    }
}



/*
 * Test CDNs
 */
function cdns_test($hostname){
    try{
        load_libs('servers');
        $result = servers_exec($hostname, 'echo 1');
        $result = array_pop($result);

        if($result != '1'){
            throw new bException(tr('cdns_test(): Failed to CDN connect to ":server"', array(':server' => $user.'@'.$hostname.':'.$port)), 'failedconnect');
        }

    }catch(Exception $e){
        throw new bException('cdns_test(): Failed', $e);
    }
}
?>
