<?php
/*
 * Pages library
 *
 * This library stores pages in the database and can
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Get a page from the database
 */
function page_get($seoname){
    try{
        $page = sql_get('SELECT `data`, `status` FROM `pages` WHERE `seoname` = :seoname', array(':seoname' => $seoname));

        if(!$page){
            throw new bException('page_get(): Page with seoname "'.str_log($seoname).'" does not exist', 'notfound');
        }

        if($page['status'] !== null){
            throw new bException('page_get(): Page with seoname "'.str_log($seoname).'" has status "'.str_log($page['status']).'" and cannot be displayed', 'status');
        }

        return $page['data'];

    }catch(Exception $e){
        throw new bException('page_get(): Failed', $e);
    }
}



/*
 * Store the specified page data the database
 */
function page_put($name, $data, $status = null){
    try{
        sql_query('INSERT INTO `pages` (`status`, `name`, `seoname`, `data`)
                   VALUES              (:status , :name , :seoname , :data )

                   ON DUPLICATE KEY UPDATE `status` = :status,
                                           `data`   = :data',


                   array('name'    => $name,
                         'seoname' => $seoname = seo_generate_unique_name($name, 'pages'),
                         'status'  => $status,
                         'data'    => $data));

        return $seoname;

    }catch(Exception $e){
        throw new bException('page_put(): Failed', $e);
    }
}
?>
