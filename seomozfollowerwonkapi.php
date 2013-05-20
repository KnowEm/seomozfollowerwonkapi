<?php
/* 
* socialAuthority v1.0 php class
* May 20, 2013
*
* A php class to check for a SEOmoz/FollowerWonk Social Authority score.
* The class takes a twitter screen name, Access ID and Secret Key and
* a string representing your Application's name and returns a
* social authority score from follower wonk.
*
*
* See: https://github.com/seomoz/Social-Authority-SDK
*
* Go here: https://followerwonk.com/social-authority
* to get an Access ID and Secret Key, which you'll need
*
* For this demo enter a Twitter screen name from the query string, like this:
* seomozfollowerwonkapi.php?screen_name=barrywise
*
*/



if(!empty($_REQUEST['screen_name'])) {

	// A TWITTER SCREEN NAME
	$screen_name = $_REQUEST['screen_name'];
	
	// THE ACCESS ID AND SECRET KEY FROM https://followerwonk.com/social-authority
	$accessID = "<PUT YOUR ACCCESS ID HERE>";
	$secretKey = "<PUT YOUR SECRET KEY HERE>";
	
	// CHANGE THIS TO SOMETHING SPECIFIC TO IDENTIFY YOUR USER AGENT
	$yourAppName = "Followerwonk API call from socialAuthority v1.0 php class";
	
	
		$authObj = new socialAuthority($screen_name, $accessID, $secretKey, $yourAppName);
		$wonkResponse = $authObj->getResponse();
		
		$social_authority = number_format((float)$wonkResponse['response']['social_authority'], 2, '.', '');
		$user_id = $wonkResponse['response']['user_id'];
		$screen_name = $wonkResponse['response']['screen_name'];
		
		if ($wonkResponse['result'] != "OK") {

			// AN ERROR OCCURRED
			echo "An error has occurred: " . $wonkResponse['response']['message'];

		} else {

			// IF ALL GOES WELL $social_authority WILL HAVE YOUR SCORE
			echo '<a href="https://twitter.com/' . $screen_name . '">' . $screen_name  .'</a> has a social authority score of ' . $social_authority;

		}
		
} else {
	echo "Specify a twitter screen name: ?screen_name=xxx";
}



class socialAuthority { 

	protected $screen_name;
	protected $accessID;
	protected $secretKey;
	protected $yourAppName;
	
	function __construct($screen_name, $accessID, $secretKey) {
		$this->screen_name = $screen_name;
		$this->accessID = $accessID;
		$this->secretKey = $secretKey;
		$this->yourAppName = $yourAppName;

		$this->timeStamp =  gmmktime() + 300;
		$this->wonkURL = "https://api.followerwonk.com/social-authority";
	}
	
	function getResponse() {
		
		$stringToSign = $this->accessID . "\n" . $this->timeStamp;

		// THIS IS BORROWED FROM THE SEOMOZ API CODE
		// GET THE "RAW" OR BINARY OUTPUT OF THE HMAC HASH.
		$binarySignature = hash_hmac('sha1', $stringToSign, $this->secretKey, true);
		// WE NEED TO BASE64-ENCODE IT AND THEN URL-ENCODE THAT.
		$urlSafeSignature = urlencode(base64_encode($binarySignature));

		$urlToFetch = $this->wonkURL . "?screen_name=" . $this->screen_name . ";AccessID=" . $this->accessID . ";Timestamp=" . $this->timeStamp . ";Signature=" . $urlSafeSignature;
		
			try {
				$curl = curl_init();  
				curl_setopt($curl, CURLOPT_URL, $urlToFetch);
				curl_setopt($curl, CURLOPT_USERAGENT, $this->yourAppName);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		  		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
		  		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		  		curl_setopt($curl, CURLOPT_TIMEOUT, 20 );
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		
				if ($output = curl_exec($curl)) {

					// GET RESPONSE CODE
					$httpResponseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
					
					if ($httpResponseCode != "200") {
						// ERROR OF SOME KIND
						throw new Exception("Response Code is " . $httpResponseCode . " " . $this-> _name_response($httpResponseCode) );
					} else {
						// DECODE JSON INTO ARRAY
						$resultArray = json_decode($output, true);
						
						if  (json_last_error() !== JSON_ERROR_NONE) {
							// JSON ERROR
							throw new Exception("JSON error response is " . print_r(json_last_error(), true));
						}

						$social_authority = $resultArray['_embedded'][0]['social_authority'];
						$user_id = $resultArray['_embedded'][0]['user_id'];
						$screen_name = $resultArray['_embedded'][0]['screen_name'];
						
						return array("result" => "OK", "response" => array("social_authority" => $social_authority, "user_id" => $user_id, "screen_name" => $screen_name));
					}
		    	} else {
		    		throw new Exception("Curl call failed.");
		    	}

				curl_close($curl);

			} catch(Exception $o) {
				return array("result" => "Error", "response" => array("message" => $o->getMessage()));
			}
	}


	private function _name_response( $code )	{
		switch ($code) {
			case 200:
				return "OK";
				break;
			case 201:
				return "CREATED";
				break;
			case 401:
				return "AUTHORIZATION REQUIRED";
				break;
			case 403:
				return "FORBIDDEN";
				break;
			case 404:
				return "NOT FOUND";
				break;
			case 420:
				return "CALM DOWN";
				break;
			case 429:
				return "LIMITS EXCEEDED";
				break;
			default:
				return "OK";
				break;
		}
	}

} // END CLASS

?>
