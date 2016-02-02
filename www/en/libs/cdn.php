<?php
/*
 * CDN library
 *
 * This library contains functions to manage the CDN servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */
define('CDN', str_from(ENVIRONMENT, 'cdn'));



/*
 * Send command to CDN servers that must be executed
 *
 * If $id is specified, the command will be sent to only that CDN server
 * If $id is NULL, the command will be sent to all CDN servers
 */
function cdn_commands_send($command, $data, $servers = null){
    global $_CONFIG;

    try{
        load_libs('crypt,curl,html,file');

        /*
         * Process files separately below
         */
        if(!empty($data['files'])){
            $files = $data['files'];
            unset($data['files']);
        }

        /*
         * Encrypt message and data
         */
        $message = encrypt(array('command' => $command, 'data' => $data), $_CONFIG['cdn']['shared_key']);

        /*
         * Build the cURL post array
         */
        $post = array('message' => $message);

        if(!empty($files)){
            /*
             * Add the required files
             */
            $count = 0;

            foreach($files as $file){
                $post['file'.$count++] = curl_file_create($file, file_mimetype($file), $file);
            }
        }

        /*
         * Determine to what CDN servers to send the information (Basically all, or one specifically)
         */
        if(!$servers){
            $servers = $_CONFIG['cdn']['servers'];
        }

        $servers = array_force($servers);

        foreach($servers as $server){
            if(!in_array($server, $_CONFIG['cdn']['servers'])){
                throw new bException(tr('cdn_commands_send(): Specified CDN server ":cdn" does not exist', array(':cdn' => $server)), 'notexist');
            }
        }

        /*
         * Send the message to all required CDN servers
         */
        foreach($servers as $server){
            $server = cdn_prefix($server);
            $start  = microtime(true);

            try{
                if(VERBOSE){
                    log_console(tr('Executing command ":command" on CDN server ":cdn"', array(':command' => $command, ':cdn' => $server)));
                }

                $result = curl_get(array('url'        => $server.'/command.php',
                                         'proxy'      => false,
                                         'getheaders' => false,
                                         'post'       => $post));

                $stop   = microtime(true);
                $time   = number_format($stop - $start, 3);
                $data   = json_decode_custom($result['data']);

                if($command == 'ping'){
                    if(isset_get($data['result']) == 'PONG'){
                        log_console(tr('Pinged CDN server ":cdn" in ":time" miliseconds', array(':cdn' => $server, ':time' => $time)), '', 'green');

                    }else{
                        log_console(tr('Pinged CDN server ":cdn" in ":time" miliseconds, but got unknown reply ":reply"', array(':cdn' => $server, ':time' => $time, ':reply' => $data['result'])), '', 'yellow');
                    }

                }else{
                    if(VERBOSE){
                        log_console(tr('Sent command ":command" to CDN server ":cdn" in ":time" miliseconds', array(':command' => $command, ':cdn' => $server, ':time' => $time)), '', 'green');
                    }
                }

            }catch(Exception $e){
                switch($e->getMessage()){
                    case 'json_decode_custom(): Syntax error, malformed JSON':
                        log_console(tr('Command ":command" to CDN server ":cdn" returned malformed data ":data"', array(':command' => $command, ':cdn' => $server, ':data' => $result['data'])), '', 'red');
                        break;

                    default:
                        log_console(tr('Failed to send command ":command" to CDN server ":cdn" with error ":exception"', array(':command' => $command, ':cdn' => $server, ':exception' => $e->getData()['data'])), '', 'red');
                }
showdie($e->getMessage());
            }
        }

        return $result;

    }catch(Exception $e){
showdie($e);
        if(substr($e->getCode(), 0, 4) == 'HTTP'){
            throw new bException($e->getData()['data'], 403);
        }

        throw new bException('cdn_commands_send(): Failed', $e);
    }
}



/*
 * Send command to CDN servers that must be executed
 */
