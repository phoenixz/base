<?php
/*
 * Log library
 *
 * This is the log library, and contains various log functions
 *
 * Written and Copyright by Sven Oostenbrink
 */



/*
 * Return an array containing the columns of the log table
 */
function log_columns(){
    return array('added',
                 'users_id',
                 'repeat',
                 'type',
                 'level',
                 'message');
}



/*
 * Return a list of available log types
 */
function log_type_list(){
    global $pdo;

    try{
        $r = $pdo->query('SELECT DISTINCT `log`.`type` FROM `log`');

        $retval = array();

        while($type = $r->fetchColumn(0)){
            $retval[] = $type;
        }

        return $retval;

    }catch(Exception $e){
        throw new lsException('log_types(): Failed', $e);
    }
}



/*
 * Return an HTML select containing all posisble log
 */
function log_type_select($select = '', $name = 'log_id', $god = true){
    global $pdo;

    try{
        //if($retval = cache_read('log_type_'.$name.'_'.$select.($god ? '_all' : ''))){
        //    return $retval;
        //}

        $retval = '<select class="categories" name="'.$name.'">';

        if($god){
            $retval .= '<option value="0"'.(!$select ? ' selected' : '').'>All categories</option>';
        }

        foreach(log_type_list() as $log){
            $retval .= '<option value="'.$log.'"'.(($log == $select) ? ' selected' : '').'>'.str_replace('_', ' ', strtoupper($log)).'</option>';
        }

        return $retval.'</select>';

//        return cache_write('log_type_'.$name.'_'.$select.($god ? '_all' : ''), $retval.'</select>');

    }catch(Exception $e){
        throw new lsException('log_type_select(): Failed', $e);
    }
}



/*
 * Return a list of the requested log entries
 */
function log_list($columns = null, $type = null, $level = 0, $from = 0, $until = 0, $count = 0, $offset = 0){
    global $pdo, $_CONFIG;

    try{
        /*
         * Validate parameters
         */
        if(!is_numeric($count) or ($count < 1) or ($count > 10000)){
            $count = $_CONFIG['paging']['count'];
        }

        if(!is_numeric($offset) or ($offset < 0)){
            $offset = 0;
        }

        if(!is_array($columns)){
            if(!is_string($columns)){
                throw new lsException('log_list(): Columns should be specified either as string or array', 'invalid');
            }

            if($columns == 'all'){
                $columns  = array();
                $get_user = true;

            }else{
                $columns = str_explode(',', $columns);
            }
        }

        if(!$columns){
            $columns = log_columns();
        }

        if(!in_array('id', $columns)){
            $columns[] = 'id';
        }

        if($key = array_search('name', $columns)){
            unset($columns[$key]);
            $get_user = true;
        }

        /*
         * Setup parameter filter
         */
        $where   = array();
        $execute = array();

        if($from){
            $where[]          = '`users`.`added` >= :from';
            $execute[':from'] = $from;
        }

        if($until){
            $where[]           = '`users`.`added` <= :until';
            $execute[':until'] = $until;
        }

        if($level){
            $where[]           = '`users`.`level` <= :level';
            $execute[':level'] = $level;
        }

        if($type){
            $where[]           = '`users`.`type`  <= :type';
            $execute[':type'] = $type;
        }

        if(!count($where)){
            $where = '';

        }else{
            $where = ' WHERE '.implode('AND', $where);
        }

        /*
         * Execute the query and return results
         */
        $retval = array();

        if(empty($get_user)){
            $r = $pdo->prepare('SELECT   `log`.`'.implode('`, `log`.`', $columns).'`

                                FROM     `log`'.

                                $where.(($count or $offset) ? '

                                ORDER BY `added` DESC LIMIT '.$offset.($count ? ', '.$count : '') : ''));

        }else{
            /*
             * Obtain user name
             */
            $r = $pdo->prepare('SELECT    `log`.`'.implode('`, `log`.`', $columns).'`, `users`.`name` AS `name`

                                FROM      `log`

                                LEFT JOIN `users`
                                ON        `users`.`id` = `log`.`users_id`'.

                                $where.(($count or $offset) ? '

                                ORDER BY  `added` DESC LIMIT '.$offset.($count ? ', '.$count : '') : ''));
        }

        $r->execute($execute);


        while($log = $r->fetch(PDO::FETCH_ASSOC)){
            $retval[] = $log;
        }

        return $retval;

    }catch(Exception $e){
        throw new lsException('log_list(): Failed', $e);
    }
}
?>
