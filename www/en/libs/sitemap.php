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
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 *
 */
function sitemap_library_init(){
    try{
        /*
         * Requires sitemap configuration
         */
        load_config('sitemap');

    }catch(Exception $e){
        throw new bException('sitemap_library_init(): Failed', $e);
    }
}



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
        $count = 0;

        if(empty($languages)){
            if($_CONFIG['language']['supported']){
                $languages = array_keys($_CONFIG['language']['supported']);

            }else{
                $languages = array($_CONFIG['language']['default']);
            }
        }

        foreach(array_force($languages) as $language){
            if($language){
                log_console(tr('Generating sitemap for language ":language"', array(':language' => $language)));

            }else{
                log_console(tr('Generating sitemap'));
            }

            if(!file_exists(ROOT.'www/'.not_empty($language, 'en'))){
                log_console(tr('Skipped sitemap generation for language ":language1", the "www/:language2" directory does not exist', array(':language1' => $language, ':language2' => not_empty($language, 'en'))), 'yellow');
                continue;
            }

            sitemap_delete_backups($language);

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
                log_console(tr('Generating single sitemap file'), 'QUIET');
                $count += sitemap_xml($language);

                file_execute_mode(ROOT.'www/'.$language, 0770, array('language' => $language), function($params){
                    if(file_exists(ROOT.'www/'.not_empty($params['language'], 'en').'/sitemap.xml')){
                        chmod(ROOT.'www/'.not_empty($params['language'], 'en').'/sitemap.xml', 0660);
                    }

                    rename(TMP.'sitemap.xml', ROOT.'www/'.not_empty($params['language'], 'en').'/sitemap.xml');
                    chmod(ROOT.'www/'.not_empty($params['language'], 'en').'/sitemap.xml', 0440);
                });

            }else{
                /*
                 * Generate multiple sitemap files
                 */
                log_console(tr('Generating sitemap files for language ":language"', array(':language' => $language)));
                sitemap_index();

                $files = sql_query('SELECT   `file`

                                    FROM     `sitemaps_data`

                                    WHERE    `status` IS NULL

                                    GROUP BY `file`');

                /*
                 * Generate the sitemap files in a temp dir which we'll then move
                 * into place
                 */
                log_console(tr('Generating ":count" sitemap files', array(':count' => $count)));
                file_ensure_path(TMP.'sitemaps');
                chmod(TMP.'sitemaps', $_CONFIG['fs']['dir_mode']);

                while($file = sql_fetch($files)){
                    if(!$file['file']) $file['file'] = 'basic';

                    cli_dot(1);
                    $count += sitemap_xml($language, $file['file']);
                }

                file_execute_mode(ROOT.'www/'.$language, 0770, array('language' => $language), function($params){
                    $params['language'] = unslash($params['language']);

                    /*
                     * Move originals to backups
                     */
                    file_move_to_backup(ROOT.'www/'.$params['language'].'/sitemaps.xml', true);
                    file_move_to_backup(ROOT.'www/'.$params['language'].'/sitemaps'    , true);

                    /*
                     * Move sub sitemap files in place
                     */
                    rename(TMP.'sitemaps', ROOT.'www/'.$params['language'].'/sitemaps');
                    chmod(ROOT.'www/'.$params['language'].'/sitemaps', 0550);

                    sitemap_delete_backups($params['language']);
                });
            }

            cli_dot(false);

            sql_query('INSERT INTO `sitemaps_generated` (`language`)
                       VALUES                           (:language )',

                       array(':language' => $language));
        }

        return $count;

    }catch(Exception $e){
        throw new bException('sitemap_generate(): Failed', $e);
    }
}



/*
 * Generate the sitemap index file.
 *
 * Data will first be written to a new temp file, and then be moved over the
 * currently existing one, if one exist
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

        log_console(tr('Generating sitemap index file'));

        while($file = sql_fetch($files, true)){
            cli_dot(1);
            $xml .= sitemap_file($file);
        }

        $xml .= "</sitemapindex>";
        $file = file_temp();

        file_put_contents($file, $xml);
        chmod($file, 0440);
        rename($file, TMP.'sitemap.xml');
        cli_dot(false);

        return $xml;

    }catch(Exception $e){
        throw new bException('sitemap_index(): Failed', $e);
    }
}



/*
 * Generate the sitemap.xml file
 */
function sitemap_xml($language = null, $file = null){
    global $_CONFIG;

    try{
        $sitemap = '';
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
            $sitemap .= 'sitemaps/'.$file;
            $query   .= ' AND `file` = :file ';
            $execute[':file'] = $file;

        }else{
            $sitemap = 'sitemap';
        }

        if($language){
            $query   .= ' AND `language` = :language ';
            $execute[':language'] = $language;
        }

        $entries = sql_query($query.' ORDER BY (`file` IS NOT NULL), `file` DESC, (`priority` IS NOT NULL), `priority` DESC', $execute);
        $count   = 0;

        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                "   <urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

        while($entry = sql_fetch($entries)){
            $count++;
            $xml .= sitemap_entry($entry);
            cli_dot(1, '');
        }

        $xml .= "</urlset>\n";

        file_ensure_path(dirname(TMP.$sitemap.'.xml'));
        file_put_contents(TMP.$sitemap.'.xml', $xml);

        return $count;

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
            $r = sql_query('DELETE FROM `sitemaps_data` WHERE `url` = :url', array(':url' => $list));
        }

        return $r->rowCount();

    }catch(Exception $e){
        throw new bException('sitemap_delete(): Failed', $e);
    }
}



