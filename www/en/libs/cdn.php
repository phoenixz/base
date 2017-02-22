<?php
/*
 * CDN library
 *
 * This library contains functions to manage the CDN servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 *
 */
function cdn_domain($current_url = false){
    global $_CONFIG;

    try{
        if(!$_CONFIG['cdn']['enabled']){
            return domain($current_url);
        }

        /*
         * We have a CDN server in session? If not, get one.
         */
        if(isset_get($_SESSION['cdn']) === null){
            $server = sql_get('SELECT `baseurl` FROM `cdn_servers` WHERE `status` IS NULL ORDER BY RAND() LIMIT 1');

            if(!$server){
                /*
                 * Err we have no CDN servers, though CDN is configured.. Just
                 * continue locally?
                 */
                notify('no-cdn-servers', tr('CDN system is enabled, but no availabe CDN servers were found'), 'developers');
                $_SESSION['cdn'] = false;
                return domain($current_url, $query, $root);
            }

            $_SESSION['cdn'] = $server;
        }

        return $_SESSION['cdn'].$current_url;

    }catch(Exception $e){
        throw new bException('cdn_domain(): Failed', $e);
    }
}



/*
 * Adds the required amount of copies of the specified file to random CDN servers
 */
function cdn_add_files($files, $section = 'pub'){
    global $_CONFIG;

    try{
        load_libs('ssh');

        if(!$section){
            throw new bException(tr('cdn_add_files(): No section specified'), 'not-specified');
        }

        if(!$file){
            throw new bException(tr('cdn_add_files(): No file specified'), 'not-specified');
        }

        /*
         * In what servers are we going to store these files?
         */
        $servers  = cdn_assign_servers();

        $file_insert = sql_prepare('INSERT INTO `cdn_storage` (`projects_id`, `section`, )
                                    VALUES                          (: , : )');

        /*
         * Register the files
         */
        foreach($files as $id => $file){
            $file_insert->execute(array(':file' => $file,
                                        ':size' => filesize($file)));

            $files[$id] = array('id'   => sql_insert_id(),
                                'file' => $file);
        }

        /*
         * Register at what CDN servers the files will be uploaded, and send the
         * files there
         */
        $server_insert = sql_prepare('INSERT INTO `cdn_files_servers` ()
                                      VALUES                          ()');

        foreach($servers as $server){
            foreach($files as $file){
                $server_insert->execute(array(':servers_id' => $server['id'],
                                              ':files_id'   => $file['id']));
            }

            cdn_send_files($files, $server, $section);
        }

        return $files;

    }catch(Exception $e){
        throw new bException('cdn_add_files(): Failed', $e);
    }
}



/*
 * Send the specified file to the specified CDN server
 */
function cdn_send_files($files, $server, $section){
    global $_CONFIG;

    try{
        load_libs('api');

        $result = api_call_base($api_account, '/cdn/add-files', array('project' => PROJECT, 'section' => $section), $files);

        return $result;

    }catch(Exception $e){
        throw new bException('cdn_send_files(): Failed', $e);
    }
}



/*
 * Removes the specified file from all CDN servers
 */
function cdn_remove_files($section = 'pub', $files){
    global $_CONFIG;

    try{
        load_libs('api');

        if(!$section){
            throw new bException(tr('cdn_remove_files(): No section specified'), 'not-specified');
        }

        if(!$files){
            throw new bException(tr('cdn_remove_files(): No files specified'), 'not-specified');
        }

        /*
         * Delete the files one by one
         */
        foreach($files as $file){
            /*
             * What CDN servers is this file stored?
             */
            $servers = sql_list('SELECT    `cdn_storage`.`seoname`

                                 FROM      ``

                                 LEFT JOIN `cdn_sto`
                                 ON        `cdn_servers`.`id` = `cdn_files_servers`.`servers_id`

                                 WHERE     `cdn_files_servers`.`file` = :file', array(':file' => $file));

            foreach($server as $server){
                $api_account = cdn_get_api_account($server);
                api_call_base($api_account, '/cdn/add-files', array('project' => PROJECT, 'section' => $section), $files);
            }
        }


    }catch(Exception $e){
showdie($e);
        throw new bException('cdn_remove_files(): Failed', $e);
    }
}



/*
 * Assigns random CDN servers for the file to be stored in the CDN
 */
function cdn_assign_servers(){
    global $_CONFIG;

    try{
        $servers = sql_list('SELECT `id`, `seoname` FROM `cdn_servers` WHERE `status` IS NULL ORDER BY RAND() LIMIT '.$_CONFIG['cdn']['servers']);
        return $servers;

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
 * Will balance all files over the available CDN servers using the configured amount of required copies
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
function cdn_validate_server($server){

    try{
        load_libs('validate,seo');

        $v = new validate_form($server, 'name,baseurl,api_account,description');

        $v->isNotEmpty ($server['name']        , tr('Please specify a CDN server name'));
        $v->hasMaxChars($server['name']   ,  32, tr('Please make sure the specified CDN server name is less than 32 characters long'));

        $v->isNotEmpty ($server['baseurl']     , tr('Please specify a base URL'));
        $v->hasMaxChars($server['baseurl'], 127, tr('Please make sure the specified base URL is less than 127 characters long'));

        $v->isNotEmpty ($server['api_account'] , tr('Please specify an API account'));

        $server['api_accounts_id'] = sql_get('SELECT `id` FROM `api_accounts` WHERE `seoname` = :seoname AND `status` IS NULL', true, array(':seoname' => $server['api_account']));

        if(!$server['api_accounts_id']){
            $v->setError(tr('Specified API account ":account" does not exist', array(':account' => $server['api_account'])));
        }

        $exists = sql_exists('cdn_servers', 'name', $server['name'], $server['id']);

        if($exists){
            $v->setError(tr('The domain ":name" already exists', array(':name' => $server['name'])));
        }

        $server['seoname'] = seo_unique($server['name'], 'cdn_servers', $server['id'], 'seoname');

        $v->isValid();

        return $server;

    }catch(Exception $e){
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

        $v->isNotEmpty ($cdn['project']    , tr('No project specified'));
        $v->hasMinChars($cdn['project'],  2, tr('Please ensure the path has at least 2 characters'));
        $v->hasMaxChars($cdn['project'], 32, tr('Please ensure the path has less than 32 characters'));

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
 * Test specified CDN server
 */
function cdn_get_api_account($server){
    try{
        load_libs('api');

        $api_account = sql_get('SELECT `api_accounts`.`seoname`

                                FROM   `cdn_servers`

                                JOIN   `api_accounts`
                                ON     `api_accounts`.`id`     = `cdn_servers`.`api_accounts_id`

                                WHERE  `cdn_servers`.`seoname` = :seoname',

                                true, array(':seoname' => $server));

        if(!$api_account){
            throw new bException(tr('cdn_validate_project(): Specified server ":server" does not exist', array(':server' => $server)), 'not-exist');
        }

        return $api_account;

    }catch(Exception $e){
        throw new bException('cdn_get_api_account(): Failed', $e);
    }
}



/*
 * Get information from specified CDN server
 */
function cdn_get_server_info($server){
    try{
        load_libs('api');

        $api_account = cdn_get_api_account($server);
        $result      = api_call_base($api_account, '/cdn/info');

        return $result;

    }catch(Exception $e){
        throw new bException('cdn_get_server_info(): Failed', $e);
    }
}



/*
 * Test specified CDN server
 */
function cdn_test_server($server){
    try{
        load_libs('api');
        $api_account = cdn_get_api_account($server);

        sql_query('UPDATE `cdn_servers` SET `status` = "testing" WHERE `seoname` = :seoname', array(':seoname' => $server));
        $result = api_test_account($api_account);

        sql_query('UPDATE `cdn_servers` SET `status` = NULL WHERE `seoname` = :seoname', array(':seoname' => $server));
        return $result;

    }catch(Exception $e){
        throw new bException('cdn_test_server(): Failed', $e);
    }
}



/*
 * Register this project at the specified CDN server
 */
function cdn_register_project($server){
    try{
        load_libs('api');

        $api_account = cdn_get_api_account($server);
        $result      = api_call_base($api_account, '/cdn/project-exists', array('project' => PROJECT));

        if(!empty($result['exists'])){
            sql_query('UPDATE `cdn_servers` SET `status` = "registering" WHERE `seoname` = :seoname', array(':seoname' => $server));
            $result = api_call_base($api, '/cdn/create-project', array('project' => PROJECT));

            sql_query('UPDATE `cdn_servers` SET `status` = NULL WHERE `seoname` = :seoname', array(':seoname' => $server));
            return $result;
        }

        /*
         * Project already exists
         */
        return false;

    }catch(Exception $e){
        throw new bException('cdn_register_project(): Failed', $e);
    }
}
?>
