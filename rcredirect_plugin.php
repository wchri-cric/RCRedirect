<?php
/*
 * PLUGIN NAME:	Bookmark Redirect

 * DESCRIPTION: Handles data posted from an "advanced" bookmark to redirect to the record
 * 				home page in a specified project. If the target record already exists then
 *				REDCap displays the record home page. If the record does not exist then
 *				REDCap displays the first data entry form with the ID for the new record
 *				pre-filled.
 *
 * VERSION:		0.1
 * DATE:		2017-09-18
 * AUTHOR:		Rick Watts (rick.watts@ualberta.ca)
 *
 * Usage:	 	Drop this file in your plugins folder.
 *				Configure an advanced bookmark to use the following link
 *					https://my.redcap.url/plugins/rcredirect_plugin.php?target_pid=123
 *
 * 				Where my.redcap.url/plugins is the location of your REDCap plugins folder
 *   			and target_pid is the ID of the project you wish to send the user to.
 *
 * 				Tick "Append record info to URL" so the the record data is available to the
 *				script.
 */

function redcap_get_bookmark($url,$authkey)
{
	#Retrieves bookmark data from REDCap

	$request = array(
		'authkey'   => $authkey,
		'format'  	=> 'json'
	);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request, '', '&'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); /* for production systems you may want to set this true */
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

	$output = curl_exec($ch);

	if (!$output || curl_errno($ch) !== CURLE_OK)
	{
		$errstr = 'cURL error (' . curl_errno($ch) . '): ' . curl_error($ch);
		exit("RCRedirect: $errstr $url");
	}
	curl_close($ch);
	$out_array=json_decode($output,true);
	return($out_array);
}

if(!isset($_POST["authkey"]))
	exit("RCRedirect: REDCap bookmark token has not been received.");

if(!isset($_GET["target_pid"]))
	exit("RCRedirect: REDCap target_pid has not been received.");

/* Set up the REDCap context */

require_once "../redcap_connect.php";

/* Retrieve the bookmark data from REDCap */

$api_path=APP_PATH_WEBROOT_FULL . "api/";
$redcap_data=redcap_get_bookmark($api_path,$_POST["authkey"]);

if (!$redcap_data) exit("RCRedirect: Unable to retrieve bookmark data from REDCap at $api_path");

/* Build the redirect URL */

$redirect_url=APP_PATH_WEBROOT_FULL.substr(APP_PATH_WEBROOT,1)."DataEntry/record_home.php?pid=".$_GET["target_pid"];

/* If the bookmark data includes the record_id then add that to the URL */

if ($_GET["record"])
	$redirect_url = $redirect_url."&id=".$_GET["record"];

/* and do the redirect */

header("Location: ".$redirect_url);
exit();
?>
