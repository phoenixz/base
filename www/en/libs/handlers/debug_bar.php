<?php
global $_CONFIG, $core;

try{
    if(!debug()) return '';
    if(!$_CONFIG['debug']['bar']) return '';

    $core->register['footer'] .= html_script('$("#debug-bar").click(function(e){ $("#debug-bar").find(".list").toggleClass("hidden"); });');

    $html = '<div class="debug" id="debug-bar">
                '.count($core->register('debug_queries')).' / '.number_format(microtime(true) - STARTTIME, 6).'
                <div class="hidden list">
                    <table>
                        <thead>
                            <tr>
                                <th>'.tr('Time').'</th>
                                <th>'.tr('Function').'</th>
                                <th>'.tr('Query').'</th>
                            </tr>
                        </thead>
                        <tbody>';

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