function cdn_commands_insert($message, $files){
    global $_CONFIG;

    try{
        load_libs('crypt,file');
        $message = decrypt($message, $_CONFIG['cdn']['shared_key']);

        if(!is_array($message)){
            throw new bException(tr('cdn_commands_insert(): Specified message is not an array'), 'invalid');
        }

        if(!is_scalar($message['data'])){
            $message['data'] = json_encode_custom($message['data']);
        }

        if(empty($files)){
            $path = '';

        }else{
            $listing = json_decode_custom($message['data']);
            $path    = ROOT.'data/cdn/processing/'.c_listing_path($listing['listings_id'], ENVIRONMENT);

            file_ensure_path($path);

            foreach($files as $file){
                rename($file['tmp_name'], ROOT.'data/cdn/processing/'.c_listing_path($listing['listings_id'], ENVIRONMENT).$file['name']);
            }
        }

        $status = null;

        switch(isset_get($message['command'])){
            case 'ping':
                json_reply('', 'PONG');
                $status = 'processed';
                // FALLTHROUGH

            case 'place-listing-data':
                // FALLTHROUGH

            case 'trash-listing-data':
                /*
                 * This is a valid command
                 */
                sql_query('INSERT INTO `cdn_commands` (`cdn`, `command`, `path`, `from`, `data`, `status`)
                           VALUES                     (:cdn , :command , :path , :from , :data , :status )',

                           array(':cdn'     => CDN,
                                 ':command' => $message['command'],
                                 ':path'    => $path,
                                 ':status'  => $status,
                                 ':from'    => $_SERVER['REMOTE_ADDR'],
                                 ':data'    => $message['data']));
                break;

            case '':
                log_database('Received empty CDN command', 'cdncommand/empty');
                throw new bException(tr('cdn_commands_insert(): No command specified in message'), 'notspecified');

            default:
                log_database('Received unknown CDN command "'.str_log($message['command']).'"', 'cdncommand/unknown');
                throw new bException(tr('cdn_commands_insert(): Unknown command "%command%" specified in message', array('%command%' => str_log($message['command']))), 'unknown');
        }

        log_database('Inserted CDN command "'.str_log($message['command']).'"', 'cdncommand/'.str_log(isset_get($message['command'])));

    }catch(Exception $e){
        throw new bException('cdn_commands_insert(): Failed', $e);
    }
}



/*
 * Execute all non processed CDN commands
 */
