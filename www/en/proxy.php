<?php
/*
 * This file should be copied to any website that is on a server with multiple ips, this file should not load any external libraries.
 */
require_once(dirname(__FILE__).'/libs/startup.php');

if(empty($_GET['url'])){
	die('FAIL');
}

$_GET['url'] = urldecode(trim($_GET['url']));

if(isset($_GET['url']) and (strtolower(substr($_GET['url'], 0, 4)) == 'http')) {
	load_libs('curl');
	$data = curl_get(array('url'        => $_GET['url'],
						   'getheaders' => false));

	if($data['status']['http_code'] == 200){
		die(base64_encode($data['data']));
	}

	die('FAIL');

}else{
	die('FAIL');
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



///*
// *
// */
//function curl_get($url) {
//    $ch = curl_init();
//    curl_setopt($ch, CURLOPT_URL, $url);
//    curl_setopt($ch, CURLOPT_USERAGENT, get_random_user_agent());
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//    curl_setopt($ch, CURLOPT_INTERFACE, get_random_ip());
//    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
//
//    return curl_exec($ch);
////    curl_close($ch);
//}



/*
 * return random user agent
 */
function get_random_user_agent() {
    $agents = array('Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1) Gecko/20021204',
					'Mozilla/5.0 (Windows NT 5.1; rv:10.0.2) Gecko/20100101 Firefox/10.0.2',
					'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
					'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36',
					'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2224.3 Safari/537.36',
					'Mozilla/5.0 (Windows NT 6.4; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2225.0 Safari/537.36',
					'Mozilla/5.0 (X11; U; Linux x86_64; en; rv:1.9.0.8) Gecko/20080528 Fedora/2.24.3-4.fc10 Epiphany/2.22 Firefox/3.0',
					'Mozilla/5.0 (X11; U; Linux i686; en; rv:1.9.0.14) Gecko/20080528 Epiphany/2.22 (Debian/2.26.3-2)',
					'Mozilla/5.0 (X11; U; Linux x86_64; en; rv:1.8.1.13) Gecko/20080322 Epiphany/2.22 Firefox/2.0.0.4',
					'Mozilla/5.0 (X11; U; Linux i686; en; rv:1.8.1.19) Gecko/20081216 Epiphany/2.20 Firefox/2.0.0.19',
					'Mozilla/5.0 (X11; U; Linux x86_64; en; rv:1.8.1.3) Gecko/20061201 Epiphany/2.18 Firefox/2.0.0.3 (Ubuntu-feisty)',
					'Mozilla/5.0 (X11; U; Linux i686; en; rv:1.8.1.3) Gecko/20070322 Epiphany/2.18',
					'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
					'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0',
					'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0',
					'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0',
					'Mozilla/5.0 (X11; Linux i586; rv:31.0) Gecko/20100101 Firefox/31.0',
					'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20130401 Firefox/31.0',
					'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:29.0) Gecko/20120101 Firefox/29.0',
					'Mozilla/5.0 (X11; OpenBSD amd64; rv:28.0) Gecko/20100101 Firefox/28.0',
					'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
					'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
					'Mozilla/5.0 (compatible, MSIE 11, Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
					'Mozilla/5.0 (compatible; MSIE 10.6; Windows NT 6.1; Trident/5.0; InfoPath.2; SLCC1; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 2.0.50727) 3gpp-gba UNTRUSTED/1.0',
					'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 7.0; InfoPath.3; .NET CLR 3.1.40767; Trident/6.0; en-IN)',
					'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)',
					'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)',
					'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))',
					'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 7.1; Trident/5.0)',
					'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; Media Center PC 6.0; InfoPath.3; MS-RTC LM 8; Zune 4.7)',
					'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)',
					'Center PC 6.0; InfoPath.2; .NET CLR 1.1.4322; .NET4.0C; Tablet PC 2.0)',
					'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/11.0.696.57)',
					'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)');

    shuffle($agents);

    return $agents[0];
}

?>
