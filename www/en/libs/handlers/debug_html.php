<?php
static $style;

try{
    if($key === null){
        $key = tr('Unknown');
    }

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
                        <thead><td>'.tr('Key').'</td><td>'.tr('Type').'</td><td>'.tr('Size').'</td><td>'.tr('Value').'</td></thead>'.debug_html_row($value, $key).'
                    </table>';

}catch(Exception $e){
    throw new bException('debug_html(): Failed', $e);
}
?>