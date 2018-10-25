<?php
/*
 * Upload library
 *
 * This library contains functions to manage uploads using the jQuery-File-Upload library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 *
 * To disable chrome strict CORS checks, start chrome with --disable-web-security
 */



/*
 * Initialize the library
 * Auto executed by libs_load
 */
function upload_library_init(){
    try{
        load_config('upload');

    }catch(Exception $e){
        throw new bException('upload_library_init(): Failed', $e);
    }
}



/*
 *
 */
function upload_dropzone($selector = null, $url = '/ajax/upload.php', $params = array()){
    try{
        if(!file_exists(ROOT.'pub/js/dropzone.js')){
            load_libs('file');
            file_copy_to_target('https://raw.github.com/enyo/dropzone/master/dist/dropzone.js', ROOT.'pub/js/', '.js', true, false);
        }

        html_load_js('dropzone');

        if(!$selector){
            /*
             * Do dropzone from all elements that have the "dropzone" class
             */
            return '';
        }

        return html_script('$("'.$selector.'").dropzone({ url: "'.$url.'" })');

    }catch(Exception $e){
        throw new bException('upload_dropzone(): Failed', $e);
    }
}



/*
 * Single file upload using ocupload
 *
 * WARNING: OCUPLOAD CANNOT UPLOAD ACROSS DOMAINS!
 */
function upload_ocupload($selector = 'input[name=upload]', $url = '/ajax/upload.php', $params = array()){
    try{
        array_params($params);
        array_default($params, 'executeon', true);

        load_libs('html');
        html_load_js('ocupload/jquery.ocupload');

        if(!empty($params['params'])){
            load_libs('json');

            if(!is_array($params['params'])){
                throw new bException(tr('upload_ocupload(): Specified $params[params] is not an array'), 'invalid');
            }
        }

        return html_script('$("'.$selector.'").upload({
                    name: "file",
                    method: "post",
                    action: "'.$url.'",
                    enctype: "multipart/form-data",
                    '.(!empty($params['params']) ? 'params: '.json_encode_custom($params['params']).',' : '').'
                    autoSubmit: true,
                    onSubmit: function() {
                        '.isset_get($params['onSubmit']).'
                    },
                    onSelect: function() {
                        '.isset_get($params['onSelect']).'
                    },
                    onComplete: function(data) {
                        '.isset_get($params['onComplete']).'
                    }
                });', $params['executeon']);

    }catch(Exception $e){
        throw new bException('upload_ocupload(): Failed', $e);
    }
}



/*
 * Jquery File Upload, supports multiple files at once
 *
 * WARNING! With iframe option, cross domain uploads will NOT work!
 *
 * See https://github.com/blueimp/jQuery-File-Upload/wiki for documentation
 */
function upload_multi($params){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'selector'  , '');
        array_default($params, 'url'       , '');
        array_default($params, 'done'      , '');
        array_default($params, 'fail'      , '');
        array_default($params, 'complete'  , '');
        array_default($params, 'process'   , '');
        array_default($params, 'processall', '');
        array_default($params, 'post'      , null);
        array_default($params, 'iframe'    , false);

        if($params['post']){
            /*
             * Validate post to be an array first to ensure clean exceptions
             */
            if(!is_array($params['post'])){
                throw new bException(tr('upload_multi(): Specified post parameter should be an array but is a ":type"', array(':type' => gettype($params['post']))), 'invalid');
            }

            if($_CONFIG['security']['csrf']['enabled']){
                /*
                 * CSRF required, auto add the current CSRF
                 */
                $params['post']['csrf'] = set_csrf();
            }
        }

        if(empty($params['selector'])){
            throw new bException(tr('upload_multi(): No "selector" specified'), 'not-specified');
        }

        if(empty($params['url'])){
            throw new bException(tr('upload_multi(): No "url" specified'), 'not-specified');
        }

        html_load_js('jquery-ui/jquery-ui,base/base');

        if($params['iframe']){
            html_load_js('jfu/jquery.iframe-transport');
        }

        html_load_js('jfu/jquery.fileupload');

        if($params['processall'] === '' and $params['process'] === ''){
            /*
             * No upload processing specified, default to basic "processall"
             */
            $params['processall'] = '
                var progress = parseInt(data.loaded / data.total * 100, 10);

                $(".progress.bar").css(
                    "width",
                    progress + "%"
                );

                $("#progress_nr").html(progress + "%");';
        }

        return html_script('$("'.$params['selector'].'").fileupload({
                url      : "'.$params['url'].'",
                '.

                ($params['complete'] ?   'complete     : '.$params['complete'].','                                  : '').

                ($params['fail']     ? "\n".'fail      : '.$params['fail'].','                                      : '').

                ($params['post']     ? "\n".'formData  : '.json_encode_custom(array_to_object($params['post'])).',' : '').'

                progress: function (e, data) {
                    '.$params['process'].'
                },
                progressall: function (e, data) {
                    '.$params['processall'].'
                }
            });');

    }catch(Exception $e){
        throw new bException('upload_multi(): Failed', $e);
    }
}



