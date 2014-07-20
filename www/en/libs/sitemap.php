<?php
/*
 * Sitemap library
 *
 * This library contains functions to build sitemaps
 *
 * Idea taken from http://yoast.com/xml-sitemap-php-script/
 * Written by Sven Oostenbrink
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Requires sitemap configuration
 */
load_config('sitemap');



/*
 * Build a sitemap in database
 */
function sitemap_build($params = array()){
    global $_CONFIG;

    try{
        array_params ($params, 'language');
        array_default($params, 'status'          , null);
        array_default($params, 'recursive'       , false);
        array_default($params, 'recursive'       , $_CONFIG['sitemap']['recursive']);
        array_default($params, 'ignore'          , $_CONFIG['sitemap']['ignore']);
        array_default($params, 'change_frequency', $_CONFIG['sitemap']['change_frequency']);
        array_default($params, 'languages'       , $_CONFIG['sitemap']['languages']);
        array_default($params, 'priority'        , $_CONFIG['sitemap']['priority']);
        array_default($params, 'filetypes'       , $_CONFIG['sitemap']['filetypes']);
        array_default($params, 'rename'          , $_CONFIG['sitemap']['rename']);
        array_default($params, 'force_html'      , $_CONFIG['sitemap']['force_html']);
        array_default($params, 'modified'        , $_CONFIG['sitemap']['modified']);

        /*
         * Determine for what language we are going to build sitemaps
         */
        if(empty($params['languages'])){
            $params['languages'] = $_CONFIG['language']['supported'];
        }

        foreach($params['languages'] as $code => $language){
            if(empty($_CONFIG['language']['supported'][$code])){
                throw new lsException('sitemap_build(): Specified (or configured) build language "'.str_log($code).'" is not supported in $_CONFIG[languages][supported]', 'notsupported');
            }
        }

        load_libs('file');

        /*
         * Create a sitemap for each language
         */
        foreach($params['languages'] as $code => $language){
            log_console('Processing language "'.$language.'"', 'language');

            sql_query('INSERT INTO `sitemap_builds` (`createdby`, `language`)
                       VALUES                       (:createdby , :language )',

                       array(':createdby' => $_SESSION['user']['id'],
                             ':language'  => $code));

            $builds_id = sql_insert_id();
            $path      = ROOT.'www/'.$code.'/';

            try{
                $h = opendir($path);

            }catch(Exception $e){
                /*
                 * Maybe the language path does not exist yet?
                 */
                if(file_exists($path)){
                    throw $e;
                }

                if(ENVIRONMENT == 'production'){
                    throw new lsException('sitemap_build(): Specified language "'.str_log($language).'" path "'.str_log($path).'" does not exist', 'notexist');

                }else{
                    /*
                     * In development mode its no big deal, just notify and continue
                     */
                    log_error('sitemap_build(): Specified language "'.str_log($language).'" path "'.str_log($path).'" does not exist, skipping', 'notexist', 'yellow');
                    continue;
                }
            }

            while(false !== ($file = readdir($h))){
                if(substr($file, 0, 1) == '.'){
                    /*
                     * Skip . .. and all hidden files
                     */
                    continue;
                }

                /*
                 * Should this file be ignored?
                 */
                if(in_array($file, $params['ignore'])){
                    log_console('File "'.str_log($code).'/'.str_log($file).'" is on the ignore list, skipping', 'file', 'yellow');
                    continue;
                }

                if(is_dir($path.$file)){
                    if(!$params['recursive']){
                        log_console('File "'.str_log($code).'/'.str_log($file).'" is a directory, and recursive is disabled. skipping', 'file', 'yellow');
                        continue;
                    }

                    /*
                     * Recurse
                     */
                    $params['path'] = $path.$file;
                    sitemap_build($params);
                }

                /*
                 * Check whether the file has on of the extensions allowed for this XML sitemap
                 */
                $extension = strtolower(file_extension($path.$file));

                if(!in_array($extension, $params['filetypes'])){
                    log_console('File "'.str_log($code).'/'.str_log($file).'" has an unknown extension, skipping', 'file', 'yellow');
                    continue;
                }

                /*
                 * If its a PHP / HTML file, try to get the meta description
                 */
                if($extension == 'html'){
                    $type        = 'html';
                    $description = file_get_contents($path.$file);
                    $description = str_until(str_from($data, ''), '');

                }elseif($extension == 'php'){
                    $type = 'html';
                    $data = file_get_contents($path.$file);

                    if(preg_match('/\'description\'\s*=>\s*(?:tr\()\'(.+?)\'/imus', $data, $match)){
                        $description = $match[1];

                    }elseif(preg_match('/\[\'description\'\]\s*=\s*(?:tr\()\'(.+?)\'/imus', $data, $match)){
                        $description = $match[1];

                    }else{
                        $description = '';
                    }

                    if($params['force_html']){
                        $file = substr($file, 0, -4).'.html';
                    }

                }else{
                    /*
                     * These are special entries, like images, videos, etc.
                     */
                    $type = file_mimetype($path.$file);

// :TODO: Implement, not supported yet
continue;
                }

                /*
                 * Rename file if needed
                 */
                if(isset($params['rename'][$file])){
                    $file_original = $file;
                    $file          = $params['rename'][$file];

                }else{
                    $file_original = null;
                }

                switch($params['modified']){
                    case 'auto':
                        /*
                         * Use the file last modified date
                         */
                        $modified = '';
                        break;

                    case 'current':
                        /*
                         * Use the current date
                         */
                        $modified = '';
                        break;

                    default:
                        /*
                         * It HAS to be a valid date!
                         */
                        $modified = '';
                        break;
                }

                /*
                 * Store in database
                 */
                log_console('Adding file "'.str_log($code).'/'.str_log($file).'"', 'file');

                sql_query('INSERT INTO `sitemap_data` (`builds_id`, `file`, `file_original`, `type`, `status`, `modified`, `priority`, `changefreq`, `description`)
                           VALUES                     (:builds_id , :file , :file_original , :type , :status , :modified , :priority , :changefreq , :description )',

                           array(':builds_id'     => $builds_id,
                                 ':file'          => $file,
                                 ':file_original' => $file_original,
                                 ':type'          => $type,
                                 ':status'        => $params['status'],
                                 ':modified'      => $modified,
                                 ':priority'      => $params['priority'],
                                 ':changefreq'    => $params['change_frequency'],
                                 ':description'   => $description));
            }

            closedir($h);
        }

    }catch(Exception $e){
        throw new lsException('sitemap_build(): Failed', $e);
    }
}



