<?php
function apiRequest($url, $post = FALSE, $headers = []) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	if($post) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	}

	$headers = [
		'Accept: application/vnd.github.v3+json, application/json',
		'User-Agent: https://myexample-oauth.com'
	];

	if(isset($_SESSION['access_token'])) {
		$headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];
	}

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);	
	return json_decode(curl_exec($ch), true);
}


$githubClientID = '<fillme>';
$githubClientSecret = '<fillme>';
$apiURLBase = 'https://api.github.com/';
$authorizeURL = 'https://github.com/login/oauth/authorize';
$tokenURL = 'https://github.com/login/oauth/access_token';
$reposUrl = 'https://api.github.com/user/repos';
$baseURL = 'http://localhost:8000';

session_start();


//
// 1. Login user. Get CODE
//
if(isset($_GET['action']) && $_GET['action'] == 'login') {
	unset($_SESSION['access_token']);
	$_SESSION['state'] = bin2hex(random_bytes(16));

	$urlQuery = [
		'response_type' => 'code',
		'client_id' => $githubClientID,
		'redirect_uri' => $baseURL,
		'scope' => 'user public_repo',
		'state' => $_SESSION['state']
	];

	// https://github.com/login/oauth/authorize
	//		?response_type=code
	//		&client_id=.....
	//		&redirect_uri=http://localhost:8000
	//		&scope=user public_repo
	//		&state=.....	
	$url = $authorizeURL . '?' . http_build_query($urlQuery); 
	header('Location: ' . $url);
	die();
}

//
// 2. OBTAIN ACCESS TOKEN (if login OK)
//
if (isset($_GET['code'])) {
	//
	// $_REQUEST = [
	//		"code" => 9e4bc31afd6b7b470de7, 
	//		"state" => c4c0408400c79b06e67a8a866d4f84a1 
	// ]
	//
	$token = apiRequest($tokenURL, [
		'grant_type' => 'authorization_code',
		'client_id' => $githubClientID,
		'client_secret' => $githubClientSecret,
		'redirect_uri' => $baseURL,
		'code' => $_GET['code']
	]);

	// Array ( 
	// 	[access_token] => aa9fbad78f8472b2c2b7babf31e51848d6215dbb 
	// 	[token_type] => bearer 
	// 	[scope] => public_repo,user 
	// ) 
	$_SESSION['token_response'] = $token;
	$_SESSION['access_token'] = $token['access_token'];
	header('Location: ' . $baseURL);
	die();
}

//
// 3. Access resource
//
if(isset($_GET['action']) && $_GET['action'] == 'repos') {	
	// redirect -> this redirect show the github page to login.
	// Array ( [0] => Array ( [id] => 80220016 [node_id] => MDEwOlJlcG9zaXRvcnk4MDIyMDAxNg== [name] => ....
	$repos = apiRequest($reposUrl);

	die();
}

//
// UI
//
if (!isset($_GET['action'])) {
	if(!empty($_SESSION['access_token'])) {
		echo '<h3>LOGGED IN</h3>';
		echo '<p><a href="?action=repos">View repos</a></p>';
		echo '<p><a href="?action=logout">Logout</a></p>';
	} else {
		echo '<h3>Not logged in</h3>';
		echo '<p><a href="?action=login">Log in</a></p>';
	}


	if (isset($_SESSION['token_response'])) {
		echo '<pre>';
		print_r($_SESSION['token_response']);
		echo '</pre>';
	}
}

