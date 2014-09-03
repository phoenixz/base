<?php
global $_CONFIG;

try{
    if($count > 0) {
        sql_query('INSERT INTO `statistics` (`code`          , `count`        , `statdate`)
                   VALUES                   ("'.cfm($code).'", '.cfi($count).','.date('d', time()).') ON DUPLICATE KEY UPDATE `count` = `count` + '.cfi($count).';');
    }

    error_log($_CONFIG['domain'].'-'.str_log($code).($details ? ' "'.str_log($details).'"' : ''));

}catch(Exception $e){
    throw new bException('add_stat(): Failed', $e);
}
?>
