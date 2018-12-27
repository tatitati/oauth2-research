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
$authorizeURL = $apiURLBase . '/login/oauth/authorize';
$baseURL = 'http://localhost:8000';

session_start();


//
// 1. Login user. Get CODE
//
if(isset($_GET['action']) && $_GET['action'] == 'login') {
	unset($_SESSION['access_token']);
	$_SESSION['state'] = bin2hex(random_bytes(16));

	$params = [
		'response_type' => 'code',
		'client_id' => $githubClientID,
		'redirect_uri' => $baseURL,
		'scope' => 'user public_repo',
		'state' => $_SESSION['state']
	];

	// redirect -> this redirect show the github page to login.
	header('Location: ' . $authorizeURL . '?' . http_build_query($params));
	die();
}

//
// 2. OBTAIN ACCESS TOKEN (if login OK)
//
if (isset($_GET['code'])) {
	$tokenURL = $apiURLBase . '/login/oauth/access_token';
	$token = apiRequest($tokenURL, [
		'grant_type' => 'authorization_code',
		'client_id' => $githubClientID,
		'client_secret' => $githubClientSecret,
		'redirect_uri' => $baseURL,
		'code' => $_GET['code']
	]);

	$_SESSION['token_reponse'] = $token;
	$_SESSION['access_token'] = $token['access_token'];
	header('Location: ' . $baseURL);
	die();
}

//
// 3. Access resource
//
if(isset($_GET['action']) && $_GET['action'] == 'repos') {
	unset($_SESSION['access_token']);
	$_SESSION['state'] = bin2hex(random_bytes(16));

	$params = [
		'response_type' => 'code',
		'client_id' => $githubClientID,
		'redirect_uri' => $baseURL,
		'scope' => 'user public_repo',
		'state' => $_SESSION['state']
	];

	// redirect -> this redirect show the github page to login.
	header('Location: ' . $authorizeURL . '?' . http_build_query($params));
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


	if (isset($_SESSION['token_reponse'])) {
		echo '<pre>';
		print_r($_SESSION['token_reponse']);
		echo '</pre>';
	}
}

