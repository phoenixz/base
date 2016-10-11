<?php
/*
 * Editors library
 *
 * This library contains functions to deploy various HTML Javascript editors
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



/*
 * Return HTML and JS for a niceditor
 */
function editors_nicedit($params){
    try{
        array_params($params);
        array_default($params, 'name' , 'editor');
        array_default($params, 'value', '');
        array_default($params, 'cols' , 40);
        array_default($params, 'rows' , 10);

        html_load_js('base/nicedit/nicEdit');

        return '<textarea name="'.$params['name'].'" id="'.$params['name'].'" cols="'.$params['cols'].'" rows="'.$params['rows'].'">'.$params['value'].'</textarea>';
//               html_script('var b = nicEditors.allTextAreas(); console.log(b); console.log("xxxxxxxxxxxxx");');
//               html_script('nicEditors.findEditor('.$params['name'].');');

    }catch(Exception $e){
        throw new bException('editors_nicedit(): Failed', $e);
    }
}



/*
 * Return HTML and JS for a tinymce
 *
 * See http://www.tinymce.com/tryit/3_x/jquery_plugin.php
 */

// :TODO: Add support for following:
/*
        <!-- Some integration calls -->
        <a href="javascript:;" onmousedown="$('#content').tinymce().show();">[Show]</a>
        <a href="javascript:;" onmousedown="$('#content').tinymce().hide();">[Hide]</a>
        <a href="javascript:;" onmousedown="$('#content').tinymce().execCommand('Bold');">[Bold]</a>
        <a href="javascript:;" onmousedown="alert($('#content').html());">[Get contents]</a>
        <a href="javascript:;" onmousedown="alert($('#content').tinymce().selection.getContent());">[Get selected HTML]</a>
        <a href="javascript:;" onmousedown="alert($('#content').tinymce().selection.getContent({format : 'text'}));">[Get selected text]</a>
        <a href="javascript:;" onmousedown="alert($('#content').tinymce().selection.getNode().nodeName);">[Get selected element]</a>
        <a href="javascript:;" onmousedown="$('#content').tinymce().execCommand('mceInsertContent',false,'<b>Hello world!!</b>');">[Insert HTML]</a>
        <a href="javascript:;" onmousedown="$('#content').tinymce().execCommand('mceReplaceContent',false,'<b>{$selection}</b>');">[Replace selection]</a>
*/
function editors_tinymce($params){
    try{
        array_params($params);
        array_default($params, 'name'         , 'editor');
        array_default($params, 'value'        , '');
        array_default($params, 'plugins'      , 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table contextmenu paste jbimages emoticons textcolor');
        array_default($params, 'toolbar'      , 'bold italic underline strikethrough forecolor backcolor styleselect fontselect fontsizeselect bullist numlist outdent indent blockquote undo redo removeformat emoticons image jbimages');
        array_default($params, 'menubar'      , 'edit insert view format table tools');

        array_default($params, 'relative_urls', false);
        array_default($params, 'height'       , '400');
        array_default($params, 'theme'        , 'modern');


        html_load_js('base/tinymce/jquery.tinymce');

        $html = '<textarea name="'.$params['name'].'" id="'.$params['name'].'" class="tinymce">'.$params['value'].'</textarea>'.
               html_script('$("#'.$params['name'].'").tinymce({
                    // Location of TinyMCE script
                    script_url : "'.domain('/pub/js/base/tinymce/tinymce.min.js').'",

                    // General options
                    theme         : "'.$params['theme'].'",
                    plugins       : "'.$params['plugins'].'",
                    relative_urls : "'.$params['relative_urls'].'",
                    height        : "'.$params['height'].'",
                    toolbar       : "'.$params['toolbar'].'",
                    menubar       : "'.$params['menubar'].'",

                    // Example content CSS (should be your site CSS)
                    content_css : "css/content.css"

                    // Drop lists for link/image/media/template dialogs
                    //template_external_list_url : "lists/template_list.js",
                    //external_link_list_url : "lists/link_list.js",
                    //external_image_list_url : "lists/image_list.js",
                    //media_external_list_url : "lists/media_list.js",

                    // Replace values for the template plugin
                    //template_replace_values : {
                    //        username : "Some User",
                    //        staffid : "991234"
                    //}
               });');

        return $html;

    }catch(Exception $e){
        throw new bException('editors_tinymce(): Failed', $e);
    }
}



/*
 * Editor summernote to atlant template
 */
function editors_summernote($params = null){
    try{
        array_params($params, 'name');
        array_default($params, 'name' , 'editor');
        array_default($params, 'class', 'summernote editor');
        array_default($params, 'extra', '');
        array_default($params, 'value', '');

        html_load_js('plugins/summernote/summernote');

        $html = '<textarea class="summernote" name="'.$params['name'].'" id="'.$params['name'].'" class="'.$params['class'].'"'.($params['extra'] ? ' '.$params['extra'] : '').'>'.$params['value'].'</textarea>'.
                html_script('$(".summernote").summernote();');

        return $html;

    }catch(Exception $e){
        throw new bException('editors_summernote(): Failed', $e);
    }
}
?>