function cdn_commands_process($limit = null, $sleep = 5000){
    try{
        load_libs('file');

        if($limit === null){
            $limit = 10;
        }

        $count    = 0;
        $commands = sql_query('SELECT `id`,
                                      `command`,
                                      `path`,
                                      `data`

                               FROM   `cdn_commands`

                               WHERE  `cdn`    = :cdn
                               AND   (`status` IS NULL
                               OR     `status` = "failed")

                               LIMIT  :limit',

                               array(':cdn'   => CDN,
                                     ':limit' => $limit));

        while($command = sql_fetch($commands)){
            try{
                if(VERBOSE){
                    log_console(tr('Processing CDN ":cdn" command ":command" with id ":id" ', array(':id' => $command['id'], ':cdn' => CDN, ':command' => $command['command'])));

                }else{
                    cli_dot();
                }

                $command['data'] = json_decode_custom($command['data']);

                switch(isset_get($command['command'])){
                    case 'place-listing-data':
                        /*
                         * Move the listing files in place
                         */
                        foreach(scandir($command['path']) as $file){
                            if(($file == '.') or ($file == '..')) continue;

                            $command['path'] = slash($command['path']);
                            $target_path     = ROOT.'data/content/images/'.c_listing_path($command['data']['listings_id'], ENVIRONMENT);

                            file_ensure_path($target_path);

                            /*
                             * Only move files if exist. If not exist, assume they've been
                             * moved already in a previously partially executed process
                             */
                            if(file_exists($command['path'].$file)){
                                rename(slash($command['path']).$file, $target_path.$file);
                            }
                        }

                        file_clear_path($command['path']);

                        /*
                         * Get previous CDN and update the listings CDN id
                         */
                        $cdn = sql_get('SELECT `cdn` FROM `listings` WHERE `id` = :id', 'cdn', array(':id' => $command['data']['listings_id']));
                        sql_query('UPDATE `listings` SET `cdn` = :cdn WHERE `id` = :id', array(':id' => $command['data']['listings_id'], CDN));

                        /*
                         * Remove the files from the previous CDN
                         */
                        cdn_trash_listing_data($command['data']['listings_id'], $cdn);
                        break;

                    case 'trash-listing-data':
                        file_delete_tree(ROOT.'data/content/images/'.c_listing_path($command['data']['listings_id'], ENVIRONMENT));
                        break;

                    case '':
                        log_database('Received empty CDN command', 'cdnexecute/empty');
                        throw new bException(tr('cdn_commands_process(): No command specified in message'), 'notspecified');

                    default:
                        log_database('Received invalid CDN command "'.str_log($command['command']).'"', 'cdnexecute/invalid');
                        throw new bException(tr('cdn_commands_process(): Unknown command "%command%" specified in message', array('%command%' => str_log($command['command']))), 'unknown');
                }

                sql_query('UPDATE `cdn_commands` SET `status` = "processed" WHERE `id` = :id', array(':id' => $command['id']));
                log_database('Excuted CDN command "'.str_log($command['command']).'"', 'cdncommand/'.str_log(isset_get($command['command'])));

                usleep($sleep);
                $count++;

            }catch(Exception $e){
                /*
                 * The command failed to process
                 */
                log_database('CDN command "'.str_log($command['command']).'" failed to execute', 'cdnexecute/failed');
                sql_query('UPDATE `cdn_commands` SET `status` = "failed" WHERE `id` = :id', array(':id' => $command['id']));
                usleep($sleep);
            }
        }

        return $count;

    }catch(Exception $e){
        throw new bException('cdn_commands_process(): Failed', $e);
    }
}



/*
 * Trash all CDN objects related to the specified listing
 */
function cdn_trash_listing_data($listings_id, $cdn = null){
    try{
        if(!$cdn){
            $cdn = sql_get('SELECT `cdn` FROM `listings` WHERE `id` = :id', 'cdn', array(':id' => $listings_id));
        }

        cdn_commands_send('trash-listing-data', array('listings_id' => $listings_id), $cdn);

    }catch(Exception $e){
        throw new bException('cdn_trash_listing(): Failed', $e);
    }
}



/*
 * Test the connection with the CDN servers
 */
function cdn_ping($servers){
    try{
        cdn_commands_send('ping', null, $servers);

    }catch(Exception $e){
        throw new bException('cdn_ping(): Failed', $e);
    }
}



/*
 * Balance all listing data over all CDN servers
 */