/*
 * Return the HTML required to use the jQuery-File-Upload library
 *
 * NOTE: This function will automatically add
 */
function upload_get_html($type, $target = null, $params = null){
    global $_CONFIG;

    load_libs('array');

    try{
        if(is_array($type)){
            $params = $type;
            $type   = $params['type'];

        }elseif(is_array($target)){
            $params = $target;
            $target = array_value($params, 'target');
        }

        if(!$target){
            $target = $_SERVER['PHP_SELF'];
        }

        if(empty($params)){
            $params = array();
        }

        /*
         * Assign defaults values
         */
        array_default($params, 'noscript', '<noscript><input type="hidden" name="redirect" value="'.$_SESSION['domain'].'"></noscript>');
        array_default($params, 'id'      , 'fileupload');
        array_default($params, 'lister'  , false);

        array_default($params, 'options' , array());
        array_default($params['options'], 'iframe'  , true);
        array_default($params['options'], 'process' , true);
        array_default($params['options'], 'image'   , true);
        array_default($params['options'], 'audio'   , true);
        array_default($params['options'], 'video'   , true);
        array_default($params['options'], 'validate', true);
        array_default($params['options'], 'ui'      , true);

        /*
         * Basic JS library requirements
         */
        $js = array('jquery-ui/jquery-ui');

        /*
         * Optional libraries
         */
        foreach($params['options'] as $key => $value){
            if(($key == 'iframe') and $value){
                $js[] = 'jfu/jquery.iframe-transport';
                continue;
            }

            if($value){
                $js[] = 'jfu/jquery.fileupload-'.$key;
            }
        }

        /*
         * Add main library
         */
        $js[] = 'jfu/jquery.fileupload';
        $js[] = 'jfu/main';


        /*
         *Determine what uploader to use
         */
        if(empty($type)){
            throw new bException('upload_get_html(): No upload widget type specified');
        }

        switch($type){
            case 'basic':
                break;

            case 'basicplus':
                break;

            case 'basicplusui':
                html_load_js($js);
                html_load_css('jfu/jquery.fileupload-ui');

                $retval = '<form id="'.$params['id'].'" action="'.$target.'" method="POST" enctype="multipart/form-data">'.

                            '<div class="row fileupload-buttonbar">
                                <div class="span7">
                                    <!-- The fileinput-button span is used to style the file input field as button -->
                                    <span class="btn btn-success fileinput-button">
                                        <i class="icon-plus icon-white"></i>
                                        <span>Add files...</span>
                                        <input type="file" name="files[]" multiple>
                                    </span>
                                    <button type="submit" class="btn btn-primary start">
                                        <i class="icon-upload icon-white"></i>
                                        <span>Start upload</span>
                                    </button>
                                    <button type="reset" class="btn btn-warning cancel">
                                        <i class="icon-ban-circle icon-white"></i>
                                        <span>Cancel upload</span>
                                    </button>
                                    <button type="button" class="btn btn-danger delete">
                                        <i class="icon-trash icon-white"></i>
                                        <span>Delete</span>
                                    </button>
                                    <input type="checkbox" class="toggle">
                                    <!-- The loading indicator is shown during file processing -->
                                    <span class="fileupload-loading"></span>
                                </div>
                                <!-- The global progress information -->
                                <div class="span5 fileupload-progress fade">
                                    <!-- The global progress bar -->
                                    <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                                        <div class="bar" style="width:0%;"></div>
                                    </div>
                                    <!-- The extended global progress information -->
                                    <div class="progress-extended">&nbsp;</div>
                                </div>
                            </div>'.

                          '</form>';

                break;

            case 'angularjs':
                break;

            default:
                throw new bException('upload_get_html(): Unknown widget type "'.str_log($type).'" specified');
        }

        if($params['lister']){
            $retval .= '<table role="presentation" class="table table-striped" id="'.$params['lister'].'"><tbody class="files"></tbody></table>';
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('upload_get_html(): Failed', $e);
    }
}



/*
 * Check the PHP $_FILES array, and return either false (all is ok) or an array containing all file upload errors
 *
 * This is the mess that PHP would give for a form with 4 file upload elements, called test1, test2, tests[] and tests[]
 *
 * Upload error codes 6 and up will always send notifications to sven because they indicate problems with server, setup, configuration, etc.
 *
 * NOTICE! ERRORS CODE 6 AND UP SHOULD NEVER BE SENT TO THE CLIENTS!!
 *
Array
(
    [test] => Array
        (
            [name] => Array
                (
                    [0] => green-apple-wallpapers-1280x720.jpg
                    [1] => wallpaper mountains aZKgoKM.jpg
                )

            [type] => Array
                (
                    [0] => image/jpeg
                    [1] => image/jpeg
                )

            [tmp_name] => Array
                (
                    [0] => /tmp/php8o6Nhe
                    [1] => /tmp/php97xtAL
                )

            [error] => Array
                (
                    [0] => 0
                    [1] => 0
                )

            [size] => Array
                (
                    [0] => 170738
                    [1] => 585197
                )

        )

    [test1] => Array
        (
            [name] => Cool-Summer-Desktop-Wallpaper1.jpg
            [type] => image/jpeg
            [tmp_name] => /tmp/phpSmqgH9
            [error] => 0
            [size] => 393814
        )

    [test2] => Array
        (
            [name] => tank-man.jpg
            [type] => image/jpeg
            [tmp_name] => /tmp/phpNKisZG
            [error] => 0
            [size] => 410809
        )

)
 */
function upload_check_files($max_uploads = null, $min_uploads = null){
    try{
        if(debug()){
            $errors = array(UPLOAD_ERR_OK         => tr('ok'),
                            UPLOAD_ERR_INI_SIZE   => tr('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
                            UPLOAD_ERR_FORM_SIZE  => tr('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
                            UPLOAD_ERR_PARTIAL    => tr('The uploaded file was only partially uploaded'),
                            UPLOAD_ERR_NO_FILE    => tr('No file was uploaded'),
                            UPLOAD_ERR_NO_TMP_DIR => tr('Missing a temporary folder'),               // 6 This will give a notification to us!
                            UPLOAD_ERR_CANT_WRITE => tr('Failed to write file to disk'),             // 7 This will give a notification to us!
                            UPLOAD_ERR_EXTENSION  => tr('A PHP extension stopped the file upload')); // 8 This will give a notification to us!

        }else{
            $errors = array(UPLOAD_ERR_OK         => tr('ok'),
                            UPLOAD_ERR_INI_SIZE   => tr('The uploaded file is too large'),
                            UPLOAD_ERR_FORM_SIZE  => tr('The uploaded file is too large'),
                            UPLOAD_ERR_PARTIAL    => tr('The file upload failed, please try again'),
                            UPLOAD_ERR_NO_FILE    => tr('The file upload failed, please try again'),
                            UPLOAD_ERR_NO_TMP_DIR => tr('The server cannot accepts file uploads right now. Please try again later'),  // 6 This will give a notification to us!
                            UPLOAD_ERR_CANT_WRITE => tr('The server cannot accepts file uploads right now. Please try again later'),  // 7 This will give a notification to us!
                            UPLOAD_ERR_EXTENSION  => tr('The server cannot accepts file uploads right now. Please try again later')); // 8 This will give a notification to us!
        }

        /*
         * Rearrange $_FILES to make a bit more sense
         */
        $files = array();
        $count = 0;

        if(empty($_FILES)){
            /*
             * Apparently no files were uploaded?
             */
            $_FILES = array();
            $error_list[0] = array(UPLOAD_ERR_NO_FILE => $errors[UPLOAD_ERR_NO_FILE]);
        }

        /*
         * Reorder the $_FILES array to make sense
         */
        foreach($_FILES as $formname => $filedata){
            if(is_array($filedata['name'])){
                foreach($filedata as $section => $data){
                    if($section === 'name'){
                        $count++;
                    }

                    foreach($data as $key => $value){

                        if(empty($files[$formname][$key])){
                            $files[$formname][$key] = array();
                        }

                        $files[$formname][$key][$section] = $value;
                    }
                }

            }else{
                $count++;
                $files[0] = $_FILES[$formname];
            }
        }

        $_FILES = $files;
        unset($files);

        /*
         * Basic validations
         */
        if($max_uploads and ($count > $max_uploads)){
            if($max_uploads == 1){
                throw new bException(tr('upload_check_files(): Multiple file uploads are not allowed'), 'multiple');
            }

            throw new bException(tr('upload_check_files(): $_FILES contains ":count" which is more than the maximum of ":max"', array(':count' => $count, ':max' => str_log($max_uploads))), 'toomany');
        }

        if($min_uploads and ($count < $min_uploads)){
            if($min_uploads == 1){
                throw new bException(tr('upload_check_files(): No files were uploaded'), 'none');
            }

            throw new bException(tr('upload_check_files(): $_FILES contains ":count" which less more than the minimum of ":min"', array(':count' => $count, ':min' => str_log($min_uploads))), 'toofew');
        }

        /*
         * Check for errors and add error messages where needed
         */
        foreach($_FILES as &$file){
            foreach($file as $key => &$value){
                switch(isset_get($value['error'])){
                    case 0:
                        continue;

                    case 6: // UPLOAD_ERR_NO_TMP_DIR
                        // FALLTHROUGH
                    case 7: // UPLOAD_ERR_CANT_WRITE
                        // FALLTHROUGH
                    case 8: // UPLOAD_ERR_EXTENSION
                        if(!debug()){
                            notify('upload_check_files()', tr('Encountered file upload error "%error%" which indicates a server or configuration error', array('%error%' => $value['error'])), 'log,developers');
                            break;
                        }

                        // FALLTHROUGH
                    default:
                        $value['error_message'] = $errors[$value['error']];
                        $error_list[$key]       = array($value['error'] => $errors[$value['error']]);

                        /*
                         * Ensure this file is removed!
                         */
                        if(file_exists($value['tmp_name'])){
                            load_libs('file');
                            file_delete($value['tmp_name']);
                        }
                }
            }
        }

        unset($file);
        unset($value);

        if(!empty($_FILES['files'][0]['error'])){
            throw new bException(isset_get($_FILES['files'][0]['error_message'], tr('PHP upload error code ":error"', array(':error' => $_FILES['files'][0]['error']))), $_FILES['files'][0]['error']);
        }

        return isset_get($error_list);

    }catch(Exception $e){
        throw new bException('upload_check_files(): Failed', $e);
    }
}



/*
 * Obsolete wrapper functions for compatibility, which should NOT be used!
 */
function upload_multi_js($selector, $url, $done_script = '', $fail_script = '', $processall_script = '') {
    try{
//        notify('obsolete', 'upload_multi_js() usage is obsolete, please use upload_multi()', 'developers');

        return upload_multi(array('selector'    => $selector,
                                  'url'         => $url,
                                  'done'        => $done_script,
                                  'fail'        => $fail_script,
                                  '$processall' => $processall_script));

    }catch(Exception $e){
        throw new bException('upload_multi_js(): Failed', $e);
    }
}
?>
