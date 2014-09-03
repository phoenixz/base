<?php
/*
 * Form data verification library
 *
 * Written by Sven Oostenbrink
 * examples :
 *

$vj = new validate_jquery();

$vj->validate('email'    , 'required' , 'true'                  , '<span class="FcbErrorTail"></span>'.tr('This field is required'));
$vj->validate('email'    , 'email'    , 'true'                  , '<span class="FcbErrorTail"></span>'.tr('This is not a valid email address'));
$vj->validate('email'    , 'remote'   , '/ajax/signup_check.php', '<span class="FcbErrorTail"></span>'.tr('This email address is already in use'));
$vj->validate('name'     , 'required' , 'true'                  , '<span class="FcbErrorTail"></span>'.tr('Please enter your name'));
$vj->validate('terms'    , 'required' , 'true'                  , '<span class="FcbErrorTail"></span>'.tr('You are required to accept the terms and conditions'));
$vj->validate('password' , 'required' , 'true'                  , '<span class="FcbErrorTail"></span>'.tr('Please enter a password'));
$vj->validate('password' , 'minlength', '8'                     , '<span class="FcbErrorTail"></span>'.tr('Your password needs to have at least 8 characters'));
$vj->validate('password2', 'equalTo'  , '#password'             , '<span class="FcbErrorTail"></span>'.tr('The password fields need to be equal'));
$vj->validate('terms'    , 'required' , 'true'                  , '<span class="FcbErrorTail"></span>'.tr('You have to accept the terms and privacy policy'));
$vj->validate('tel'      , 'regex'    , '^[^a-z]*$'             , '<span class="FcbErrorTail"></span>'.tr('Please specify a valid phone number'));

$params = array('id'           => 'FcbSignup',

                'quickhandler' => array('target' => '/ajax/signup.php',
                                        'fail'   => 'alert("FAIL"); alert(textStatus);'));

$html .= $vj->output_validation($params);

*/



class validate_jquery {
    var $validations = array();

    function validate($element, $rule, $value, $msg) {
        switch($rule){
            case 'required':
                // FALLTHROUGH
            case 'email':
                // FALLTHROUGH
            case 'remote':
                // FALLTHROUGH
            case 'minlength':
                // FALLTHROUGH
            case 'maxlength':
                // FALLTHROUGH
            case 'equalTo':
                // FALLTHROUGH
            case 'regex':
                break;

            default:
                throw new lsException('validate_jquery->validate(): Unknown rule "'.str_log($rule).'" specified', 'unknown');
        }

        $this->validations[$element][] = array('rule'  => $rule,
                                               'value' => $value,
                                               'msg'   => addslashes($msg));
    }

