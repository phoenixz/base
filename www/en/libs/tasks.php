<?php
/*
 * Tasks library
 *
 * This library can store generic tasks in the database
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function tasks_library_init(){
    try{
        load_config('tasks');

    }catch(Exception $e){
        throw new bException('tasks_library_init(): Failed', $e);
    }
}



/*
 * Add the specified task to the tasks table
 */
function tasks_add($task){
    try{
        array_params($task);
        array_default($task, 'status'      , 'new');
        array_default($task, 'method'      , 'normal');
        array_default($task, 'time_limit'  , 30);
        array_default($task, 'auto_execute', true);

        $task = tasks_validate($task);

        load_libs('json');

        sql_query('INSERT INTO `tasks` (`createdby`, `meta_id`, `after`, `status`, `command`, `method`, `time_limit`, `verbose`, `parents_id`, `parrallel`, `data`, `description`)
                   VALUES              (:createdby , :meta_id , :after , :status , :command , :method , :time_limit , :verbose , :parents_id , :parrallel , :data , :description )',

                   array(':createdby'   => $_SESSION['user']['id'],
                         ':meta_id'     => meta_action(),
                         ':status'      => cfm($task['status']),
                         ':command'     => cfm($task['command']),
                         ':parents_id'  => get_null($task['parents_id']),
                         ':parrallel'   => (boolean) $task['parrallel'],
                         ':method'      => cfm($task['method']),
                         ':time_limit'  => cfm($task['time_limit']),
                         ':verbose'     => $task['verbose'],
                         ':after'       => ($task['after'] ? date_convert($task['after'], 'mysql') : null),
                         ':data'        => json_encode_custom($task['data']),
                         ':description' => $task['description']));

        $task['id'] = sql_insert_id();


        if($task['auto_execute']){
            log_file(tr('Added new auto executing task ":description" with id ":id"', array(':description' => $task['description'], ':id' => $task['id'])), 'tasks');
            run_background('base/tasks execute --env '.ENVIRONMENT, true, false);

        }else{
            log_file(tr('Added new task ":description" with id ":id"', array(':description' => $task['description'], ':id' => $task['id'])), 'tasks');
        }

        return $task;

    }catch(Exception $e){
        throw new bException('tasks_add(): Failed', $e);
    }
}



/*
 * Add the specified task to the tasks table
 */
function tasks_update($task, $executed = false){
    try{
        $task = tasks_validate($task);

        load_libs('json');
        meta_action($task['meta_id'], 'update');

        $execute = array(':id'         => $task['id'],
                         ':after'      => $task['after'],
                         ':status'     => $task['status'],
                         ':verbose'    => $task['verbose'],
                         ':executed'   => get_null($task['executed']),
                         ':pid'        => get_null($task['pid']),
                         ':results'    => json_encode_custom($task['results']));

        if($executed){
            $execute[':time_spent'] = $task['time_spent'];
        }

        sql_query('UPDATE `tasks`

                   SET    `after`      = :after,
         '.($executed ? ' `executedon` = NOW(),
                          `time_spent` = :time_spent,' : '').'
                          `executed`   = :executed,
                          `pid`        = :pid,
                          `status`     = :status,
                          `verbose`    = :verbose,
                          `results`    = :results

                   WHERE  `id`         = :id',

                   $execute);

        return $task;

    }catch(Exception $e){
        throw new bException('tasks_update(): Failed', $e);
    }
}



/*
 * Validate the specified task
 *
 * In a task, data may be just about anything and everything (minus objects)
 * since it will pass through json_encode. The only thing it will not store
 * is object types
 */