/*
 * Add a new URL to the sitemap table
 */
function sitemap_add_entry($entry){
    try{
        array_params($entry);
        array_default($entry, 'url'             , '');
        array_default($entry, 'priority'        , '');
        array_default($entry, 'page_modifiedon' , null);
        array_default($entry, 'change_frequency', '');
        array_default($entry, 'language'        , '');
        array_default($entry, 'group'           , 'standard');
        array_default($entry, 'file'            , null);

        $entry = sitemap_validate_entry($entry);

        if($entry['page_modifiedon']){
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
                             ':url'                     => $entry['url'],
                             ':priority'                => $entry['priority'],
                             ':page_modifiedon'         => date_convert($entry['page_modifiedon'], 'c'),
                             ':change_frequency'        => $entry['change_frequency'],
                             ':language'                => get_null($entry['language']),
                             ':group'                   => $entry['group'],
                             ':file'                    => get_null($entry['file']),
                             ':url_update'              => $entry['url'],
                             ':modifiedby_update'       => isset_get($_SESSION['user']['id']),
                             ':priority_update'         => $entry['priority'],
                             ':page_modifiedon_update'  => date_convert($entry['page_modifiedon'], 'c'),
                             ':change_frequency_update' => $entry['change_frequency'],
                             ':language_update'         => get_null($entry['language']),
                             ':file_update'             => get_null($entry['file']),
                             ':group_update'            => $entry['group']));

        }else{
            sql_query('INSERT INTO `sitemaps_data` (`createdby`, `url`, `priority`, `page_modifiedon`, `change_frequency`, `language`, `group`, `file`)
                       VALUES                      (:createdby , :url , :priority , NOW()            , :change_frequency , :language , :group , :file )

                       ON DUPLICATE KEY UPDATE `url`              = :url_update,
                                               `modifiedon`       = UTC_TIMESTAMP(),
                                               `modifiedby`       = :modifiedby_update,
                                               `priority`         = :priority_update,
                                               `page_modifiedon`  = NOW(),
                                               `change_frequency` = :change_frequency_update,
                                               `language`         = :language_update,
                                               `file`             = :file_update,
                                               `group`            = :group_update',

                       array(':createdby'               => isset_get($_SESSION['user']['id']),
                             ':url'                     => $entry['url'],
                             ':priority'                => $entry['priority'],
                             ':change_frequency'        => $entry['change_frequency'],
                             ':language'                => get_null($entry['language']),
                             ':group'                   => $entry['group'],
                             ':file'                    => get_null($entry['file']),
                             ':url_update'              => $entry['url'],
                             ':modifiedby_update'       => isset_get($_SESSION['user']['id']),
                             ':priority_update'         => $entry['priority'],
                             ':change_frequency_update' => $entry['change_frequency'],
                             ':language_update'         => get_null($entry['language']),
                             ':file_update'             => get_null($entry['file']),
                             ':group_update'            => $entry['group']));
        }

        if(empty($entry['id'])){
            $entry['id'] = sql_insert_id();
        }

        return $entry;

    }catch(Exception $e){
        throw new bException('sitemap_add_entry(): Failed', $e);
    }
}



/*
 * Delete all sitemap tmp and backup files and directories
 */
function sitemap_delete_backups($language){
    try{
        if(file_exists(TMP.'sitemap.xml')){
            chmod(TMP.'sitemap.xml', 0660);
            file_delete(TMP.'sitemap.xml');
        }

        if(file_exists(TMP.'sitemaps')){
            chmod(TMP.'sitemaps', 0770);

            file_tree_execute(array('path'     => TMP.'sitemaps',
                                    'callback' => function($file){
                                        chmod($file, 0660);
                                        file_delete($file);
                                    }));

            file_delete(TMP.'sitemaps');
        }

        if(file_exists(ROOT.'www/'.not_empty($language, 'en').'/sitemaps~')){
            chmod(ROOT.'www/'.not_empty($language, 'en').'/sitemaps~', 0770);

            file_tree_execute(array('path'     => ROOT.'www/'.not_empty($language, 'en').'/sitemaps~',
                                    'callback' => function($file){
                                        chmod($file, 0660);
                                        file_delete($file);
                                    }));

            file_delete(ROOT.'www/'.not_empty($language, 'en').'/sitemaps~');
        }

    }catch(Exception $e){
        throw new bException('sitemap_delete_backups(): Failed', $e);
    }
}



/*
 *
 */
function sitemap_validate_entry($entry){
    global $_CONFIG;

    try{
        load_libs('validate');

        $v = new validate_form($entry, 'createdby,status,url,priority,page_modifiedon,change_frequency,language,group,file');

        $entry['page_modifiedon'] = date_convert($entry['page_modifiedon'], 'mysql');
        $entry['file']            = get_null($entry['file']);

        if($_CONFIG['language']['supported']){
            $v->inArray($entry['language'], $_CONFIG['language']['supported'], tr('Please ensure that the specified language is supported'));

        }else{
            $entry['language'] = $_CONFIG['language']['default'];
        }

        return $entry;

    }catch(Exception $e){
        throw new bException('sitemap_validate_entry(): Failed', $e);
    }
}
?>
