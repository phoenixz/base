<?php
/*
 * Debug library
 *
 * This library contains debug functions
 *
 * These functions do not have a prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@svenoostenbrink.com>
 */



/*
 * Short hand for show and then randomly die
 */
function showrandomdie($data = '', $return = false, $quiet = false, $trace_offset = 2){
    show($data, $return, $quiet, $trace_offset);

    if(mt_rand(0, 5) > 4){
        die();
    }
}



/*
 * Short hand for show and then die
 */
function showdie($data = '', $return = false, $quiet = false, $trace_offset = 2){
    show($data, $return, $quiet, $trace_offset);
    die();
}



/*
 * Show debug data in a readable format
 */
function show($data = '', $return = false, $quiet = false, $trace_offset = 1){
    $retval = '';

    if(ENVIRONMENT== 'production'){
        if(!debug()){
            return '';
        }

// :TODO:SVEN:20130430: This should NEVER happen, send notification!
    }

    if(PLATFORM == 'apache'){
        echo debug_html($data, 'Unknown', $trace_offset);

/*
        if(is_scalar($data) or is_null($data)){
            $retval .= 'DEBUG '.current_file($trace_offset).'@'.current_line($trace_offset).': '.htmlentities($data).'<br/>';

        }else{
            *
             * Sort if is array for easier reading
             *
            if(is_array($data)){
                ksort($data);
            }

            if(!$quiet){
                $retval .= '<div class="debug"><div class="debug title">DEBUG SHOW '.current_file($trace_offset).'@'.current_line($trace_offset).':<div/><pre>';
            }

            $retval .= print_r($data, true);

            if(!$quiet){
                $retval .= '</pre></div>';
            }
        }
*/
    }else{
        if(is_scalar($data)){
            $retval .= (!$quiet ? 'DEBUG SHOW ('.current_file($trace_offset).'@'.current_line($trace_offset).'): ' : '').$data."\n";

        }else{
            /*
             * Sort if is array for easier reading
             */
            if(is_array($data)){
                ksort($data);
            }

            if(!$quiet){
                $retval .= "DEBUG SHOW (".current_file($trace_offset).'@'.current_line($trace_offset).")\n";
            }

            $retval .= print_r($data, true);
            $retval .= "\n";
        }
    }

    if($return){
        return $retval;
    }

    echo $retval;
    return $data;
}



/*
 * Show nice HTML table with all debug data
 */
function debug_html($value, $key = 'Unknown', $trace_offset = 0){
    static $style;

    try{
        if(empty($style)){
            $style  = true;

            $retval = '<style type="text/css">
                        table.debug{
                            font-family: sans-serif;
                            width:99%;
                            background:#AAAAAA;
                            border-collapse:collapse;
                            border-spacing:2px;
                            margin: 5px auto 5px auto;
                        }

                        table.debug thead{
                            background: #00A0CF;
                        }

                        table.debug td{
                            border: 1px solid black;
                            padding: 2px;
                        }
                       </style>';
        }else{
            $retval = '';
        }

        return $retval.'<table class="debug">
                            <thead><td colspan="4">'.current_file(1 + $trace_offset).'@'.current_line(1 + $trace_offset).'</td></thead>
                            <thead><td>Name</td><td>Type</td><td>Size</td><td>Value</td></thead>'.debug_html_row($value, $key).'
                        </table>';

    }catch(Exception $e){
        throw new lsException('debug_html(): Failed', $e);
    }
}



/*
 * Show HTML <tr> for the specified debug data
 */
function debug_html_row($value, $key = 'Unknown', $type = null){
    try{
        if($type === null){
            $type = gettype($value);
        }

        switch($type){
            case 'string':
                if(is_numeric($value)){
                    $type = 'numeric';
                }
                //FALLTHROUGH

            case 'integer':
                //FALLTHROUGH

            case 'double':
                return '<tr>
                            <td>'.$key.'</td>
                            <td>'.$type.'</td>
                            <td>'.strlen((string) $value).'</td>
                            <td>'.htmlentities($value).'</td>
                        </tr>';

            case 'boolean':
                return '<tr>
                            <td>'.$key.'</td>
                            <td>'.$type.'</td>
                            <td>1</td>
                            <td>'.($value ? 'true' : 'false').'</td>
                        </tr>';

            case 'NULL':
                return '<tr>
                            <td>'.$key.'</td>
                            <td>'.$type.'</td>
                            <td>0</td>
                            <td>'.htmlentities($value).'</td>
                        </tr>';

            case 'resource':
                return '<tr><td>'.$key.'</td>
                            <td>'.$type.'</td>
                            <td>?</td>
                            <td>'.$value.'</td>
                        </tr>';

            case 'method':
                // FALLTHROUGH

            case 'property':
                return '<tr><td>'.$key.'</td>
                            <td>'.$type.'</td>
                            <td>'.strlen($value).'</td>
                            <td>'.$value.'</td>
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
                                    <thead><td>Name</td><td>Type</td><td>Size</td><td>Value</td></thead>'.$retval.'
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

                $retval .= '<pre>'.print_r($value, true).'</pre>';

                return '<tr>
                            <td>'.$key.'</td>
                            <td>'.$type.'</td>
                            <td>?</td>
                            <td style="padding:0">
                                <table class="debug">
                                    <thead><td>Name</td><td>Type</td><td>Size</td><td>Value</td></thead>'.$retval.'
                                </table>
                            </td>
                        </tr>';

            default:
                return '<tr>
                            <td>'.$key.'</td>
                            <td>Unknown</td>
                            <td>???</td>
                            <td>'.htmlentities($value).'</td>
                        </tr>';
        }

    }catch(Exception $e){
        throw new lsException('debug_html_row(): Failed', $e);
    }
}



