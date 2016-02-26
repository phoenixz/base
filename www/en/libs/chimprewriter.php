<?php
/*
 * Empty library
 *
 * This is an empty template library file
 *
 * Written and Copyright by Sven Oostenbrink
 */


/*
 * Empty function
 */
function empty_function(){
    try{

    }catch(Exception $e){
        throw new bException('empty(): Failed', $e);
    }
}
?>


<?php
//SpinChimp API Sample PHP Code

DEFINE('APIURL','http://api.chimprewriter.com/');
DEFINE('ApplicationID','YOUR_APP_NAME_HERE');

function GlobalSpin($email,$apiKey, $text, $quality, $protectedTerms, $posmatch, $rewrite) {

	//Check Inputs
	if (!isset($email) || trim($email) === '') return 'No email specified';
	if (!isset($apiKey) || trim($apiKey) === '') return 'No APIKey specified';
	if (!isset($text) || trim($text) === '') return "";

	//Add paramaters
	$paramaters = array();
	$paramaters['email'] = $email;
	$paramaters['apiKey'] = $apiKey;
	$paramaters['aid'] = ApplicationID;
	if (isset($quality) && trim($quality) !== '')
		$paramaters['quality'] = $quality;
	if (isset($protectedTerms) && trim($protectedTerms) !== '')
		$paramaters['protectedterms'] = $protectedTerms;
	if (isset($posmatch) && trim($posmatch) !== '')
		$paramaters['posmatch'] = $posmatch;
	if (isset($rewrite) && trim($rewrite) !== '')
		$paramaters['rewrite'] = $rewrite;

	$qs = buildQueryString($paramaters);
	$result = makeApiRequest(APIURL,'GlobalSpin',$qs,$text);
	return $result;
}

function GenerateSpin($email,$apiKey, $text, $dontIncludeOriginal, $reorderParagraphs) {

	//Check Inputs
	if (!isset($email) || trim($email) === '') return 'No email specified';
	if (!isset($apiKey) || trim($apiKey) === '') return 'No APIKey specified';
	if (!isset($text) || trim($text) === '') return "";

	//Add paramaters
	$paramaters = array();
	$paramaters['email'] = $email;
	$paramaters['apiKey'] = $apiKey;
	$paramaters['aid'] = ApplicationID;
	if (isset($dontIncludeOriginal) && trim($dontIncludeOriginal) !== '')
		$paramaters['dontincludeoriginal'] = $dontIncludeOriginal;
	if (isset($reorderParagraphs) && trim($reorderParagraphs) !== '')
		$paramaters['reorderparagraphs'] = $reorderParagraphs;

	$qs = buildQueryString($paramaters);
	$result = makeApiRequest(APIURL,'GenerateSpin',$qs,$text);
	return $result;
}

function CalcWordDensity($email,$apiKey, $text, $minLength) {

	//Check Inputs
	if (!isset($email) || trim($email) === '') return 'No email specified';
	if (!isset($apiKey) || trim($apiKey) === '') return 'No APIKey specified';
	if (!isset($text) || trim($text) === '') return "";

	//Add paramaters
	$paramaters = array();
	$paramaters['email'] = $email;
	$paramaters['apiKey'] = $apiKey;
	$paramaters['aid'] = ApplicationID;
	if (isset($minLength) && trim($minLength) !== '')
		$paramaters['minlength'] = $minLength;

	$qs = buildQueryString($paramaters);
	$result = makeApiRequest(APIURL,'CalcWordDensity',$qs,$text);
	return $result;
}

function buildQueryString($paramaters)
{
	//Construct querystring for uri
	$data = '';
	$firstparam = true;
	foreach ($paramaters as $key => $value) {
		if ($firstparam) $firstparam = false;
		else $data .= '&';
		$data .= $key . '=' . urlencode($value);
	}
	return $data;
}

function makeApiRequest($url, $command, $querystring, $text) {
	$req = curl_init();
	curl_setopt($req, CURLOPT_URL, APIURL . $command . '?' . $querystring);
	curl_setopt($req,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($req,CURLOPT_FOLLOWLOCATION,1);
	curl_setopt($req, CURLOPT_POST, true);
	curl_setopt($req, CURLOPT_POSTFIELDS, $text);
	$result = trim(curl_exec($req));
	curl_close($req);
	return $result;
}

$result = GlobalSpin('youremail@yourdomain.com', 'TheAPIKeyYouFoundOnTheUserManagementPage', 'The body of your article!', null, null, null, null);
echo $result;

?>
