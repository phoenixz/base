<?php
/*
 * Editors library
 *
 * This library contains functions to deploy various HTML Javascript editors
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
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
        array_default($params, 'name'            , 'editor');
        array_default($params, 'class'           , 'summernote editor');
        array_default($params, 'extra'           , '');
        array_default($params, 'value'           , '');
        array_default($params, 'height'          , 500);
        array_default($params, 'focus'           , false);
        array_default($params, 'on_image_upload' , '');
        array_default($params, 'toolbar'         , null);
        array_default($params, 'tooltips'        , false);
        array_default($params, 'placeholder'     , '');
        array_default($params, 'on_init'         , '');

//        html_load_css('bootstrap/bootstrap,summernote/summernote');
        html_load_css('bootstrap/bootstrap');
        html_load_css('summernote');
        html_load_js('plugins/summernote/summernote');
        html_load_js('plugins/summernote/summernote_custom');

        if(!$params['tooltips']){
            $params['on_init'] .= ' alert("YAY"); $(".note-editor [data-name=\"ul\"]").tooltip("disable");';
        }

        if($params['height'])          $options['height']        = $params['height'];
        if($params['focus'])           $options['focus']         = $params['focus'];
        if($params['on_image_upload']) $options['onImageUpload'] = $params['on_image_upload'];
        if($params['placeholder'])     $options['placeholder']   = $params['placeholder'];

        if($params['on_init']){
            $options['onInit'] = 'function(){'.$params['on_init'].'}';
        }

        if($params['toolbar']){
            /*
             * Validate the toolbar
             */
            if(!is_array($params['toolbar'])){
                throw new bException('editors_summernote(): Specified toolbar option is invalid, must be an array', 'invalid');
            }

            $available = array('picture',
                               'link',
                               'video',
                               'table',
                               'hr',
                               'fontname',
                               'fontsize',
                               'color',
                               'bold',
                               'italic',
                               'underline',
                               'strikethrough',
                               'superscript',
                               'subscript',
                               'clear',
                               'style',
                               'ol',
                               'ul',
                               'paragraph',
                               'height',
                               'fullscreen',
                               'codeview',
                               'undo',
                               'redo',
                               'help');

            foreach($params['toolbar'] as $group => $buttons){
                $entry = array($group);

                if(!is_array($buttons)){
                    throw new bException(tr('editors_summernote(): Specified toolbar group ":group" is invalid, must be an array', array(':group' => $group)), 'invalid');
                }

                foreach($buttons as $button){
                    if(!is_scalar($button)){
                        throw new bException(tr('editors_summernote(): Specified toolbar group ":group" contains an invalid button. Button name should be scalar', array(':group' => $group)), 'invalid');
                    }

                    if(!in_array($button, $available)){
                        throw new bException(tr('editors_summernote(): Specified toolbar group ":group" contains unknown button ":button". Buttons should be one of ""', array(':group' => $group, ':button' => $button)), 'unknown');
                    }
                }

                $entry[]              = $buttons;
                $options['toolbar'][] = $entry;
            }
        }

        $value_arr    = array();
        $replace_keys = array();

        /*
         * Move all functions out of the way so we won't break them with json
         */
        foreach($options as $key => &$value){
            if(is_scalar($value)){
                /*
                 * Look for values starting with 'function('
                 */
                if((strpos($value, 'function(') === 0) or (strpos($value, '(function(') === 0)){
                    /*
                     * Store function string.
                     */
                    $value_arr[] = $value;
                    /*
                     * Replace function string in $foo with a ‘unique’ special key.
                     */
                    $value = '%' . $key . '%';
                    /*
                     * Later on, we’ll look for the value, and replace it.
                     */
                    $replace_keys[$key] = '"' . $value . '"';
                }
            }
        }

        /*
         * Build JS options array
         */
        if(!empty($options)){
            $options = json_encode($options);
            $options = str_replace($replace_keys, $value_arr, $options);

        }else{
            $options = '';
        }

        $html = '<textarea class="summernote" name="'.$params['name'].'" id="'.$params['name'].'" class="'.$params['class'].'"'.($params['extra'] ? ' '.$params['extra'] : '').'>'.$params['value'].'</textarea>'.
                html_script('$(".summernote").summernote('.$options.');');

        return $html;

    }catch(Exception $e){
        throw new bException('editors_summernote(): Failed', $e);
    }
}

?>
