<?php
/*
 * AMP library
 *
 * This library adds support for Google AMP pages to websites. The library uses
 * template pages and fills in data from the specified resource. AMP pages will
 * be stored in cache to keep server load low
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Show the AMP verion of the specified page
 */
function amp_page($params){
    try{
        array_params($params);
        array_default($params, 'template', null);
        array_default($params, 'resource', null);

        load_libs('cache');

        $data = cache_read($cache, 'amp');
        if($data) return data;

        if(!$params['template']){
            throw new bException(tr('amp_page(): No template page specified'), 'not-specified');
        }

        if(!$params['resource']){
            throw new bException(tr('amp_page(): No resource specified'), 'not-specified');
        }

        $file = ROOT.'data/content/amp/'.$params['template'].'.html';

        if(!file_exists($file)){
            throw new bException(tr('amp_page(): Specified template ":template"', array(':template' => $template)), 'not-exist');
        }

        $data = file_get_contents($file);

        foreach($resource as $key => $value){
            $data = str_replace($key, $value, $data);
        }

        $data = cache_write($data, $cache, 'amp');

        return $data;

    }catch(Exception $e){
        throw new bException('amp_page(): Failed', $e);
    }
}
?>
