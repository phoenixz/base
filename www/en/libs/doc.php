<?php
/*
 * DOC library
 *
 * This library is a documentation scanner / generator. It will scan projects,
 * and generate documentation for them
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @param
 * @return
 */
function doc_library_init(){
    try{
        load_config('doc');

    }catch(Exception $e){
        throw new bException('doc_library_init(): Failed', $e);
    }
}



/*
 * Have parse THIS project
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 */
function doc_parse_this($path){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_project(): Failed', $e);
    }
}



/*
 * Parse the specified project
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 * @param string $path The project that should be parsed
 */
function doc_parse_project($project){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_project(): Failed', $e);
    }
}



/*
 * Parse the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 * @param string $path The path that should be parsed
 */
function doc_parse_path($path){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_path(): Failed', $e);
    }
}



/*
 * Parse the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 * @param string $file
 */
function doc_parse_file($file){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_file(): Failed', $e);
    }
}



/*
 * Parse the header commentary of the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 * @param string $file
 */
function doc_parse_file_header($file){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_file_header(): Failed', $e);
    }
}



/*
 * Parse the specified library file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 * @param string $file
 */
function doc_parse_library_file($file){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_library_file(): Failed', $e);
    }
}



/*
 * Parse the specified comment section
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 * @param string $comment
 */
function doc_parse_function($function){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_function(): Failed', $e);
    }
}



/*
 * Parse the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 * @param string $tag
 */
function doc_parse_function_doc($tag){
    try{

    }catch(Exception $e){
        throw new bException('doc_parse_function_doc(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 */
function doc_insert_function(){
    try{

    }catch(Exception $e){
        throw new bException('doc_insert_function(): Failed', $e);
    }
}



/*
 * Add documentation about a function or method
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 */
function doc_insert_file_header(){
    try{

    }catch(Exception $e){
        throw new bException('doc_insert_file_header(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 */
function doc_insert_class(){
    try{

    }catch(Exception $e){
        throw new bException('doc_insert_class(): Failed', $e);
    }
}



/*
 * Generate documentation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 */
function doc_generate($template, $project = null){
    try{

    }catch(Exception $e){
        throw new bException('doc_generate(): Failed', $e);
    }
}



/*
 * Generate documentation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package phpdoc
 *
 */
function doc_generate_page($template, $page){
    try{

    }catch(Exception $e){
        throw new bException('doc_generate_page(): Failed', $e);
    }
}
?>
