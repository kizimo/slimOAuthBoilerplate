<?php

require "oauth2/PDOOAuth2.inc";

class GenericOAuth2Server extends PDOOAuth2 {
	public function finishClientAuthorization2($params = array()) {
	    $params += array(
	      'scope' => NULL,
	      'state' => NULL,
	    );
	    extract($params);
	
	    $redirect_uri = $this->getRedirectUri($client_id);
	   
	      if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE || $response_type == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
	        $code = $this->createAuthCode($client_id, $redirect_uri, $scope);
	
	      if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN || $response_type == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
	        $result = $this->createAccessToken($client_id, $scope);

	//var_dump($result["fragment"]["access_token"]);
		if($code) $result["code"] = $code;
		$result["state"] = $state;
		$result["redirect_uri"] = $redirect_uri;
		//unset($result["fragment"]["access_token"]); //var_dump(json_encode($parse_url));
	    //return json_encode($result["fragment"]);
	    return $result;
   }
   
  private function createAuthCode($client_id, $redirect_uri, $scope = NULL) {
    $code = $this->genAuthCode();
    $this->setAuthCode($code, $client_id, $redirect_uri, time() + $this->getVariable('auth_code_lifetime', OAUTH2_DEFAULT_AUTH_CODE_LIFETIME), $scope);
    return $code;
  }
  
  public function getTokenFromHeader() {
  	$auth_header = $this->getAuthorizationHeader();
    if ($auth_header !== FALSE) {
      $auth_header = trim($auth_header);
      $token = explode(' ',$auth_header);
      return $token[1];
    }
  }
  
  public function verifyAccessToken($scope = NULL, $exit_not_present = TRUE, $exit_invalid = TRUE, $exit_expired = TRUE, $exit_scope = TRUE, $realm = NULL) {
    $token_param = $this->getAccessTokenParams();
    if ($token_param === FALSE){ // Access token was not provided
      error_log(OAUTH2_HTTP_BAD_REQUEST.$realm.OAUTH2_ERROR_INVALID_REQUEST.'   The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.'.$scope);
      return FALSE;
    }
      
    // Get the stored token data (from the implementing subclass)
    $token = $this->getAccessToken($token_param);
    if ($token === NULL){
      error_log(OAUTH2_HTTP_UNAUTHORIZED. $realm, OAUTH2_ERROR_INVALID_TOKEN.'  The access token provided is invalid.'.$scope);
      return FALSE;
    }
    
    // Check token expiration (I'm leaving this check separated, later we'll fill in better error messages)
    if (isset($token["expires"]) && time() > $token["expires"]){
       error_log(OAUTH2_HTTP_UNAUTHORIZED.$realm.OAUTH2_ERROR_EXPIRED_TOKEN.'  The access token provided has expired.'.$scope);
       return FALSE;
    }
    
    // Check scope, if provided
    // If token doesn't have a scope, it's NULL/empty, or it's insufficient, then throw an error
    if ($scope && (!isset($token["scope"]) || !$token["scope"] || !$this->checkScope($scope, $token["scope"]))) {
		error_log(OAUTH2_HTTP_FORBIDDEN, $realm, OAUTH2_ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.', NULL, $scope);
		return FALSE;
	}

    return TRUE;
  }
  
    private function getAuthorizationHeader() {
    if (array_key_exists("HTTP_AUTHORIZATION", $_SERVER))
      return $_SERVER["HTTP_AUTHORIZATION"];

    if (function_exists("apache_request_headers")) {
      $headers = apache_request_headers();

      if (array_key_exists("Authorization", $headers))
        return $headers["Authorization"];
    }

    return FALSE;
  }

  
  private function getAccessTokenParams() {
    $auth_header = $this->getAuthorizationHeader();

    if ($auth_header !== FALSE) {
      // Make sure only the auth header is set
      if (isset($_GET[OAUTH2_TOKEN_PARAM_NAME]) || isset($_POST[OAUTH2_TOKEN_PARAM_NAME]))
        $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Auth token found in GET or POST when token present in header');

      $auth_header = trim($auth_header);

      // Make sure it's Token authorization
      //if (strcmp(substr($auth_header, 0, 5), "OAuth ") !== 0)
      if (preg_match('/Auth/',$auth_header) == 0)
        $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST,'Auth header found that doesn\'t start with "OAuth"');

      // Parse the rest of the header
      
      //if (preg_match('/\s*OAuth\s*="(.+)"/', substr($auth_header, 5), $matches) == 0 || count($matches) < 2)
      //  $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Malformed auth header');
      $token = explode(' ',$auth_header);
      return $token[1];
    }

    if (isset($_GET[OAUTH2_TOKEN_PARAM_NAME])) {
      if (isset($_POST[OAUTH2_TOKEN_PARAM_NAME])) // Both GET and POST are not allowed
        $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Only send the token in GET or POST, not both');

      return $_GET[OAUTH2_TOKEN_PARAM_NAME];
    }

    if (isset($_POST[OAUTH2_TOKEN_PARAM_NAME]))
      return $_POST[OAUTH2_TOKEN_PARAM_NAME];

    return FALSE;
  }
  
