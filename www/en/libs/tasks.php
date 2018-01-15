<?php
/*
 * Tasks library
 *
 * This library can store generic tasks in the database
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Add the specified task to the tasks table
 */
function tasks_add($task){
    try{
        array_params($task);
        array_default($task, 'status'    , 'new');
        array_default($task, 'method'    , 'normal');
        array_default($task, 'time_limit', 30);

        $task = tasks_validate($task);

        load_libs('meta,json');

        sql_query('INSERT INTO `tasks` (`createdby`, `meta_id`, `after`, `status`, `command`, `method`, `time_limit`, `parents_id`, `data`, `description`)
                   VALUES              (:createdby , :meta_id , :after , :status , :command , :method , :time_limit , :parents_id , :data , :description )',

                   array(':createdby'   => $_SESSION['user']['id'],
                         ':meta_id'     => meta_action(),
                         ':status'      => cfm($task['status']),
                         ':command'     => cfm($task['command']),
                         ':parents_id'  => get_null($task['parents_id']),
                         ':method'      => cfm($task['method']),
                         ':time_limit'  => cfm($task['time_limit']),
                         ':after'       => ($task['after'] ? date_convert($task['after'], 'mysql') : null),
                         ':data'        => json_encode_custom($task['data']),
                         ':description' => $task['description']));

        $task['id'] = sql_insert_id();

        log_file(tr('Added new task ":description" with id ":id"', array(':description' => $task['description'], ':id' => $task['id'])), 'tasks');
        run_background('base/tasks execute --env '.ENVIRONMENT);

        return $task['id'];

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

        load_libs('meta,json');

        meta_action($task['meta_id'], 'update');

        sql_query('UPDATE `tasks`

                   SET    `after`      = :after,
         '.($executed ? ' `executedon` = NOW(), ' : '').'
                          `status`     = :status,
                          `results`    = :results

                   WHERE  `id`         = :id',

                   array(':id'         => $task['id'],
                         ':after'      => $task['after'],
                         ':status'     => $task['status'],
                         ':results'    => json_encode_custom($task['results'])));

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
    try{
        load_libs('validate');

        $v = new validate_form($task, 'status,command,after,data,results,method,time_limit,time_spent,parents_id');

        if($task['command']){
            $v->isRegex($task['command'], '/[a-z0-9\/]/', tr('Please ensure that the task command has only alpha-numeric characters'));
            $v->hasMinChars($task['command'], 2, tr('Please ensure the task command has at least 2 characters'));
            $v->hasMaxChars($task['command'], 32, tr('Please ensure the task command has a maximum of 32 characters'));

        }else{
            $v->setError(tr('Please ensure that the task has a command specified'));
        }

        $v->isDateTime($task['after'], tr('Please specify a valid after date / time'), null);
        $v->inArray($task['method'], array('background', 'internal', 'normal', 'function'), tr('Please specify a valid method'));
        $v->inArray($task['status'], array('new', 'processing', 'completed', 'failed', 'timeout', 'deleted'), tr('Please specify a valid status'));
        $v->isNatural($task['time_limit'], 1, tr('Please specify a valid time limit'), null);
        $v->isBetween($task['time_limit'], 1, 600, tr('Please specify a valid time limit (between 0 and 600 seconds)'), null);
        $v->isNumeric($task['time_spent'], tr('Please specify a valid time spent'), null);
        $v->isNatural($task['parents_id'], 1, tr('Please specify a valid parents id'), null);
        $v->hasMinChars($task['description'], 8, tr('Please use more than 8 characters for the description'), null);
        $v->hasMaxChars($task['description'], 2047, tr('Please use more than 8 characters for the description'), null);

        if($task['parents_id']){
            $exists = sql_get('SELECT `id` FROM `tasks` WHERE `id` = :id', true, array(':id' => $task['parents_id']));

            if(!$exists){
                $v->setError(tr('Specified parent tasks id ":id" does not exist', array(':id' => $task['parents_id'])));
            }
        }

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
function tasks_get($filter, $set_status = false){
    try{
        if(is_natural($filter)){
            $where   = ' WHERE `id` = :id ';

            $execute = array(':id' => $filter);

        }else{
            $where = ' WHERE  `status` = :status
                       AND   (`after` IS NULL OR `after` <= UTC_TIMESTAMP()) ';

            $execute = array(':status' => $filter);
        }

        $task = sql_get('SELECT   `id`,
                                  `meta_id`,
                                  `parents_id`,
                                  `command`,
                                  `status`,
                                  `after`,
                                  `data`,
                                  `results`,
                                  `time_limit`,
                                  `time_spent`,
                                  `description`,
                                  `method`

                         FROM     `tasks`

                        '.$where.'

                         ORDER BY `createdon` ASC

                         LIMIT    1',

                         $execute);

        if($task){
            if($set_status){
                tasks_validate_status($set_status);
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
                                     `tasks`.`command`,
                                     `tasks`.`data`,
                                     `tasks`.`status`,
                                     `tasks`.`after`,
                                     `tasks`.`method`,
                                     `tasks`.`time_limit`,
                                     `tasks`.`time_spent`,
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
?>
