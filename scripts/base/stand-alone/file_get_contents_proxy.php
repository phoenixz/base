<?php
/*
This file should be copied to any website that is on a server with multiple ips, this file should not load any external libraries.
*/
$_GET['url'] = urldecode(trim($_GET['url']));

if(isset($_GET['url']) and (strtolower(substr($_GET['url'], 0, 4)) == 'http')) {
    echo base64_encode(curl_get($_GET['url']));

} else {
    echo 'FAIL';
}



/*
 *
 */
function get_random_ip() {
    global $col;

    $ips = explode("\n",shell_exec('/sbin/ifconfig|grep "Mask:255.255.255.0"|sed "s/^.*addr://"|sed "s/ .*//"'));

    shuffle($ips);

    if(empty($ips)) {
        return '';
    }

    return $ips[0];
}



/*
 *
 */
function curl_get($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, get_random_user_agent());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_INTERFACE, get_random_ip());
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

    return curl_exec($ch);
//    curl_close($ch);
}



/*
 * return random user agent
 */
function get_random_user_agent() {
    $agents = array('"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; Alexa Toolbar)"',
                    'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1) Gecko/20021204');

    shuffle($agents);

    return $agents[0];
}

?>
