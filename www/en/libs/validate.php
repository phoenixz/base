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
 * Basic validation defines
 */
define('VALIDATE_NOT'                   ,          1); // Validate in reverse. isNotEmpty would fail it source is not empty. isAlpha would fail if source contains alpha, etc.
define('VALIDATE_ALLOW_EMPTY_NULL'      ,          2); // Empty values are allowed, and will be converted to null
define('VALIDATE_ALLOW_EMPTY_INTEGER'   ,          4); // Empty values are allowed, and will be converted to 0
define('VALIDATE_ALLOW_EMPTY_BOOLEAN'   ,          8); // Empty values are allowed, and will be converted to false
define('VALIDATE_ALLOW_EMPTY_STRING'    ,         16); // Empty values are allowed, and will be converted to ""
define('VALIDATE_IGNORE_HTML'           ,         32); // Beyond the normal validation, the source may contain HTML
define('VALIDATE_IGNORE_DOT'            ,         64); // Beyond the normal validation, the source may contain the . character
define('VALIDATE_IGNORE_COMMA'          ,        128); // Beyond the normal validation, the source may contain the , character
define('VALIDATE_IGNORE_DASH'           ,        256); // Beyond the normal validation, the source may contain the - character
define('VALIDATE_IGNORE_UNDERSCORE'     ,        512); // Beyond the normal validation, the source may contain the _ character
define('VALIDATE_IGNORE_SLASH'          ,       1024); // Beyond the normal validation, the source may contain the / or \ character
define('VALIDATE_IGNORE_CARET'          ,       2048); // Beyond the normal validation, the source may contain the ^ character
define('VALIDATE_IGNORE_COLON'          ,       4096); // Beyond the normal validation, the source may contain the : character
define('VALIDATE_IGNORE_SEMICOLON'      ,       8192); // Beyond the normal validation, the source may contain the ; character
define('VALIDATE_IGNORE_QUESTIONMARK'   ,      16384); // Beyond the normal validation, the source may contain the ? character
define('VALIDATE_IGNORE_EXCLAMATIONMARK',      32768); // Beyond the normal validation, the source may contain the ! character
define('VALIDATE_IGNORE_AT'             ,      65536); // Beyond the normal validation, the source may contain the @ character
define('VALIDATE_IGNORE_POUND'          ,     131072); // Beyond the normal validation, the source may contain the # character
define('VALIDATE_IGNORE_PERCENT'        ,     262144); // Beyond the normal validation, the source may contain the % character
define('VALIDATE_IGNORE_DOLLAR'         ,     524288); // Beyond the normal validation, the source may contain the $ character
define('VALIDATE_IGNORE_AMPERSANT'      ,    1048576); // Beyond the normal validation, the source may contain the & character
define('VALIDATE_IGNORE_ASTERISK'       ,    2097152); // Beyond the normal validation, the source may contain the * character
define('VALIDATE_IGNORE_PLUS'           ,    4194304); // Beyond the normal validation, the source may contain the + character
define('VALIDATE_IGNORE_EQUALSIGN'      ,    8388608); // Beyond the normal validation, the source may contain the = character
define('VALIDATE_IGNORE_PIPE'           ,   16777216); // Beyond the normal validation, the source may contain the | character
define('VALIDATE_IGNORE_TILDE'          ,   33554432); // Beyond the normal validation, the source may contain the ~ character
define('VALIDATE_IGNORE_SQUAREBRACKETS' ,   67108864); // Beyond the normal validation, the source may contain the [] characters
define('VALIDATE_IGNORE_CURLYBRACKETS'  ,  134217728); // Beyond the normal validation, the source may contain the {} characters
define('VALIDATE_IGNORE_PARENTHESES'    ,  268435456); // Beyond the normal validation, the source may contain the () characters
define('VALIDATE_IGNORE_SINGLEQUOTES'   ,  536870912); // Beyond the normal validation, the source may contain the ' character
define('VALIDATE_IGNORE_DOUBLEQUOTES'   , 1073741824); // Beyond the normal validation, the source may contain the " character
define('VALIDATE_IGNORE_SPACE'          , 2147483648); // Beyond the normal validation, the source may contain the " " character
define('VALIDATE_IGNORE_ALL'            , 4294967232); // Ignore all special characters, except HTML



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

    function validate($element, $rule, $value, $message){
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
                throw new bException(tr('validate_jquery->validate(): Unknown rule ":rule" specified', array(':rule' => $rule)), 'unknown');
        }

        $this->validations[$element][] = array('rule'  => $rule,
                                               'value' => $value,
                                               'msg'   => addslashes($message));
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

    private $allowEmpty;
    private $not;
    private $scalar;



    /*
     *
     */
    function __construct(&$source = null, $columns = null, $default_value = null){
        try{
            array_ensure($source, $columns, $default_value, true);

        }catch(Exception $e){
            throw new bException('validate_form::__construct(): Failed', $e);
        }
    }



    /*
     * Parse the flags given with the validation
     */
    private function parseFlags(&$value, $message, $flags, $allowEmpty = true){
        try{
            $this->allowEmpty = 'no';
            $this->not        = false;
            $this->scalar     = is_scalar($value);

            if(!$flags){
                $this->testValue = $value;

            }else{
                if($flags & VALIDATE_NOT){
                    $this->not = true;
                }

                if($flags & VALIDATE_ALLOW_EMPTY_NULL){
                    $this->allowEmpty = null;

                }elseif($flags & VALIDATE_ALLOW_EMPTY_INTEGER){
                    $this->allowEmpty = 0;

                }elseif($flags & VALIDATE_ALLOW_EMPTY_BOOLEAN){
                    $this->allowEmpty = false;

                }elseif($flags & VALIDATE_ALLOW_EMPTY_STRING){
                    $this->allowEmpty = '';
                }

                if($value){
                    /*
                     * Ignore special characters?
                     */
                    if($flags & VALIDATE_IGNORE_DOT){
                        /*
                         * . characters are allowed, remove them from the test value
                         */
                        $replace[] = '.';
                    }

                    if($flags & VALIDATE_IGNORE_COMMA){
                        /*
                         * , characters are allowed, remove them from the test value
                         */
                        $replace[] = ',';
                    }

                    if($flags & VALIDATE_IGNORE_DASH){
                        /*
                         * - characters are allowed, remove them from the test value
                         */
                        $replace[] = '-';
                    }

                    if($flags & VALIDATE_IGNORE_UNDERSCORE){
                        /*
                         * - characters are allowed, remove them from the test value
                         */
                        $replace[] = '_';
                    }

                    if($flags & VALIDATE_IGNORE_SLASH){
                        /*
                         * / or \ characters are allowed, remove them from the test value
                         */
                        $replace[] = '/';
                        $replace[] = '\\';
                    }

                    if($flags & VALIDATE_IGNORE_CARET){
                        /*
                         * \t characters are allowed, remove them from the test value
                         */
                        $replace[] = '^';
                    }

                    if($flags & VALIDATE_IGNORE_COLON){
                        /*
                         * : characters are allowed, remove them from the test value
                         */
                        $replace[] = ':';
                    }

                    if($flags & VALIDATE_IGNORE_SEMICOLON){
                        /*
                         * ; characters are allowed, remove them from the test value
                         */
                        $replace[] = ';';
                    }

                    if($flags & VALIDATE_IGNORE_QUESTIONMARK){
                        /*
                         * ? characters are allowed, remove them from the test value
                         */
                        $replace[] = '?';
                    }

                    if($flags & VALIDATE_IGNORE_EXCLAMATIONMARK){
                        /*
                         * ! characters are allowed, remove them from the test value
                         */
                        $replace[] = '!';
                    }

                    if($flags & VALIDATE_IGNORE_AT){
                        /*
                         * @ characters are allowed, remove them from the test value
                         */
                        $replace[] = '@';
                    }

                    if($flags & VALIDATE_IGNORE_POUND){
                        /*
                         * # characters are allowed, remove them from the test value
                         */
                        $replace[] = '#';
                    }

                    if($flags & VALIDATE_IGNORE_PERCENT){
                        /*
                         * % characters are allowed, remove them from the test value
                         */
                        $replace[] = '%';
                    }

                    if($flags & VALIDATE_IGNORE_DOLLAR){
                        /*
                         * $ characters are allowed, remove them from the test value
                         */
                        $replace[] = '$';
                    }

                    if($flags & VALIDATE_IGNORE_AMPERSANT){
                        /*
                         * & characters are allowed, remove them from the test value
                         */
                        $replace[] = '&';
                    }

                    if($flags & VALIDATE_IGNORE_ASTERISK){
                        /*
                         * * characters are allowed, remove them from the test value
                         */
                        $replace[] = '*';
                    }

                    if($flags & VALIDATE_IGNORE_PLUS){
                        /*
                         * + characters are allowed, remove them from the test value
                         */
                        $replace[] = '+';
                    }

                    if($flags & VALIDATE_IGNORE_EQUALSIGN){
                        /*
                         * = characters are allowed, remove them from the test value
                         */
                        $replace[] = '=';
                    }

                    if($flags & VALIDATE_IGNORE_PIPE){
                        /*
                         * | characters are allowed, remove them from the test value
                         */
                        $replace[] = '|';
                    }

                    if($flags & VALIDATE_IGNORE_TILDE){
                        /*
                         * ~ characters are allowed, remove them from the test value
                         */
                        $replace[] = '~';
                    }

                    if($flags & VALIDATE_IGNORE_SQUAREBRACKETS){
                        /*
                         * [] characters are allowed, remove them from the test value
                         */
                        $replace[] = '[';
                        $replace[] = ']';
                    }

                    if($flags & VALIDATE_IGNORE_CURLYBRACKETS){
                        /*
                         * {} characters are allowed, remove them from the test value
                         */
                        $replace[] = '{';
                        $replace[] = '}';
                    }

                    if($flags & VALIDATE_IGNORE_PARENTHESES){
                        /*
                         * () characters are allowed, remove them from the test value
                         */
                        $replace[] = '(';
                        $replace[] = ')';
                    }

                    if($flags & VALIDATE_IGNORE_SINGLEQUOTES){
                        /*
                         * ' characters are allowed, remove them from the test value
                         */
                        $replace[] = "'";
                    }

                    if($flags & VALIDATE_IGNORE_DOUBLEQUOTES){
                        /*
                         * " characters are allowed, remove them from the test value
                         */
                        $replace[] = '"';
                    }

                    if($flags & VALIDATE_IGNORE_SPACE){
                        /*
                         *   characters are allowed, remove them from the test value
                         */
                        $replace[] = ' ';
                    }
                }

                /*
                 * Ignore certain characters?
                 */
                if(empty($replace)){
                    $this->testValue = $value;

                }else{
                    if(!$this->scalar){
                        $this->setError($message);
                        $this->testValue = $value;

                    }else{
                        $this->testValue = str_replace($replace, array_fill(0, count($replace), ''), $value);
                    }
                }

                if($flags & VALIDATE_IGNORE_HTML){
                    /*
                     * Ignore HTML
                     */
                    $this->testValue = strip_tags($this->testValue);
                    $this->testValue = preg_replace('/&#?[a-z0-9]{2,8};/i', '', $this->testValue);
                }

            }

            if(!$allowEmpty and ($this->allowEmpty !== 'no')){
                /*
                 * The function executing this validation says its okay for a
                 * variable to be empty, even though this variable can never
                 * ever be empty!
                 */
                throw new bException(tr('validate_form::parseFlags(): Some VALIDATE_ALLOW_EMPTY type flag was specified by the function ":function()" for the validation method ":method", while this method does not support those flags', array(':function' => current_function(2), ':method' => current_function(1))), 'invalid');
            }

            return $this->allowEmpty($value, $message);

        }catch(Exception $e){
            throw new bException(tr('validate_form::parseFlags(): Failed'), $e);
        }
    }



    /*
     * Process value and determine if empty is allowed or not. If it is empty
     * and empty is allowed, then enfore the empty value to be the allowed
     * data type
     */
    private function allowEmpty(&$value, $message = null){
        try{
            if(!empty($value)){
                return true;
            }

            if($this->allowEmpty){
                /*
                 * This is contradictory, but being here it means that $value
                 * is empty, but $empty is not, so $value is NOT allowed to
                 * be empty
                 */
                return $this->setError($message);
            }

            /*
             * $value may be empty, ensure it has the right data type
             */
            $value = $this->allowEmpty;
            return false;

        }catch(Exception $e){
            throw new bException('validate_form::allowEmpty(): Failed', $e);
        }
    }



    /*
     *
     */
    function isScalar(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if($this->not xor !is_scalar($value)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isScalar(): Failed', $e);
        }
    }



    /*
     *
     */
    function isNotEmpty(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags, false)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor !$value){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isNotEmpty(): Failed', $e);
        }
    }



    /*
     * Validate if specified value is a valid table row status.
     * status can be NULL by default
     */
    function isStatus(&$value, $message = null, $flags = VALIDATE_ALLOW_EMPTY_NULL){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor (($value !== '_new') and !$this->isRegex($value, '/[a-z-]{1,16}/', $message))){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isStatus(): Failed', $e);
        }
    }



    /*
     * Only allow a valid (unverified!) email address
     */
    function isEmail(&$value, $message = null, $flags = null){
        try{
            return $this->isFilter($value, FILTER_VALIDATE_EMAIL, $message, $flags);

        }catch(Exception $e){
            throw new bException('validate_form::isEmail(): Failed', $e);
        }
    }



    /*
     *
     */
    function isUrl(&$value, $message = null, $flags = null){
        try{
            return $this->isFilter($value, FILTER_VALIDATE_URL, $message, $flags);

        }catch(Exception $e){
            throw new bException('validate_form::isUrl(): Failed', $e);
        }
    }



    /*
     * Apply specified filter
     *
     * See http://php.net/manual/en/filter.filters.validate.php
     *
     * Valid filters (with optional flags):
     * FILTER_VALIDATE_BOOLEAN
     *      FILTER_NULL_ON_FAILURE
     * FILTER_VALIDATE_EMAIL
     *      FILTER_FLAG_EMAIL_UNICODE
     * FILTER_VALIDATE_FLOAT
     *      FILTER_FLAG_ALLOW_THOUSAND
     * FILTER_VALIDATE_INT
     *      FILTER_FLAG_ALLOW_OCTAL, FILTER_FLAG_ALLOW_HEX
     * FILTER_VALIDATE_IP
     *      FILTER_FLAG_IPV4, FILTER_FLAG_IPV6, FILTER_FLAG_NO_PRIV_RANGE, FILTER_FLAG_NO_RES_RANGE
     * FILTER_VALIDATE_MAC
     * FILTER_VALIDATE_REGEXP
     * FILTER_VALIDATE_URL
     *      FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED, FILTER_FLAG_PATH_REQUIRED, FILTER_FLAG_QUERY_REQUIRED
     */
    function isFilter(&$value, $filter_flags, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if($this->not xor !filter_var($this->testValue, $filter_flags)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isFilter(): Failed', $e);
        }
    }



    /*
     * Only allow numeric values (integers, floats, strings with numbers)
     */
    function isNumeric(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!$value){
                $value = 0;
            }

            if($this->not xor !is_numeric($this->testValue)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isNumeric(): Failed', $e);
        }
    }



    /*
     * Only allow integer numbers 1 and up
     */
    function isNatural(&$value, $start, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if($this->not xor !is_natural($this->testValue, $start)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isNatural(): Failed', $e);
        }
    }



    /*
     * Only allow alpha characters
     */
    function isAlpha(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor !ctype_alpha($this->testValue)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isAlpha(): Failed', $e);
        }
    }



    /*
     * Only allow alpha numeric characters
     */
    function isAlphaNumeric(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor !ctype_alnum($this->testValue)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isAlphaNumeric(): Failed', $e);
        }
    }



    /*
     * Only allow alpha numeric and - characters
     */
    function isHexadecimal(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if(substr($this->testValue, 0, 2) == '0x'){
                $this->testValue = substr($this->testValue, 2);
            }

            if($this->not xor !preg_match('/[0-9a-f]+/i', $this->testValue)){
               return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isHexadecimal(): Failed', $e);
        }
    }



    /*
     *
     */
    function isPhonenumber(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            $phone = str_replace(array('(', ')', ' ', '-', '.', '/'), '', $this->testValue);
            $phone = str_replace('ext', 'x', $phone);
            $ext   = str_from($phone, 'x');
            $phone = str_until($phone, 'x');

            if($ext == $phone){
                /*
                 * str_from() found no 'x'
                 */
                $ext = '';
            }

            $this->testValue = $phone;

            if($this->not xor (!is_numeric($phone) or ( strlen($phone) > 13))){
                return $this->setError($message);
            }

            if($ext){
                $this->testValue = $phone.($ext ? 'x'.$ext : '');

                if($this->not xor (!is_numeric($ext) or ( strlen($ext) > 6))){
                    return $this->setError($message);
                }
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isPhonenumber(): Failed', $e);
        }
    }



    /*
     *
     */
    function isEqual(&$value, $value2, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags, false)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if(!is_scalar($value2)){
                return $this->setError($message);
            }

            $value  = trim($value);
            $value2 = trim($value2);

            if($this->not xor $value != $value2){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isEqual(): Failed', $e);
        }
    }



    ///*
    // *
    // */
    //function isNotEqual(&$value, $value2, $message = null, $flags = null){
    //    try{
    //        if(!$this->parseFlags($value, $message, $flags, false)){
    //            return true;
    //        }
    //
    //        if(!is_scalar($value)){
    //            return $this->setError($message);
    //        }
    //
    //        if(!is_scalar($value2)){
    //            return $this->setError($message);
    //        }
    //
    //        $value  = trim($value);
    //        $value2 = trim($value2);
    //
    //        if($this->not xor $value == $value2){
    //            return $this->setError($message);
    //        }
    //
    //        return true;
    //
    //    }catch(Exception $e){
    //        throw new bException('validate_form::isNotEqual(): Failed', $e);
    //    }
    //}



    /*
     *
     */
    function isBetween(&$value, $min, $max, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor (($this->testValue < $min) and ($this->testValue > $max))){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isBetween(): Failed', $e);
        }
    }



    /*
     *
     */
    function isEnabled(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags, false)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor !$value){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isEnabled(): Failed', $e);
        }
    }



    /*
     *
     */
    function hasChars(&$value, $chars, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            foreach(array_force($chars) as $char){
                if($this->not xor !strpos($this->testValue, $char)){
                    return $this->setError($message);
                }
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::hasChars(): Failed', $e);
        }
    }



    /*
     * Opposite of hasChars.
     *
     * Exists because it is used rather much and is easier to use than the
     * VALIDATE_NOT flag
     */
    function hasNoChars(&$value, $chars, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            foreach(array_force($chars) as $char){
                if($this->not xor strpos($this->testValue, $char)){
                    return $this->setError($message);
                }
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::hasNoChars(): Failed', $e);
        }
    }



    /*
     *
     */
    function hasMinChars(&$value, $limit, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor (strlen($value) < $limit)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::hasMinChars(): Failed', $e);
        }
    }



    /*
     *
     */
    function hasMaxChars(&$value, $limit, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor (strlen($value) > $limit)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::hasMaxChars(): Failed', $e);
        }
    }



    /*
     *
     */
    function isFacebookUserpage(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if(!$this->isUrl($value, $message, $empty)){
                return false;
            }

            if($this->not xor !preg_match('/^(?:https?:\/\/)(?:www\.)?facebook\.com\/(.+)$/', $this->testValue)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isFacebookUserpage(): Failed', $e);
        }
    }



    /*
     *
     */
    function isTwitterUserpage(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if(!$this->isUrl($value, $message, $empty)){
                return false;
            }

            if($this->not xor !preg_match('/^(?:https?:\/\/)(?:www\.)?twitter\.com\/(.+)$/', $this->testValue)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isTwitterUserpage(): Failed', $e);
        }
    }



    /*
     *
     */
    function isGoogleplusUserpage(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if(!$this->isUrl($value, $message, $empty)){
                return false;
            }

            if($this->not xor !preg_match('/^(?:(?:https?:\/\/)?plus\.google\.com\/)(\d{21,})(?:\/posts)?$/', $this->testValue, $matches)){
                return $this->setError($message);
            }

            return $matches[1];

        }catch(Exception $e){
            throw new bException('validate_form::isGoogleplusUserpage(): Failed', $e);
        }
    }



    /*
     *
     */
    function isYoutubeUserpage(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if(!$this->isUrl($value, $message, $empty)){
                return false;
            }

            if($this->not xor preg_match('/^(?:https?:\/\/)(?:www\.)?youtube\.com\/user\/(.+)$/', $this->testValue)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isYoutubeUserpage(): Failed', $e);
        }
    }



    /*
     *
     */
    function isLinkedinUserpage(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if(!$this->isUrl($value, $message, $empty)){
                return false;
            }

            if($this->not xor !preg_match('/^(?:https?:\/\/)(?:www\.)?linkedin\.com\/(.+)$/', $this->testValue)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isLinkedinUserpage(): Failed', $e);
        }
    }



    /*
     *
     */
    function isChecked(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags, false)){
                return true;
            }

            if($this->not xor !$value){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isChecked(): Failed', $e);
        }
    }



    /*
     *
     */
    function isRegex(&$value, $regex, $message = null, $flags = null){
         try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor !preg_match($regex, $this->testValue)){
               return $this->setError($message);
            }

            return true;

         }catch(Exception $e){
            throw new bException(tr('validate_form::isRegex(): failed (possibly invalid regex?)'), $e);
         }
    }



    /*
     *
     */
    function isDate(&$value, $message = null, $flags = null){
        try{
// :TODO: IMPLEMENT

        }catch(Exception $e){
            throw new bException('validate_form::isDate(): Failed', $e);
        }
    }



    /*
     *
     */
    function isDateTime(&$value, $message = null, $flags = null){
        try{
// :TODO: IMPLEMENT

        }catch(Exception $e){
            throw new bException('validate_form::isDateTime(): Failed', $e);
        }
    }



    /*
     *
     */
    function isTime(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            load_libs('time');

            try{
                time_validate($this->testValue);

            }catch(Exception $e){
                if($this->not){
                    return true;
                }

                return $this->setError($message);
            }

            if($this->not){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            if($e->getCode() == 'invalid'){
                throw new bException(tr('validate_form->isTime(): Specified time ":value" is invalid', array(':value' => $value)), $e);
            }

            throw new bException('validate_form::isTime(): Failed', $e);
        }
    }



    /*
     *
     */
    function isLatitude(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!$this->isNumeric($value, $message)){
                return false;
            }

            if($this->not xor (($this->testValue < -90) or ($this->testValue > 90))){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            if($e->getCode() == 'invalid'){
                throw new bException(tr('validate_form->isLatitude(): Specified latitude ":value" is invalid', array(':value' => $value)), $e);
            }

            throw new bException('validate_form::isLatitude(): Failed', $e);
        }
    }



    /*
     *
     */
    function isLongitude(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!$this->isNumeric($value, $message)){
                return false;
            }

            if($this->not xor (($this->testValue < -180) or ($this->testValue > 180))){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            if($e->getCode() == 'invalid'){
                throw new bException(tr('validate_form->isLongitude(): Specified longitude ":value" is invalid', array(':value' => $value)), $e);
            }

            throw new bException('validate_form::isLongitude(): Failed', $e);
        }
    }



    /*
     *
     */
    function isTimezone(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            load_libs('date');

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor !date_timezones_exists($this->testValue)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::isTimezone(): Failed', $e);
        }
    }



    /*
     *
     */
    function isPassword(&$value, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            load_libs('user');

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if(user_password_strength($value)){
                return true;
            }

            return $this->setError($message);

        }catch(Exception $e){
            throw new bException('validate_form::isTimezone(): Failed', $e);
        }
    }



    /*
     * Ensure that the specified value is in the specified array values
     * Basically this is an enum check
     */
    function inArray(&$value, $array, $message = null, $flags = null){
        try{
            if(!$this->parseFlags($value, $message, $flags)){
                return true;
            }

            if(!is_scalar($value)){
                return $this->setError($message);
            }

            if($this->not xor !in_array($this->testValue, $array)){
                return $this->setError($message);
            }

            return true;

        }catch(Exception $e){
            throw new bException('validate_form::inArray(): Failed', $e);
        }
    }



    /*
     *
     */
    function setError($message){
        try{
            if(!$message){
                return false;
            }

            if(is_object($message) and $message instanceof bException){
                $message = str_from($message->getMessage(), '():');
            }

            $this->errors[] = $message;
            return false;

        }catch(Exception $e){
            throw new bException('validate_form::setError(): Failed', $e);
        }
    }



    /*
     *
     */
    function isValid($throw_exception = true){
        try{
            if($this->errors and $throw_exception){
                throw new bException($this->errors, 'validation');
            }

            return ! (boolean) $this->errors;

        }catch(Exception $e){
            throw new bException('validate_form::isValid(): Failed', $e);
        }
    }



    /*
     *
     */
    function getErrors($separator = null){
        try{
            if(!count($this->errors)){
                return null;
            }

            if($separator){
                if($separator === true){
                    if(PLATFORM_HTTP){
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

        }catch(Exception $e){
            throw new bException('validate_form::getErrors(): Failed', $e);
        }
    }
}
?>
