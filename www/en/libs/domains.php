<?php
/*
 * Custom domains library
 *
 * This library contains functions to manage toolkit domains
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package domains
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the domains library configuration
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package domains
 *
 * @return void
 */
function domains_library_init(){
    try{
        load_config('domains');

    }catch(Exception $e){
        throw new bException('domains_library_init(): Failed', $e);
    }
}



/*
 *
 */
function domains_validate($domain){
    global $_CONFIG;

    try{
        load_libs('validate,seo');

        $v = new validate_form($domain, 'provider,customer,servers,mx_domain,description,domain');
        $v->isNotEmpty($domain['domain']  , tr('Please specifiy a domain name'));

        /*
         * Validate provider, customer
         */
        if($domain['provider']){
            $domain['providers_id'] = sql_get('SELECT `id` FROM `providers` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $domain['provider']), 'id');

            if(!$domain['providers_id']){
                $v->setError(tr('Specified provider ":provider" does not exist', array(':provider' => $domain['provider'])));
            }

        }else{
            $domain['providers_id'] = null;
        }

        if($domain['customer']){
            $domain['customers_id'] = sql_get('SELECT `id` FROM `customers` WHERE `seoname` = :seoname AND `status` IS NULL', array(':seoname' => $domain['customer']), 'id');

            if(!$domain['customers_id']){
                $v->setError(tr('Specified customer ":customer" does not exist', array(':customer' => $domain['customer'])));
            }

        }else{
            $domain['customers_id'] = null;
        }

        $domain['mx_domains_id'] = sql_get('SELECT `id` FROM `email_servers` WHERE `seodomain` = :seodomain AND `status` IS NULL', array(':seodomain' => $domain['mx_domain']), 'id');

        /*
         * Check if the domain already exists
         * Make seodomain
         */
        $exists = sql_get('SELECT `id` FROM `domains` WHERE `domain` = :domain AND `id` != :id', array(':domain' => $domain['domain'], ':id' => isset_get($domain['id'], 0)), 'id');

        if($exists){
            $v->setError(tr('Domain ":domain" already exists', array(':domain' => $domain['domain'])));
        }

        $domain['seodomain'] = seo_unique($domain['domain'], 'domains', $domain['id'], 'seodomain');

        /*
         * Validate specified server
         */
        if(!$domain['servers']){
            $domain['servers'] = null;

        }else{
            if(!is_array($domain['servers'])){
                throw new bException(tr('Invalid servers data specified'), 'invalid');

            }else{
                $servers = array();

                foreach($domain['servers'] as $server){
                    if(!$server) continue;

                    $servers_id = sql_get('SELECT `id` FROM `servers` WHERE `seodomain` = :seodomain AND `status` IS NULL', array(':seodomain' => $server), 'id');
                    $servers[] = $servers_id;

                    if(!$servers_id){
                        $v->setError(tr('Specified server ":server" does not exist', array(':server' => $server)));
                    }
                }

                $domain['servers'] = $servers;
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
function domains_validate_keyword($keyword){
    global $_CONFIG;

    try{
        load_libs('validate,seo');

        $v = new validate_form($keyword, 'keyword');
        $v->isNotEmpty($keyword['keyword'], tr('Please specifiy a domain keyword'));

        $v->isValid();

        $v->hasMaxChars($keyword['keyword'], 64, tr('Please specifiy a domain keyword of less than 64 characters'));
        $v->isAlphaNumeric($keyword['keyword'], tr('Please specifiy a valid domain keyword ([a-z-]+)'), VALIDATE_IGNORE_DASH);

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `domains_keywords` WHERE `keyword` = :keyword', true, array(':keyword' => $keyword['keyword']));

        if($exists){
            $v->setError(tr('Specified keyword ":keyword" already exists', array(':keyword' => $keyword['keyword'])));
        }

        $v->isValid();

        $keyword['seokeyword'] = seo_string($keyword['keyword']);

        return $keyword;

    }catch(Exception $e){
        throw new bException('domains_validate_keyword(): Failed', $e);
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
                            `domains`.`createdby`,
                            `domains`.`meta_id`,
                            `domains`.`status`,
                            `domains`.`type`,
                            `domains`.`domain`,
                            `domains`.`seodomain`,
                            `domains`.`description`,

                            `createdby`.`name`   AS `createdby_name`,
                            `createdby`.`email`  AS `createdby_email`,

                            `providers`.`seoname`    AS `provider`,
                            `customers`.`seoname`    AS `customer`,
                            `mx_domains`.`domain`    AS `mx_domain`,
                            `mx_domains`.`seodomain` AS `mx_seodomain`

                  FROM      `domains`

                  LEFT JOIN `users` AS `createdby`
                  ON        `domains`.`createdby`  = `createdby`.`id`

                  LEFT JOIN `providers`
                  ON        `providers`.`id`       = `domains`.`providers_id`

                  LEFT JOIN `customers`
                  ON        `customers`.`id`       = `domains`.`customers_id`

                  LEFT JOIN `email_servers` AS `mx_domains`
                  ON        `mx_domains`.`id`      = `domains`.`mx_domains_id` ';

        if($domain){
            if(!is_string($domain)){
                throw new bException(tr('domains_get(): Specified domain name ":name" is not a string', array(':name' => $domain)), 'invalid');
            }

            $retval = sql_get($query.'

                              WHERE     (`domains`.`seodomain` = :seodomain OR `domains`.`domain` = :domain)
                              AND       (`domains`.`status` IS NULL OR (`domains`.`type` = "scan" AND `domains`.`status` IN ("exists", "available")))',

                              array(':domain'    => $domain,
                                    ':seodomain' => $domain));

        }else{
            /*
             * Pre-create a new domain
             */
            $retval = sql_get($query.'

                              WHERE  `domains`.`createdby` = :createdby
                              AND    `domains`.`status`    = "_new"',

                              array(':createdby' => $_SESSION['user']['id']));

            if(!$retval){
                sql_query('INSERT INTO `domains` (`createdby`, `meta_id`, `status`)
                           VALUES                (:createdby , :meta_id , :status )',

                           array(':status'    => '_new',
                                 ':meta_id'   => meta_action(),
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
 * List all linked domains for the specified server
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package servers
 *
 * @param mixed $domain The domain for which the servers must be returned. May be specified by id, domain, seodomain, or domains array
 * @return array The servers for the specified domain
 */
function domains_list_servers($domain){
    global $_CONFIG;

    try{
        $domain  = domains_get_id($domain);
        $servers = sql_list('SELECT   `servers`.`domain`,
                                      `servers`.`seodomain`

                             FROM     `domains_servers`

                             JOIN     `servers`
                             ON       `domains_servers`.`domains_id` = :domains_id
                             AND      `domains_servers`.`servers_id` = `servers`.`id`
                             AND      `servers`.`status`             IS NULL

                             ORDER BY `servers`.`domain` ASC',

                             array(':domains_id' => $domain));

        return $servers;

    }catch(Exception $e){
        throw new bException('domains_list_servers(): Failed', $e);
    }
}



/*
 * Update the linked servers for the specified domain
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package domains
 *
 * @param mixed $domain
 * @param array $servers
 * @return The amount of servers added for the domain
 */
function domains_update_servers($domain, $servers){
    global $_CONFIG;

    try{
        $domain = domains_get_id($domain);

        sql_query('DELETE FROM `domains_servers` WHERE `domains_id` = :domains_id', array(':domains_id' => $domain));

        if(empty($servers)){
            return false;
        }

        $insert = sql_prepare('INSERT INTO `domains_servers` (`domains_id`, `servers_id`)
                               VALUES                        (:domains_id , :servers_id )');

        foreach($servers as $servers_id){
            $insert->execute(array(':domains_id' => $domain,
                                   ':servers_id' => $servers_id));
        }

        return count($servers);

    }catch(Exception $e){
        throw new bException('domains_update_servers(): Failed', $e);
    }
}



/*
 * Add keyword to the domains scan. Add scanneable domains with all keyword
 * combinations to the domains table
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package domains
 *
 * @param string $keyword
 * @return integer Amount of scaneable domains generated from the added keyword
 */
function domains_add_keyword($keyword){
    global $_CONFIG;

    try{
        $keyword = domains_validate_keyword($keyword);

        sql_query('INSERT INTO `domains_keywords` (`createdby`, `meta_id`, `keyword`, `seokeyword`)
                   VALUES                         (:createdby , :meta_id , :keyword , :seokeyword )',

                   array(':createdby'  => isset_get($_SESSION['user']['id']),
                         ':meta_id'    => meta_action(),
                         ':keyword'    => $keyword['keyword'],
                         ':seokeyword' => $keyword['seokeyword']));

        $insert_id        = sql_insert_id();
        $count            = 0;
        $options          = array('', '-');
        $reverses         = array(true, false);
        $combination_list = sql_query('SELECT TRUE AS `keyword`

                                       UNION ALL

                                       SELECT `keyword`

                                       FROM   `domains_keywords`

                                       WHERE  `status` IS NULL');
        $insert           = sql_prepare('INSERT INTO `domains` (`createdby`, `meta_id`, `domain`, `type`)
                                         VALUES                (:createdby , :meta_id , :domain , "scan")');

        while($combination = sql_fetch($combination_list, true)){
            if($combination === '1'){
                $combination = '';
            }

            foreach(array_force($_CONFIG['domains']['scanner']['default_tlds']) as $tld){
                foreach($options as $option){
                    foreach($reverses as $reverse){
                        if(!$combination){
                            /*
                             * Never combine "" with an option
                             */
                            if($option){
                                continue;
                            }

                            $domain = $keyword['keyword'].'.'.$tld;

                        }else{
                            if($reverse){
                                $domain = $combination.$option.$keyword['keyword'].'.'.$tld;

                            }else{
                                $domain = $keyword['keyword'].$option.$combination.'.'.$tld;
                            }
                        }

                        $exists = sql_get('SELECT `id` FROM `domains` WHERE `domain` = :domain', true, array(':domain' => $domain));

                        if(!$exists){
                            $count++;
                            $insert->execute(array(':createdby' => isset_get($_SESSION['user']['id']),
                                                   ':meta_id'   => meta_action(),
                                                   ':domain'    => $domain));
                        }
                    }
                }
            }
        }

        return $count;

    }catch(Exception $e){
        throw new bException('domains_add_keyword(): Failed', $e);
    }
}



/*
 * Scan keyword domains
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package domains
 *
 * @return
 */
function domains_scan_keywords(){
    try{
        $domains = sql_query('SELECT `domain` FROM `domains` WHERE `type` = "scan" AND `status` IS NULL');

        while($domain = sql_fetch($domains)){

        }

    }catch(Exception $e){
        throw new bException('domains_scan_keywords(): Failed', $e);
    }
}



/*
 * Returns an array with all domains that are like the specified domain
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package domains
 *
 * @param string $domain The domain section that is being searched for
 * @return array The list of domains that was found
 */
function domains_like($domain){
    try{
        $domain = sql_get('SELECT `domain`

                           FROM   `domains`

                           WHERE  `domain`    LIKE :domain
                           OR     `seodomain` LIKE :seodomain',

                           true, array(':domain'    => '%'.$domain.'%',
                                       ':seodomain' => '%'.$domain.'%'));

        if(!$domain){
            /*
             * Specified domain not found in the default domains list, try domains list
             */
            if(!$domain){
                throw new bException(tr('domains_like(): Specified domain ":domain" does not exist', array(':domain' => $domain)), 'not-exist');
            }
        }

        return $domain;

    }catch(Exception $e){
        throw new bException('domains_like(): Failed', $e);
    }
}



/*
 * Insert the specified domain in the database after validation, and return it with the 'id' set
 *
 * This function will first validate the domain using domains_validate() and then insert the domain in the database. Once this is done, the linked servers will be registered using domains_update_servers()
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package domains
 * @see domains_validate()
 * @see domains_update_servers()
 *
 * @param params $domain
 * @return array The inserted domain with the id column set
 */
function domains_insert($domain){
    try{
            $domain = domains_validate($domain);

            sql_query('INSERT INTO `domains` (`createdby`, `meta_id`, `status`, `mx_domains_id`, `customers_id`, `providers_id`, `domain`, `seodomain`, `description`)
                       VALUES                (:createdby , :meta_id , :status , :mx_domains_id , :customers_id , :providers_id , :domain , :seodomain , :description )',

                       array(':status'          => null,
                             ':createdby'       => $_SESSION['user']['id'],
                             ':meta_id'         => meta_action(),
                             ':customers_id'    => $domain['customers_id'],
                             ':providers_id'    => $domain['providers_id'],
                             ':mx_domains_id'   => $domain['mx_domains_id'],
                             ':domain'          => $domain['domain'],
                             ':seodomain'       => $domain['seodomain'],
                             ':description'     => $domain['description']));

        $domain['id'] = sql_insert_id();

        domains_update_servers($domain, $domain['servers']);

        return $domain;

    }catch(Exception $e){
        throw new bException('domains_insert(): Failed', $e);
    }
}



/*
 * Update specified domain in the database after validation, and return it
 *
 * This function will first validate the domain using domains_validate() and then update the domain in the database. Once this is done, the linked servers will be registered using domains_update_servers()
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @provider Function reference
 * @package domains
 * @see domains_validate()
 * @see domains_update_servers()
 *
 * @param params $domain
 * @return array The updated domain
 */
function domains_update($domain, $new = false){
    try{
        $domain = domains_validate($domain);
        meta_action($domain['meta_id'], 'update');

        sql_query('UPDATE `domains`

                   SET    `status`        = :status,
                          `customers_id`  = :customers_id,
                          `providers_id`  = :providers_id,
                          `mx_domains_id` = :mx_domains_id,
                          `domain`        = :domain,
                          `seodomain`     = :seodomain,
                          `description`   = :description

                   WHERE  `id`            = :id'.($new ? ' AND `status` = "_new"' : ''),

                   array(':id'            => $domain['id'],
                         ':status'        => null,
                         ':customers_id'  => $domain['customers_id'],
                         ':providers_id'  => $domain['providers_id'],
                         ':mx_domains_id' => $domain['mx_domains_id'],
                         ':domain'        => $domain['domain'],
                         ':seodomain'     => $domain['seodomain'],
                         ':description'   => $domain['description']));

        domains_update_servers($domain, $domain['servers']);

        return $domain;

    }catch(Exception $e){
        throw new bException('domains_update(): Failed', $e);
    }
}



/*
 * Ensure that the specified domain exists
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package domains
 *
 * @param string $domain
 * @return integer The database table id for the specified domain
 */
function domains_ensure($domain, $column = 'id'){
    try{
        $exists = domains_get($domain);

        if($exists){
            return $exists[$column];
        }

        $domain = domains_insert(array('domain' => $domain));

        return $domain[$column];

    }catch(Exception $e){
		throw new bException('domains_ensure(): Failed', $e);
	}
}



/*
 * Returns the ID for the specified domain data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package domains
 *
 * @param mixed $domain
 * @param integer The domains_id
 */
function domains_get_id($domain){
    try{
        if(!$domain){
            return null;
        }

        if(is_array($domain)){
            $domain = $domain['id'];

        }elseif(!is_numeric($domain)){
            $domain = domains_get($domain);
            $domain = $domain['id'];
        }

        return $domain;

    }catch(Exception $e){
		throw new bException('domains_get_id(): Failed', $e);
	}
}
?>