function tasks_validate($task){
    global $_CONFIG;

    try{
        load_libs('validate');

        $v = new validate_form($task, 'status,command,after,data,results,method,time_limit,executed,time_spent,parents_id,parrallel,verbose');

        if($task['time_limit'] === ""){
            $task['time_limit'] = $_CONFIG['tasks']['default_time_limit'];
        }

        $v->isNotEmpty($task['command'], tr('Please ensure that the task has a command specified'));
        $v->isRegex($task['command'], '/[a-z0-9\/]/', tr('Please ensure that the task command has only alpha-numeric characters'));
        $v->hasMinChars($task['command'], 2, tr('Please ensure the task command has at least 2 characters'));
        $v->hasMaxChars($task['command'], 32, tr('Please ensure the task command has a maximum of 32 characters'));
        $v->isDateTime($task['after'], tr('Please specify a valid after date / time'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->inArray($task['method'], array('background', 'internal', 'normal', 'function'), tr('Please specify a valid method'));
        $v->inArray($task['status'], array('new', 'processing', 'completed', 'failed', 'timeout', 'deleted'), tr('Please specify a valid status'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($task['time_limit'], 0, tr('Please specify a valid time limit'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isBetween($task['time_limit'], 0, 1800, tr('Please specify a valid time limit (between 0 and 1800 seconds)'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNumeric($task['time_spent'], tr('Please specify a valid time spent'), VALIDATE_ALLOW_EMPTY_INTEGER);
        $v->isNatural($task['parents_id'], 1, tr('Please specify a valid parents id'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMinChars($task['description'], 8, tr('Please use more than 8 characters for the description'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->hasMaxChars($task['description'], 2047, tr('Please use more than 8 characters for the description'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isNatural($task['pid'], 1, tr('Please specify a valid pid (process id)'), VALIDATE_ALLOW_EMPTY_NULL);
        $v->isBetween($task['pid'], 1, 65535, tr('Please specify a valid pid (process id)'), VALIDATE_ALLOW_EMPTY_NULL);

        if($task['parents_id']){
            $exists = sql_get('SELECT `id`, `method` FROM `tasks` WHERE `id` = :id', array(':id' => $task['parents_id']));

            if(!$exists){
                $v->setError(tr('Specified parent tasks id ":id" does not exist', array(':id' => $task['parents_id'])));
            }

            if($task['parrallel'] and ($exists['method'] !== 'background')){
                $v->setError(tr('Parrallel tasks require parent task running in mode "background"'));
            }

        }else{
            if($task['parrallel']){
                $v->setError(tr('Parrallel was specified without parents_id'));
            }
        }

        $task['verbose']   = (boolean) $task['verbose'];
        $task['parrallel'] = (boolean) $task['parrallel'];


        if(is_object($task['data'])){
            $v->setError(tr('Specified task data is an object data type, which is not supported'));
        }

        if(is_object($task['results'])){
            $v->setError(tr('Specified task results is an object data type, which is not supported'));
        }

        $v->isValid();

        return $task;

    }catch(Exception $e){
        throw new bException('tasks_validate(): Failed', $e);
    }
}



/*
 * Validate the specified task status
 */
function tasks_validate_status($status){
    try{
        foreach(array_force($status) as $entry){
            switch($entry){
                case 'new':
                    // FALLTHROUGH
                case 'processing':
                    // FALLTHROUGH
                case 'completed':
                    // FALLTHROUGH
                case 'failed':
                    // FALLTHROUGH
                case 'waiting_parent':
                    // FALLTHROUGH
                case 'timeout':
                    // FALLTHROUGH
                case 'deleted':
                    break;

                default:
                    throw new bException(tr('tasks_validate_status(): Unknown status ":status" specified', array(':status' => $entry)), 'unknown');
            }
        }

    }catch(Exception $e){
        throw new bException('tasks_validate_status(): Failed', $e);
    }
}



/*
 * Get a task with the specified status
 */
function tasks_get($filters, $set_status = false, $min_id = null){
    try{
        if(is_natural($filters)){
            $where   = ' WHERE `tasks`.`id` = :id ';

            $execute = array(':id' => $filters);

        }else{
            $filters = array_force($filters);
            $execute = sql_in($filters, ':filter');
            $where   = ' WHERE  `tasks`.`status` IN('.implode(', ', array_keys($execute)).')
                         AND   (`tasks`.`after` IS NULL OR `tasks`.`after` <= UTC_TIMESTAMP()) ';

            if($min_id){
                $where .= ' AND `tasks`.`id` > :id ';
                $execute['id'] = $min_id;
            }
        }

        $task = sql_get('SELECT    `tasks`.`id`,
                                   `tasks`.`meta_id`,
                                   `tasks`.`createdby`,
                                   `tasks`.`parents_id`,
                                   `tasks`.`parrallel`,
                                   `tasks`.`pid`,
                                   `tasks`.`command`,
                                   `tasks`.`status`,
                                   `tasks`.`after`,
                                   `tasks`.`data`,
                                   `tasks`.`verbose`,
                                   `tasks`.`results`,
                                   `tasks`.`time_limit`,
                                   `tasks`.`time_spent`,
                                   `tasks`.`executed`,
                                   `tasks`.`description`,
                                   `tasks`.`method`,

                                   `users`.`name`     AS `createdby_name`,
                                   `users`.`email`    AS `createdby_email`,
                                   `users`.`username` AS `createdby_username`,
                                   `users`.`nickname` AS `createdby_nickname`

                         FROM      `tasks`

                         LEFT JOIN `users`
                         ON        `users`.`id` = `tasks`.`createdby`

                        '.$where.'

                         ORDER BY  `tasks`.`createdon` ASC

                         LIMIT    1',

                         $execute);

        if($task){
            if($set_status){
                tasks_validate_status($set_status);
                meta_action($task['meta_id'], 'set-status', $set_status);
                sql_query('UPDATE `tasks` SET `status` = :status WHERE `id` = :id', array(':id' => $task['id'], ':status' => $set_status));
            }
        }

        return $task;

    }catch(Exception $e){
        throw new bException('tasks_get(): Failed', $e);
    }
}



/*
 * List all tasts with the specified status
 */
function tasks_list($status, $limit = 10){
    try{
        if($status){
            $status = array_force($status);
            tasks_validate_status($status);

            if(count($status) == 1){
                $status = array(':status' => array_shift($status));
                $where  = 'WHERE    `tasks`.`status` = :status
                           AND     (`tasks`.`after` IS NULL OR `tasks`.`after` <= UTC_TIMESTAMP())';

            }else{
                $status = sql_in($status);
                $where  = 'WHERE    `tasks`.`status` IN('.implode(', ', array_keys($status)).')
                           AND     (`tasks`.`after` IS NULL OR `tasks`.`after` <= UTC_TIMESTAMP())';
            }


        }else{
            $where  = '';
            $status = array();
        }

        $task = sql_query('SELECT    `tasks`.`id`,
                                     `tasks`.`meta_id`,
                                     `tasks`.`parents_id`,
                                     `tasks`.`parrallel`,
                                     `tasks`.`pid`,
                                     `tasks`.`command`,
                                     `tasks`.`data`,
                                     `tasks`.`status`,
                                     `tasks`.`after`,
                                     `tasks`.`method`,
                                     `tasks`.`verbose`,
                                     `tasks`.`time_limit`,
                                     `tasks`.`time_spent`,
                                     `tasks`.`executed`,
                                     `tasks`.`description`,

                                     `users`.`name`,
                                     `users`.`email`,
                                     `users`.`username`,
                                     `users`.`nickname`

                           FROM      `tasks`

                           LEFT JOIN `users`
                           ON        `users`.`id` = `tasks`.`createdby`

                           '.$where.'

                           ORDER BY  `tasks`.`createdon` ASC

                           LIMIT     '.$limit,

                           $status);

        return $task;

    }catch(Exception $e){
        throw new bException('tasks_list(): Failed', $e);
    }
}



/*
 * Test if the core MySQL server is still available. If not, disconnect so that
 * later queries will auto reconnect
 */
function task_test_mysql(){
    /*
     * Tasks may have affected the MySQL server or our connection
     * to it. Test MySQL connection. If dropped, restart our
     * connection right now
     */
    try{
        sql_query('SELECT 1');

    }catch(Exception $e){
        $message = $e->getMessage();

        if(!preg_match('/send of .+? bytes failed with .+? Broken pipe/', $message)){
            /*
             * This is a different error, keep on throwing
             */
            throw new bException('task_test_mysql(): Failed', $e);
        }

        /*
         * MySQL server went away, close the connection
         */
        sql_close();
    }
}



/*
 * Reset the specified task, plus all tasks that have this task as a parent
 *
 */
function tasks_reset($tasks_id){
    try{
        sql_query('UPDATE `tasks` SET `status` = "new", `results` = null WHERE `id` = :id', array(':id' => $tasks_id));

        $count    = 1;
        $children = sql_query('SELECT `id` FROM `tasks` WHERE `parents_id` = :parents_id', array(':parents_id' => $tasks_id));

        while($child = sql_fetch($children, true)){
            tasks_reset($child);
            $count++;
        }

        return $count;

    }catch(Exception $e){
        throw new bException('tasks_reset(): Failed', $e);
    }
}



/*
 * Mark the specified task, plus all tasks that have this task as a parent, as
 * failed
 */
function tasks_failed($tasks_id){
    try{
        sql_query('UPDATE `tasks` SET `status` = "failed" WHERE `id` = :id', array(':id' => $tasks_id));

        $count    = 1;
        $children = sql_query('SELECT `id` FROM `tasks` WHERE `parents_id` = :parents_id', array(':parents_id' => $tasks_id));

        while($child = sql_fetch($children, true)){
            tasks_failed($child);
            $count++;
        }

        return $count;

    }catch(Exception $e){
        throw new bException('tasks_failed(): Failed', $e);
    }
}



/*
 *
 */
function tasks_check_pid($tasks_id){
    try{
        $task = sql_get('SELECT `id`, `pid` FROM `tasks` WHERE `id` = :id', array(':id' => $tasks_id));

        if(!$task){
            throw new bException(tr('tasks_check_pid(): Task ":task" does not exist', array(':task' => $tasks_id)), 'not-exist');
        }

        if(!$task['pid']){
            throw new bException(tr('tasks_check_pid(): Task ":task" does not have a pid', array(':task' => $tasks_id)), 'empty');
        }

        load_libs('cli');

        return cli_pid($task['pid']);

    }catch(Exception $e){
        throw new bException('tasks_check_pid(): Failed', $e);
    }
}
?>
