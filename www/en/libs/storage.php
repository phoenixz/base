<?php
/*
 * New storage library
 *
 * This is the new storage library, progrenitor to the old blog library
 *
 * Tables:
 *
 * storage : This used to be the blogs table
 * storage_categories  hiarchical structure under which
 * storage_documents : These are the main documents
 * storage_pages : These are the pages from the documents containing the actual texts. All pages should have the same content, just in a different language
 * storage_comments : These are the comments made on storage_texts
 * storage_keywords : Blog documents can have multiple keywords, stored here
 * storage_key_values : Blog texts can have multiple key_value pairs. Stored here, per text
 * storage_key_values_definitions : The definitions of the available key_value pairs, per storage
 * storage_files :
 * storage_file_types :
 * storage_files : The files linked to each document. If file_types_id is NULL, then the file can be of any type. This is why this table will have its independant type, mime1, and mime2 columns
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 *
 */
function storage_url($url, $section = null, $page = null, $language = null){
    try{
        if(!$language){
            $language = LANGUAGE;
        }

        $url = str_replace(':language', $language, $url);

        if($section){
            $url = str_replace(':seosection', $section['seoname'], $url);
        }

        if($page and !empty($page['seoname'])){
            $url = str_replace(':seodocument', $page['seoname'], $url);
        }

        return $url;

    }catch(Exception $e){
        throw new bException('storage_url(): Failed', $e);
    }
}



/*
 * Ensure that the specified section is a section array, and not just a
 * section id
 */
function storage_ensure_section($section){
    try{
        if(is_array($section)){
            return $section;
        }

        load_libs('storage-sections');

        $section = storage_sections_get($section, false);
        return $section;

    }catch(Exception $e){
        throw new bException('storage_url(): Failed', $e);
    }
}
?>
