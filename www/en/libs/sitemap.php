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
function sitemap_generate($languages = null){
    global $_CONFIG;

    try{
        load_libs('file');

        if(empty($languages)){
            $languages = array_keys($_CONFIG['language']['supported']);
        }

        foreach(array_force($languages) as $language){
            cli_log(tr('Generating sitemap file(s) for language ":language"', array(':language' => $language)));

            file_delete(ROOT.'www/'.$language.'/sitemap*');

            $count = sql_get('SELECT COUNT(*) AS `count`

                              FROM   (SELECT   `file`

                                      FROM     `sitemaps_data`

                                      WHERE    `status` IS     NULL
                                      AND      `file`   IS NOT NULL

                                      GROUP BY `file`) AS `count`', 'count');

            if(!$count){
                /*
                 * There are no sitemap entries that require extra sitemap files
                 * Just generate the default sitemap.xml file and we're done!
                 */
                cli_log(tr('Generating single sitemap file'));
                return sitemap_xml(null);
            }

            /*
             * Generate multiple sitemap files
             */
            file_ensure_path(ROOT.'www/en/sitemaps');
            sitemap_index();

            $files = sql_query('SELECT   `file`

                                FROM     `sitemaps_data`

                                WHERE    `status` IS NULL

                                GROUP BY `file`');

            cli_log(tr('Generating ":count" sitemap files', array(':count' => $count)));

            while($file = sql_fetch($files)){
                if(!$file['file']) $file['file'] = 'basic';

                cli_dot(1);
                sitemap_xml($file['file']);
            }

            cli_dot(false);

            sql_query('INSERT INTO `sitemaps_generated` (`language`)
                       VALUES                           (:language )',

                       array(':language' => $language));
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

                            FROM     `sitemaps_data`

                            WHERE    `status` IS     NULL
                            AND      `file`   IS NOT NULL

                            GROUP BY `file`');

        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                "    <sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n".
                sitemap_file('basic');

        cli_log(tr('Generating sitemap index file'));

        while($file = sql_fetch($files, true)){
            cli_dot(1);
            $xml .= sitemap_file($file);
        }

        $xml .= "</sitemapindex>";

        file_put_contents(ROOT.'www/en/sitemap.xml', $xml);
        cli_dot(false);

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
        $execute = array();
        $query   = 'SELECT    `id`,
                              `url`,
                              `page_modifiedon`,
                              `change_frequency`,
                              `priority`,
                              `url`

                    FROM      `sitemaps_data`

                    WHERE     `status` IS NULL';

        if($file){
            $sitemap = 'sitemaps/'.$file;
            $query  .= ' AND `file` = :file ';
            $execute[':file'] = $file;

        }else{
            $sitemap = 'sitemap';
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
            $xml .= sitemap_entry($entry);
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
                        $retval[] = "    <loc>".$entry[$key]."</loc>\n";
                        break;

                    case 'page_modifiedon':
                        $retval[] = "    <lastmod>".date_convert($entry[$key], 'c')."</lastmod>\n";
                        break;

                    case 'change_frequency':
                        $retval[] = "    <changefreq>".$entry[$key]."</changefreq>\n";
                        break;

                    case 'priority':
                        $retval[] = "    <priority>".number_format($entry[$key], 2)."</priority>\n";
                        break;
                }
            }
        }

        return "<url>\n".implode($retval)."</url>\n";

    }catch(Exception $e){
        throw new bException('sitemap_entry(): Failed', $e);
    }
}



/*
 * Get a sitemap file
 */
function sitemap_file($file, $lastmod = null){
    try{
        if(empty($file)){
            throw new bException(tr('sitemap_file(): No file specified'), 'not-specified');
        }

        if(empty($lastmod)){
            $lastmod = date('c');
        }

        return  "<sitemap>\n".
                "   <loc>".domain('/sitemaps/'.$file.'.xml')."</loc>\n".
                "   <lastmod>".date_convert($lastmod, 'c')."</lastmod>\n".
                "</sitemap>\n";

    }catch(Exception $e){
        throw new bException('sitemap_file(): Failed', $e);
    }
}



/*
 * Clear the sitemap table
 */
function sitemap_clear($groups = null){
    try{
        if($groups){
            $in = sql_in($groups);
            $r  = sql_query('DELETE FROM `sitemaps_data` WHERE `group` IN ('.sql_in_columns($in).')', $in);

        }else{
            $r = sql_query('DELETE FROM `sitemaps_data`');
            $r = sql_query('DELETE FROM `sitemaps_generated`');
        }

        return $r->rowCount();

    }catch(Exception $e){
        throw new bException('sitemap_clear(): Failed', $e);
    }
}



/*
 * Delete indivitual entries from the sitemap table
 */
function sitemap_delete($list){
    try{
        if(is_array($list) or is_numeric($list) or (is_string($list) and strstr($list, ','))){
            /*
             * Delete by one or multiple id's
             */
            $in = sql_in(array_force($list));
            $r  = sql_query('DELETE FROM `sitemaps_data` WHERE `id` IN ('.sql_in_columns($in).')', $in);

        }else{
            /*
             * Delete by URL
             */
            $r  = sql_query('DELETE FROM `sitemaps_data` WHERE `url` = :url', $list);
        }

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

        sql_query('INSERT INTO `sitemaps_data` (`createdby`, `url`, `priority`, `page_modifiedon`, `change_frequency`, `language`, `group`, `file`)
                   VALUES                      (:createdby , :url , :priority , :page_modifiedon , :change_frequency , :language , :group , :file )

                   ON DUPLICATE KEY UPDATE `url`              = :url_update,
                                           `modifiedon`       = UTC_TIMESTAMP(),
                                           `modifiedby`       = :modifiedby_update,
                                           `priority`         = :priority_update,
                                           `page_modifiedon`  = :page_modifiedon_update,
                                           `change_frequency` = :change_frequency_update,
                                           `language`         = :language_update,
                                           `file`             = :file_update,
                                           `group`            = :group_update',

                   array(':createdby'               => isset_get($_SESSION['user']['id']),
                         ':url'                     => $url['url'],
                         ':priority'                => $url['priority'],
                         ':page_modifiedon'         => $url['page_modifiedon'],
                         ':change_frequency'        => $url['change_frequency'],
                         ':language'                => get_null($url['language']),
                         ':group'                   => $url['group'],
                         ':file'                    => $url['file'],
                         ':url_update'              => $url['url'],
                         ':modifiedby_update'       => isset_get($_SESSION['user']['id']),
                         ':priority_update'         => $url['priority'],
                         ':page_modifiedon_update'  => $url['page_modifiedon'],
                         ':change_frequency_update' => $url['change_frequency'],
                         ':language_update'         => get_null($url['language']),
                         ':file_update'             => $url['file'],
                         ':group_update'            => $url['group']));

        return sql_insert_id();

    }catch(Exception $e){
        throw new bException('sitemap_add_url(): Failed', $e);
    }
}
?>
