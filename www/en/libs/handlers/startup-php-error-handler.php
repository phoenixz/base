<?php
global $core;

if(empty($core->register['ready'])){
    throw new bException('Pre core-ready PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', $errno);
}

$session   = "\n\n\n<br><br>SESSION DATA<br><br>\n\n\n".htmlentities(print_r(isset_get($_SESSION), true));
$server    = "\n\n\n<br><br>SERVER DATA<br><br>\n\n\n".htmlentities(print_r(isset_get($_SERVER), true));
$trace     = "\n\nFUNCTION TRACE\n".htmlentities(print_r(debug_backtrace(), true));

notify(array('title'       => 'php-error',
             'description' => '<pre> PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"'.$server.$session.$trace.'</pre>',
             'class'       => 'exception'));

if(PLATFORM_HTTP){
    error_log('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"');
    log_file('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', 'php-errors');

}else{
    log_console('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', 'exception');
}

throw new bException('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', $errno);
?>
