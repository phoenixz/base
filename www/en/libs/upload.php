<?php
/*
 * Upload library
 *
 * This library contains functions to manage uploads using the jQuery-File-Upload library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Process uploaded files
 */
function upload_process(){
    try{

    }catch(Exception $e){
        throw new bException('upload_process(): Failed', $e);
    }
}



/*
 * Single file upload using ocupload
 */
function upload_ocupload($selector = "input[name=upload]", $url = '/ajax/upload.php', $params = array()){
    try{
        array_params($params);
        array_default($params, 'executeon', true);

        load_libs('html');
        html_load_js('base/ocupload/jquery.ocupload');

        return html_script('$("'.$selector.'").upload({
                    name: "file",
                    method: "post",
                    action: "'.$url.'",
                    enctype: "multipart/form-data",
                    '.(!empty($params['params']) ? 'params: '.$params['params'].',.' : '').'
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
 *
 */
function upload_multi_js($element, $url, $done_script = '', $fail_script = '') {
    html_load_js('base/jquery-ui/jquery-ui');
    html_load_js('base/jfu/jquery.iframe-transport');
    html_load_js('base/jfu/jquery.fileupload');

    return "<script>
        $('".$element."').fileupload({
            url      : '".$url."',
            ".

            ($done_script ?      'done  : function (e, data) { $.handleDone(data.result, '.$done_script.'); },' : '').

            ($fail_script ? "\n".'fail  : function (e, data) { $.handleFail(data, '.$fail_script.'); },' : '').'

            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);

                $(".progress.bar").css(
                    "width",
                    progress + "%"
                );

                $("#progress_nr").html(progress + "%");
            }
        });
        </script>';
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
        array_default($params, 'noscript', '<noscript><input type="hidden" name="redirect" value="'.$_CONFIG['domain'].'"></noscript>');
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
        $js = array('base/jquery-ui/jquery-ui');

        /*
         * Optional libraries
         */
        foreach($params['options'] as $key => $value){
            if(($key == 'iframe') and $value){
                $js[] = 'base/jfu/jquery.iframe-transport';
                continue;
            }

            if($value){
                $js[] = 'base/jfu/jquery.fileupload-'.$key;
            }
        }

        /*
         * Add main library
         */
        $js[] = 'base/jfu/jquery.fileupload';
        $js[] = 'base/jfu/main';


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
function upload_check_files($files = null){
    if(debug()){
        $errors = array(UPLOAD_ERR_OK         => tr('ok'),
                        UPLOAD_ERR_INI_SIZE   => tr('The uploaded file is too large'),
                        UPLOAD_ERR_FORM_SIZE  => tr('The uploaded file is too large'),
                        UPLOAD_ERR_PARTIAL    => tr('The file upload failed, please try again'),
                        UPLOAD_ERR_NO_FILE    => tr('The file upload failed, please try again'),
                        UPLOAD_ERR_NO_TMP_DIR => tr('The server cannot accepts file uploads right now. Please try again later'),  // 6 This will give a notification to us!
                        UPLOAD_ERR_CANT_WRITE => tr('The server cannot accepts file uploads right now. Please try again later'),  // 7 This will give a notification to us!
                        UPLOAD_ERR_EXTENSION  => tr('The server cannot accepts file uploads right now. Please try again later')); // 8 This will give a notification to us!

    }else{
        $errors = array(UPLOAD_ERR_OK         => tr('ok'),
                        UPLOAD_ERR_INI_SIZE   => tr('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
                        UPLOAD_ERR_FORM_SIZE  => tr('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
                        UPLOAD_ERR_PARTIAL    => tr('The uploaded file was only partially uploaded'),
                        UPLOAD_ERR_NO_FILE    => tr('No file was uploaded'),
                        UPLOAD_ERR_NO_TMP_DIR => tr('Missing a temporary folder'),               // 6 This will give a notification to us!
                        UPLOAD_ERR_CANT_WRITE => tr('Failed to write file to disk'),             // 7 This will give a notification to us!
                        UPLOAD_ERR_EXTENSION  => tr('A PHP extension stopped the file upload')); // 8 This will give a notification to us!
    }

    if($files === null){
        $files = $_FILES;
    }

    foreach($files as $key => $value){
        if((is_array($value) and !empty($value['error'])) or (($key == 'error') and $value)){
            if(is_scalar($value)){
                /*
                 * This comes from a form input named "foobar"
                 */
                if($value){
                    $failed[] = array('code'    => $value,
                                      'message' => $errors[$value]);
                }

            }elseif(is_scalar(isset_get($value[0]))){
                /*
                 * This comes from a form input named "foobar"
                 */
                if($value[0]){
                    $failed[] = array('code'    => $value[0],
                                      'message' => $errors[$value[0]]);
                }

            }elseif(is_scalar(isset_get($value['error']))){
                /*
                 * This comes from a form input named "foobar"
                 */
                if($value['error']){
                    $failed[$key] = array('code'    => $value['error'],
                                          'message' => $errors[$value['error']]);
                }

            }else{
                /*
                 * This comes from a form input named "foobar[]"
                 */
                foreach($value['error'] as $subkey => $errorcode){
                    if($errorcode){
                        $failed[$key] = array('code'    => $errorcode,
                                              'message' => $errors[$errorcode]);
                    }
                }
            }
        }
    }

    if(empty($failed)){
        return false;
    }

    /*
     * Check if errors should cause notifications or not
     */
    foreach($failed as $key => $error){
        if($error['code'] >= 6){
// :TODO:SVEN:20130717: Add site admin notification here!
        }
    }

    return $failed;
}
?>
