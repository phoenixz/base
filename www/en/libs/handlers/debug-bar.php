<?php
global $_CONFIG, $core;

try{
    load_libs('numbers');

    if(!debug()) return '';

    if($_CONFIG['debug']['bar'] === false){
        return '';

    }elseif($_CONFIG['debug']['bar'] === 'limited'){
        if(empty($_SESSION['user']['id']) or !has_rights("debug")){
            /*
             * Only show debug bar to authenticated users with "debug" right
             */
            return false;
        }

    }elseif($_CONFIG['debug']['bar'] === true){
        /*
         * Show debug bar!
         */

    }else{
        throw new bException(tr('debug_bar(): Unknown configuration option ":option" specified. Please specify true, false, or "limited"', array(':option' => $_CONFIG['debug']['bar'])), 'unknown');
    }

    /*
     * Add debug bar javascript directly to the footer, as this debug bar is
     * added AFTER html_generate_js() and so won't be processed anymore
     */
    $core->register['footer'] .= html_script('$("#debug-bar").click(function(e){ $("#debug-bar").find(".list").toggleClass("hidden"); });');

    /*
     * Build HTML
     */
    $html = '<div class="debug" id="debug-bar">
                '.($_CONFIG['cache']['method'] ? '(CACHE='.$_CONFIG['cache']['method'].') ' : '').count($core->register('debug_queries')).' / '.number_format(microtime(true) - STARTTIME, 6).'
                <div class="hidden list">
                    <table style="width:100%">
                        <thead>
                            <tr>
                                <th>'.tr('Time').'</th>
                                <th>'.tr('Function').'</th>
                                <th>'.tr('Query').'</th>
                            </tr>
                        </thead>
                        <tbody>';

    /*
     * Add query statistical data ordered by slowest queries first
     */
    usort($core->register['debug_queries'], 'debug_bar_sort');

    foreach($core->register['debug_queries'] as $query){
        $html .= '      <tr>
                            <td>'.number_format($query['time'], 6).'</td>
                            <td>'.$query['function'].'</td>
                            <td>'.$query['query'].'</td>
                        </tr>';
    }

    $html .= '          </tbody>
                    </table>
                    <table style="width:100%">
                        <thead>
                            <tr>
                                <th>'.tr('Peak memory usage').'</th>
                                <th>'.tr('Execution time').'</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>'.human_readable(memory_get_peak_usage()).'</td>
                            <td>'.tr(':time milliseconds', array(':time' => number_format((microtime(true) - STARTTIME) * 1000, 2))).'</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
             </div>';

    $html  = str_replace(':query_count'   , count($core->register('debug_queries'))      , $html);
    $html  = str_replace(':execution_time', number_format(microtime(true) - STARTTIME, 6), $html);

    return $html;

}catch(Exception $e){
    throw new bException(tr('debug_bar(): Failed'), $e);
}



/*
 * Order by time, showing the
 */
function debug_bar_sort($a, $b){
    try{
        if($a['time'] > $b['time']){
            return -1;

        }elseif($a['time'] < $b['time']){
            return 1;

        }else{
            /*
             * They're the same, so ordering doesn't matter
             */
            return 0;
        }

    }catch(Exception $e){
        throw new bException(tr('debug_bar_sort(): Failed'), $e);
    }
}
?>