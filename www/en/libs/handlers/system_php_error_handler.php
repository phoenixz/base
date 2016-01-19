<?php
$session = "\n\nSESSION DATA\n".print_r(isset_get($_SESSION), true);
$server  = "\n\nSERVER DATA\n".print_r(isset_get($_SERVER), true);

notify('error', '<pre> PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"'.$server.$session.'</pre>');

if(PLATFORM == 'http'){
    error_log('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"');
}

throw new bException('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', $errno);
?>