/*
 * Return the file where this call was made
 */
function current_file($trace = 0){
    $backtrace = debug_backtrace();

    if(!isset($backtrace[$trace])){
        return 'no_current_file';
    }

    return $backtrace[$trace]['file'];
}



/*
 * Return the line number where this call was made
 */
function current_line($trace = 0){
    $backtrace = debug_backtrace();

    if(!isset($backtrace[$trace])){
        return -1;
    }

    return $backtrace[$trace]['line'];
}


/*
 * Return the function where this call was made
 */
function current_function($trace = 0){
    $backtrace = debug_backtrace();

    if(!isset($backtrace[$trace + 1])){
        return 'no_current_function';
    }

    return $backtrace[$trace + 1]['function'];
}



/*
 * Return a test value that can be used for quick form debugging
 */
function debug_value($format, $size = null){
    /*
     * Generate debug value
     */
    load_libs('synonyms');

    switch($format){
        case 'username':
            // FALLTHROUGH
        case 'word':
            return synonym_random(1);

        case 'name':
            return not_empty(str_force(synonym_random(not_empty($size, mt_rand(1, 4))), ' '), str_random(not_empty($size, 32), false, '0123456789abcdefghijklmnopqrstuvwxyz     '));

        case 'text':
            // FALLTHROUGH
        case 'words':
            return not_empty(str_force(synonym_random(not_empty($size, mt_rand(5, 15))), ' '), str_random(not_empty($size, 150), false, '0123456789abcdefghijklmnopqrstuvwxyz     '));

        case 'email':
            return str_replace(' ', '', not_empty(str_force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '._-')), str_random()).'@'.not_empty(str_force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '_-')), str_random()).'.com');

        case 'url':
            return str_replace(' ', '', 'http://'.not_empty(str_force(synonym_random(mt_rand(1, 2), true), str_random(mt_rand(0, 1), false, '._-')), str_random()).'.'.pick_random(1, 'com', 'co', 'mx', 'org', 'net', 'guru'));

        case 'random':
            return str_random(not_empty($size, 150), false, '0123456789abcdefghijklmnopqrstuvwxyz     ');

        case 'zip':
            // FALLTHROUGH
        case 'zipcode':
            return str_random(not_empty($size, 5), false, '0123456789');

        case 'number':
            return str_random(not_empty($size, 8), false, '0123456789');

        case 'address':
            return str_random().' '.str_random(not_empty($size, 8), false, '0123456789');

        case 'password':
            return 'aaaaaaaa';

        case 'checked':
            if($type){
                return ' checked ';
            }

            return '';

        default:
            return $format;
    }
}



/*
 *
 */
function debug_sql($query, $column = null, $execute = null, $return_only = false){
    try{
        if(is_array($column)){
            /*
             * Argument shift, no columns were specified.
             */
            $tmp     = $execute;
            $execute = $column;
            $column  = $tmp;
            unset($tmp);
        }

        if(is_array($execute)){
            foreach($execute as $key => $value){
                if(is_numeric($value)){
                    $query = str_replace($key, $value, $query);

                }elseif(is_null($value)){
                    $query = str_replace($key, ' NULL ', $query);

                }else{
                    $query = str_replace($key, '"'.(!is_scalar($value) ? ' [NOT SCALAR] ' : '').str_force($value).'"', $query);
                }
            }
        }

        if($return_only){
            return $query;
        }

        return show($query);

    }catch(Exception $e){
        throw new lsException('debug_sql(): Failed', $e);
    }
}



/*
 * Gives a filtered debug_backtrace()
 */
function debug_trace($filters = 'args'){
    try{
        $filters = array_force($filters);
        $trace   = array();

        foreach(debug_backtrace() as $part){
            foreach($filters as $filter){
                unset($part[$filter]);
            }

            $trace[] = $part;
        }

        return $trace;

    }catch(Exception $e){
        throw new lsException('debug_trace(): Failed', $e);
    }
}
?>