function cdn_balance(){
    global $_CONFIG;

    try{
        log_console('Balancing listing data over all CDN servers', '', 'white');

        $cdns     = $_CONFIG['cdn']['servers'];
        $from     = sql_list('SELECT   `cdn`,
                                       COUNT(`id`) AS `count`

                              FROM     `listings`

                              GROUP BY `cdn');

        log_console(tr('Found ":count" CDN servers', array(':count' => count($cdns))));

        /*
         * Check that all configured CDN's are in this from list.
         * If a CDN server is not in the list, it probably is new
         * Add it as having 0 listings assigned.
         */
        foreach($cdns as $cdn){
            if(!isset($from[$cdn])){
                log_console(tr('Found non assigned CDN server ":cdn", will move data there', array(':cdn' => $cdn)));
                $from[$cdn] = 0;
            }
        }

        /*
         * Check $from list, ensure that all CDN's are currently available
         */
        $average = $from;
        $to      = $from;

        foreach($from as $cdn => $count){
            if($count and !in_array($cdn, $cdns)){
                log_console(tr('Found CDN ":cdn" has assigned ":count" listings. Since this CDN is not configured (anymore?), all listings will be removed and redistributed over the other CDN servers.', array(':cdn' => $cdn, ':count' => $count)), '', 'yellow');
                unset($average[$cdn]);
            }
        }

        /*
         * We'll try to average the files over all currently configured CDN servers
         * So calculate the average and copy from high amount servers to low amount servers
         */
        $average  = ceil(array_average($average)) + 2;
        $sum      = array_sum($from);
        $results  = array('failures' => array());

        /*
         * Split the CDN servers into two lists, one where we will copy
         * FROM, and one where we will copy TO
         *
         * We will only copy FROM CDN servers that have MORE than average
         * We will only copy TO CDN servers that have LESS than average
         */
        foreach($from as $cdn => $count){
            if($count and !in_array($cdn, $cdns)){
                /*
                 * This CDN server has listings assigned, but is no longer configured.
                 * Move everything away from it
                 */
                unset($to[$cdn]);
                $from[$cdn] = $count;

            }elseif($count >= $average){
                /*
                 * This CDN server has less than average listings assigned, move the
                 * difference in listings to it
                 */
                unset($to[$cdn]);
                $from[$cdn] = $count - $average;

            }else{
                /*
                 * This CDN server has more than average listings assigned, move the
                 * difference in listings from it
                 */
                unset($from[$cdn]);
                $to[$cdn] = $average - $count;
            }
        }

        /*
         * Now we know how many listings to copy from where and how many to copy to where
         *
         * Go over all $from servers and move files to $to servers
         * Calculate the amount of listings that are too many on this
         * server and move only that amount away to other servers.
         */
        foreach($from as $from_cdn => $limit){
            $listings = sql_query('SELECT `id`

                                   FROM   `listings`

                                   WHERE  `cdn` = :cdn

                                   ORDER BY RAND()

                                   LIMIT  :limit',

                                   array(':cdn'   => $from_cdn,
                                         ':limit' => $limit));

            while($listing = sql_fetch($listings)){
                /*
                 * Pick a random new CDN server
                 */
                $to_cdn = array_rand($to);

                if(--$to[$to_cdn] <= 0){
                    /*
                     * This CDN server is full, no longer send information here
                     */
                    unset($to[$to_cdn]);
                }

                try{
                    $result = cdn_move_listing_data($listing['id'], $from_cdn, $to_cdn);

                    if(!$result){
                        /*
                         * This listing currently has no data, so its not moving anything.
                         * Update the CDN number manually here.
                         */
                        sql_query('UPDATE `listings` SET `cdn` = :cdn WHERE `id` = :id', array(':id' => $listing['id'], ':cdn' => $to_cdn));
                    }

                }catch(Exception $e){
                    /*
                     * Oops, this one failed
                     */
                    $results['failures'][] = $listing['id'];
                }
            }
        }

        return $results;

    }catch(Exception $e){
showdie($e);
        throw new bException('cdn_balance(): Failed', $e);
    }
}



/*
 * Move all listing data (images, videos, etc) away from the specified CDN server
 * so that the server can be removed
 */
function cdn_remove($cdn){
    try{
		if(!$cdn){
			throw new bException(tr('cdn_remove(): No CDN specified'), 'notspecified');
		}

		if(!is_numeric($cdn)){
			throw new bException(tr('cdn_remove(): Invalid CDN ":cdn" specified, must be numeric', array(':cdn' => $cdn)), 'invalid');
		}

		if(!in_array($cdn, $_CONFIG['cdn']['servers'])){
			throw new bException(tr('cdn_remove(): Specified CDN ":cdn" does not exist, check "$_CONFIG[cdn][servers]" configuration', array(':cdn' => $cdn)), 'invalid');
		}

        $counts   = sql_list('SELECT   `cdn`,
                                       COUNT(`id`) AS `count`

                              FROM     `listings`

                              GROUP BY `cdn');

        $cdns     = array_filter_values($_CONFIG['cdn']['servers'], $cdn);
        $to_cdn   = current($cdns);
        $average  = $count / $cdns;
        $results  = array('failures' => array());

        $listings = sql_query('SELECT `id`

                               FROM   `listings`

                               WHERE  `cdn` = :cdn',

                               array(':cdn' => $cdn));

        $results['results'] = $listings->rowCount();

        while($listing = sql_fetch($listings)){
            /*
             * Search next available CDN server
             */
            $search = true;

            while($search){
                $to_cdn = array_next_value($cdns, $to_cdn);

                if(++$counts[$to_cdn] > $average){
                    /*
                     * This one has already more than the average, fill up the
                     * other CDN servers, unless this is the final one
                     */
                    if(count($cdns) > 1){
                        unset($cdns[$to_cdn]);
                        continue;
                    }

                    $search = false;
                }
            }

            try{
                cdn_move_listing_data($listing['id'], $cdn, $to_cdn);

            }catch(Exception $e){
                /*
                 * Oops, this one failed
                 */
                $results['failures'][] = $listing['id'];
            }
        }

        return $results;

    }catch(Exception $e){
        throw new bException('cdn_remove(): Failed', $e);
    }
}



/*
 * Move all listing data from its current CDN server to the specified CDN server
 */
function cdn_move_listing_data($listings_id, $from_cdn, $to_cdn){
    try{
        $files = sql_list('SELECT `file`

                           FROM   `images`

                           WHERE  `listings_id` = :listings_id',

                           array(':listings_id' => $listings_id));

        foreach($files as $file){
            foreach(array('micro', 'small', 'large') as $type){
                $sendfile = ROOT.'data/content/images/'.c_listing_path($listings_id, ((substr(ENVIRONMENT, 0, 3) == 'cdn') ? '' : ENVIRONMENT.'_').'cdn'.$from_cdn).$file.'-'.$type.'.jpg';

                if(!file_exists($sendfile)){
                    /*
                     * This file does not exist, remove it from the database.
                     */
                    if(VERBOSE){
                        log_console(tr('Removing non existing image ":image" from listing ":listing" from database', array(':listing' => $listings_id, ':image' => $file)), '', 'yellow');
                    }

                    sql_query('DELETE FROM `images`

                               WHERE       `listings_id` = :listings_id
                               AND         `file`        = :file',

                               array(':listings_id' => $listings_id,
                                     ':file'        => $file));

                }else{
                    $sendfiles[] = $sendfile;
                }
            }
        }

        if(empty($sendfiles)){
            log_console(tr('Not moving data for listing ":listing" because no images are linked', array(':listing' => $listings_id)), '', 'yellow');
            return false;

        }else{
            return cdn_commands_send('place-listing-data', array('listings_id' => $listings_id, 'files' => $sendfiles), $to_cdn);
        }

    }catch(Exception $e){
        throw new bException('cdn_move_listing_data(): Failed', $e);
    }
}



/*
 * Receive all data (images, video, etc) for the specified listing, and place
 * it on the required directories
 */
function cdn_place_listing_data($listing, $files){
    try{
        load_libs('file');

        $path = ROOT.'data/images/'.c_listing_path($listing['id']);
        file_ensure_path($path);

        foreach($files as $file){
            rename($file, $path);
        }

        return true;

    }catch(Exception $e){
        throw new bException('cdn_place_listing_data(): Failed', $e);
    }
}



/*
 * Remove all image and video links that point to non existing files (which would cause 404)
 */
function cdn_clean(){
    try{
        $listings = sql_query('SELECT `id` FROM `listings` WHERE `cdn` = :cdn', array(':cdn' => $cdn));
        $p        = sql_prepare('SELECT `id`, `file` FROM `listings` WHERE `listings_id` = :listings_id');
        $r        = sql_prepare('DELETE FROM `listings` WHERE `listings_id` = :listings_id');

        while($listing = sql_fetch($listings)){
            $image = $p->execute(array(':listings_id' => $listing['id']));

            if(!file_exists(ROOT.'data/cdn/processing/'.c_listing_path($listing['listings_id'], ENVIRONMENT).$image['file'])){
                $r->execute(array(':listings_id' => $listing['id']));
            }
        }

    }catch(Exception $e){
        throw new bException('cdn_clean(): Failed', $e);
    }
}
?>