/*
 * Create a sitemap in XML format
 */
function sitemap_xml($params = array(), $return = false){
    global $_CONFIG;

    try{
        array_params($params);

        $r   = sitemap_get_build_resource($params);

        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
               "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        while($page = sql_fetch($r)){
            if(isset_get($_CONFIG['sitemap']['xls'])){
                $xml .= '<?xml-stylesheet type="text/xsl" href="'.SITEMAP_DIR_URL.$xsl.'"?>'."\n";
            }

            $xml .= "<url>\n".
                    '   <loc>'.domain('/'.$page['file'])."</loc>\n".
                    '   <lastmod>'.$page['modified']."</lastmod>\n".
                    '   <changefreq>'.$page['changefreq']."</changefreq>\n".
                    '   <priority>'.$page['priority']."</priority>\n".
                    "</url>\n";
        }

        $xml .= '</urlset>';

        if($return){
            return $xml;
        }

        /*
         * Send XML header
         */
        header('Content-Type: application/xml');

        echo $xml;

    }catch(Exception $e){
        throw new lsException('sitemap_xml(): Failed', $e);
    }
}



/*
 * Create a sitemap in HTML format
 */
function sitemap_html($params = array(), $return = false){
    global $_CONFIG;

    try{
        array_params($params);

        $r   = sitemap_get_build_resource($params);

        $html = "<!DOCTYPE html>\n<html><head><title></title></head><body><ul>";

        while($page = sql_fetch($r)){
            $html .= "<li>\n".
                    '   <span>'.domain('/'.$page['file'])."</span>\n".
                    '   <span>'.$page['modified']."</span>\n".
                    '   <span>'.$page['changefreq']."</span>\n".
                    '   <span>'.$page['priority']."</span>\n".
                    "</li>\n";
        }

        $html .= '</ul></body></html>';

        if($return){
            return $html;
        }

        /*
         * Send html header
         */
        echo $html;

    }catch(Exception $e){
        throw new lsException('sitemap_html(): Failed', $e);
    }
}



/*
 * Create a sitemap in plain text format
 */
function sitemap_txt($params = array(), $return = false){
    global $_CONFIG;

    try{
        array_params($params);

        $r     = sitemap_get_build_resource($params);

        $text  = '';

        while($page = sql_fetch($r)){
            $text .= $page['file'].($page['file_original'] ? ' ('.$page['file_original'].')' : '')."\n";
        }

        if($return){
            return $text;
        }

        echo $text;

    }catch(Exception $e){
        throw new lsException('sitemap_txt(): Failed', $e);
    }
}



/*
 * Get the data resource for the specified parameters
 */
function sitemap_get_build_resource($params){
    try{
        array_params($params);
        array_default($params, 'language', LANGUAGE);

        $build  = sql_get('SELECT `id`, `createdon` FROM `sitemap_builds` WHERE `language` = :language ORDER BY `createdon` DESC LIMIT 1', array(':language' => str_log($params['language'])));

        if(!$build){
            throw new lsException('sitemap_getbuild(): No sitemap builds found', 'notfound');
        }

        $r = sql_query('SELECT * FROM `sitemap_data` WHERE `builds_id` = :builds_id', array(':builds_id' => $build['id']));

        if(!$r->rowCount()){
            throw new lsException('sitemap_getbuild(): Build "'.$build['id'].'" from date "'.$build['createdon'].'" for language "'.str_log($params['language']).'" has no data', 'empty');
        }

        return $r;

    }catch(Exception $e){
        throw new lsException('sitemap_getbuild(): Failed', $e);
    }
}
?>