  public function grantAccessToken() {
    $filters = array(
      "grant_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_GRANT_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
      "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
      "code" => array("flags" => FILTER_REQUIRE_SCALAR),
      "redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
      "username" => array("flags" => FILTER_REQUIRE_SCALAR),
      "password" => array("flags" => FILTER_REQUIRE_SCALAR),
      "assertion_type" => array("flags" => FILTER_REQUIRE_SCALAR),
      "assertion" => array("flags" => FILTER_REQUIRE_SCALAR),
      "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
    );

    //$input = filter_input_array(INPUT_GET, $filters);
    $input = $_POST;

    // Grant Type must be specified.
    if (!$input["grant_type"])
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');

    // Make sure we've implemented the requested grant type
    if (!in_array($input["grant_type"], $this->getSupportedGrantTypes()))
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE);

    // Authorize the client
    $client = $this->getClientCredentials();

    if ($this->checkClientCredentials($client[0], $client[1]) === FALSE)
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);

    if (!$this->checkRestrictedGrantType($client[0], $input["grant_type"]))
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNAUTHORIZED_CLIENT);

    // Do the granting
    switch ($input["grant_type"]) {
      case OAUTH2_GRANT_TYPE_AUTH_CODE:
        if (!$input["code"] || !$input["redirect_uri"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);

        $stored = $this->getAuthCode($input["code"]);

        // Ensure that the input uri starts with the stored uri
        if ($stored === NULL || (strcasecmp(substr($input["redirect_uri"], 0, strlen($stored["redirect_uri"])), $stored["redirect_uri"]) !== 0) || $client[0] != $stored["client_id"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

        if ($stored["expires"] < time())
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN);

        break;
      case OAUTH2_GRANT_TYPE_USER_CREDENTIALS:
        if (!$input["username"] || !$input["password"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Missing parameters. "username" and "password" required');

        $stored = $this->checkUserCredentials($client[0], $input["username"], $input["password"]);

        if ($stored === FALSE)
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

        break;
      case OAUTH2_GRANT_TYPE_ASSERTION:
        if (!$input["assertion_type"] || !$input["assertion"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);

        $stored = $this->checkAssertion($client[0], $input["assertion_type"], $input["assertion"]);

        if ($stored === FALSE)
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

        break;
      case OAUTH2_GRANT_TYPE_REFRESH_TOKEN:
        if (!$input["refresh_token"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'No "refresh_token" parameter found');

        $stored = $this->getRefreshToken($input["refresh_token"]);

        if ($stored === NULL || $client[0] != $stored["client_id"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

        if ($stored["expires"] < time())
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN);

        // store the refresh token locally so we can delete it when a new refresh token is generated
        $this->setVariable('_old_refresh_token', $stored["token"]);

        break;
      case OAUTH2_GRANT_TYPE_NONE:
        $stored = $this->checkNoneAccess($client[0]);

        if ($stored === FALSE)
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);
    }

    // Check scope, if provided
    if ($input["scope"] && (!is_array($stored) || !isset($stored["scope"]) || !$this->checkScope($input["scope"], $stored["scope"])))
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_SCOPE);

    if (!$input["scope"])
      $input["scope"] = NULL;

    $token = $this->createAccessToken($client[0], $input["scope"]);

    return json_encode($token);
  }
	
}

?>

