<?php 
	
	require "twitteroauth-master/autoload.php"; // Twitter autoload

	use Abraham\TwitterOAuth\TwitterOAuth;

	session_start();

	define("CONSUMER_KEY", "YOUR_KEY");
	define("CONSUMER_SECRET", "YOUR_SECRET");

	/**
	 * If verifier set then get access token
	 */
	if(isset($_REQUEST['oauth_verifier'])) {
		
		/**
		 * Get Temporary credentials
		 */
		$request_token = [];
		$request_token['oauth_token'] = $_SESSION['twtr_oauth_token'];
		$request_token['oauth_token_secret'] = $_SESSION['twtr_oauth_token_secret'];
		
		/* If denied, bail. */
		if (isset($_REQUEST['denied'])) {
			exit('Permission was denied. Please start over.');
		}

		/* If the oauth_token is not what we expect, bail. */
		if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token']) {
			$_SESSION['twtr_oauth_token'] = '';
			$_SESSION['twtr_oauth_token_secret'] = '';
			exit;
		}
		
		/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
		$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $request_token['oauth_token'], $request_token['oauth_token_secret']);

		/* Request access token from twitter */
		$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));
		
		/** Initialize connection with new credentials */
		$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

		/** Finally post content */
		$result = $connection->post("statuses/update", array("status" => "hello world! this is user:".uniqid()));
		
		if(isset($result->errors) && count($result->errors) > 0) {
			exit("Error while posting.");
		}

		header("Location: http://twitter.com");

	} else {

		/**
		 * Init class with config params
		 */
		$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
		
		/** Get Temp credentials */
		$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => "http://127.0.0.1:9051/x/social/twitter"));

		/**
		 * Check last httpCode. If everything is ok = `200` set temp creds in session
		 */
		switch ($connection->getLastHttpCode()) {
			case 200:
				/** Write OAuth token and secret into session */
				$_SESSION["twtr_oauth_token"] = $request_token['oauth_token'];
				$_SESSION["twtr_oauth_token_secret"] = $request_token['oauth_token_secret'];

				/** Build Auth URL */
				$url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
				
				/** Redirect to OAuth URL where user will allow us to do stuff */
				header("Location: $url");
				break;
			default:
				/* Show notification if something went wrong. */
				echo 'Could not connect to Twitter. Refresh the page or try again later.';
		}
	}
?>