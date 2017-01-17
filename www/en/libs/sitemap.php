<?php
/*
 * Sitemap library
 *
 * This library contains functions to manage available site URL's and generate
 * sitemaps from there
 *
 * Written by Sven Oostenbrink
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Requires sitemap configuration
 */
load_config('sitemap');



/*
 * Regenerate (all) sitemap file(s)
 * If sitemap database does not contain any "file" data then only the
 * sitemap.xml will be created. If it does, the sitemap.xml will be the index
 * file, and the other sitemap files will be auto generated one by one
 */
function sitemap_generate(){
    global $_CONFIG;

    try{
        load_libs('file');

        foreach($_CONFIG['language']['supported'] as $code => $name){
            file_delete(ROOT.'www/'.$code.'/sitemap*');
        }

        $count = sql_get('SELECT COUNT(*) AS `count`

                          FROM   `sitemap_data`

                          WHERE  `status` IS     NULL
                          AND    `file`   IS NOT NULL', 'count');

        if(!$count){
            /*
             * There are no sitemap entries that require extra sitemap files
             * Just generate the default sitemap.xml file and we're done!
             */
            sitemap_xml(null);

        }else{
            /*
             * Generate multiple sitemap files
             */
            sitemap_index();

            $files = sql_query('SELECT   `file`

                                FROM     `sitemap_data`

                                WHERE    `status` IS     NULL

                                GROUP BY `file`');

            while($file = sql_fetch($files)){
                if(!$file) $file = 'basic';
                sitemap_xml($file);
            }
        }

    }catch(Exception $e){
        throw new bException('sitemap_generate(): Failed', $e);
    }
}



/*
 * Generate the sitemap index file
 */
function sitemap_index(){
    try{
        $files = sql_query('SELECT   `file`

                            FROM     `sitemap_data`

                            WHERE    `status` IS NULL

                            GROUP BY `file`');

        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                "    <sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        while($file = sql_fetch($files, 'file')){
            $xml .= sitemap_file($file);
        }

        $xml .= "</sitemapindex>";

        file_put_contents(ROOT.'www/en/sitemap.xml', $xml);

        return $xml;

    }catch(Exception $e){
        throw new bException('sitemap_xml(): Failed', $e);
    }
}



/*
 * Generate the sitemap.xml file
 */
function sitemap_xml($file = null, $language = null){
    global $_CONFIG;

    try{
        $sitemap = 'sitemap';

        $query   = 'SELECT    `id`,
                              `url`,
                              `page_modifiedon`,
                              `change_frequency`,
                              `priority`,
                              `url`

                    FROM      `sitemap_data`

                    WHERE     `status` IS NULL ';

        if($file){
            $sitemap = '-'.$file;
            $query  .= ' AND `file` = :file ';
            $execute[':file'] = $file;
        }

        if($language){
            $sitemap = '-'.$language;
            $query .= ' AND `language` = :language ';
            $execute[':language'] = $language;
        }

        $entries = sql_query($query.'ORDER BY (`file` IS NOT NULL), `file` DESC, (`priority` IS NOT NULL), `priority` DESC', $execute);

        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                "   <urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        while($entry = sql_fetch($entries)){
            $xml .= sitemap_entry($entry)."\n";
        }

        $xml .= "</urlset>\n";

        file_put_contents(ROOT.'www/en/'.$sitemap.'.xml', $xml);

        return $xml;

    }catch(Exception $e){
        throw new bException('sitemap_xml(): Failed', $e);
    }
}



/*
 * Get a sitemap entry
 */
function sitemap_entry($entry){
    try{
        if(empty($entry['url'])){
            throw new bException(tr('sitemap_entry(): No URL specified'), 'not-specified');
        }

        $keys = array('url',
                      'page_modifiedon',
                      'change_frequency',
                      'priority');

        foreach($keys as $key){
            if(!empty($entry[$key])){
                switch($key){
                    case 'url':
                        $retval[] = '<loc>'.$entry[$key].'</loc>';
                        break;

                    case 'page_modifiedon':
                        $retval[] = '<lastmod>'.date_convert($entry[$key], 'c').'</lastmod>';
                        break;

                    case 'change_frequency':
                        $retval[] = '<changefreq>'.$entry[$key].'</changefreq>';
                        break;

                    case 'priority':
                        $retval[] = '<priority>'.number_format($entry[$key], 2).'</priority>';
                        break;
                }
            }
        }

        return '<url>'.implode($retval).'</url>';

    }catch(Exception $e){
        throw new bException('sitemap_entry(): Failed', $e);
    }
}



/*
 * Get a sitemap file
 */
function sitemap_file($file, $lastmod){
    try{
        if(empty($file['url'])){
            throw new bException(tr('sitemap_file(): No URL specified'), 'not-specified');
        }

        return  "<sitemap>\n".
                "   <loc>'.domain('/sitemap-'.$file.'.xml').'</loc>\n".
                "   <lastmod>'.date_convert($lastmod, 'c').'</lastmod>\n".
                "</sitemap>\n";

    }catch(Exception $e){
        throw new bException('sitemap_file(): Failed', $e);
    }
}



/*
 * Clear the sitemap table
 */
function sitemap_clear($groups){
    try{
        if($groups){
            $in = sql_in($groups);
            $r  = sql_query('DELETE FROM `sitemap` WHERE `group` IN ('.sql_in_columns($in).')', $in);

        }else{
            $r = sql_query('DELETE FROM `sitemap`');
        }

        return $r->rowCount();

    }catch(Exception $e){
        throw new bException('sitemap_clear(): Failed', $e);
    }
}



/*
 * Delete indivitual entries from the sitemap table
 */
function sitemap_delete($filter){
    try{
        $in = sql_in($groups);
        $r  = sql_query('DELETE FROM `sitemap` WHERE `group` IN ('.sql_in_columns($in).')', $in);

        return $r->rowCount();

    }catch(Exception $e){
        throw new bException('sitemap_delete(): Failed', $e);
    }
}



/*
 * Add a new URL to the sitemap table
 */
function sitemap_add_url($url){
    try{
        array_params($url);
        array_default($url, 'url'             , '');
        array_default($url, 'priority'        , '');
        array_default($url, 'page_modifiedon' , '');
        array_default($url, 'change_frequency', '');
        array_default($url, 'language'        , '');
        array_default($url, 'group'           , 'standard');
        array_default($url, 'file'            , null);

        sql_query('INSERT INTO `sitemap` (`createdby`, `url`, `priority`, `page_modifiedon`, `change_frequency`, `language`, `group`, `file`)
                   VALUES                (:createdby , :url , :priority , :page_modifiedon , :change_frequency , :language , :group , :file )',

                   array(':createdby'        => isset_get($_SESSION['user']['id']),
                         ':url'              => $url['url'],
                         ':priority'         => $url['priority'],
                         ':page_modifiedon'  => $url['page_modifiedon'],
                         ':change_frequency' => $url['change_frequency'],
                         ':group'            => $url['group'],
                         ':language'         => $url['language'],
                         ':file'             => $url['file']));

        return sql_insert_id();

    }catch(Exception $e){
        throw new bException('sitemap_add_url(): Failed', $e);
    }
}



/*
 * Add a new URL to the sitemap table
 */
function sitemap_update_url($url, $id){
    try{
        array_params($url);
        array_default($url, 'id'              , null);
        array_default($url, 'url'             , '');
        array_default($url, 'priority'        , '');
        array_default($url, 'page_modifiedon' , '');
        array_default($url, 'change_frequency', '');
        array_default($url, 'language'        , '');
        array_default($url, 'group'           , 'standard');
        array_default($url, 'file'            , null);

        sql_query('UPDATE `sitemap`

                   SET    `modifiedby`       = :modifiedby,
                          `modifiedon`       = :NOW(),
                          `url`              = :url,
                          `priority`         = :priority,
                          `page_modifiedon`  = :page_modifiedon,
                          `change_frequency` = :change_frequency,
                          `language`         = :language,
                          `group`            = :group,
                          `file`             = :file

                   WHERE  `id`               = :id',

                   array(':modifiedby'       => isset_get($_SESSION['user']['id']),
                         ':url'              => $url['url'],
                         ':priority'         => $url['priority'],
                         ':page_modifiedon'  => $url['page_modifiedon'],
                         ':change_frequency' => $url['change_frequency'],
                         ':group'            => $url['group'],
                         ':language'         => $url['language'],
                         ':file'             => $url['file']));

    }catch(Exception $e){
        throw new bException('sitemap_add_url(): Failed', $e);
    }
}
?>
