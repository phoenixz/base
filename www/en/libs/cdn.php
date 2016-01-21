<?php
/*
 * CDN library
 *
 * This library contains functions to manage the CDN servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */


/*
 * Send command to CDN servers that must be executed
 *
 * If $id is specified, the command will be sent to only that CDN server
 * If $id is NULL, the command will be sent to all CDN servers
 */
function cdn_commands_send($command, $data, $id = null){
    global $_CONFIG;

    try{
        load_libs('crypt,curl,html');
        $message = encrypt(array('command' => $command, 'data' => $data), $_CONFIG['cdn']['shared_key']);

        if($id){
            $from  = $id;
            $until = $id;

        }else{
            $from  = 1;
            $until = $_CONFIG['cdn']['servers'];
        }

        for($server = $from; $server <= $until; $server++){
            $server = cdn_prefix($server);
            $result = curl_get(array('url'        => $server.'/command.php',
                                     'getheaders' => false,
                                     'post'       => array('message' => $message)));
        }

    }catch(Exception $e){
        if(substr($e->getCode(), 0, 4) == 'HTTP'){
            throw new bException($e->getData()['data'], 403);
        }

        throw new bException('cdn_commands_send(): Failed', $e);
    }
}



/*
 * Send command to CDN servers that must be executed
 */
function cdn_commands_insert($message){
    global $_CONFIG;

    try{
        load_libs('crypt');
        $message = decrypt($message, $_CONFIG['cdn']['shared_key']);

        if(!is_array($message)){
            throw new bException(tr('cdn_commands_insert(): Specified message is not an array'), 'invalid');
        }

        if(!is_scalar($message['data'])){
            $message['data'] = json_encode_custom($message['data']);
        }

        switch(isset_get($message['command'])){
            case 'trash_listing':
                // FALLTHROUGH
            case 'test':
                /*
                 * This is a valid command
                 */
                sql_query('INSERT INTO `cdn_commands` (`command`, `data`)
                           VALUES                     (:command , :data )',

                           array(':command' => $message['command'],
                                 ':data'    => $message['data']));
                break;

            case '':
                log_database('Received empty CDN command', 'cdncommand/empty');
                throw new bException(tr('cdn_commands_insert(): No command specified in message'), 'notspecified');

            default:
                log_database('Received invalid CDN command "'.str_log($message['command']).'"', 'cdncommand/invalid');
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
        if($limit === null){
            $limit = 10;
        }

        $count    = 0;
        $commands = sql_query('SELECT `id`, `message`, `data` FROM `cdn_commands` WHERE `status` IS NULL LIMIT '.$limit);

        while($command = sql_fetch($commands)){
            try{
                switch(isset_get($command['command'])){
                    case 'trash_listing':
                        $images = sql_query('SELECT `id` FROM `images` WHERE `listings_id` = :listings_id', array(':listings_id' => $command['data']['listings_id']));

                        /*
                         * Delete all images related to this listing
                         */
                        while($image = sql_fetch($images)){
                            file_clear_path(ROOT.'data/content/images/'.$command['data']['listings_id'].'/'.$image.'-original.jpg');
                            file_clear_path(ROOT.'data/content/images/'.$command['data']['listings_id'].'/'.$image.'-micro.jpg');

                            file_clear_path(ROOT.'data/content/images/'.$command['data']['listings_id'].'/'.$image.'-small.jpg');
                            file_clear_path(ROOT.'data/content/images/'.$command['data']['listings_id'].'/'.$image.'-large.jpg');

                            file_clear_path(ROOT.'data/content/images/'.$command['data']['listings_id'].'/'.$image.'-small@2x.jpg');
                            file_clear_path(ROOT.'data/content/images/'.$command['data']['listings_id'].'/'.$image.'-large@2x.jpg');
                        }

                        /*
                         * Delete all DB data related to this listing
                         */
                        sql_query('DELETE FROM `images`   WHERE `listings_id` = :listings_id', array(':listings_id' => $command['data']['listings_id']));
                        sql_query('DELETE FROM `flags`    WHERE `listings_id` = :listings_id', array(':listings_id' => $command['data']['listings_id']));
                        sql_query('DELETE FROM `renewals` WHERE `listings_id` = :listings_id', array(':listings_id' => $command['data']['listings_id']));
                        sql_query('DELETE FROM `listings` WHERE `id`          = :id'         , array(':id'          => $command['data']['listings_id']));
                        log_database('Excuted CDN command "'.str_log($command['command']).'"', 'cdncommand/'.str_log(isset_get($command['command'])));
                        break;

                        case '':
                            log_database('Received empty CDN command', 'cdnexecute/empty');
                            throw new bException(tr('cdn_commands_process(): No command specified in message'), 'notspecified');

                        default:
                            log_database('Received invalid CDN command "'.str_log($command['command']).'"', 'cdnexecute/invalid');
                            throw new bException(tr('cdn_commands_process(): Unknown command "%command%" specified in message', array('%command%' => str_log($message['command']))), 'unknown');
                }

                sql_query('UPDATE `cdn_commands` SET `status` = "processed" WHERE `id` = :id', array(':id' => $command['id']));
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
function cdn_trash_listing($listing){
    try{
        cdn_commands_send('trash-listing', $listing);

    }catch(Exception $e){
        throw new bException('cdn_trash_listing(): Failed', $e);
    }
}



/*
 * Test the connection with the CDN servers
 */
function cdn_test(){
    try{
        cdn_commands_send('test', null);

    }catch(Exception $e){
        throw new bException('cdn_test(): Failed', $e);
    }
}
?>
