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
            $server = sql_get('SELECT `ssh_accounts`.`username`,
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

//show('rsync -az -e "ssh -p '.$_CONFIG['cdn']['port'].' -o ConnectTimeout='.$_CONFIG['cdn']['timeout'].',ControlPath='.TMP.'cdn'.$servers_id.'.sock'.'" '.$file.' source.jpg');
//            safe_exec('rsync -az -e "ssh -p '.$_CONFIG['cdn']['port'].' -o ConnectTimeout='.$_CONFIG['cdn']['timeout'].',ControlPath='.TMP.'cdn'.$servers_id.'.sock'.'" '.$file.' source.jpg');
//            safe_exec('rsync -az -e "ssh -p '.$_CONFIG['cdn']['port'].' -o ConnectTimeout='.$_CONFIG['cdn']['timeout'].',ControlPath='.$socket.'" source '.$server['domain'].'');
        }

showdie('FIISHED');
        return ','.implode(',', $servers).',';

    }catch(Exception $e){
showdie($e);
        throw new bException('cdn_add_object(): Failed', $e);
    }
}



/*
 * Removes the specified object from all CDN servers
 */
function cdn_remove_object($table, $file){
    global $_CONFIG;

    try{
        if(!$table){
            throw new bException(tr('cdn_remove_object(): No table specified'), 'not-specified');
        }

        if(!$file){
            throw new bException(tr('cdn_remove_object(): No file specified'), 'not-specified');
        }

    }catch(Exception $e){
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
 * Validate CDN
 */
function cdn_validate($cdn){

    try{
        load_libs('validate');

        $vj = new validate_form($cdn, 'project_code,table,table_code,path,ssh_accounts_id');

        $vj->validate($cdn['project_code'],     'required',  'true'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the project code exists'));
        $vj->validate($cdn['table'],            'required',  'true'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the table name exists'));
        $vj->validate($cdn['table_code'],       'required',  'true'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the table code exists'));
        $vj->validate($cdn['table_code'],       'minlength', '4'           , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the table code has 4 characters'));
        $vj->validate($cdn['path'],             'required',  'true'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the path exists'));
        $vj->validate($cdn['ssh_accounts_id'],  'required',  'true'        , '<span class="FcbErrorTail"></span>'.tr('Please ensure that the ssh account id exists'));

        $v->isValid();

        return $cdn;

    }catch(Exception $e){
        throw new bException(tr('cdn_validate(): Failed'), $e);
    }
}



/*
 * Test CDNs
 */
function cdns_test($hostname){
    try{
        $result = servers_exec($hostname, 'echo 1');
        $result = array_pop($result);

        if($result != '1'){
            throw new bException(tr('servers_test(): Failed to SSH connect to ":server"', array(':server' => $user.'@'.$hostname.':'.$port)), 'failedconnect');
        }

    }catch(Exception $e){
        throw new bException('cdns_test(): Failed', $e);
    }
}




/*
 * *********************************************************************************************************
 * BELOW ARE OBSOLETE CDN FUNCTIONS
 * *********************************************************************************************************
 */

///*
// * Send command to CDN servers that must be executed
// *
// * If $id is specified, the command will be sent to only that CDN server
// * If $id is NULL, the command will be sent to all CDN servers
// */
//function cdn_commands_send($command, $data, $servers = null){
//    global $_CONFIG;
//
//    try{
//        load_libs('crypt,curl,html,file');
//
//        /*
//         * Process files separately below
//         */
//        if(!empty($data['files'])){
//            $files = $data['files'];
//            unset($data['files']);
//        }
//
//        /*
//         * Encrypt message and data
//         */
//        $message = encrypt(array('command' => $command, 'data' => $data), $_CONFIG['cdn']['shared_key']);
//
//        /*
//         * Build the cURL post array
//         */
//        $post = array('message' => $message);
//
//        if(!empty($files)){
//            /*
//             * Add the required files
//             */
//            $count = 0;
//
//            foreach($files as $file){
//                $post['file'.$count++] = curl_file_create($file, file_mimetype($file), $file);
//            }
//        }
//
//        /*
//         * Determine to what CDN servers to send the information (Basically all, or one specifically)
//         */
//        if(!$servers){
//            $servers = $_CONFIG['cdn']['servers'];
//        }
//
//        $servers = array_force($servers);
//
//        foreach($servers as $server){
//            if(!in_array($server, $_CONFIG['cdn']['servers'])){
//                log_console(tr('Specified CDN server ":cdn" does not exist. This is a problem unless this server recently was deactivated', array(':cdn' => $server)), '', 'yellow');
//                notify('cdn_server_notexist', tr('cdn_commands_send(): Specified CDN server ":cdn" does not exist. This is a problem unless this server recently was deactivated', array(':cdn' => $server)), 'developers');
//            }
//        }
//
//        /*
//         * Send the message to all required CDN servers
//         */
//        foreach($servers as $server){
//            $server = cdn_prefix($server);
//            $start  = microtime(true);
//
//            try{
//                if(VERBOSE){
//                    log_console(tr('Sending command ":command" to CDN server ":cdn"', array(':command' => $command, ':cdn' => $server)));
//                }
//
//                $result = curl_get(array('url'        => slash($server).'command.php',
//                                         'proxy'      => false,
//                                         'getheaders' => false,
//                                         'post'       => $post));
//
//                $stop   = microtime(true);
//                $time   = number_format($stop - $start, 3);
//                $data   = json_decode_custom($result['data']);
//
//                if($command == 'ping'){
//                    if(isset_get($data['result']) == 'PONG'){
//                        log_console(tr('Pinged CDN server ":cdn" in ":time" miliseconds', array(':cdn' => $server, ':time' => $time)), '', 'green');
//
//                    }else{
//                        log_console(tr('Pinged CDN server ":cdn" in ":time" miliseconds, but got unknown reply ":reply"', array(':cdn' => $server, ':time' => $time, ':reply' => $data['result'])), '', 'yellow');
//                    }
//
//                }else{
//                    if(VERBOSE){
//                        log_console(tr('Sent command ":command" to CDN server ":cdn" in ":time" miliseconds', array(':command' => $command, ':cdn' => $server, ':time' => $time)), '', 'green');
//                    }
//                }
//
//            }catch(Exception $e){
//show($e);
//show($result['data']);
//showdie($server);
//                switch($e->getMessage()){
//                    case 'json_decode_custom(): Syntax error, malformed JSON':
//                        log_console(tr('Command ":command" to CDN server ":cdn" returned malformed data ":data"', array(':command' => $command, ':cdn' => $server, ':data' => $result['data'])), '', 'red');
//                        break;
//
//                    default:
//                        log_console(tr('Failed to send command ":command" to CDN server ":cdn" with error ":exception"', array(':command' => $command, ':cdn' => $server, ':exception' => $e->getData()['data'])), '', 'red');
//                }
//showdie($e->getMessage());
//            }
//        }
//
//        return $result;
//
//    }catch(Exception $e){
//showdie($e);
//        if(substr($e->getCode(), 0, 4) == 'HTTP'){
//            throw new bException($e->getData()['data'], 403);
//        }
//
//        throw new bException('cdn_commands_send(): Failed', $e);
//    }
//}
//
//
//
///*
// * Send command to CDN servers that must be executed
// */
//function cdn_commands_insert($message, $files){
//    global $_CONFIG;
//
//    try{
//        load_libs('crypt,file');
//        $message = decrypt($message, $_CONFIG['cdn']['shared_key']);
//
//        if(!is_array($message)){
//            throw new bException(tr('cdn_commands_insert(): Specified message is not an array'), 'invalid');
//        }
//
//        if(!is_scalar($message['data'])){
//            $message['data'] = json_encode_custom($message['data']);
//        }
//
//        if(empty($files)){
//            $path = '';
//
//        }else{
//            $listing = json_decode_custom($message['data']);
//            $path    = ROOT.'data/cdn/processing/'.c_listing_path($listing['listings_id'], ENVIRONMENT);
//
//            file_ensure_path($path);
//
//            foreach($files as $file){
//                rename($file['tmp_name'], ROOT.'data/cdn/processing/'.c_listing_path($listing['listings_id'], ENVIRONMENT).$file['name']);
//            }
//        }
//
//        $status = null;
//
//        switch(isset_get($message['command'])){
//            case 'ping':
//                json_reply('', 'PONG');
//                $status = 'processed';
//                // FALLTHROUGH
//
//            case 'move-listing':
//                // FALLTHROUGH
//
//            case 'place-listing-data':
//                // FALLTHROUGH
//
//            case 'trash-listing-data':
//                /*
//                 * This is a valid command
//                 */
//                sql_query('INSERT INTO `cdn_commands` (`cdn`, `command`, `path`, `from`, `data`, `status`)
//                           VALUES                     (:cdn , :command , :path , :from , :data , :status )',
//
//                           array(':cdn'     => CDN,
//                                 ':command' => $message['command'],
//                                 ':path'    => $path,
//                                 ':status'  => $status,
//                                 ':from'    => $_SERVER['REMOTE_ADDR'],
//                                 ':data'    => $message['data']));
//                break;
//
//            case '':
//                log_database('Received empty CDN command', 'cdncommand/empty');
//                throw new bException(tr('cdn_commands_insert(): No command specified in message'), 'not-specified');
//
//            default:
//                log_database('Received unknown CDN command "'.str_log($message['command']).'"', 'cdncommand/unknown');
//                throw new bException(tr('cdn_commands_insert(): Unknown command "%command%" specified in message', array('%command%' => str_log($message['command']))), 'unknown');
//        }
//
//        log_database('Inserted CDN command "'.str_log($message['command']).'"', 'cdncommand/'.str_log(isset_get($message['command'])));
//
//        if(!debug()){
//            run_background('base/cdn process --verbose -e '.ENVIRONMENT, 'cdn');
//        }
//
//    }catch(Exception $e){
//        throw new bException('cdn_commands_insert(): Failed', $e);
//    }
//}
//
//
//
///*
// * Execute all non processed CDN commands
// */
//function cdn_commands_process($retries = null, $sleep = 5000){
//    try{
//        log_console(tr('Executing commands for CDN server ":cdn"', array(':cdn' => CDN)), '', 'white');
//        load_libs('file,json');
//
//        if($retries === null){
//            $retries = 10;
//        }
//
//        $retry = 0;
//        $count = 0;
//
//        while(true){
//            $commands = sql_query('SELECT `id`,
//                                          `command`,
//                                          `path`,
//                                          `data`
//
//                                   FROM   `cdn_commands`
//
//                                   WHERE  `cdn`    = :cdn
//                                   AND    `status` IS NULL',
//
//                                   array(':cdn' => CDN));
//
//            if(!$commands->rowCount()){
//                /*
//                 * No commands found, waiting..
//                 */
//                if(++$retry >= $retries){
//                    log_console(tr('No commands found after ":retries" retries, quitting successfully', array(':retries' => $retries)), '', 'green');
//                    break;
//                }
//
//                usleep($sleep * 1000);
//                log_console(tr('No commands found, retrying'));
//
//            }else{
//                $retry = 0;
//            }
//
//            while($command = sql_fetch($commands)){
//                try{
//                    if(VERBOSE){
//                        log_console(tr('Processing CDN ":cdn" command ":command" with id ":id"', array(':id' => $command['id'], ':cdn' => CDN, ':command' => $command['command'])));
//
//                    }else{
//                        cli_dot();
//                    }
//
//                    $command['data'] = json_decode_custom($command['data']);
//
//                    switch(isset_get($command['command'])){
//                        case 'move-listing':
//                            if(VERBOSE){
//                                log_console(tr('move-listing-data for listing ":listing"', array(':listing' => $command['data']['listings_id'])));
//                            }
//
//                            $result = cdn_move_listing_data($command['data']['listings_id'], $command['data']['to_cdn_id']);
//
//                            if(!$result){
//                                /*
//                                 * This listing currently has no data, so its not moving anything.
//                                 * Update the CDN number manually here.
//                                 */
//                                sql_query('UPDATE `listings` SET `cdn` = :cdn WHERE `id` = :id', array(':id' => $command['data']['listings_id'], ':cdn' => $command['data']['to_cdn_id']));
//                            }
//
//                            break;
//
//                        case 'place-listing-data':
//                            /*
//                             * Move the listing files in place
//                             */
//                            if(VERBOSE){
//                                log_console(tr('place-listing-data for listing ":listing"', array(':listing' => $command['data']['listings_id'])));
//                            }
//
//                            $command['path'] = slash($command['path']);
//                            $target_path     = ROOT.'data/content/images/'.c_listing_path($command['data']['listings_id'], ENVIRONMENT);
//
//                            file_ensure_path($target_path);
//
//                            foreach(scandir($command['path']) as $file){
//                                if(($file == '.') or ($file == '..')) continue;
//
//                                /*
//                                 * Only move files if exist. If not exist, assume they've been
//                                 * moved already in a previously partially executed process
//                                 */
//                                rename(slash($command['path']).$file, $target_path.$file);
//                            }
//
//                            /*
//                             * First delete the endpoint path containing all the images.
//                             * Then try to clean up the upper tree
//                             */
//                            file_clear_path($command['path']);
//
//                            /*
//                             * Get previous CDN and update the listings CDN id
//                             */
//                            $cdn = sql_get('SELECT `cdn` FROM `listings` WHERE `id` = :id', 'cdn', array(':id' => $command['data']['listings_id']));
//                            sql_query('UPDATE `listings` SET `cdn` = :cdn WHERE `id` = :id', array(':id' => $command['data']['listings_id'], ':cdn' => CDN));
//
//                            /*
//                             * Remove the files from the previous CDN
//                             */
//                            cdn_trash_listing_data($command['data']['listings_id'], $cdn);
//                            break;
//
//                        case 'trash-listing-data':
//                            /*
//                             * Delete the endpoint path that contains the file
//                             * Then cleanup above
//                             */
//                            if(VERBOSE){
//                                log_console(tr('trash-listing-data for listing ":listing"', array(':listing' => $command['data']['listings_id'])));
//                            }
//
//                            file_delete_tree(ROOT.'data/content/images/'.c_listing_path($command['data']['listings_id'], ENVIRONMENT));
//                            file_clear_path (ROOT.'data/content/images/'.c_listing_path($command['data']['listings_id'], ENVIRONMENT));
//                            break;
//
//                        case '':
//                            log_database('Received empty CDN command', 'cdnexecute/empty');
//                            throw new bException(tr('cdn_commands_process(): No command specified in message'), 'not-specified');
//
//                        default:
//                            log_database('Received invalid CDN command "'.str_log($command['command']).'"', 'cdnexecute/invalid');
//                            throw new bException(tr('cdn_commands_process(): Unknown command "%command%" specified in message', array('%command%' => str_log($command['command']))), 'unknown');
//                    }
//
//                    sql_query('UPDATE `cdn_commands` SET `status` = "processed" WHERE `id` = :id', array(':id' => $command['id']));
//                    log_database('Excuted CDN command "'.str_log($command['command']).'"', 'cdncommand/'.str_log(isset_get($command['command'])));
//
//                    usleep($sleep);
//                    $count++;
//
//                }catch(Exception $e){
//                    /*
//                     * The command failed to process
//                     */
//                    log_database('CDN command "'.str_log($command['command']).'" failed to execute', 'cdnexecute/failed');
//                    sql_query('UPDATE `cdn_commands` SET `status` = "failed" WHERE `id` = :id', array(':id' => $command['id']));
//                    usleep($sleep);
//                }
//            }
//        }
//
//        return $count;
//
//    }catch(Exception $e){
//        throw new bException('cdn_commands_process(): Failed', $e);
//    }
//}
//
//
//
///*
// * Trash all CDN objects related to the specified listing
// */
//function cdn_trash_listing_data($listings_id, $cdn = null){
//    try{
//        if(!$cdn){
//            $cdn = sql_get('SELECT `cdn` FROM `listings` WHERE `id` = :id', 'cdn', array(':id' => $listings_id));
//        }
//
//        cdn_commands_send('trash-listing-data', array('listings_id' => $listings_id), $cdn);
//
//    }catch(Exception $e){
//        throw new bException('cdn_trash_listing(): Failed', $e);
//    }
//}
//
//
//
///*
// * Test the connection with the CDN servers
// */
//function cdn_ping($servers){
//    try{
//        cdn_commands_send('ping', null, $servers);
//
//    }catch(Exception $e){
//        throw new bException('cdn_ping(): Failed', $e);
//    }
//}
//
//
//
///*
// * Balance all listing data over all CDN servers
// */
//function cdn_balance(){
//    global $_CONFIG;
//
//    try{
//        log_console('Balancing listing data over all CDN servers', '', 'white');
//
//        $cdns     = $_CONFIG['cdn']['servers'];
//        $from     = sql_list('SELECT   `cdn`,
//                                       COUNT(`id`) AS `count`
//
//                              FROM     `listings`
//
//                              GROUP BY `cdn');
//
//        log_console(tr('Found ":count" CDN servers', array(':count' => count($cdns))));
//
//        /*
//         * Check that all configured CDN's are in this from list.
//         * If a CDN server is not in the list, it probably is new
//         * Add it as having 0 listings assigned.
//         */
//        foreach($cdns as $cdn){
//            if(!isset($from[$cdn])){
//                log_console(tr('Found non assigned CDN server ":cdn", will move data there', array(':cdn' => $cdn)));
//                $from[$cdn] = 0;
//            }
//        }
//
//        /*
//         * Check $from list, ensure that all CDN's are currently available
//         */
//        foreach($from as $cdn => $count){
//            if($count and !in_array($cdn, $cdns)){
//                log_console(tr('Found CDN ":cdn" has assigned ":count" listings. Since this CDN is not configured (anymore?), all listings will be removed and redistributed over the other CDN servers.', array(':cdn' => $cdn, ':count' => $count)), '', 'yellow');
//            }
//        }
//
//        /*
//         * We'll try to average the files over all currently configured CDN servers
//         * So calculate the average and copy from high amount servers to low amount servers
//         */
//        $sum      = array_sum($from);
//        $average  = ceil($sum / count($cdns)) + count($cdns);
//        $results  = array('failures' => array());
//        $to       = $from;
//
//        /*
//         * Split the CDN servers into two lists, one where we will copy
//         * FROM, and one where we will copy TO
//         *
//         * We will only copy FROM CDN servers that have MORE than average
//         * We will only copy TO CDN servers that have LESS than average
//         */
//        foreach($from as $cdn => $count){
//            if($count and !in_array($cdn, $cdns)){
//                /*
//                 * This CDN server has listings assigned, but is no longer configured.
//                 * Move everything away from it
//                 */
//                unset($to[$cdn]);
//                $from[$cdn] = $count;
//
//            }elseif($count < $average){
//                /*
//                 * This CDN server has less than average listings assigned, move the
//                 * difference in listings to it
//                 */
//                unset($from[$cdn]);
//                $to[$cdn] = $average - $count;
//
//            }else{
//                /*
//                 * This CDN server has more than average listings assigned, move the
//                 * difference in listings from it
//                 */
//                unset($to[$cdn]);
//                $from[$cdn] = $count - $average;
//            }
//        }
//
//        /*
//         * Now we know how many listings to copy from where and how many to copy to where
//         *
//         * Go over all $from servers and move files to $to servers
//         * Calculate the amount of listings that are too many on this
//         * server and move only that amount away to other servers.
//         */
//        foreach($from as $from_cdn => $limit){
//            try{
//                $listings = sql_query('SELECT `id`,
//                                              `cdn`
//
//                                       FROM   `listings`
//
//                                       WHERE  `cdn` = :cdn
//
//                                       ORDER BY RAND()
//
//                                       LIMIT  :limit',
//
//                                       array(':cdn'   => $from_cdn,
//                                             ':limit' => $limit));
//
//                while($listing = sql_fetch($listings)){
//                    /*
//                     * Pick a random new CDN server
//                     */
//                    $to_cdn = array_rand($to);
//
//                    if(--$to[$to_cdn] <= 0){
//                        /*
//                         * This CDN server is full, no longer send information here
//                         */
//                        unset($to[$to_cdn]);
//                    }
//
//                    try{
//                        cdn_commands_send('move-listing', array('listings_id' => $listing['id'], 'to_cdn_id' => $to_cdn), $listing['cdn']);
//
//                        if(!VERBOSE){
//                            cli_dot();
//                        }
//
//                    }catch(Exception $e){
//                        /*
//                         * Oops, this one failed
//                         */
//                        $results['failures'][] = $listing['id'];
//                    }
//                }
//
//            }catch(Exception $e){
//                $message = log_console(tr('Failed to move listing ":listing" to CDN ":cdn", exception ":exception"', array(':listing' => $listing['id'], ':cdn' => $to_cdn, ':exception' => str_log($e->getMessages()))), '', 'yellow');
//
//                log_console($message, '', 'yellow');
//                notify('listing_move_cdn_fail', $message, 'developers');
//            }
//        }
//
//        cli_dot(false);
//
//        return $results;
//
//    }catch(Exception $e){
//showdie($e);
//        throw new bException('cdn_balance(): Failed', $e);
//    }
//}
//
//
//
///*
// * Move all listing data (images, videos, etc) away from the specified CDN server
// * so that the server can be removed
// */
//function cdn_remove($cdn){
//throw new bException('cdn_remove(): This function is obsolete, and no longer supported. Disable the CDN you wish to remove in configuration, and run "cdn balance" instead');
////    try{
////        if(!$cdn){
////            throw new bException(tr('cdn_remove(): No CDN specified'), 'not-specified');
////        }
////
////        if(!is_numeric($cdn)){
////            throw new bException(tr('cdn_remove(): Invalid CDN ":cdn" specified, must be numeric', array(':cdn' => $cdn)), 'invalid');
////        }
////
////        if(!in_array($cdn, $_CONFIG['cdn']['servers'])){
////            throw new bException(tr('cdn_remove(): Specified CDN ":cdn" does not exist, check "$_CONFIG[cdn][servers]" configuration', array(':cdn' => $cdn)), 'invalid');
////        }
////
////        $counts   = sql_list('SELECT   `cdn`,
////                                       COUNT(`id`) AS `count`
////
////                              FROM     `listings`
////
////                              GROUP BY `cdn');
////
////        $cdns     = array_filter_values($_CONFIG['cdn']['servers'], $cdn);
////        $to_cdn   = current($cdns);
////        $average  = $count / $cdns;
////        $results  = array('failures' => array());
////
////        $listings = sql_query('SELECT `id`
////
////                               FROM   `listings`
////
////                               WHERE  `cdn` = :cdn',
////
////                               array(':cdn' => $cdn));
////
////        $results['results'] = $listings->rowCount();
////
////        while($listing = sql_fetch($listings)){
////            /*
////             * Search next available CDN server
////             */
////            $search = true;
////
////            while($search){
////                $to_cdn = array_next_value($cdns, $to_cdn);
////
////                if(++$counts[$to_cdn] > $average){
////                    /*
////                     * This one has already more than the average, fill up the
////                     * other CDN servers, unless this is the final one
////                     */
////                    if(count($cdns) > 1){
////                        unset($cdns[$to_cdn]);
////                        continue;
////                    }
////
////                    $search = false;
////                }
////            }
////
////            try{
////                cdn_move_listing_data($listing['id'], $cdn, $to_cdn);
////
////            }catch(Exception $e){
////                /*
////                 * Oops, this one failed
////                 */
////                $results['failures'][] = $listing['id'];
////            }
////        }
////
////        return $results;
////
////    }catch(Exception $e){
////        throw new bException('cdn_remove(): Failed', $e);
////    }
//}
//
//
//
///*
// * Move all listing data from its current CDN server to the specified CDN server
// */
//function cdn_move_listing_data($listings_id, $to_cdn){
//    try{
//        $files = sql_list('SELECT `file`
//
//                           FROM   `images`
//
//                           WHERE  `listings_id` = :listings_id',
//
//                           array(':listings_id' => $listings_id));
//
//        foreach($files as $file){
//            foreach(array('micro', 'small', 'large', 'small@2x', 'large@2x') as $type){
//                $sendfile = ROOT.'data/content/images/'.c_listing_path($listings_id).$file.'-'.$type.'.jpg';
//
//                if(!file_exists($sendfile)){
//                    /*
//                     * This file does not exist, remove it from the database.
//                     */
//                    if(VERBOSE){
//                        log_console(tr('Removing non existing image ":image" from listing ":listing" from database', array(':listing' => $listings_id, ':image' => $file)), '', 'yellow');
//                    }
//
//                    sql_query('DELETE FROM `images`
//
//                               WHERE       `listings_id` = :listings_id
//                               AND         `file`        = :file',
//
//                               array(':listings_id' => $listings_id,
//                                     ':file'        => $file));
//
//                }else{
//                    $sendfiles[] = $sendfile;
//                }
//            }
//        }
//
//        if(empty($sendfiles)){
//            if(VERBOSE){
//                log_console(tr('Not moving data for listing ":listing" because no images are linked', array(':listing' => $listings_id)), '', 'yellow');
//
//            }else{
//                cli_dot(10, '.', '');
//            }
//
//            return false;
//
//        }else{
//            if(VERBOSE){
//                log_console(tr('Moving ":count" data objects for listing ":listing"', array(':listing' => $listings_id, ':count' => count($sendfiles))), '', 'green');
//
//            }else{
//                cli_dot(10, '.', 'green');
//            }
//
//            return cdn_commands_send('place-listing-data', array('listings_id' => $listings_id, 'files' => $sendfiles), $to_cdn);
//        }
//
//    }catch(Exception $e){
//        throw new bException('cdn_move_listing_data(): Failed', $e);
//    }
//}
//
//
//
///*
// * Receive all data (images, video, etc) for the specified listing, and place
// * it on the required directories
// */
//function cdn_place_listing_data($listing, $files){
//    try{
//        load_libs('file');
//
//        $path = ROOT.'data/images/'.c_listing_path($listing['id']);
//        file_ensure_path($path);
//
//        foreach($files as $file){
//            rename($file, $path);
//        }
//
//        return true;
//
//    }catch(Exception $e){
//        throw new bException('cdn_place_listing_data(): Failed', $e);
//    }
//}
//
//
//
///*
// * Remove all image and video links that point to non existing files (which would cause 404)
// */
//function cdn_clean(){
//    try{
//        load_libs('file');
//        log_console(tr('Cleaning image and video links where the linked files no longer exist for CDN ":cdn"', array(':cdn' => CDN)), '', 'white');
//
//        foreach(array('images', 'videos') as $type){
//            log_console(tr('Cleaning ":type" type objects', array(':type' => $type)));
//
//            $listings = sql_query  ('SELECT `id` FROM `listings` WHERE `cdn` = :cdn', array(':cdn' => CDN));
//            $r        = sql_prepare('DELETE FROM `'.$type.'` WHERE `listings_id` = :listings_id');
//
//            while($listing = sql_fetch($listings)){
//                $objects = sql_query('SELECT `id`, `file` FROM `'.$type.'` WHERE `listings_id` = :listings_id', array(':listings_id' => $listing['id']));
//
//                while($object = sql_fetch($objects)){
//                    $missing = !file_exists(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-micro.jpg')    or
//                               !file_exists(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-small.jpg')    or
//                               !file_exists(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-large.jpg')    or
//                               !file_exists(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-small@2x.jpg') or
//                               !file_exists(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-large@2x.jpg');
//
//                    if(!$missing){
//                        cli_dot(10, '.', 'green');
//
//                    }else{
//                        if(VERBOSE){
//                            log_console(tr('Deleting link (and possible garbage) for ":type" object ":file" from listing ":listing"', array(':type' => $type, ':file' => $object['file'], ':listing' => $listing['id'])));
//
//                        }else{
//                            cli_dot(10, '!', 'yellow');
//                        }
//
//                        /*
//                         * Delete files to be sure
//                         */
//                        file_delete(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-micro.jpg');
//                        file_delete(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-small.jpg');
//                        file_delete(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-large.jpg');
//                        file_delete(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-small@2x.jpg');
//                        file_delete(ROOT.'data/content/images/'.c_listing_path($listing['id'], ENVIRONMENT).$object['file'].'-large@2x.jpg');
//
//                        $r->execute(array(':listings_id' => $listing['id']));
//                    }
//                }
//            }
//
//            cli_dot(false);
//        }
//
//        log_console('Finished', '', 'green');
//
//    }catch(Exception $e){
//        throw new bException('cdn_clean(): Failed', $e);
//    }
//}
?>
