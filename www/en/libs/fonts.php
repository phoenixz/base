<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */


/*
 * Convert ufpdf font to normal ttf font file
 */
function fonts_convert_ufpdf($params){
    try{
        array_params($params);
        ensure_key($params, 'font'	 , '');
        ensure_key($params, 'unicode', true);

        /*
         * If multiple fonts have been specified, handle them one by one
         */
        if(is_array($params['font'])){
            foreach($params['font'] as $font){
                $params['font'] = $font;
                fonts_convert_ufpdf($params);
            }

            return true;
        }

        /*
         * If no font was specified we can't continue.
         */
        if(!$params['font']){
            throw(bException(zxc('fonts_convert_ufpdf(): No font specified'), 'fonts'));
        }

        /*
         * Load needed libraries
         */
        lib_load('shell', 'fork,mv,cp');

        if($params['unicode'])	lib_load_ext('ufpdf', 'tools/makefontuni');
        else					lib_load_ext('fpdf' , 'tools/makefontuni');

        /*
         * If a font file was specified, then remove file data
         */
        if(strpos($params['font'], '.ttf')){
            $params['font'] = basename($params['font'], '.ttf');
        }

        /*
         * Create the font file with unicode extension
         */
        if($params['unicode']) sh_cp($kernel->config('paths', 'var').'fonts/'.$params['font'].'.ttf', $kernel->config('paths', 'var').'fonts/'.$params['font'].'_uni.ttf');

        /*
         * Convert ttf font file
         */
        sh_fork('usr/bin/ttf2pt1u', array('-a', '-Ob', $kernel->config('paths', 'var').'fonts/'.$params['font'].($params['unicode'] ? '_uni' : '').'.ttf'));

        /*
         * Run PHP from PHP, okay, we definately can do better than this!! :)
         */
        MakeFont($kernel->config('paths', 'var').'fonts/'.$params['font'].($params['unicode'] ? '_uni' : '').'.ttf', $kernel->config('paths', 'var').'fonts/'.$params['font'].($params['unicode'] ? '_uni' : '').'.ufm');

        /*
         * Move the output files from MakeFont to the fonts directory
         */
        sh_mv($params['font'].($params['unicode'] ? '_uni' : '').'*', $kernel->config('paths', 'var').'fonts/', false);

    }catch(Exception $e){
        throw new bException('fonts_convert_ufpdf(): Failed', $e);
    }
}
?>
