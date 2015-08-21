<?php
    try{
        $ips = $_CONFIG['security']['signin']['ip_lock'];

        if(($ips === true) or is_numeric($ips)){
            /*
             * Get the last locked IP from the database
             * If there is none, then it's not a problem, it will never match, and
             * require a user with iplock rights to set
             */
            $ips = sql_list('SELECT `ip` FROM `ip_locks` ORDER BY `id` DESC LIMIT '.cfi($ips));

        }elseif(is_string($ips)){
            $ips = array($ips);
        }

        /*
         * Is the current IP allowed?
         */
        foreach($ips as $ip){
            if($ip != $_SERVER['REMOTE_ADDR']){
                $match = true;
                break;
            }
        }

        if(empty($match)){
            /*
             * Current IP was not allowed. If this user has ip_lock rights (or god right, obviously), then we can continue
             */
            if(!has_rights('ip_lock', $user)){
                throw new bException('handlers/user_ip_lock: Your current IP "'.str_log($_SERVER['REMOTE_ADDR']).'" is not allowed to login', 'iplock');
            }

            /*
             * Users with the god right will NOT automatically update the ip_locks table!
             */
            if(!has_rights('god', $user)){
                /*
                 * This user can reset the iplock by simply logging in
                 */
                sql_query('INSERT INTO `ip_locks` (`createdby`, `ip`)
                           VALUES                 (:createdby , :ip )',

                           array(':createdby' => $user['id'],
                                 ':ip'        => $_SERVER['REMOTE_ADDR']));

                html_flash_set(log_database('Updated IP lock to "'.str_log($_SERVER['REMOTE_ADDR']).'"', 'ip_locks_updated'), 'info');
            }
        }

    }catch(Exception $e){
        throw new bException('handlers/user_ip_lock: Failed', $e);
    }
?>