    function output_validation($params, $script = '') {
        try{
            load_libs('array');
            html_load_js('base/jquery.validate');

            if(!is_string($params)){
                if(!is_array($params)){
                    throw new lsException('validate_jquery->output_validation(): Invalid $params specified. Must be either string, or assoc array containing at least "id"');
                }

                if(empty($params['id'])){
                    throw new lsException('validate_jquery->output_validation(): Invalid $params specified. Must be either string, or assoc array containing at least "id"');
                }

            }else{
                if(!$params){
                    throw new lsException('validate_jquery->output_validation(): Empty $params specified');
                }

                $params = array('id' => $params);
            }

            $html = '<script type="text/javascript">
            $(document).ready(function(){
                $("'.str_starts($params['id'], '#').'").validate({
                rules: {';
                $kom = '';

                foreach($this->validations as $element => $validations) {
                    $html .= $kom.$element.': {';
                    $kom2  = '';

                    foreach($validations as $val) {
                        if(($val['value'] != 'true') and ($val['value'] != 'false')){
                            $val['value'] = '"'.$val['value'].'"';

                            if($val['rule'] == 'regex'){
                                /*
                                 * Don't forget to add the regular expression extension!
                                 */
                                $addregex = true;
                            }
                        }

                        $html .= $kom2.$val['rule'].':'.$val['value']."\n";
                        $kom2=',';
                    }

                    $html .= '}';
                    $kom   = ',';
                }

                $html .= '},
                messages: {';

                $kom = '';

                foreach($this->validations as $element => $validations) {
                    $html .= $kom.$element.': {';
                    $kom2  = '';

                    foreach($validations as $val) {
                        $html .= $kom2.$val['rule'].':"'.$val['msg']."\"\n";
                        $kom2  = ',';
                    }

                    $html .= '}';
                    $kom   = ',';
                }

                $html .= '}';

                if(!empty($params['submithandler'])){
                    if(!empty($params['submithandler'])){
                        throw new lsException('validateJquery->output_validation(): Both submithandler and quickhandler are specified, these handlers are mutually exclusive');
                    }

                    $html .= ",\n".'submitHandler : function(form){'.$params['submithandler'].'}';
                }

                if(!empty($params['quickhandler'])){
                    if(!is_array($params['quickhandler'])){
                        throw new lsException('validateJquery->output_validation(): Invalid quickhandler specified, it should be an assoc array');
                    }

                    $handler = $params['quickhandler'];

                    foreach(array('target', 'fail') as $key){
                        if(empty($handler[$key])){
                            throw new lsException('validateJquery->output_validation(): No quickhandler key "'.$key.'" specified');
                        }
                    }

                    array_default($handler, 'done', '');
                    array_default($handler, 'json', true);

                    $html .= ",\nsubmitHandler : function(form){\$.post(\"".$handler['target']."\", $(form).serialize())
                            .done(function(data)   { $.handleDone(data, function(data){".$handler['done']."}, cbe) })
                            .fail(function(a, b, e){ $.handleFail(e, cbe) });
                        }\n";
// :TODO:SVEN:20120709: Remove this crap. Only left here just in case its needed ..
                //e.stopPropagation();
                //return false;';
                }

                if(!empty($params['errorplacement'])){
                    $html .= ",\nerrorPlacement : function(error, element){".$params['errorplacement']."}\n";
                }

                $html .= "});\n";

                if(!empty($params['quickhandler'])){
                    $html .= "var cbe = function(e){".$handler['fail']."};\n";
                }

            $html .= '});'."\n";


            if($script){
                $html .= $script."\n";
            }

            if(!empty($addregex)){
                $html .= '$.validator.addMethod(
                            "regex",
                            function(value, element, regexp) {
                                var re = new RegExp(regexp);
                                return this.optional(element) || re.test(value);
                            },
                            "Please check your input."
                        );';
            }

            return $html .= '</script>';

        }catch(Exception $e){
            throw new lsException('validateJquery->output_validation(): Failed', $e);
        }
    }
}



/*
 * Form validation tests
 */
class validate_form {
    public  $errors = array();
    private $source = array();



    /*
     *
     */
    function __construct(&$source = null, $columns = null, $default_value = null){
        try{
            if(is_array($source)){
                $this->source = $source;

                foreach($source as $key => &$value){
                    $value = mb_trim($value);
                }

                unset($value);

                /*
                 * Make sure "id" is always available, since it will near always be used.
                 */
                array_default($source, 'id', 0);

                if($columns){
                    array_ensure($source, $columns, $default_value);
                }
            }

        }catch(Exception $e){
            throw new lsException('validate_form->__construct(): Failed', $e);
        }
    }



    /*
     *
     */
    function isNotEmpty($value, $msg) {
        $value = trim($value);

        if(!$value) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isValidName($value, $msg) {
        if(!$value) return '';

        if(!is_string($value) and strlen($value) <= 64) {
            $this->errors[] = $msg;

        }else{
            return cfm($value);
        }
    }



    /*
     *
     */
    function isAlphanum($value, $msg) {
        if(!$value) return '';

        if(!ctype_alnum($value)) {
            $this->errors[] = $msg;

        }else{
            return cfm($value);
        }
    }



    /*
     *
     */
    function isValidEmail($value, $msg) {
        $value = cfm($value);
        if(!$value) return '';

        if(!is_valid_email($value)) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isNumeric($value, $msg) {
        if($value != cfi($value)){
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isValidPhonenumber($value, $msg) {
        $value = strtolower(cfm($value));
        if(!$value) return '';

//      if(!preg_match('^(?:(?:\+?1\s*(?:[.-]\s*)?)?(?:\(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\s*\)|([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$', $value)) {
        if(!preg_match('/^(?:(?:\+|00)[1-9]{1,5}\s*)?(?:\(?[0-9]{1,4}\)?\s*)?[0-9]{2,4}(?:\s|-)?[0-9]{3,5}(?:x|ext[0-9]{1,5})?$/', $value)) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isEqual($value, $value2, $msg) {
        $value  = trim($value);
        $value2 = trim($value2);

        if($value != $value2) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isNotEqual($value, $value2, $msg) {
        $value  = trim($value);
        $value2 = trim($value2);

        if($value == $value2) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isBetween($value, $min, $max, $msg) {
        if($value < $min and $value > $max) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isEnabled($value, $msg) {
        $value = cfm($value);

        if(!$value) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function hasMinChars($value, $limit, $msg) {
        $value = trim($value);

        if(strlen($value) < $limit) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function hasMaxChars($value, $limit, $msg) {
        $value = trim($value);

        if(strlen($value) > $limit) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function hasChars($value, $limit, $msg) {
        $value = trim($value);

        if(strlen($value) != $limit) {
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isValidUrl($value, $msg) {
        if(!$value) return '';

        if(filter_var($value, FILTER_VALIDATE_URL) === FALSE) {
            $this->errors[] = $msg;
        } else {
            return $value;
        }
    }



    /*
     *
     */
    function isValidFacebookUserpage($value, $msg) {
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:https?:\/\/)(?:www\.)?facebook\.com\/(.+)$/', $value)){
            return $value;

        }else{
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    function isValidTwitterUserpage($value, $msg) {
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:https?:\/\/)(?:www\.)?twitter\.com\/(.+)$/', $value)){
            return $value;

        }else{
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    function isValidGoogleplusUserpage($value, $msg) {
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:(?:https?:\/\/)?plus\.google\.com\/)(\d{21,})(?:\/posts)?$/', $value, $matches)){
            return $matches[1];

        }else{
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    function isValidYoutubeUserpage($value, $msg) {
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:https?:\/\/)(?:www\.)?youtube\.com\/user\/(.+)$/', $value)){
            return $value;

        }else{
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    function isValidLinkedinUserpage($value, $msg) {
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:https?:\/\/)(?:www\.)?linkedin\.com\/(.+)$/', $value)){
            return $value;

        }else{
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    function isChecked($value, $msg) {
        if(!$value){
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    function isValidPassword($value, $msg) {
        if(strlen($value) >= 8){
            return $value;

        }else{
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    function isRegex($value, $regex, $msg) {
        if(preg_match($regex, $value)){
            return $value;

        }else{
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    function isInRange($value, $min, $max, $msg) {
        if(!is_numeric($value) or ($value < $min) or ($value > $max)){
            $this->errors[] = $msg;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isDate($value, $msg) {
// :TODO: IMPLEMENT
        return $value;
    }



    /*
     *
     */
    function isTime($value, $msg) {
        try{
            load_libs('date');

            $value = date_time_validate($value);

            return $value['time'];

        }catch(Exception $e){
            if($e->code == 'invalid'){
                throw new lsException('validate_form->is_time(): Specified time "'.str_log($value).'" is invalid', $e);
            }

            throw new lsException('validate_form->is_time(): Failed', $e);
        }
    }



    /*
     *
     */
    function sqlQuery($sql, $result, $msg) {
        $res = sql_get($sql);

        if($res['result'] != $result) {
            $this->errors[] = $msg;
        }
    }



    /*
     *
     */
    //set error, useful if validation is done outside of this script.
    function setError($msg) {
        $this->errors[] = $msg;
    }



    /*
     *
     */
    function isValid() {
        return !count($this->errors);
    }



    /*
     *
     */
    function getErrors($separator = null){
        if(!count($this->errors)){
            throw new lsException('validate->getErrors(): There are no errors', 'noerrors');
        }

        if($separator){
            $html = '';

            foreach($this->errors as $key => $value) {
                $html .= $value.$separator;
            }

            return $html;
        }

        return $this->errors;
    }



    /*
     * DEPRECATED, DO NOT USE ALL FOLLOWING METHIDS! Only still available to avoid missing method crashes
     */
    function get_errors($separator = null){
        return $this->getErrors($separator);
    }

    function is_valid() {
        return $this->isValid();
    }

    function is_not_empty($value, $msg) {
        return $this->isNotEmpty($value, $msg);
    }

    function is_alphanum($value, $msg) {
        return $this->isAlphanum($value, $msg);
    }

    function is_valid_name($value, $msg) {
        return $this->isValidName($value, $msg);
    }

    function is_valid_email($value, $msg) {
        return $this->isValidEmail($value, $msg);
    }

    function is_numeric($value, $msg) {
        return $this->isNumeric($value, $msg);
    }

    function is_valid_phonenumber($value, $msg) {
        return $this->isValidPhonenumber($value, $msg);
    }

    function is_equal($value, $value2, $msg) {
        return $this->isEqual($value, $value2, $msg);
    }

    function is_between($value, $min, $max, $msg) {
        return $this->isBetween($value, $min, $max, $msg);
    }

    function is_enabled($value, $msg) {
        return $this->isEnabled($value, $msg);
    }

    function has_min_chars($value, $limit, $msg) {
        return $this->hasMinChars($value, $limit, $msg);
    }

    function has_max_chars($value, $limit, $msg) {
        return $this->hasMaxChars($value, $limit, $msg);
    }

    function is_valid_url($value, $msg) {
        return $this->isValidUrl($value, $msg);
    }

    function is_valid_facebook_userpage($value, $msg) {
        return $this->isValidFacebookUserpage($value, $msg);
    }

    function is_valid_twitter_userpage($value, $msg) {
        return $this->isValidTwitterUserpage($value, $msg);
    }

    function is_valid_googleplus_userpage($value, $msg) {
        return $this->isValidGoogleplusUserpage($value, $msg);
    }

    function is_valid_youtube_userpage($value, $msg) {
        return $this->isValidYoutubeUserpage($value, $msg);
    }

    function is_valid_linkedin_userpage($value, $msg) {
        return $this->isValidLinkedinUserpage($value, $msg);
    }

    function is_checked($value, $msg) {
        return $this->isChecked($value, $msg);
    }

    function is_valid_password($value, $msg) {
        return $this->isValidPassword($value, $msg);
    }

    function is_regex($value, $regex, $msg) {
        return $this->isRegex($value, $regex, $msg);
    }

    function is_in_range($value, $min, $max, $msg) {
        return $this->isInRange($value, $min, $max, $msg);
    }

    function is_date($value, $msg) {
        return $this->isDate($value, $msg);
    }

    function is_time($value, $msg) {
        return $this->isTime($value, $msg);
    }

    function sql_query($value, $msg) {
        return $this->sqlQuery($value, $msg);
    }

    function set_error($msg) {
        return $this->setError($msg);
    }
}

?>
