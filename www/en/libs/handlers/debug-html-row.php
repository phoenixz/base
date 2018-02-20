<?php
try{
    if($type === null){
        $type = gettype($value);
    }

    if($key === null){
        $key = tr('Unknown');
    }

    switch($type){
        case 'string':
            if(is_numeric($value)){
                $type = tr('numeric');

                if(is_integer($value)){
                    $type .= tr(' (integer)');

                }elseif(is_float($value)){
                    $type .= tr(' (float)');

                }elseif(is_string($value)){
                    $type .= tr(' (string)');

                }else{
                    $type .= tr(' (unknown)');
                }

            }else{
                $type = tr('string');
            }

            //FALLTHROUGH

        case 'integer':
            //FALLTHROUGH

        case 'double':
            return '<tr>
                        <td>'.$key.'</td>
                        <td>'.$type.'</td>
                        <td>'.strlen((string) $value).'</td>
                        <td class="value">'.htmlentities($value).'</td>
                    </tr>';

        case 'boolean':
            return '<tr>
                        <td>'.$key.'</td>
                        <td>'.$type.'</td>
                        <td>1</td>
                        <td class="value">'.($value ? tr('true') : tr('false')).'</td>
                    </tr>';

        case 'NULL':
            return '<tr>
                        <td>'.$key.'</td>
                        <td>'.$type.'</td>
                        <td>0</td>
                        <td class="value">'.htmlentities($value).'</td>
                    </tr>';

        case 'resource':
            return '<tr><td>'.$key.'</td>
                        <td>'.$type.'</td>
                        <td>?</td>
                        <td class="value">'.$value.'</td>
                    </tr>';

        case 'method':
            // FALLTHROUGH

        case 'property':
            return '<tr><td>'.$key.'</td>
                        <td>'.$type.'</td>
                        <td>'.strlen($value).'</td>
                        <td class="value">'.$value.'</td>
                    </tr>';

        case 'array':
            $retval = '';

            ksort($value);

            foreach($value as $subkey => $subvalue){
                $retval .= debug_html_row($subvalue, $subkey);
            }

            return '<tr>
                        <td>'.$key.'</td>
                        <td>'.$type.'</td>
                        <td>'.count($value).'</td>
                        <td style="padding:0">
                            <table class="debug">
                                <thead><td>'.tr('Key').'</td><td>'.tr('Type').'</td><td>'.tr('Size').'</td><td>'.tr('Value').'</td></thead>'.$retval.'
                            </table>
                        </td>
                    </tr>';

        case 'object':
            $retval = '';

// :DELETE: This is not working, only print_r seems to be able to get all required object data..
            ///*
            // * Display all object properties
            // */
            //foreach(get_object_vars($value) as $var){
            //    $retval .= debug_html($value->$var, $var, 'property');
            //}
            //
            ///*
            // * Display all object methods
            // */
            //foreach(get_class_methods($value) as $method){
            //    $retval .= debug_html('', $method, 'method');
            //}
            //

            $retval .= '<pre>'.print_r(variable_zts_safe($value), true).'</pre>';

            return '<tr>
                        <td>'.$key.'</td>
                        <td>'.$type.'</td>
                        <td>?</td>
                        <td>'.$retval.'</td>
                    </tr>';

        default:
            return '<tr>
                        <td>'.$key.'</td>
                        <td>'.tr('Unknown').'</td>
                        <td>???</td>
                        <td class="value">'.htmlentities($value).'</td>
                    </tr>';
    }

}catch(Exception $e){
    throw new bException('debug_html_row(): Failed', $e);
}
?>