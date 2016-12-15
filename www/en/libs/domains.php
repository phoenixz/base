<?php
/*
 * Custom domains library
 *
 * This library contains functions to manage toolkit domains
 *
 * Written and Copyright by Sven Oostenbrink
 */



/*
 *
 */
function domains_validate($domain){
    global $_CONFIG;

    try{
        load_libs('validate,seo');

        $v = new validate_form($domain, 'port,provider,customer,description,mx_domain,domain,servers');
        $v->isNotEmpty($domain['provider'], tr('Please specifiy a provider for this domain'));
        $v->isNotEmpty($domain['customer'], tr('Please specifiy a customer for this domain'));
        $v->isNotEmpty($domain['domain']  , tr('Please specifiy a domain name'));

        /*
         * Validate provider, customer, and ssh account
         */
        $domain['providers_id']  = sql_get('SELECT `id` FROM `providers` WHERE `seoname`   = :seoname   AND `status` IS NULL', array(':seoname'   => $domain['provider']) , 'id');
        $domain['customers_id']  = sql_get('SELECT `id` FROM `customers` WHERE `seoname`   = :seoname   AND `status` IS NULL', array(':seoname'   => $domain['customer']) , 'id');
        $domain['mx_domains_id'] = sql_get('SELECT `id` FROM `domains`   WHERE `seodomain` = :seodomain AND `status` IS NULL', array(':seodomain' => $domain['mx_domain']), 'id');

        if(!$domain['providers_id']){
            $v->setError(tr('domains_validate(): Specified provider ":provider" does not exist', array(':provider' => $domain['provider'])));
        }

        if(!$domain['customers_id']){
            $v->setError(tr('domains_validate(): Specified customer ":customer" does not exist', array(':customer' => $domain['customer'])));
        }

        /*
         * Already exists?
         */
        if($domain['id']){
            if(sql_get('SELECT `id` FROM `domains` WHERE `domain` = :domain AND `id` != :id', array(':domain' => $domain['domain'], ':id' => $domain['id']), 'id')){
                $v->setError(tr('domains_validate(): Domain ":domain" already exists', array(':domain' => $domain['domain'])));
            }

        }else{
            if(sql_get('SELECT `id` FROM `domains` WHERE `domain` = :domain', array(':domain' => $domain['domain']), 'id')){
                $v->setError(tr('domains_validate(): Domain ":domain" already exists', array(':domain' => $domain['domain'])));
            }
        }

        $domain['seodomain']  = seo_unique($domain['domain'], 'domains', $domain['id'], 'seodomain');

        if(!$domain['servers']){
            $domain['servers'] = null;

        }else{
            if(!is_array($domain['servers'])){
                throw new bException(tr('domains_validate(): Invalid servers data specified'), 'invalid');

            }else{
                $server_list = array();

                foreach($domain['servers'] as $server){
                    if(!$server) continue;

                    $servers_id = sql_get('SELECT `id` FROM `servers` WHERE `seohostname` = :seohostname AND `status` IS NULL', array(':seohostname' => $server), 'id');
                    $server_list[] = $servers_id;

                    if(!$servers_id){
                        $v->setError(tr('domains_validate(): Specified server ":server" does not exist', array(':server' => $server)));
                    }
                }

                $domain['server_list'] = $server_list;
            }
        }

        $v->isValid();
        return $domain;

    }catch(Exception $e){
        throw new bException('domains_validate(): Failed', $e);
    }
}



/*
 *
 */
function domains_get($domain = null){
    global $_CONFIG;

    try{
        $query = 'SELECT    `domains`.`id`,
                            `domains`.`createdon`,
                            `domains`.`modifiedon`,
                            `domains`.`status`,
                            `domains`.`domain`,
                            `domains`.`seodomain`,
                            `domains`.`description`,
                            `domains`.`web`,
                            `domains`.`mail`,

                            `createdby`.`name`   AS `createdby_name`,
                            `createdby`.`email`  AS `createdby_email`,
                            `modifiedby`.`name`  AS `modifiedby_name`,
                            `modifiedby`.`email` AS `modifiedby_email`,

                            `providers`.`seoname`    AS `provider`,
                            `customers`.`seoname`    AS `customer`,
                            `mx_domains`.`seodomain` AS `mx_domain`

                  FROM      `domains`

                  LEFT JOIN `users` AS `createdby`
                  ON        `domains`.`createdby`  = `createdby`.`id`

                  LEFT JOIN `users` AS `modifiedby`
                  ON        `domains`.`modifiedby` = `modifiedby`.`id`

                  LEFT JOIN `providers`
                  ON        `providers`.`id`       = `domains`.`providers_id`

                  LEFT JOIN `customers`
                  ON        `customers`.`id`       = `domains`.`customers_id`

                  LEFT JOIN `domains` AS `mx_domains`
                  ON        `mx_domains`.`id`      = `domains`.`mx_domains_id`';

        if($domain){
            if(!is_string($domain)){
                throw new bException(tr('domains_get(): Specified domain name ":name" is not a string', array(':name' => $domain)), 'invalid');
            }

            $retval = sql_get($query.'

                              WHERE      `domains`.`seodomain` = :seodomain
                              AND        `domains`.`status` IS NULL',

                              array(':seodomain' => $domain));

        }else{
            /*
             * Pre-create a new domain
             */
            $retval = sql_get($query.'

                              WHERE  `domains`.`createdby` = :createdby

                              AND    `domains`.`status`    = "_new"',

                              array(':createdby' => $_SESSION['user']['id']));

            if(!$retval){
                sql_query('INSERT INTO `domains` (`createdby`, `status`)
                           VALUES                (:createdby , :status )',

                           array(':status'    => '_new',
                                 ':createdby' => isset_get($_SESSION['user']['id'])));

                return domains_get($domain);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('domains_get(): Failed', $e);
    }
}




/*
 * Update the linked servers for the specified domain
 */
function domains_update_servers($domain){
    global $_CONFIG;

    try{
        sql_query('DELETE FROM `domains_servers` WHERE `domains_id` = :domains_id', array(':domains_id' => $domain['id']));
        $insert = sql_prepare('INSERT INTO `domains_servers` (`domains_id`, `servers_id`) VALUES (:domains_id, :servers_id)');

        if(empty($domain['server_list'])){
            return false;
        }

        foreach($domain['server_list'] as $servers_id){
            $insert->execute(array(':domains_id' => $domain['id'],
                                   ':servers_id' => $servers_id));
        }

        return count($domain['server_list']);

    }catch(Exception $e){
        throw new bException('domains_update_servers(): Failed', $e);
    }
}
?>
