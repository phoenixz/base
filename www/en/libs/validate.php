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


/*
*
*/
function verify_js($params){
    try{
        html_load_js('verify');

        array_params($params);
        array_default($params, 'rules'      , null);
        array_default($params, 'group_rules', null);
        array_default($params, 'submit'     , null);

        $script = '';

        if(debug()){
            $script .= "$.verify.debug = true;\n";
            }

        if($params['submit']){
            $script .= '$.verify.beforeSubmit = '.$params['submit'].";\n";
        }

        if($params['rules']){
            foreach($params['rules'] as $name => $rule){
                $script .= '$.verify.addRules({
                                '.$name.' : '.$rule.'
                            });';
            }
        }

        if($params['group_rules']){
            foreach($params['group_rules'] as $rule){
                $script .= '$.verify.addGroupRules({
                               '.$rule.'
                            });';
            }
        }

        return html_script($script, false);

    }catch(Exception $e){
        throw new bException('validate_js(): Failed', $e);
    }
}



class validate_jquery {
    var $validations = array();

    function validate($element, $rule, $value, $msg = null){
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
                throw new bException('validate_jquery->validate(): Unknown rule "'.str_log($rule).'" specified', 'unknown');
        }

        $this->validations[$element][] = array('rule'  => $rule,
                                               'value' => $value,
                                               'msg'   => addslashes($msg));
    }

    function output_validation($params, $script = ''){
        try{
            load_libs('array');
            html_load_js('base/jquery.validate');

            if(!is_string($params)){
                if(!is_array($params)){
                    throw new bException('validate_jquery->output_validation(): Invalid $params specified. Must be either string, or assoc array containing at least "id"');
                }

                if(empty($params['id'])){
                    throw new bException('validate_jquery->output_validation(): Invalid $params specified. Must be either string, or assoc array containing at least "id"');
                }

            }else{
                if(!$params){
                    throw new bException('validate_jquery->output_validation(): Empty $params specified');
                }

                $params = array('id' => $params);
            }

            $params['id'] = str_starts($params['id'], '#');

            $html = 'validator = $("'.$params['id'].'").validate({
                ignore: ".ignore",
                rules: {';
                $kom = '';

                foreach($this->validations as $element => $validations){
                    $html .= $kom.$element.': {';
                    $kom2  = '';

                    foreach($validations as $val){
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

                foreach($this->validations as $element => $validations){
                    $html .= $kom.$element.': {';
                    $kom2  = '';

                    foreach($validations as $val){
                        $html .= $kom2.$val['rule'].':"'.$val['msg']."\"\n";
                        $kom2  = ',';
                    }

                    $html .= '}';
                    $kom   = ',';
                }

                $html .= '}';

                if(!empty($params['submithandler'])){
                    if(!empty($params['quickhandler'])){
                        throw new bException('validateJquery->output_validation(): Both submithandler and quickhandler are specified, these handlers are mutually exclusive');
                    }

                    $html .= ",\n".'submitHandler : function(form){'.$params['submithandler'].'}';

                }elseif(!empty($params['quickhandler'])){
                    if(!is_array($params['quickhandler'])){
                        throw new bException('validateJquery->output_validation(): Invalid quickhandler specified, it should be an assoc array');
                    }

                    $handler = $params['quickhandler'];

// :DELETE: These checks are no longer necesary since now we have default values
                    //foreach(array('target', 'fail') as $key){
                    //    if(empty($handler[$key])){
                    //        throw new bException('validateJquery->output_validation(): No quickhandler key "'.$key.'" specified');
                    //    }
                    //}

                    array_default($handler, 'done'  , '');
                    array_default($handler, 'json'  , true);
                    array_default($handler, 'fail'  , '$.handleFail(e, cbe)');
                    array_default($handler, 'target', "$(".$params['id'].").prop('action')");

                    $html .= ",\nsubmitHandler : function(form){\$.post(\"".$handler['target']."\", $(form).serialize())
                            .done(function(data)   { $.handleDone(data, function(data){".$handler['done']."}, cbe) })
                            .fail(function(a, b, e){ ".$handler['fail']." });
                        }\n";
// :TODO:SVEN:20120709: Remove this crap. Only left here just in case its needed ..
                //e.stopPropagation();
                //return false;';
                }

                if(!empty($params['onsubmit'])){
                    $html .= ",\nonsubmit : ".$params['onsubmit']."\n";
                }

                if(!empty($params['invalidhandler'])){
                    $html .= ",\ninvalidHandler: function(error, validator){".$params['invalidhandler']."}\n";
                }

                if(!empty($params['errorplacement'])){
                    $html .= ",\nerrorPlacement : function(error, element){".$params['errorplacement']."}\n";
                }

                $html .= "});\n";

                if(!empty($params['quickhandler'])){
                    $html .= "var cbe = function(e){".$handler['fail']."};\n";
                }

            $html .= "\n";


            if($script){
                $html .= $script."\n";
            }

            if(!empty($addregex)){
                $html .= '$.validator.addMethod(
                            "regex",
                            function(value, element, regexp){
                                var re = new RegExp(regexp);
                                return this.optional(element) || re.test(value);
                            },
                            "Please check your input."
                        );';
            }

            return html_script('
                var validator;

                $(document).ready(function(e){
                    '.$html.'
                });
            ', false);

        }catch(Exception $e){
            throw new bException('validateJquery->output_validation(): Failed', $e);
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

                $source = str_trim_array($source);

                /*
                 * Make sure "id" is always available, since it will near always be used.
                 */
                array_default($source, 'id', 0);

                if($columns){
                    array_ensure($source, $columns, $default_value);
                }
            }

        }catch(Exception $e){
            throw new bException('validate_form->__construct(): Failed', $e);
        }
    }



    /*
     *
     */
    function isNotEmpty($value, $msg = null){
        $value = trim($value);

        if(!$value){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isValidName($value, $msg = null){
        if(!$value) return '';

        if(!is_string($value) and strlen($value) <= 64){
            $this->setError($msg);
            return false;

        }else{
            return cfm($value);
        }
    }



    /*
     * Only allow alpha numeric characters
     */
    function isAlphaNumeric($value, $msg = null){
        if(!$value) return '';

        if(!ctype_alnum($value)){
            $this->setError($msg);
            return false;

        }else{
            return cfm($value);
        }
    }



    /*
     * Only allow a valid (unverified!) email address
     */
    function isValidEmail($value, $msg = null){
        $value = cfm($value);
        if(!$value) return '';

        if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", $value)){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     * Only allow numeric values (integers, floats, strings with numbers)
     */
    function isNumeric($value, $msg = null){
        if(!is_numeric($value)){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     * Only allow integer numbers 1 and up
     */
    function isNatural($value, $msg = null){
        if(!is_natural($value)){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isValidPhonenumber($value, $msg = null){
        $value = strtolower(cfm($value));
        if(!$value) return '';

//      if(!preg_match('^(?:(?:\+?1\s*(?:[.-]\s*)?)?(?:\(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\s*\)|([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$', $value)){
//        if(!preg_match('/^(?:(?:\+|00)[1-9]{1,5}\s*)?(?:\(?[0-9]{1,4}\)?\s*)?[0-9]{2,4}(?:\s|-)?[0-9]{3,5}(?:x|ext[0-9]{1,5})?$/', $value)){
        if(!preg_match('/\(?([0-9]{3})\)?(?:[ .-]{1,5})?([0-9]{3})(?:[ .-]{1,5})?([0-9]{4})/', $value)){
            $this->setError($msg);
            return false;

        }else{
            return str_replace(array(' ', '.', '-', '(', ')'), '', $value);
        }
    }



    /*
     *
     */
    function isEqual($value, $value2, $msg = null){
        $value  = trim($value);
        $value2 = trim($value2);

        if($value != $value2){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isNotEqual($value, $value2, $msg = null){
        $value  = trim($value);
        $value2 = trim($value2);

        if($value == $value2){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isBetween($value, $min, $max, $msg = null){
        if($value < $min and $value > $max){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isEnabled($value, $msg = null){
        $value = cfm($value);

        if(!$value){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function hasNoChars($value, $chars, $msg = null){
        $value = trim($value);

        foreach(array_force($chars) as $char){
            if(strpos($value, $char)){
                $this->setError($msg);
            return false;
                break;

            }else{
                return $value;
            }
        }
    }



    /*
     *
     */
    function hasMinChars($value, $limit, $msg = null){
        $value = trim($value);

        if(strlen($value) < $limit){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function hasMaxChars($value, $limit, $msg = null){
        $value = trim($value);

        if(strlen($value) > $limit){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function hasChars($value, $limit, $msg = null){
        $value = trim($value);

        if(strlen($value) != $limit){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isValidUrl($value, $msg = null){
        if(!$value) return '';

        if(filter_var($value, FILTER_VALIDATE_URL) === FALSE){
            $this->setError($msg);
            return false;
        } else {
            return $value;
        }
    }



    /*
     *
     */
    function isValidFacebookUserpage($value, $msg = null){
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:https?:\/\/)(?:www\.)?facebook\.com\/(.+)$/', $value)){
            return $value;

        }else{
            $this->setError($msg);
            return false;
        }
    }



    /*
     *
     */
    function isValidTwitterUserpage($value, $msg = null){
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:https?:\/\/)(?:www\.)?twitter\.com\/(.+)$/', $value)){
            return $value;

        }else{
            $this->setError($msg);
            return false;
        }
    }



    /*
     *
     */
    function isValidGoogleplusUserpage($value, $msg = null){
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:(?:https?:\/\/)?plus\.google\.com\/)(\d{21,})(?:\/posts)?$/', $value, $matches)){
            return $matches[1];

        }else{
            $this->setError($msg);
            return false;
        }
    }



    /*
     *
     */
    function isValidYoutubeUserpage($value, $msg = null){
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:https?:\/\/)(?:www\.)?youtube\.com\/user\/(.+)$/', $value)){
            return $value;

        }else{
            $this->setError($msg);
            return false;
        }
    }



    /*
     *
     */
    function isValidLinkedinUserpage($value, $msg = null){
        $value  = cfm($value);

        if(!$value) return '';

        if(preg_match('/^(?:https?:\/\/)(?:www\.)?linkedin\.com\/(.+)$/', $value)){
            return $value;

        }else{
            $this->setError($msg);
            return false;
        }
    }



    /*
     *
     */
    function isChecked($value, $msg = null){
        if(!$value){
            $this->setError($msg);
            return false;
        }
    }



    /*
     *
     */
    function isValidPassword($value, $msg = null){
        if(strlen($value) >= 8){
            return $value;

        }else{
            $this->setError($msg);
            return false;
        }
    }



    /*
     *
     */
    function isRegex($value, $regex, $msg = null){
         try{
            if(preg_match($regex, $value)){
               return $value;

            }else{
               $this->setError($msg);
               return false;
            }

         }catch(Exception $e){
            throw new bException(tr('validate_form::isRegex(): failed (possibly invalid regex?)'), $e);
         }
    }



    /*
     *
     */
    function isInRange($value, $min, $max, $msg = null){
        if(!is_numeric($value) or ($value < $min) or ($value > $max)){
            $this->setError($msg);
            return false;

        }else{
            return $value;
        }
    }



    /*
     *
     */
    function isDate($value, $msg = null){
// :TODO: IMPLEMENT
        return $value;
    }



    /*
     *
     */
    function isTime($value, $msg = null){
        try{
            load_libs('date');

            $value = date_time_validate($value);

            return $value['time'];

        }catch(Exception $e){
            if($e->getCode() == 'invalid'){
                throw new bException('validate_form->is_time(): Specified time "'.str_log($value).'" is invalid', $e);
            }

            throw new bException('validate_form->is_time(): Failed', $e);
        }
    }



    /*
     * Ensure that the specified value is in the specified array values
     * Basically this is an enum check
     */
    function inArray($value, $array, $msg = null){
        if(!in_array($value, $array)){
            $this->setError($msg);
            return false;
        }

        return $value;
    }



    /*
     *
     */
    function sqlQuery($sql, $result, $msg = null){
        $res = sql_get($sql);

        if($res['result'] != $result){
            $this->setError($msg);
            return false;
        }
    }



    /*
     *
     */
    //set error, useful if validation is done outside of this script.
    function setError($msg){
        if(is_object($msg) and $msg instanceof bException){
            $msg = str_from($msg->getMessage(), '():');
        }

        if($msg){
            $this->errors[] = $msg;
            return false;
        }
    }



    /*
     *
     */
    function isValid($throw_exception = true){
        $valid = !count($this->errors);

        if(!$valid and $throw_exception){
            throw new bException($this->errors, 'validation');
        }

        return $valid;
    }



    /*
     *
     */
    function getErrors($separator = null){
        if(!count($this->errors)){
            throw new bException('validate->getErrors(): There are no errors', 'noerrors');
        }

        if($separator){
            if($separator === true){
                if(PLATFORM == 'http'){
                    $separator = '<br />';

                }else{
                    $separator = "\n";
                }
            }

            $retval = '';

            foreach($this->errors as $key => $value){
                $retval .= $value.$separator;
            }

            return $retval;
        }

        return $this->errors;
    }



    /*
     * OBSOLETE WRAPPER METHODS
     */
    function isAlphanum($value, $msg = null){
        return isAlphaNumeric($value, $msg);
    }

}
?>
